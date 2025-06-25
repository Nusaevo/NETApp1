<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Master\SalesReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdTire1\Master\MatlUom;

class OrderDtl extends BaseModel
{
    // use SoftDeletes;

    protected $table = 'order_dtls';
    protected $fillable = [
        'tr_code',
        'trhdr_id',
        'tr_type',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_descr',
        'matl_uom',
        'qty',
        'qty_reff',
        'price',
        'amt',
        'disc_pct',
        'dpp',
        'ppn',
        'price_uom',
        'amt_tax',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($orderDtl) {
            $qty = $orderDtl->qty;
            $price = $orderDtl->price;
            $discPct = $orderDtl->disc_pct / 100;

            // Ambil tax_pct dari OrderHdr jika ada, jika tidak gunakan default 0
            $orderHdr = $orderDtl->OrderHdr;
            $taxPct = $orderHdr ? ($orderHdr->tax_pct / 100) : 0;
            $taxFlag = $orderHdr ? $orderHdr->tax_flag : 'N';

            // Calculate amt with discount
            $orderDtl->amt = $qty * $price * (1 - $discPct);

            // Calculate amt_tax based on tax flag
            if ($taxFlag === 'I') {
                $orderDtl->amt_tax = $orderDtl->amt;
            } elseif ($taxFlag === 'E') {
                $orderDtl->amt_tax = round($orderDtl->amt * (1 + $taxPct), 2);
            } else { // tax_flag === 'N'
                $orderDtl->amt_tax = $orderDtl->amt;
            }

            // Calculate DPP based on tax flag
            $priceDisc = $price * (1 - $discPct);
            if ($taxFlag === 'I') {
                $orderDtl->dpp = round($priceDisc / (1 + $taxPct), 2);
                $orderDtl->ppn = round($orderDtl->dpp * $taxPct / 100, 2);
            } elseif ($taxFlag === 'E') {
                $orderDtl->dpp = round($priceDisc, 2);
                $orderDtl->ppn = round($orderDtl->dpp * $taxPct / 100, 2);
            } else {
                $orderDtl->dpp = round($priceDisc, 2);
                $orderDtl->ppn = 0;
            }

            // price_base = base_factor yang ada di MatlUom
            $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                ->where('matl_uom', $orderDtl->matl_uom)
                ->first();
            $orderDtl->qty_uom = $matlUom->matl_uom;
        });
    }

    #region Relations
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id', 'id');
    }

    public function OrderHdr()
    {
        return $this->belongsTo(OrderHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }

    public function SalesReward()
    {
        return $this->belongsTo(SalesReward::class, 'matl_id', 'matl_id');
    }
    #endregion

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }
}
