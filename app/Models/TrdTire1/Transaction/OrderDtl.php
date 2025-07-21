<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Master\SalesReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdTire1\Master\MatlUom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        'qty_uom',
        'qty_base',
        'qty_reff',
        'price',
        'amt',
        'amt_beforetax',
        'amt_tax',
        'disc_pct',
        'price_uom',
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

    public function DelivDtl()
    {
        return $this->hasMany(DelivDtl::class, 'reffdtl_id', 'id');
    }
    #endregion

    #region Delivery Status Methods
    public function hasDelivery()
    {
        return $this->DelivDtl()->exists();
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
}
