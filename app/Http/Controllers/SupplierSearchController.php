<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierSearchController extends Controller
{
    public function selectSearch(Request $request)
    {
        $supllier = [];
        if ($request->has('q')) {
            $search = $request->q;
            $supllier = Supplier::select("id", "name")
                ->where('name', 'LIKE', "%$search%")
                ->whereNull('suppliers.deleted_at')
                ->get();
        }
        return response()->json($supllier);
    }
}
