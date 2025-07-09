<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DropdownSearchController
 *
 * Enhanced AJAX dropdown search controller with support for:
 * - Multiple WHERE conditions (AND/OR operators)
 * - Multi-label display
 * - Various comparison operators
 *
 * WHERE Condition Examples:
 * - Simple: "status_code=A"
 * - Multiple AND: "status_code=A&deleted_at=null"
 * - AND with OR: "status_code=A&type=SUPPLIER|type=CUSTOMER"
 * - Complex: "status=A&deleted_at=null&(type=SUPPLIER|type=CUSTOMER)&price>100"
 *
 * Label Examples:
 * - Single: "name"
 * - Multiple: "code,name" (displays as "ABC123 - Product Name")
 * - Complex: "code,name,category" (displays as "ABC123 - Product Name - Electronics")
 */

class DropdownSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $model = $request->get('model');
            $where = $request->get('where');
            $optionValue = $request->get('option_value', 'id');
            $optionLabel = $request->get('option_label', 'name');
            $specificId = $request->get('id'); // For value restoration

            // Debug logging
            \Log::info('Dropdown search request:', [
                'query' => $query,
                'model' => $model,
                'where' => $where,
                'optionValue' => $optionValue,
                'optionLabel' => $optionLabel,
                'specificId' => $specificId
            ]);

            if (!$model || !class_exists($model)) {
                \Log::error('Model not found or invalid:', ['model' => $model]);
                return response()->json([
                    'results' => [],
                    'error' => 'Model not found: ' . $model
                ]);
            }

            // Initialize query
            $queryBuilder = $model::query();

            // If searching for a specific ID (value restoration)
            if ($specificId) {
                $item = $queryBuilder->find($specificId);
                if ($item) {
                    // Support multiple labels separated by comma
                    $labels = explode(',', $optionLabel);
                    $displayText = '';

                    foreach ($labels as $index => $label) {
                        $label = trim($label);
                        if ($label && isset($item->{$label})) {
                            if ($index > 0) {
                                $displayText .= ' - ';
                            }
                            $displayText .= $item->{$label};
                        }
                    }

                    return response()->json([
                        'results' => [[
                            'id' => $item->{$optionValue},
                            'text' => $displayText ?: $item->{trim(explode(',', $optionLabel)[0])}
                        ]]
                    ]);
                } else {
                    return response()->json(['results' => []]);
                }
            }

            // Apply where conditions if provided
            if ($where) {
                $this->applyWhereConditions($queryBuilder, $where);
            }

            // Apply search query if provided
            if ($query) {
                $queryBuilder->where(function($q) use ($query, $optionLabel, $optionValue) {
                    // Support multiple labels separated by comma
                    $searchFields = array_merge(
                        explode(',', $optionLabel),
                        [$optionValue]
                    );

                    foreach ($searchFields as $field) {
                        $field = trim($field);
                        if ($field) {
                            // Use case-insensitive LIKE search
                            $q->orWhere($field, 'ILIKE', "%{$query}%")
                              ->orWhere($field, 'LIKE', "%{$query}%");
                        }
                    }
                });
            } else {
                // If no search query, limit results to prevent loading too much data
                // unless this is a specific ID lookup (for restoring selected values)
                $isIdLookup = strpos($where, 'id=') !== false;
                if (!$isIdLookup) {
                    $queryBuilder->limit(50); // Limit when no search term
                }
            }

            // Get results with appropriate limiting
            $results = $queryBuilder
                ->when(!$query && strpos($where ?: '', 'id=') === false, function($q) {
                    return $q->limit(50); // Limit only when no search and not ID lookup
                })
                ->when($query, function($q) {
                    return $q->limit(100); // Limit search results
                })
                ->get()
                ->map(function ($item) use ($optionValue, $optionLabel) {
                    // Support multiple labels separated by comma
                    $labels = explode(',', $optionLabel);
                    $displayText = '';

                    foreach ($labels as $index => $label) {
                        $label = trim($label);
                        if ($label && isset($item->{$label})) {
                            if ($index > 0) {
                                $displayText .= ' - ';
                            }
                            $displayText .= $item->{$label};
                        }
                    }

                    return [
                        'id' => $item->{$optionValue},
                        'text' => $displayText ?: $item->{trim(explode(',', $optionLabel)[0])}
                    ];
                });

            \Log::info('Dropdown search results count:', ['count' => $results->count()]);

            return response()->json([
                'results' => $results
            ]);

        } catch (\Exception $e) {
            \Log::error('Dropdown search error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'results' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply complex WHERE conditions to the query builder
     * Supports AND/OR operators and various condition formats
     *
     * @param \Illuminate\Database\Eloquent\Builder $queryBuilder
     * @param string $where
     */
    private function applyWhereConditions($queryBuilder, $where)
    {
        // Split by & for AND conditions
        $andGroups = explode('&', $where);

        foreach ($andGroups as $andGroup) {
            $andGroup = trim($andGroup);
            if (empty($andGroup)) continue;

            // Check if this group contains OR conditions (separated by |)
            if (strpos($andGroup, '|') !== false) {
                // Handle OR conditions within this AND group
                $orConditions = explode('|', $andGroup);
                $queryBuilder->where(function($q) use ($orConditions) {
                    foreach ($orConditions as $orCondition) {
                        $this->applySingleCondition($q, trim($orCondition), 'or');
                    }
                });
            } else {
                // Single AND condition
                $this->applySingleCondition($queryBuilder, $andGroup, 'and');
            }
        }
    }

    /**
     * Apply a single WHERE condition
     *
     * @param \Illuminate\Database\Eloquent\Builder $queryBuilder
     * @param string $condition
     * @param string $boolean (and|or)
     */
    private function applySingleCondition($queryBuilder, $condition, $boolean = 'and')
    {
        if (empty($condition)) return;

        // Handle different operators
        if (strpos($condition, '!=') !== false) {
            [$field, $value] = explode('!=', $condition, 2);
            $field = trim($field);
            $value = trim($value);

            if ($value === 'null') {
                $queryBuilder->whereNotNull($field, $boolean);
            } else {
                $queryBuilder->where($field, '!=', $value, $boolean);
            }
        } elseif (strpos($condition, '=') !== false) {
            [$field, $value] = explode('=', $condition, 2);
            $field = trim($field);
            $value = trim($value);

            if ($value === 'null') {
                $queryBuilder->whereNull($field, $boolean);
            } else {
                $queryBuilder->where($field, $value, $boolean);
            }
        } elseif (strpos($condition, '>') !== false) {
            [$field, $value] = explode('>', $condition, 2);
            $queryBuilder->where(trim($field), '>', trim($value), $boolean);
        } elseif (strpos($condition, '<') !== false) {
            [$field, $value] = explode('<', $condition, 2);
            $queryBuilder->where(trim($field), '<', trim($value), $boolean);
        }
        // Add more operators as needed (LIKE, IN, etc.)
    }
}
