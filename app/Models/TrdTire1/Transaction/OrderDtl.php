<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Master\SalesReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdTire1\Master\MatlUom;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OrderDtl extends BaseModel
{
    protected $table = 'order_dtls';
    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'qty',
        'qty_uom',
        'qty_base',
        'price',
        'price_curr',
        'price_uom',
        'price_base',
        'disc_pct',
        'price_afterdisc',
        'price_beforetax',
        'amt',
        'amt_beforetax',
        'amt_adjustdtl',
        'amt_tax',
        'qty_reff',
        'gt_process_date',
        'gt_tr_code',
        'gt_partner_id',
        'gt_partner_code',
    ];

    protected $casts = [
        'qty' => 'float',
        'qty_base' => 'float',
        'price' => 'float',
        'price_curr' => 'float',
        'price_base' => 'float',
        'disc_pct' => 'float',
        'price_afterdisc' => 'float',
        'price_beforetax' => 'float',
        'amt' => 'float',
        'amt_beforetax' => 'float',
        'amt_adjustdtl' => 'float',
        'amt_tax' => 'float',
        'qty_reff' => 'float',
    ];

    protected $appends = ['has_delivery', 'is_editable'];

    protected static function boot()
    {
        parent::boot();
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

    public function DelivPacking()
    {
        return $this->hasMany(DelivPacking::class, 'reffdtl_id', 'id');
    }
    #endregion

    #region Delivery Status Methods
    public function hasDelivery()
    {
        return $this->DelivPacking()->exists();
    }

    public function isEditable()
    {
        return !$this->hasDelivery();
    }

    public function getHasDeliveryAttribute()
    {
        return $this->hasDelivery();
    }

    public function getIsEditableAttribute()
    {
        return $this->isEditable();
    }
    #endregion

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }

    #region Update Qty Reff
    public static function updateQtyReff($qtyDeliv, $orderDtlId)
    {
        $orderDtl = self::find($orderDtlId);
        if ($orderDtl) {
            $orderDtl->qty_reff += $qtyDeliv;
            $orderDtl->save();
        }
    }

    public static function getNextTrSeq(int $trhdrId): int
    {
        $lastSeq = self::where('trhdr_id', $trhdrId)->max('tr_seq');
        return ($lastSeq ?? 0) + 1;
    }
}
