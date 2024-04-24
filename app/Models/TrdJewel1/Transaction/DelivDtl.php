<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Inventories\IvtBal;
use App\Models\TrdJewel1\Inventories\IvtBalUnit;

class DelivDtl extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        // Handle when creating a new delivery detail
        static::creating(function ($delivDtl) {
            $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                                 ->where('wh_id', $delivDtl->wh_code)
                                 ->first();
            if ($existingBal) {
                $existingBal->increment('qty_oh', $delivDtl->qty);
                IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                          ->where('wh_id', $delivDtl->wh_code)
                          ->increment('qty_oh', $delivDtl->qty);
            } else {
                $inventoryBalData = [
                    'matl_id' => $delivDtl->matl_id,
                    'matl_code' => $delivDtl->matl_code,
                    'matl_uom' => $delivDtl->matl_uom,
                    'matl_descr' => $delivDtl->matl_descr,
                    'wh_id' => $delivDtl->wh_code,
                    'wh_code' => $delivDtl->wh_code,
                    'qty_oh' => $delivDtl->qty,
                ];
                $newIvtBal = IvtBal::create($inventoryBalData);
                $inventoryBalUnitsData = [
                    'ivt_id' => $newIvtBal->id,
                    'matl_id' => $delivDtl->matl_id,
                    'wh_id' => $delivDtl->wh_code,
                    'matl_uom' => $delivDtl->matl_uom,
                    'unit_code' => $delivDtl->matl_uom,
                    'qty_oh' => $delivDtl->qty,
                ];
                IvtBalUnit::create($inventoryBalUnitsData);
            }
        });

        static::deleting(function ($delivDtl) {
            $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                                 ->where('wh_id', $delivDtl->wh_code)
                                 ->first();
            if ($existingBal) {
                $existingBal->decrement('qty_oh', $delivDtl->qty);
                if ($existingBal->qty_oh <= 0) {
                    IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                              ->where('wh_id', $delivDtl->wh_code)
                              ->delete();
                } else {
                    IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                              ->where('wh_id', $delivDtl->wh_code)
                              ->decrement('qty_oh', $delivDtl->qty);
                }
            }
        });
    }


    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
        'tr_seq',
        'reffdtl_id',
        'reffhdrtr_type',
        'reffhdrtr_id',
        'reffdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'wh_code',
        'qty',
        'qty_reff',
        'status_code'
    ];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

    public function scopeGetByOrderHdr($query, $id)
    {
        return $query->where('trhdr_id', $id);
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function DelivHdr()
    {
        return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id');
    }
}
