<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\MatlUom;

class MaterialService
{        public function updLastBuyingPrice($matlId, $matlUom, $lastBuyingPrice, $lastBuyingDate)
    {
        $matlUomRec = MatlUom::where([
            'matl_id'  => $matlId,
            'matl_uom' => $matlUom,
        ])->first();

        if ($matlUomRec) {
            // Update jika belum ada last_buying_date atau jika tanggal baru lebih baru
            if (is_null($matlUomRec->last_buying_date) || $lastBuyingDate >= $matlUomRec->last_buying_date) {
                $matlUomRec->last_buying_price = $lastBuyingPrice;
                $matlUomRec->last_buying_date = $lastBuyingDate;
                $matlUomRec->save();
            }
        }
    }
}
