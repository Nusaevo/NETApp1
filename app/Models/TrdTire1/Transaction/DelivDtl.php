<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Inventories\IvtBal;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Enums\Constant;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Inventories\IvtLog;
use App\Models\TrdTire1\Master\MatlUom;
// Pastikan BillingDtl sudah di-import jika digunakan di sini
use App\Models\TrdTire1\Transaction\{OrderDtl, BillingDtl};

class DelivDtl extends BaseModel
{
    use SoftDeletes;

    protected $table = 'deliv_dtls';

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'reffdtl_id',
        'reffhdrtr_type',
        'reffhdrtr_code',
        'reffdtltr_seq',
        'reffhdrtr_id',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'wh_code',
        'qty',
        'wh_id',
        'status_code',
        'ivt_id',
        'batch_code'
    ];

    protected static function boot()
    {
        parent::boot();

        // Event saving: sebelum data disimpan
        static::saving(function ($delivDtl) {
            // Generate batch code jika kosong
            if (empty($delivDtl->batch_code)) {
                $delivDtl->batch_code = date('ymd');
            }
        });

        // Event saved: setelah data tersimpan
        static::saved(function ($delivDtl) {
            $header = $delivDtl->DelivHdr;
            $orderDtl = $delivDtl->OrderDtl;
            $billingHdr = BillingHdr::where('tr_code', $delivDtl->tr_code)
                ->where('tr_type', $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB')
                ->first();

            // Update BillingDtl
            BillingDtl::updateOrCreate(
                [
                    'trhdr_id' => $billingHdr->id,
                    'tr_seq'   => $delivDtl->tr_seq,
                    'tr_type'  => $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB',
                ],
                [
                    'trhdr_id'   => $billingHdr->id,
                    'tr_type'    => $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB',
                    'tr_code'    => $delivDtl->tr_code,
                    'tr_seq'     => $delivDtl->tr_seq,
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_code'  => $delivDtl->matl_code,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'descr'      => $delivDtl->matl_descr,
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl ? $orderDtl->amt : 0,
                    'amt'        => $delivDtl->qty * ($orderDtl ? $orderDtl->amt : 0),
                    'dlvdtl_id'  => $delivDtl->id,
                    'dlvdtlr_seq'=> $header ? $header->tr_seq : $delivDtl->tr_seq,
                    'dlvhdr_type'=> $header ? $header->tr_type : $delivDtl->tr_type,
                    'dlvhdr_id'  => $header ? $header->id : $delivDtl->trhdr_id,
                ]
            );
        });

        static::deleting(function ($delivDtl) {
            DB::transaction(function () use ($delivDtl) {
                // Rollback OrderDtl
                if ($delivDtl->OrderDtl) {
                    $delivDtl->OrderDtl->decrement('qty_reff', $delivDtl->qty);
                }

                // Hapus billing
                BillingDtl::where('dlvdtl_id', $delivDtl->id)->forceDelete();
            });
        });
    }

    /**
     * Scope untuk mendapatkan data berdasarkan delivery header dan tipe transaksi
     */
    public function scopeGetByDelivHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }

    #region Relations

    /**
     * Relasi ke master Material
     */
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    /**
     * Relasi ke delivery header berdasarkan tipe transaksi
     */
    public function DelivHdr()
    {
        if ($this->tr_type) {
            return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')
                ->where('tr_type', $this->tr_type);
        }
        return null;
    }

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'reffdtl_id', 'id')
            ->where('tr_type', $this->reffhdrtr_type);
    }

    #endregion
}
