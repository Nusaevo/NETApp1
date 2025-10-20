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
            // Get search term and searchOnSpace setting - don't use default value to detect null
            $rawSearchTerm = $request->has('q') ? $request->get('q') : null;
            $searchOnSpace = $request->get('search_on_space', 'false');

            // Debug logging disabled
            /*
            \Log::info('Search Parameters DEBUG', [
                'q_raw' => $rawSearchTerm,
                'q_null' => $rawSearchTerm === null,
                'q_empty' => $rawSearchTerm === '',
                'q_length' => is_string($rawSearchTerm) ? strlen($rawSearchTerm) : 0,
                'q_bytes' => is_string($rawSearchTerm) ? bin2hex($rawSearchTerm) : '',
                'search_on_space' => $searchOnSpace,
                'search_on_space_type' => gettype($searchOnSpace),
                'is_space_only' => is_string($rawSearchTerm) && preg_match('/^\s+$/', $rawSearchTerm) ? 'true' : 'false',
                'all_request_params' => $request->all()
            ]);
            */

            // Handle search on space mode - check BEFORE trimming
            $isSpaceOnlySearch = false;

            // Fix: Lebih agresif mendeteksi spasi dengan multiple checks
            $isOnlySpaces = preg_match('/^\s+$/', $rawSearchTerm) === 1;
            $isEmptyAfterTrim = trim($rawSearchTerm) === '';
            $hasOnlySpaceCharacters = $isOnlySpaces || $isEmptyAfterTrim;

            // Fix: Improve searchOnSpace detection (handle string 'true' or boolean true)
            $isSpaceEnabled = in_array($searchOnSpace, ['true', true], true);

            // Fix: Empty initial search also triggers search-on-space (when q is null)
            $isInitialEmptySearch = $rawSearchTerm === null || $rawSearchTerm === '';

            // Debug logging disabled
            /*
            \Log::info('Space detection debug', [
                'rawTerm' => $rawSearchTerm,
                'rawTerm_type' => gettype($rawSearchTerm),
                'rawLength' => is_string($rawSearchTerm) ? strlen($rawSearchTerm) : 0,
                'rawBytes' => is_string($rawSearchTerm) ? array_map('ord', str_split($rawSearchTerm)) : [],
                'rawHex' => is_string($rawSearchTerm) ? bin2hex($rawSearchTerm) : '',
                'isOnlySpaces' => $isOnlySpaces,
                'isEmptyAfterTrim' => $isEmptyAfterTrim,
                'hasOnlySpaceCharacters' => $hasOnlySpaceCharacters,
                'isInitialEmptySearch' => $isInitialEmptySearch,
                'isSpaceEnabled' => $isSpaceEnabled
            ]);
            */

            // Fix: Also activate search when dropdown is initially opened with no input (null q param)
            if ($isSpaceEnabled && ($isInitialEmptySearch || ($hasOnlySpaceCharacters && $rawSearchTerm !== null))) {
                // User opened dropdown or typed space(s) and searchOnSpace is enabled - search all data
                $isSpaceOnlySearch = true;
                $searchTerm = ''; // Empty search term will return all data
                // Debug logging disabled
                /*
                \Log::info('SearchOnSpace ACTIVATED!', [
                    'rawSearchTerm' => $rawSearchTerm,
                    'rawLength' => strlen($rawSearchTerm),
                    'searchOnSpace' => $searchOnSpace,
                    'isSpaceEnabled' => $isSpaceEnabled,
                    'hasOnlySpaceCharacters' => $hasOnlySpaceCharacters
                ]);
                */
            } else {
                // Normal processing - trim the search term
                $searchTerm = trim($rawSearchTerm);
                if (empty($searchTerm)) {
                    $searchTerm = '';
                }
                // Debug logging disabled
                /*
                \Log::info('Normal search processing', [
                    'rawSearchTerm' => $rawSearchTerm,
                    'searchTerm' => $searchTerm,
                    'isSpaceEnabled' => $isSpaceEnabled,
                    'hasOnlySpaceCharacters' => $hasOnlySpaceCharacters,
                    'isSpaceOnlySearch' => $isSpaceOnlySearch
                ]);
                */
            }

            $optionValue = $request->get('option_value', 'id');
            $optionLabel = $request->get('option_label', 'name');
            $specificId = $request->get('id'); // For value restoration
            $preserveExisting = $request->get('preserve_existing', false);
            $bypassFilters = $request->get('bypass_filters', false);

            // Debug all request parameters
            // \Log::debug('All dropdown search parameters:', $request->all());

            // Get SQL query - check all possible parameter names with more detail
            $sqlQuery = $request->input('query');

            // Use the sanitizer helper to clean and validate the query
            $sqlQuery = $this->sanitizeSqlQuery($sqlQuery);

            // \Log::debug('Initial query parameter processing:', [
            //     'query_value' => $sqlQuery,
            //     'query_type' => gettype($sqlQuery),
            //     'query_length' => $sqlQuery ? strlen($sqlQuery) : 0
            // ]);

            // Try different methods to get the query parameter if empty
            if (empty($sqlQuery)) {
                // Try getting directly from GET params
                $rawQuery = $request->query('query');
                $sqlQuery = $this->sanitizeSqlQuery($rawQuery);
                // \Log::debug('Trying query from query params:', ['value' => $sqlQuery]);
            }

            if (empty($sqlQuery)) {
                // Try sqlQuery as a fallback parameter name
                $rawQuery = $request->input('sqlQuery');
                $sqlQuery = $this->sanitizeSqlQuery($rawQuery);
                // \Log::debug('Using sqlQuery as fallback:', ['value' => $sqlQuery]);
            }

            // Final debug output of resolved query
            // \Log::info('Final SQL query:', [
            //     'query' => $sqlQuery,
            //     'length' => $sqlQuery ? strlen($sqlQuery) : 0,
            //     'request_data' => $request->all(),
            //     'preserve_existing' => $preserveExisting,
            //     'bypass_filters' => $bypassFilters,
            //     'specific_id' => $specificId
            // ]);

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
                // \Log::error('No database connection available', [
                //     'app_code' => Session::get('app_code'),
                //     'requestConnection' => $requestConnection
                // ]);
            }

            // Debug logging
            // \Log::info('Dropdown search request:', [
            //     'searchTerm' => $searchTerm,
            //     'sqlQuery' => $sqlQuery,
            //     'requestedConnection' => $requestConnection,
            //     'resolvedConnection' => $connection,
            //     'app_code' => Session::get('app_code'),
            //     'optionValue' => $optionValue,
            //     'optionLabel' => $optionLabel,
            //     'specificId' => $specificId
            // ]);

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
                // \Log::error('Invalid database connection:', ['connection' => $connection, 'error' => $e->getMessage()]);
                return response()->json([
                    'results' => [],
                    'error' => 'Invalid database connection: ' . $connection
                ], 400);
            }

            // Validate SQL query
            if (!$sqlQuery) {
                $errorMessage = 'SQL query is required';
                // \Log::error($errorMessage, [
                //     'request_all' => $request->all(),
                //     'request_headers' => $request->header(),
                //     'search_term' => $searchTerm
                // ]);

                return response()->json([
                    'results' => [],
                    'error' => $errorMessage
                ], 400);
            }

            // Process SQL query
            try {
                $modifiedQuery = "";

                if ($preserveExisting && $specificId && $bypassFilters) {
                    // This is for fetching existing/selected value - bypass ALL filters
                    $modifiedQuery = $this->bypassAllFilters($sqlQuery, $optionValue, $specificId);

                    // \Log::info('SELECTED VALUE QUERY: ' . $modifiedQuery);

                    try {
                        $results = $db->select($modifiedQuery);
                    } catch (\Exception $e) {
                        // \Log::error('Selected value query error: ' . $e->getMessage());
                        throw $e;
                    }

                } else if ($specificId) {
                    // Regular specific ID lookup with normal filters
                    $modifiedQuery = $this->modifyQueryForId($sqlQuery, $optionValue, $specificId);
                    $results = $db->select($modifiedQuery);

                    // If no results found with normal filters, try bypass mode automatically
                    if (empty($results)) {
                        $bypassQuery = $this->bypassAllFilters($sqlQuery, $optionValue, $specificId);
                        // \Log::info('FALLBACK SELECTED VALUE QUERY: ' . $bypassQuery);
                        $results = $db->select($bypassQuery);
                    }

                } else {
                    // Normal search with filters applied
                    if (!empty($searchTerm)) {
                        $modifiedQuery = $this->modifyQueryForSearch($sqlQuery, $searchTerm, $optionLabel);
                        // Execute query for regular search
                        $results = $db->select($modifiedQuery . ' LIMIT 50');
                    } else if ($isSpaceOnlySearch) {
                        // Space-only search - return all data with limit
                        $finalQuery = $sqlQuery . ' LIMIT 100';
                        // Debug logging disabled
                        /*
                        \Log::info('Executing space search query', [
                            'query' => $finalQuery,
                            'connection' => $connection,
                            'isSpaceOnlySearch' => $isSpaceOnlySearch,
                            'searchOnSpace' => $searchOnSpace
                        ]);
                        */
                        $results = $db->select($finalQuery);
                        // Debug logging disabled
                        // \Log::info('Space search results', ['count' => count($results)]);
                    } else {
                        // Empty search term - return empty results to prevent showing all data
                        // Debug logging disabled
                        /*
                        \Log::info('Empty search with no space-search - returning empty results', [
                            'isSpaceOnlySearch' => $isSpaceOnlySearch,
                            'searchOnSpace' => $searchOnSpace,
                            'rawSearchTerm' => $rawSearchTerm
                        ]);
                        */
                        $results = [];
                    }
                }                // Handle specific ID lookup (both bypass and normal)
                if ($specificId && !empty($results)) {
                    $item = $results[0];

                    // Check if the required property exists
                    if (!property_exists($item, $optionValue)) {
                        // \Log::error('Column not found in results:', [
                        //     'optionValue' => $optionValue,
                        //     'available' => array_keys(get_object_vars($item))
                        // ]);

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
                } else if ($specificId) {
                    // \Log::info('No results found for specific ID lookup:', [
                    //     'id' => $specificId,
                    //     'query' => $modifiedQuery
                    // ]);
                    return response()->json(['results' => []]);
                }

                // Format results for regular search
                $formattedResults = collect($results)->map(function ($item) use ($optionValue, $optionLabel) {
                    $displayText = $this->formatDisplayText($item, $optionLabel);

                    // Make sure ID exists and is properly formatted
                    $id = property_exists($item, $optionValue) ? $item->{$optionValue} : null;

                    if ($id === null) {
                        // \Log::warning('Dropdown item missing ID field:', [
                        //     'optionValue' => $optionValue,
                        //     'item' => json_encode($item)
                        // ]);
                    }

                    return [
                        'id' => $id,
                        'text' => $displayText
                    ];
                })->filter(function($item) {
                    return $item['id'] !== null; // Filter out items with null IDs
                });

                //\Log::info('Dropdown search results count:', ['count' => count($formattedResults)]);

                return response()->json([
                    'results' => $formattedResults
                ]);
            } catch (\Exception $e) {
                //\Log::error('Error in dropdown search: ' . $e->getMessage());
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            // \Log::error('Dropdown search error:', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'results' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format the display text based on the option label format
     *
     * Enhanced to support template placeholders:
     * - Use {} for dynamic field replacement: "Kode : {m.code}; Nama :{m.name}"
     * - Text outside {} is displayed as-is
     * - Text inside {} is replaced with field values
     * - Simple formats like "m.code, m.name, qtyoh" are automatically converted to placeholder format
     *
     * Separator rules:
     * - Use semicolon (;) for line breaks: "code;name" → "ABC123\nProduct Name"
     * - Use comma (,) for dash separator: "code,name" → "ABC123 - Product Name"
     *
     * @param object $item
     * @param string $optionLabel
     * @return string
     */
    private function formatDisplayText($item, $optionLabel)
    {
        // Check if the optionLabel contains placeholders {}
        if (preg_match('/\{[^}]+\}/', $optionLabel)) {
            // Handle template format with placeholders
            return $this->formatDisplayTextWithPlaceholders($item, $optionLabel);
        }

        // Convert simple field format to placeholder format for consistent handling
        $convertedLabel = $this->convertToPlaceholderFormat($optionLabel);
        return $this->formatDisplayTextWithPlaceholders($item, $convertedLabel);
    }

    /**
     * Convert simple field format to placeholder format
     * Example: "m.code, m.name, qtyoh" becomes "{m.code}, {m.name}, {qtyoh}"
     *
     * @param string $optionLabel
     * @return string
     */
    private function convertToPlaceholderFormat($optionLabel)
    {
        // Determine separator and split accordingly
        $useSemicolon = strpos($optionLabel, ';') !== false;
        $separator = $useSemicolon ? ';' : ',';
        $labels = explode($separator, $optionLabel);

        $convertedParts = [];
        foreach ($labels as $label) {
            $label = trim($label);
            if (!empty($label)) {
                $convertedParts[] = '{' . $label . '}';
            }
        }

        return implode($separator, $convertedParts);
    }

    /**
     * Format display text with template placeholders
     * Handles format like "Kode : {m.code}; Nama :{m.name}" or "{m.code}, {m.name}, {qtyoh}"
     *
     * @param object $item
     * @param string $optionLabel
     * @return string
     */
    private function formatDisplayTextWithPlaceholders($item, $optionLabel)
    {
        // Determine separator for line breaks
        $useSemicolon = strpos($optionLabel, ';') !== false;
        $separator = $useSemicolon ? ';' : ',';
        $segments = explode($separator, $optionLabel);

        $displayText = '';
        $foundAny = false;

        foreach ($segments as $index => $segment) {
            $segment = trim($segment);
            $processedSegment = '';

            // Process placeholders in this segment
            $processedSegment = preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($item) {
                $fieldName = trim($matches[1]);

                // Handle table aliases: if field contains dot (e.g., "m.code"), extract just the column name
                if (strpos($fieldName, '.') !== false) {
                    $parts = explode('.', $fieldName);
                    $fieldName = end($parts);
                }

                // Return field value with null handling
                if (property_exists($item, $fieldName)) {
                    $value = $item->{$fieldName};
                    // Convert null values to empty string for consistent UI display
                    if ($value === null || $value === '') {
                        \Log::debug('Null/empty field mapped to empty string:', [
                            'field' => $fieldName,
                            'original_value' => $value
                        ]);
                        return '';
                    }
                    return (string)$value; // Ensure string conversion for display
                } else {
                    \Log::debug('Placeholder field not found:', [
                        'original_field' => $matches[1],
                        'processed_field' => $fieldName,
                        'available_fields' => array_keys(get_object_vars($item))
                    ]);
                    return ''; // Return empty string for missing fields
                }
            }, $segment);

            // Add this segment if it has content (after placeholder replacement)
            if (!empty($processedSegment)) {
                if ($index > 0 && $foundAny) {
                    if ($useSemicolon) {
                        // Use line break for semicolon separator
                        $displayText .= "\n";
                    } else {
                        // Use dash for comma separator - same as original format
                        $displayText .= ' - ';
                    }
                }
                $displayText .= $processedSegment;
                $foundAny = true;
            }
        }

        // If no segments with valid data were processed, return a fallback
        if (!$foundAny) {
            \Log::warning('No valid field values found in template', [
                'template' => $optionLabel,
                'available_fields' => array_keys(get_object_vars($item))
            ]);
            return '[No Data Available]';
        }

        return $displayText;
    }

    /**
     * Create a simplified query for selected value lookup
     * Uses basic SELECT with simple WHERE condition: WHERE {optionValue} = xxxx
     * This bypasses all complex filters and joins to ensure selected values can always be found
     * Supports both aliased fields (m.id) and simple fields (id)
     */
    private function bypassAllFilters($sqlQuery, $optionValue, $specificId)
    {
        // For selected value lookup, we want ONLY the ID condition
        // Remove all existing WHERE clauses and add only WHERE id = specificId

        // Handle flexible optionValue - support both "id" and "m.id" format
        $whereField = $optionValue;

        // If optionValue contains a dot (table alias), check if it exists in the query
        if (strpos($optionValue, '.') !== false) {
            // Extract table alias and field name
            $parts = explode('.', $optionValue);
            $tableAlias = $parts[0];
            $fieldName = $parts[1];

            // Check if the table alias exists in the query
            if (stripos($sqlQuery, $tableAlias . ' ') !== false || stripos($sqlQuery, ' ' . $tableAlias) !== false) {
                // Alias exists in query, use full format (e.g., "m.id")
                $whereField = $optionValue;
            } else {
                // Alias doesn't exist, fall back to simple field name (e.g., "id")
                $whereField = $fieldName;
            }
        } else {
            // Simple field name without alias, try to detect if we need to add an alias
            // Look for common table aliases in the query that might need the field
            if (preg_match('/\bFROM\s+\w+\s+([a-zA-Z]+)\b/i', $sqlQuery, $matches)) {
                $potentialAlias = $matches[1];

                // Check if this alias is used elsewhere in the query
                if (stripos($sqlQuery, $potentialAlias . '.') !== false) {
                    // Alias is used, so we should probably use it too
                    $whereField = $potentialAlias . '.' . $optionValue;
                }
            }
        }

        // Remove existing WHERE clause completely for selected value lookup
        $upperQuery = strtoupper($sqlQuery);
        $wherePos = strpos($upperQuery, ' WHERE ');

        if ($wherePos !== false) {
            // Remove everything from WHERE onwards
            $queryWithoutWhere = substr($sqlQuery, 0, $wherePos);
        } else {
            // No WHERE clause to remove
            $queryWithoutWhere = $sqlQuery;
        }

        // Add ONLY the ID condition
        $escapedId = is_numeric($specificId) ? $specificId : "'" . addslashes($specificId) . "'";

        // Create new WHERE clause with ONLY the ID condition
        $finalQuery = $queryWithoutWhere . " WHERE {$whereField} = {$escapedId}";

        return $finalQuery;
    }    /**
     * Modify SQL query to search by term
     *
     * @param string $sqlQuery
     * @param string $searchTerm
     * @return string
     */    private function modifyQueryForSearch($sqlQuery, $searchTerm, $optionLabel = null)
    {
        // Check if search term is empty or only whitespace
        if (empty($searchTerm) || trim($searchTerm) === '') {
            // No search term provided or only spaces, return original query without search filters
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
        // Support both comma and semicolon as separators, and handle placeholders {}
        $searchableFields = $this->extractSearchableFields($optionLabel);

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
        // Cast all fields to text first to handle numeric fields
        $searchConditions = [];
        foreach ($searchableFields as $field) {
            $searchConditions[] = "UPPER(CAST($field AS TEXT)) LIKE '%{$escapedSearchTerm}%'";
        }

        $searchClause = '(' . implode(' OR ', $searchConditions) . ')';

        // \Log::info('Generated search clause with table aliases and text casting', [
        //     'searchFields' => $searchableFields,
        //     'clause' => $searchClause,
        //     'uppercased_term' => $upperSearchTerm,
        //     'original_option_label' => $optionLabel
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
     * Extract searchable fields from optionLabel, handling both simple fields and placeholders
     * All fields are now treated as if they have {} placeholders for consistent search behavior
     *
     * @param string $optionLabel
     * @return array
     */
    private function extractSearchableFields($optionLabel)
    {
        $searchableFields = [];

        if (empty($optionLabel)) {
            return $searchableFields;
        }

        // Check if optionLabel contains placeholders {}
        if (preg_match_all('/\{([^}]+)\}/', $optionLabel, $matches)) {
            // Extract field names from placeholders
            foreach ($matches[1] as $field) {
                $field = trim($field);

                if (!empty($field)) {
                    // Preserve full field name with table alias for search queries
                    $searchableFields[] = $field;
                }
            }
        } else {
            // Convert simple field format to placeholder format for consistent handling
            // Support both comma and semicolon as separators
            $separator = strpos($optionLabel, ';') !== false ? ';' : ',';
            $optionLabelFields = explode($separator, $optionLabel);

            // Trim and clean up the field names, preserving table aliases
            foreach ($optionLabelFields as $field) {
                $field = trim($field);
                if (!empty($field)) {
                    // Preserve full field name with table alias for search queries
                    $searchableFields[] = $field;
                }
            }
        }

        return array_unique($searchableFields); // Remove duplicates
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
            // \Log::warning('Invalid SQL query detected (must start with SELECT)', [
            //     'query' => $query
            // ]);
            return null;
        }

        // Log the sanitized query
        // \Log::debug('Sanitized SQL query', [
        //     'sanitized_query' => $query,
        //     'length' => strlen($query)
        // ]);

        return $query;
    }
}
