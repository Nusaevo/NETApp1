<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\MatlUom;

class MaterialService
{
    public function updLastBuyingPrice($matlId, $matlUom, $lastBuyingPrice, $lastBuyingDate)
    {
        $matlUomRec = MatlUom::where([
            'matl_id'  => $matlId,
            'matl_uom' => $matlUom,
            // update last buying date < dari $lastBuyingDate
            'last_buying_date' => '<', $lastBuyingDate
        ])->first();

        if ($matlUomRec) {
            $matlUomRec->last_buying_price = $lastBuyingPrice;
            $matlUomRec->last_buying_date = $lastBuyingDate;
            $matlUomRec->save();
        }
    }
}
