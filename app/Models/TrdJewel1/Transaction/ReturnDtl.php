<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Inventories\IvtBal;
use App\Models\TrdJewel1\Inventories\IvtBalUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ReturnDtl extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        // static::creating(function ($delivDtl) {
        //     $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
        //         ->where('wh_id', $delivDtl->wh_code)
        //         ->first();
        //     $qtyChange = (float)$delivDtl->qty;
        //     if ($delivDtl->tr_type === 'BB') {
        //         $qtyChange = -$qtyChange;
        //     }

        //     if ($existingBal) {
        //         $existingBalQty = currencyToNumeric($existingBal->qty_oh);
        //         $newQty = $existingBalQty + $qtyChange;
        //         $existingBal->qty_oh = $newQty;
        //         $existingBal->save();

        //         // Update corresponding record in IvtBalUnit
        //         $existingBalUnit = IvtBalUnit::where('matl_id', $delivDtl->matl_id)
        //             ->where('wh_id', $delivDtl->wh_code)
        //             ->first();
        //         if ($existingBalUnit) {
        //             $existingBalUnitQty = currencyToNumeric($existingBalUnit->qty_oh);
        //             $existingBalUnit->qty_oh = $existingBalUnitQty + $qtyChange;
        //             $existingBalUnit->save();
        //         }
        //     } else {
        //         $inventoryBalData = [
        //             'matl_id' => $delivDtl->matl_id,
        //             'matl_code' => $delivDtl->matl_code,
        //             'matl_uom' => $delivDtl->matl_uom,
        //             'matl_descr' => $delivDtl->matl_descr,
        //             'wh_id' => $delivDtl->wh_code,
        //             'wh_code' => $delivDtl->wh_code,
        //             'qty_oh' => $qtyChange,
        //         ];
        //         $newIvtBal = IvtBal::create($inventoryBalData);
        //         $inventoryBalUnitsData = [
        //             'ivt_id' => $newIvtBal->id,
        //             'matl_id' => $delivDtl->matl_id,
        //             'wh_id' => $delivDtl->wh_code,
        //             'matl_uom' => $delivDtl->matl_uom,
        //             'unit_code' => $delivDtl->matl_uom,
        //             'qty_oh' => $qtyChange,
        //         ];
        //         IvtBalUnit::create($inventoryBalUnitsData);
        //     }
        // });
        static::created(function ($returnDtl) {
            $OrderDtl = $returnDtl->OrderDtl;  // Assuming the relation method name is OrderDtl
            if ($OrderDtl) {
                $OrderDtlQtyReff = ceil(currencyToNumeric($OrderDtl->qty_reff));
                $returnQty = ceil(currencyToNumeric($OrderDtl->qty));
                $newQtyReff = $OrderDtlQtyReff - $returnQty;
                $OrderDtl->qty_reff = number_format($newQtyReff, 2);
                $OrderDtl->save();
            }
        });

        static::deleting(function ($returnDtl) {
                $OrderDtl = $returnDtl->OrderDtl;
                if ($OrderDtl) {
                    $OrderDtlQtyReff = ceil(currencyToNumeric($OrderDtl->qty_reff));
                    $returnQty =  ceil(currencyToNumeric($OrderDtl->qty));
                    $newQtyReff = $OrderDtlQtyReff + $returnQty;
                    $OrderDtl->qty_reff = number_format($newQtyReff, 2);
                    $OrderDtl->save();
                }
        });
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
        'tr_seq',
        'dlvdtl_id',
        'dlvhdrtr_type',
        'dlvhdrtr_id',
        'dlvdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'qty',
        'qty_uom',
        'qty_base',
        'price',
        'price_uom',
        'price_base',
        'amt',
        'status_code',
        'qty_reff',
    ];

    #region Relations
    public function ReturnHdr()
    {
        return $this->belongsTo(ReturnHdr::class, 'trhdr_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'dlvdtl_id', 'id')->where('tr_type', 'SO');;
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
    #endregion


    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
        ->where('tr_type', $trType);;
    }
}
