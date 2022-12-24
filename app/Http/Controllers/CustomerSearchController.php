<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerSearchController extends Controller
{
    public function selectSearch(Request $request)
    {
        $customer = [];
        // $this->item_price = ItemPrice::leftJoin('item_units', 'item_units.id', '=', 'item_prices.item_unit_id')
        // ->leftJoin('items', 'items.id', '=', 'item_units.item_id')
        // ->leftJoin('units', 'units.id', '=', 'item_units.unit_id')
        // ->leftJoin('category_items', 'category_items.id', '=', 'items.category_item_id')
        // ->leftJoin('price_categories', 'price_categories.id', '=', 'item_prices.price_category_id')
        // ->when($category_id, function (Builder $query) use ($category_id) {
        //     $query->where('category_items.id', $category_id);
        // })
        // ->when($keyword, function (Builder $query) use ($keyword) {
        //     $query->whereRaw('LCASE(items.name) like ' . '"%' . $keyword . '%"');
        // })
        // ->select('item_prices.id as id', 'items.name as item_name', 'units.name as unit_name', 'category_items.name as category_name', 'price_categories.name as price_category_name', 'item_prices.price as price')
        // ->get();
        if ($request->has('q')) {
            $search = $request->q;
            $customer = Customer::select("id", "name", "price_category_id")
                ->where('name', 'LIKE', "%$search%")
                ->get();
        }
        return response()->json($customer);
    }
}
