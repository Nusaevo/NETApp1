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
        'reffhdr_id',
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
        return $this->belongsTo(Material::class, 'matl_id', 'id');
    }

    /**
     * Relasi ke delivery header berdasarkan tipe transaksi
     */
    public function DelivHdr()
    {
        return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'reffdtl_id', 'id');
    }

    public function IvtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id', 'matl_id')->where('wh_id', $this->wh_id);
    }

    #endregion
}

