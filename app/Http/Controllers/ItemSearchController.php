<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use DB;

class ItemSearchController extends Controller
{
    public function selectSearch(Request $request)
    {
        $item = [];
        if ($request->has('q')) {
            $search = $request->q;
            $item = Item::leftJoin('item_units', 'items.id', '=', 'item_units.item_id')
                ->leftJoin('units', 'units.id', '=', 'item_units.unit_id')
                ->where('items.name', 'LIKE', "%$search%")
                ->select("item_units.id", DB::raw("CONCAT(items.name,'-',units.name) AS name"))
                ->get();
        }
        return response()->json($item);
    }
}
