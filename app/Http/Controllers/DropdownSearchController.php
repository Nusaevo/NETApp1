<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * DropdownSearchController
 *
 * Enhanced AJAX dropdown search controller with support for:
 * - Raw SQL query support with case-insensitive search (using UPPER)
 * - Dynamic database connection (uses app_code from session by default)
 * - Multi-label display
 * - Smart field detection for searching (uses optionLabel fields automatically)
 *
 * Search Features:
 * - All searches are performed using UPPER() for case-insensitive matching
 * - Automatically searches fields specified in optionLabel parameter
 * - Falls back to smart field detection if no optionLabel fields are provided
 *
 * Query Examples:
 * - Simple: "SELECT id, code, name FROM config_const WHERE const_group='MMATL_BRAND' AND deleted_at IS NULL"
 * - With search: "SELECT id, code, name FROM material WHERE status_code='A' AND deleted_at IS NULL AND (UPPER(code) LIKE :search OR UPPER(name) LIKE :search)"
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
            $searchTerm = $request->get('q', '');
            $optionValue = $request->get('option_value', 'id');
            $optionLabel = $request->get('option_label', 'name');
            $specificId = $request->get('id'); // For value restoration            // Debug all request parameters
            \Log::debug('All dropdown search parameters:', $request->all());

            // Get SQL query - check all possible parameter names with more detail
            $sqlQuery = $request->input('query');

            // Use the sanitizer helper to clean and validate the query
            $sqlQuery = $this->sanitizeSqlQuery($sqlQuery);

            \Log::debug('Initial query parameter processing:', [
                'query_value' => $sqlQuery,
                'query_type' => gettype($sqlQuery),
                'query_length' => $sqlQuery ? strlen($sqlQuery) : 0
            ]);

            // Try different methods to get the query parameter if empty
            if (empty($sqlQuery)) {
                // Try getting directly from GET params
                $rawQuery = $request->query('query');
                $sqlQuery = $this->sanitizeSqlQuery($rawQuery);
                \Log::debug('Trying query from query params:', ['value' => $sqlQuery]);
            }

            if (empty($sqlQuery)) {
                // Try sqlQuery as a fallback parameter name
                $rawQuery = $request->input('sqlQuery');
                $sqlQuery = $this->sanitizeSqlQuery($rawQuery);
                \Log::debug('Using sqlQuery as fallback:', ['value' => $sqlQuery]);
            }

            // Final debug output of resolved query
            \Log::info('Final SQL query:', [
                'query' => $sqlQuery,
                'length' => $sqlQuery ? strlen($sqlQuery) : 0,
                'request_data' => $request->all()
            ]);

            $requestConnection = $request->get('connection');


            // If connection is "Default" (case insensitive), use app_code from session
            $connection = null;
            if ($requestConnection) {
                $connection = (strtolower($requestConnection) === 'default')
                    ? Session::get('app_code')
                    : $requestConnection;
            } else {
                $connection = Session::get('app_code');
            }

            // If still empty, fail with a clear message
            if (!$connection) {
                \Log::error('No database connection available', [
                    'app_code' => Session::get('app_code'),
                    'requestConnection' => $requestConnection
                ]);
            }

            // Debug logging
            \Log::info('Dropdown search request:', [
                'searchTerm' => $searchTerm,
                'sqlQuery' => $sqlQuery,
                'requestedConnection' => $requestConnection,
                'resolvedConnection' => $connection,
                'app_code' => Session::get('app_code'),
                'optionValue' => $optionValue,
                'optionLabel' => $optionLabel,
                'specificId' => $specificId
            ]);

            // Validate connection
            if (!$connection) {
                return response()->json([
                    'results' => [],
                    'error' => 'No connection specified and no app_code in session'
                ], 400);
            }

            // Get DB connection
            try {
                $db = DB::connection($connection);
            } catch (\Exception $e) {
                \Log::error('Invalid database connection:', ['connection' => $connection, 'error' => $e->getMessage()]);
                return response()->json([
                    'results' => [],
                    'error' => 'Invalid database connection: ' . $connection
                ], 400);
            }

            // Validate SQL query
            if (!$sqlQuery) {
                $errorMessage = 'SQL query is required';
                \Log::error($errorMessage, [
                    'request_all' => $request->all(),
                    'request_headers' => $request->header(),
                    'search_term' => $searchTerm
                ]);

                return response()->json([
                    'results' => [],
                    'error' => $errorMessage
                ], 400);
            }

            // Process SQL query
            try {
                // For search, modify query to include search term
                $modifiedQuery = "";
                if (!empty($searchTerm)) {
                    $modifiedQuery = $this->modifyQueryForSearch($sqlQuery, $searchTerm, $optionLabel);
                } else {
                    $modifiedQuery = $sqlQuery;
                }

                // Execute query for regular search
                $results = $db->select($modifiedQuery . ' LIMIT 50');

                // Prepare query with replacements for search term or specific ID
                if ($specificId) {
                    // For specific ID lookup, modify query to filter by ID
                    $modifiedQuery = $this->modifyQueryForId($sqlQuery, $optionValue, $specificId);

                    try {
                        $results = $db->select($modifiedQuery);

                        \Log::info('Specific ID lookup query:', [
                            'modifiedQuery' => $modifiedQuery,
                            'results' => count($results),
                            'optionLabel' => $optionLabel
                        ]);

                        if (!empty($results)) {
                            $item = $results[0];

                            // Check if the required property exists
                            if (!property_exists($item, $optionValue)) {
                                \Log::error('Column not found in results:', [
                                    'optionValue' => $optionValue,
                                    'available' => array_keys(get_object_vars($item))
                                ]);

                                return response()->json([
                                    'results' => [],
                                    'error' => "Column '{$optionValue}' not found in result set"
                                ], 400);
                            }

                            $displayText = $this->formatDisplayText($item, $optionLabel);

                            return response()->json([
                                'results' => [[
                                    'id' => $item->{$optionValue},
                                    'text' => $displayText
                                ]]
                            ]);
                        } else {
                            \Log::info('No results found for specific ID lookup:', [
                                'id' => $specificId,
                                'query' => $modifiedQuery
                            ]);
                            return response()->json(['results' => []]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error in specific ID lookup:', [
                            'query' => $modifiedQuery,
                            'error' => $e->getMessage()
                        ]);

                        return response()->json([
                            'results' => [],
                            'error' => 'Error retrieving specific ID: ' . $e->getMessage()
                        ], 500);
                    }
                }

                // Format results
                $formattedResults = collect($results)->map(function ($item) use ($optionValue, $optionLabel) {
                    $displayText = $this->formatDisplayText($item, $optionLabel);

                    // Make sure ID exists and is properly formatted
                    $id = property_exists($item, $optionValue) ? $item->{$optionValue} : null;

                    if ($id === null) {
                        \Log::warning('Dropdown item missing ID field:', [
                            'optionValue' => $optionValue,
                            'item' => json_encode($item)
                        ]);
                    }

                    return [
                        'id' => $id,
                        'text' => $displayText
                    ];
                })->filter(function($item) {
                    return $item['id'] !== null; // Filter out items with null IDs
                });

                \Log::info('Dropdown search results count:', ['count' => count($formattedResults)]);

                return response()->json([
                    'results' => $formattedResults
                ]);
            } catch (\Exception $e) {
                \Log::error('Error in dropdown search: ' . $e->getMessage());
                return response()->json(['error' => $e->getMessage()], 500);
            }
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
     * Format the display text based on the option label format
     *
     * @param object $item
     * @param string $optionLabel
     * @return string
     */
    private function formatDisplayText($item, $optionLabel)
    {
        // Support multiple labels separated by comma
        $labels = explode(',', $optionLabel);
        $displayText = '';
        $foundAny = false;

        foreach ($labels as $index => $label) {
            $label = trim($label);
            if ($label && property_exists($item, $label) && $item->{$label} !== null) {
                if ($index > 0 && $foundAny) {
                    $displayText .= ' - ';
                }
                $displayText .= $item->{$label};
                $foundAny = true;
            }
        }

        // If no labels were found, try to use just the first one or return a default
        if (!$foundAny && !empty($labels)) {
            $firstLabel = trim($labels[0]);
            if (property_exists($item, $firstLabel) && $item->{$firstLabel} !== null) {
                $displayText = $item->{$firstLabel};
            } else {
                $displayText = '[No Label]';
                \Log::warning('No display labels found for item', [
                    'requestedLabels' => $optionLabel,
                    'availableFields' => array_keys(get_object_vars($item))
                ]);
            }
        }

        return $displayText;
    }

    /**
     * Modify SQL query to search by term
     *
     * @param string $sqlQuery
     * @param string $searchTerm
     * @return string
     */    private function modifyQueryForSearch($sqlQuery, $searchTerm, $optionLabel = null)
    {
        // Log the raw query for debugging
        // \Log::debug('modifyQueryForSearch raw inputs', [
        //     'sqlQuery' => $sqlQuery,
        //     'searchTerm' => $searchTerm,
        //     'optionLabel' => $optionLabel
        // ]);

        if (empty($searchTerm)) {
            // No search term provided, return original query
            return $sqlQuery;
        }

        // Convert search term to uppercase for case-insensitive search
        $upperSearchTerm = strtoupper($searchTerm);

        // Escape the search term for SQL safety
        $escapedSearchTerm = addslashes($upperSearchTerm);

        // Check if the query already has a WHERE clause
        $hasWhere = stripos($sqlQuery, ' WHERE ') !== false;

        // Check if the query already contains search placeholders
        $hasSearchPlaceholder = stripos($sqlQuery, ':search') !== false;

        if ($hasSearchPlaceholder) {
            // The query already has search placeholders, replace them
            // \Log::info('Using placeholder in search query', [
            //     'term' => $searchTerm,
            //     'upper_term' => $upperSearchTerm
            // ]);

            // If the query contains explicit placeholders, modify to use UPPER
            if (stripos($sqlQuery, 'UPPER(') === false) {
                // No UPPER already in query, add it via transformation
                $sqlQuery = preg_replace(
                    '/([a-zA-Z0-9_\.]+)\s+LIKE\s+:search/i',
                    'UPPER($1) LIKE :search',
                    $sqlQuery
                );
            }

            $modifiedQuery = str_replace(':search', '%' . $escapedSearchTerm . '%', $sqlQuery);
            return $modifiedQuery;
        }

        // Use optionLabel fields as searchable fields
        // These are the fields that are shown in the dropdown, so they should be searched
        $optionLabelFields = explode(',', $optionLabel ?? '');

        // Trim and clean up the field names
        $searchableFields = [];
        foreach ($optionLabelFields as $field) {
            $field = trim($field);
            if (!empty($field)) {
                $searchableFields[] = $field;
            }
        }

        // If no searchable fields from optionLabel, use defaults or detect from query
        if (empty($searchableFields)) {
            //\Log::info('No fields found in optionLabel, attempting to detect from query');

            $searchableFields = ['name', 'code']; // Default fields

            // Try to extract field names from the SELECT clause
            preg_match('/SELECT\s+.*?\s+FROM\s+/is', $sqlQuery, $selectMatches);
            $selectClause = $selectMatches[0] ?? '';

            if (!empty($selectClause)) {
                preg_match_all('/\b([a-zA-Z0-9_]+)(?:\s+as\s+[a-zA-Z0-9_]+)?\b/i', $selectClause, $fieldMatches);
                if (!empty($fieldMatches[1])) {
                    $extractedFields = array_diff($fieldMatches[1], ['SELECT', 'FROM', 'AS']);
                    if (!empty($extractedFields)) {
                        // Filter out common non-searchable fields
                        $nonSearchableFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
                        $potentialSearchFields = array_diff($extractedFields, $nonSearchableFields);

                        if (!empty($potentialSearchFields)) {
                            $searchableFields = $potentialSearchFields;
                        }
                    }
                }
            }
        }

        //\Log::debug('Using searchable fields', ['fields' => $searchableFields]);

        // Build search conditions for all searchable fields using UPPER for case-insensitive search
        $searchConditions = [];
        foreach ($searchableFields as $field) {
            $searchConditions[] = "UPPER($field) LIKE '%{$escapedSearchTerm}%'";
        }

        $searchClause = '(' . implode(' OR ', $searchConditions) . ')';

        // \Log::info('Generated search clause', [
        //     'searchFields' => $searchableFields,
        //     'clause' => $searchClause,
        //     'uppercased_term' => $upperSearchTerm
        // ]);

        // No search placeholders, we need to add the search condition
        if ($hasWhere) {
            // Add search condition to existing WHERE clause
            return $sqlQuery . " AND " . $searchClause;
        } else {
            // Add new WHERE clause with search condition
            return $sqlQuery . " WHERE " . $searchClause;
        }
    }

    /**
     * Modify SQL query to filter by ID
     *
     * @param string $sqlQuery
     * @param string $optionValue
     * @param mixed $id
     * @return string
     */
    private function modifyQueryForId($sqlQuery, $optionValue, $id)
    {
        // Log for debugging
        \Log::debug('modifyQueryForId inputs', [
            'sqlQuery' => $sqlQuery,
            'optionValue' => $optionValue,
            'id' => $id
        ]);

        // Ensure the SQL query is valid
        $sqlQuery = $this->sanitizeSqlQuery($sqlQuery);

        if (empty($sqlQuery)) {
            \Log::error('Invalid SQL query in modifyQueryForId');
            return "SELECT * FROM dual WHERE 1=0"; // Return empty query
        }

        // Check if the query already has a WHERE clause
        $hasWhere = stripos($sqlQuery, ' WHERE ') !== false;

        // Use query parameters instead of string concatenation
        if ($hasWhere) {
            // Add ID filter to existing WHERE clause
            return $sqlQuery . " AND " . $optionValue . " = '" . addslashes($id) . "'";
        } else {
            // Add new WHERE clause with ID filter
            return $sqlQuery . " WHERE " . $optionValue . " = '" . addslashes($id) . "'";
        }
    }

    /**
     * Helper method to sanitize and validate SQL query
     *
     * @param string|null $query The SQL query to sanitize
     * @return string|null The sanitized query or null if invalid
     */
    private function sanitizeSqlQuery($query)
    {
        if (empty($query)) {
            return null;
        }

        // Decode HTML entities that may have been encoded during transport
        $query = html_entity_decode($query, ENT_QUOTES, 'UTF-8');

        // Basic validation: must start with SELECT
        if (!preg_match('/^\s*SELECT\s+/i', $query)) {
            \Log::warning('Invalid SQL query detected (must start with SELECT)', [
                'query' => $query
            ]);
            return null;
        }

        // Log the sanitized query
        \Log::debug('Sanitized SQL query', [
            'sanitized_query' => $query,
            'length' => strlen($query)
        ]);

        return $query;
    }
}
