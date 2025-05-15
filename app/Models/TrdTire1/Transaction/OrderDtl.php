<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Master\SalesReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdTire1\Master\MatlUom;

class OrderDtl extends BaseModel
{
    use SoftDeletes;

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
        'qty_reff',
        'price',
        'amt',
        'disc_pct',
        'dpp',
        'ppn',
        'price_uom',
        'amt_tax',

    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($orderDtl) {
            $qty = $orderDtl->qty;
            $price = $orderDtl->price;
            $discPct = $orderDtl->disc_pct / 100;
            $taxPct = $orderDtl->OrderHdr->tax_pct / 100;

            // Calculate amt
            $orderDtl->amt = $qty * $price * (1 - $discPct);

            // Calculate amt_tax based on tax flag
            if ($orderDtl->OrderHdr->tax_flag === 'I') {
                $orderDtl->amt_tax = $orderDtl->amt;
            } elseif ($orderDtl->OrderHdr->tax_flag === 'E') {
                $orderDtl->amt_tax = round($orderDtl->amt * (1 + $taxPct), 2);
            } else { // tax_flag === 'N'
                $orderDtl->amt_tax = $orderDtl->amt;
            }

            // Calculate DPP based on tax flag
            $priceDisc = $price * (1 - $discPct);
            if ($orderDtl->OrderHdr->tax_flag === 'I') {
                $orderDtl->dpp = round($priceDisc / (1 + $taxPct), 2);
                $orderDtl->ppn = round($orderDtl->dpp * $taxPct / 100, 2);
            } elseif ($orderDtl->OrderHdr->tax_flag === 'E') {
                $orderDtl->dpp = round($priceDisc, 2);
                $orderDtl->ppn = round($orderDtl->dpp * $taxPct / 100, 2);
            } else {
                $orderDtl->dpp = round($priceDisc, 2);
                $orderDtl->ppn = 0;
            }

            // price_base = base_factor yang ada di MatlUom
            $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                ->where('matl_uom', $orderDtl->matl_uom)
                ->first();
            $orderDtl->qty_uom = $matlUom->matl_uom;

            // Create BillingDtl and DelivDtl if payment term is CASH
            $orderHdr = $orderDtl->OrderHdr;
            if ($orderHdr && $orderHdr->payment_term === 'CASH') {
                BillingDtl::create([
                    'trhdr_id' => $orderDtl->trhdr_id,
                    'tr_type' => 'ARB',
                    'tr_code' => $orderDtl->tr_code,
                    'tr_seq' => $orderDtl->tr_seq,
                    'matl_id' => $orderDtl->matl_id,
                    'matl_code' => $orderDtl->matl_code,
                    'matl_uom' => $orderDtl->matl_uom,
                    'descr' => $orderDtl->matl_descr,
                    'qty' => $orderDtl->qty,
                    'price' => $orderDtl->price,
                    'amt' => $orderDtl->amt,
                ]);

                DelivDtl::create([
                    'trhdr_id' => $orderDtl->trhdr_id,
                    'tr_type' => 'SD',
                    'tr_code' => $orderDtl->tr_code,
                    'tr_seq' => $orderDtl->tr_seq,
                    'matl_id' => $orderDtl->matl_id,
                    'matl_code' => $orderDtl->matl_code,
                    'matl_uom' => $orderDtl->matl_uom,
                    'matl_descr' => $orderDtl->matl_descr,
                    'qty' => $orderDtl->qty,
                ]);
            }
        });

        static::saved(function ($orderDtl) {
            $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                ->where('matl_uom', $orderDtl->matl_uom)
                ->first();

            if ($matlUom) {
                // Calculate oldQty and newQty
                $oldQty = (float) $orderDtl->getOriginal('qty', 0);
                $newQty = (float) $orderDtl->qty;
                $delta = $newQty - ($orderDtl->exists ? $oldQty : 0);


                // Adjust qty_fgi or qty_fgr based on delta
                if ($delta !== 0) {
                    if ($orderDtl->tr_type === 'SO') {
                        $matlUom->qty_fgi += $delta;
                    } elseif ($orderDtl->tr_type === 'PO') {
                        $matlUom->qty_fgr += $delta;
                    }
                    $matlUom->save();
                }
            }
        });

        static::deleting(function ($orderDtl) {
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
                    }
                    $delivDtl->forceDelete();
                }

                BillingDtl::where('trhdr_id', $orderDtl->trhdr_id)
                    ->where('tr_seq', $orderDtl->tr_seq)
                    ->forceDelete();

                // Restore qty_fgr or qty_fgi in MatlUom
                $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                    ->where('matl_uom', $orderDtl->matl_uom)
                    ->first();

                if ($matlUom) {
                    if ($orderDtl->tr_type === 'PO') {
                        $matlUom->qty_fgr -= $orderDtl->qty;
                    } elseif ($orderDtl->tr_type === 'SO') {
                        $matlUom->qty_fgi -= $orderDtl->qty;
                    }
                    $matlUom->save();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
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
    // public function OrderDtl()
    // {
    //     return $this->hasMany(OrderDtl::class);  // pastikan nama model dan foreign key sesuai
    // }

    #endregion

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }
}
