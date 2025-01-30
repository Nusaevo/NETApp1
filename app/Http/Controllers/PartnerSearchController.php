<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DropdownSearchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'q'            => 'nullable|string|max:255',
                'page'         => 'nullable|integer|min:1',
                'model'        => 'nullable|string',
                'where'        => 'nullable|string',
                'option_value' => 'nullable|string',
                'option_label' => 'nullable|string',
            ]);

            $search       = $request->input('q', '');
            $page         = $request->input('page', 1);
            $model        = $request->input('model', 'App\\Models\\TrdTire1\\Master\\Partner');
            $where        = $request->input('where', '');  // contoh: "status=Active"
            $optionValue  = $request->input('option_value', 'id');
            $optionLabel  = $request->input('option_label', 'name');
            $perPage      = 10;

            // Pastikan class model valid
            if (!class_exists($model)) {
                $model = 'App\\Models\\TrdTire1\\Master\\Partner';
            }

            // Mulai query
            $query = (new $model)->newQuery();

            // SELECT field2, field2 -> di sini optionValue dan optionLabel
            $query->select($optionValue, $optionLabel);

            // Contoh default filter
            $query->whereNull('deleted_at');

            // Jika ada WHERE tambahan
            if (!empty($where) && strpos($where, '=') !== false) {
                [$col, $val] = explode('=', $where);
                $query->where(trim($col), trim($val));
            }

            // Jika ada pencarian
            if (!empty($search)) {
                // Kita asumsikan option_label adalah field yang dicari
                $query->where($optionLabel, 'like', "{$search}%");
            }

            // Paginate
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            // Format data untuk Select2
            $formattedResults = $results->map(function ($item) use ($optionValue, $optionLabel) {
                return [
                    'id'   => $item->{$optionValue},
                    'text' => $item->{$optionLabel},
                ];
            });

            return response()->json([
                'results' => $formattedResults,
                'pagination' => [
                    'more' => $results->currentPage() < $results->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
