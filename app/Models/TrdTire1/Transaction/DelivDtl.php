<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Inventories\IvtBalUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Enums\Constant;
use App\Traits\BaseTrait;
class DelivDtl extends BaseModel
{
    use SoftDeletes;

    protected $table = 'deliv_dtls';
    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'reffdtl_id',
        'reffhdrtr_type',
        'reffhdrtr_code',
        'reffdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'wh_code',
        'qty',
        'wh_id',
        'wh_code',
        'status_code'
    ];


    protected static function boot()
    {
        parent::boot();
        static::saving(function ($delivDtl) {
            // Disable amt calculation
            // $qty = $delivDtl->qty;
            // $price = $delivDtl->price;
            // $delivDtl->amt = $qty * $price;
        });
        static::deleting(function ($delivDtl) {
            DB::beginTransaction();
            try {
                $delivDtls = DelivDtl::where('trhdr_id', $delivDtl->trhdr_id)
                    ->where('tr_seq', $delivDtl->tr_seq)
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

                BillingDtl::where('trhdr_id', $delivDtl->trhdr_id)
                    ->where('tr_seq', $delivDtl->tr_seq)
                    ->forceDelete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

    public function scopeGetByDelivHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
                     ->where('tr_type', $trType);
    }

    #region Relations

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function DelivHdr()
    {
        if ($this->tr_type) {
            return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
        }
        return null;
    }

    #endregion
}
