<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Inventories\IvtBalUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDtl extends BaseModel
{
    use SoftDeletes;

    protected $table = 'order_dtls';

    protected $fillable = [
        'tr_id',
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
        'amt'
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($orderDtl) {
            $qty = $orderDtl->qty;
            $price = $orderDtl->price;
            $orderDtl->amt = $qty * $price;
        });
        static::deleting(function ($orderDtl) {
            DB::beginTransaction();
            try {
                $delivDtls = DelivDtl::where('trhdr_id', $orderDtl->trhdr_id)
                    ->where('tr_seq', $orderDtl->tr_seq)
                    ->get();

                foreach ($delivDtls as $delivDtl) {
                    $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                        ->where('wh_id', $delivDtl->wh_code)
                        ->first();
                    $qtyChange = (float)$delivDtl->qty;
                    if ($delivDtl->tr_type === 'SO') {
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
                    }
                    $delivDtl->forceDelete();
                }

                BillingDtl::where('trhdr_id', $orderDtl->trhdr_id)
                    ->where('tr_seq', $orderDtl->tr_seq)
                    ->forceDelete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

    #region Relations
    public function material()
    {
        return $this->belongsTo(Material::class, 'matl_id', 'id');
    }

    public function OrderHdr()
    {
        return $this->belongsTo(OrderHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }
    #endregion

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
                     ->where('tr_type', $trType);
    }
}
