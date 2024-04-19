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
        static::creating(function ($delivDtl) {
            $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                                 ->where('wh_id', $delivDtl->wh_id)
                                 ->first();
            if ($existingBal) {
                $existingBal->increment('qty_oh', $delivDtl->qty);
                IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                          ->where('wh_id', $delivDtl->wh_id)
                          ->update(['qty_oh' => \DB::raw('qty_oh + ' . $delivDtl->qty)]);
            } else {
                $inventoryBalData = [
                    'matl_id' => $delivDtl->matl_id,
                    'matl_code' => $delivDtl->matl_code,
                    'matl_uom' => $delivDtl->matl_uom,
                    'matl_descr' => $delivDtl->matl_descr,
                    'wh_id' => $delivDtl->wh_id,
                    'wh_code' => $delivDtl->wh_code,
                    'qty_oh' => $delivDtl->qty,
                ];
                $newIvtBal = IvtBal::create($inventoryBalData);
                $inventoryBalUnitsData = [
                    'ivt_id' => $newIvtBal->id,
                    'matl_id' => $delivDtl->matl_id,
                    'wh_id' => $delivDtl->wh_id,
                    'matl_uom_id' => $delivDtl->id,
                    'uom' => $delivDtl->name,
                    'qty_oh' => $delivDtl->qty,
                ];
                IvtBalUnit::create($inventoryBalUnitsData);
            }
        });

        static::deleting(function ($delivDtl) {
            $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                                 ->where('wh_id', $delivDtl->wh_id)
                                 ->first();
            if ($existingBal) {
                $existingBal->decrement('qty_oh', $delivDtl->qty);
                if ($existingBal->qty_oh <= 0) {
                    $existingBal->delete();
                }

                // Delete corresponding records from IvtBalUnit
                IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                          ->where('wh_id', $delivDtl->wh_id)
                          ->delete();
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
