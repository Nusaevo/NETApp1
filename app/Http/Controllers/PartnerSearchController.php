<?php

namespace App\Http\Controllers;

use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Http\Request;
use Debugbar;

class PartnerSearchController extends Controller
{
    /**
     * Fetch all partners or search by name for Select2.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Validate the input
            $request->validate([
                'q' => 'nullable|string|max:255',
            ]);

            $search = $request->get('q', ''); // Default to an empty string
            Debugbar::info('Search query received:', $search);

            // Fetch partners matching the search query
            $partners = Partner::select('id', 'name')
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'LIKE', "%$search%");
                })
                ->whereNull('deleted_at') // Exclude soft-deleted records
                ->paginate(10); // Paginate results for better performance

            // Log the query results
            Debugbar::info('Query results:', $partners->items());

            // Format the results for Select2
            $formattedResults = $partners->getCollection()->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'text' => $partner->name,
                ];
            });

            Debugbar::info('Formatted results for response:', $formattedResults);

            return response()->json([
                'results' => $formattedResults,
                'pagination' => [
                    'more' => $partners->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Debugbar::error('Error occurred while fetching partners:', $e->getMessage());

            return response()->json([
                'error' => 'An error occurred while processing your request.',
            ], 500);
        }
    }
}
