<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use DB;

class ItemSearchController extends Controller
{
    public function selectSearch(Request $request)
    {
        $item = [];
        if ($request->has('q')) {
            $search = strtolower($request->q);
            // $item = Item::leftJoin('item_units', 'items.id', '=', 'item_units.item_id')
            //     ->leftJoin('units', 'units.id', '=', 'item_units.unit_id')
            //     ->select("item_units.id", DB::raw("CONCAT(items.name,'-',units.name) AS name"))
            //     ->where('items.name', 'LIKE', "%$search%")
            //     ->whereNull('items.deleted_at')
            //     ->whereNull('item_units.deleted_at')
            //     ->get();
            $item = Material::select('id', 'name')
            ->whereRaw('LOWER(name) LIKE ?', ["%$search%"])
            ->whereNull('deleted_at')
            ->get();


        }
        return response()->json($item);
    }
}
