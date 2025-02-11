<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Inventories\IvtBalUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Traits\BaseTrait;
class DelivDtl extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        // Handle when creating a new delivery detail
        static::creating(function ($delivDtl) {

            // Check if IvtBal record exists
            $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
            ->where('wh_id', $delivDtl->wh_code)
            ->where('batch_code', $delivDtl->batch_code)
            ->first();

            // Generate batch_code if not provided
            if (empty($delivDtl->batch_code)) {
                $delivDtl->batch_code = date('y/m/d');
            }

            $qtyChange = (float)$delivDtl->qty;
            if ($delivDtl->tr_type === 'SD') {
                $qtyChange = -$qtyChange;
            }

            if ($existingBal) {
                $existingBalQty = $existingBal->qty_oh;
                $newQty = $existingBalQty + $qtyChange;
                $existingBal->qty_oh = $newQty;
                $existingBal->save();

                // Update corresponding record in IvtBalUnit
                $existingBalUnit = IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                    ->where('wh_id', $delivDtl->wh_code)
                    ->first();
                if ($existingBalUnit) {
                    $existingBalUnitQty = $existingBalUnit->qty_oh;
                    $existingBalUnit->qty_oh = $existingBalUnitQty + $qtyChange;
                    $existingBalUnit->save();
                }
            } else {
                $inventoryBalData = [
                    'matl_id' => $delivDtl->matl_id,
                    'matl_code' => $delivDtl->matl_code,
                    'matl_uom' => $delivDtl->matl_uom,
                    'matl_descr' => $delivDtl->matl_descr,
                    'wh_id' => $delivDtl->wh_code,
                    'wh_code' => $delivDtl->wh_code,
                    'batch_code' => $delivDtl->batch_code,
                    'qty_oh' => $qtyChange,
                ];
                $newIvtBal = IvtBal::create($inventoryBalData);
                $inventoryBalUnitsData = [
                    'ivt_id' => $newIvtBal->id,
                    'matl_id' => $delivDtl->matl_id,
                    'wh_id' => $delivDtl->wh_code,
                    'matl_uom' => $delivDtl->matl_uom,
                    'unit_code' => $delivDtl->matl_uom,
                    'qty_oh' => $qtyChange,
                ];
                IvtBalUnit::create($inventoryBalUnitsData);
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
    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
                     ->where('tr_type', $trType);
    }


    #region Relations

    public function material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function delivHdr()
    {
        return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }

    public function ivtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id', 'matl_id')
                    ->where('wh_id', $this->wh_code)
                    ->where('batch_code', $this->batch_code);
    }
    #endregion
}
