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

class DelivPacking extends BaseModel
{
 

    protected $table = 'deliv_packings';
    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'reffdtl_id',
        'reffhdr_id',
        'reffhdrtr_type',
        'reffhdrtr_code',
        'reffdtltr_seq',
        'matl_descr',
        'qty',
    ];
    protected $casts = [
        'qty' => 'float',
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

    public function DelivHdr()
    {
        return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'reffdtl_id', 'id');
    }

    public function DelivPickings()
    {
        return $this->hasMany(DelivPicking::class, 'trpacking_id', 'id');
    }
    #endregion

    #region Delivery Status Methods
    public function hasDelivery()
    {
        return $this->DelivPickings()->exists();
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

    public function scopeGetByDelivHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }

    public static function getNextTrSeq($trhdrId): int
    {
        $lastSeq = self::where('trhdr_id', $trhdrId)->max('tr_seq');
        return ($lastSeq ?? 0) + 1;
    }
}

