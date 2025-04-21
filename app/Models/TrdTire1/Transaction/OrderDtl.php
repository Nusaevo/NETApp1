<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Inventories\IvtBal;
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
            $priceDisc = $price * (1 - $discPct);

            // Calculate DPP based on tax flag
            if ($orderDtl->OrderHdr->tax_flag === 'I') {
                $orderDtl->dpp = $priceDisc / (1 + $taxPct);
            } elseif ($orderDtl->OrderHdr->tax_flag === 'E') {
                $orderDtl->dpp = $priceDisc;
            } else {
                $orderDtl->dpp = $priceDisc;
            }

            // Calculate amt and amt_tax
            $orderDtl->amt = $priceDisc * $qty;
            $orderDtl->amt_tax = round($orderDtl->amt * (1 + $taxPct), 2); // Include tax in the total amount

            // price_base = base_factor yang ada di MatlUom
            $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                ->where('matl_uom', $orderDtl->matl_uom)
                ->first();
            $orderDtl->price_base = $matlUom->base_factor;
            // $orderDtl->qty_base = $qty * $matlUom->base_factor;
            $orderDtl->qty_uom = $matlUom->matl_uom;
            if ($matlUom) {
                if ($orderDtl->tr_type === 'SO') {
                    $matlUom->qty_fgi = $qty;
                } elseif ($orderDtl->tr_type === 'PO') {
                    $matlUom->qty_fgr = $qty;
                }
            }
            $matlUom->save();

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

                // Decrement qty_fgr in MatlUom
                $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                    ->where('matl_uom', $orderDtl->matl_uom)
                    ->first();

                if ($matlUom) {
                    $matlUom->decrement('qty_fgr', $orderDtl->qty);
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
