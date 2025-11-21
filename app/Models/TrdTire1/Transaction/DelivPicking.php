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

class DelivPicking extends BaseModel
{
    protected $table = 'deliv_pickings';
    protected $fillable = [
        'trpacking_id',
        'tr_seq',
        'ivt_id',
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'qty',
        'trhdr_id',
        'tr_type',
        'tr_code',
        'trpacking_seq',
    ];
    protected $casts = [
        'qty' => 'float',
        'trpacking_id' => 'integer',
        'tr_seq' => 'integer',
        'ivt_id' => 'integer',
        'matl_id' => 'integer',
        'wh_id' => 'integer',
        'trhdr_id' => 'integer',
        'trpacking_seq' => 'integer',
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

    public function DelivPacking()
    {
        return $this->belongsTo(DelivPacking::class, 'trpacking_id', 'id');
    }

    public function IvtBal()
    {
        return $this->belongsTo(IvtBal::class, 'ivt_id', 'id');
    }

    public function SalesReward()
    {
        return $this->belongsTo(SalesReward::class, 'matl_id', 'matl_id');
    }
    #endregion

    #region Delivery Status Methods
    public function hasDelivery()
    {
        return $this->qty_reff > 0;
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

    public function scopeGetByDelivPacking($query, $id)
    {
        return $query->where('trpacking_id', $id);
    }

    public static function getNextTrSeq(int $trpackingId):int
    {
        $lastSeq = self::where('trpacking_id', $trpackingId)->max('tr_seq');
        return ($lastSeq ?? 0) + 1;
    }

}
