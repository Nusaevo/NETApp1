<?php

namespace App\Livewire\Component;

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class UiDropdownSelect extends Component
{
    public $options = [];
    public $selectedValue;
    public $selectedLabel = '';
    public $textFieldSearch = '';
    public $searchTerm = '';
    public $highlightIndex = 0;
    public $isSearching = false;
    public $isOpen = false;
    public $showDropdown = false;
    public $model;
    public $placeHolder;
    public $span;
    public $query; // Raw SQL query support
    public $connection;
    public $optionValue;
    public $optionLabel;
    public $onChanged;
    public $inputClass = 'form-select'; // Default class
    public $minSearchLength = 1; // Allow 1 character search
    public $label = '';
    public $required = 'false';
    public $clickEvent = '';
    public $buttonName = '';
    public $action = '';
    public $buttonEnabled = 'true';
    public $enabled = 'true';
    public $visible = 'true';
    public $labelLoaded = false; // Track if label has been loaded

    /**
     * Mount method to initialize dynamic queries and parameters.
     */
    public function mount(
        $model = null,
        $query = null,
        $connection = 'Default',
        $optionValue = 'id',
        $optionLabel = 'name',
        $selectedValue = null,
        $type = 'string',
        $placeHolder = '',
        $span = 'Full',
        $onChanged = null,
        $inputClass = 'form-select',
        $label = '',
        $required = 'false',
        $clickEvent = '',
        $buttonName = '',
        $action = '',
        $buttonEnabled = 'true',
        $enabled = 'true',
        $visible = 'true'
    )
    {
        // Initialize dynamic properties
        $this->model = $model;
        $this->query = $query;
        $this->connection = $connection;
        $this->optionValue = $optionValue;
        $this->optionLabel = $optionLabel;
        $this->placeHolder = $placeHolder;
        $this->span = $span;
        $this->selectedValue = $selectedValue;
        $this->label = $label;
        $this->required = $required;
        $this->clickEvent = $clickEvent;
        $this->buttonName = $buttonName;
        $this->action = $action;
        $this->buttonEnabled = $buttonEnabled;
        $this->enabled = $enabled;
        $this->visible = $visible;
        $this->onChanged = $onChanged;
        $this->inputClass = $inputClass;

        // Don't load selected label immediately - use lazy loading
        // The label will be loaded when the component is first rendered
        // This improves initial page load performance

        // Load initial options if no search term
        if (empty($this->searchTerm)) {
            $this->loadInitialOptions();
        }
    }

    /**
     * Update textFieldSearch field and perform search using enhanced logic
     */
    public function updatedTextFieldSearch()
    {
        // Get and sanitize search term - trim spaces and prevent space-only searches
        $rawSearchTerm = $this->textFieldSearch;
        $this->searchTerm = trim($rawSearchTerm);

        Log::info('updatedTextFieldSearch called', [
            'rawSearchTerm' => $rawSearchTerm,
            'searchTerm' => $this->searchTerm,
            'minSearchLength' => $this->minSearchLength
        ]);

        // Prevent search if term is empty or contains only spaces/whitespace
        if (empty($this->searchTerm) || ctype_space($rawSearchTerm)) {
            $this->options = [];
            $this->showDropdown = false;
            $this->highlightIndex = 0;
            return;
        }

        // Add early return for overly long search terms to prevent timeout
        if (strlen($this->searchTerm) > 100) {
            $this->options = [];
            $this->showDropdown = false;
            return;
        }

        // Allow search with minimum 1 character
        if (strlen(trim($this->searchTerm)) >= $this->minSearchLength) {
            $this->isSearching = true;
            $this->showDropdown = true;
            $this->searchViaEnhancedLogic();
        } else {
            $this->options = [];
            $this->showDropdown = strlen(trim($this->searchTerm)) > 0;
        }
        $this->highlightIndex = 0;
    }

    /**
     * Update search term and perform search using direct database queries.
     */
    public function updatedSearchTerm()
    {
        if (strlen(trim($this->searchTerm)) >= $this->minSearchLength) {
            $this->isSearching = true;
            $this->showDropdown = true;
            $this->searchViaController();
        } else {
            $this->options = [];
            $this->showDropdown = strlen(trim($this->searchTerm)) > 0;
        }
        $this->highlightIndex = 0;
    }

    /**
     * Load initial options when component mounts
     */
    public function loadInitialOptions()
    {
        if ($this->query) {
            // Use raw SQL query for better performance
            $this->options = [];
        }
    }

    /**
     * Lazy load the selected label when needed
     */
    public function loadSelectedLabel()
    {
        // If already loaded, don't reload
        if ($this->labelLoaded) {
            return;
        }

        if (!$this->selectedValue) {
            $this->selectedLabel = '';
            $this->labelLoaded = true;
            return;
        }

        $this->setSelectedLabel();
        $this->labelLoaded = true;
    }

    /**
     * Set the selected label based on current value using enhanced logic
     */
    public function setSelectedLabel()
    {
        if (!$this->selectedValue) {
            $this->selectedLabel = '';
            return;
        }

        try {
            if (!$this->query) {
                $this->selectedLabel = 'ID: ' . $this->selectedValue;
                return;
            }

            // Build query for selected value using bypass logic like DropdownSearchController
            $selectedQuery = $this->buildQueryForSelectedValue();
            if ($selectedQuery) {
                $connection = (strtolower($this->connection) === 'default')
                    ? Session::get('app_code')
                    : $this->connection;

                $result = DB::connection($connection)->select($selectedQuery);

                if (!empty($result)) {
                    $this->selectedLabel = $this->formatEnhancedDisplayText($result[0]);
                } else {
                    $this->selectedLabel = 'ID: ' . $this->selectedValue . ' (Not Found)';

                }
            } else {
                $this->selectedLabel = 'ID: ' . $this->selectedValue;
            }
        } catch (\Exception $e) {
            $this->selectedLabel = 'ID: ' . $this->selectedValue . ' (Error)';
            Log::error('UiDropdownSelect setSelectedLabel error', [
                'message' => $e->getMessage(),
                'selectedValue' => $this->selectedValue
            ]);
        }
    }

    /**
     * Build query for selected value using bypass logic from DropdownSearchController
     */
    private function buildQueryForSelectedValue()
    {
        if (!$this->query) return null;

        $sqlQuery = $this->sanitizeSqlQuery($this->query);
        if (!$sqlQuery) return null;

        // Handle placeholder format like "{item_units.id}" - use the exact field inside braces for WHERE clause
        $whereField = $this->optionValue;

        if (preg_match('/^\{([^}]+)\}$/', $this->optionValue, $matches)) {
            // If optionValue has {}, use exactly what's inside the braces for WHERE clause
            $whereField = trim($matches[1]); // e.g., "item_units.id" from "{item_units.id}"
        } else {
            // Handle non-placeholder format
            if (strpos($this->optionValue, '.') !== false) {
                // Already has table.field format, use as-is
                $whereField = $this->optionValue;
            } else {
                // Simple field name, try to detect if we need table prefix
                if (preg_match('/\bFROM\s+\w+\s+([a-zA-Z]+)\b/i', $sqlQuery, $matches)) {
                    $potentialAlias = $matches[1];
                    if (stripos($sqlQuery, $potentialAlias . '.') !== false) {
                        $whereField = $potentialAlias . '.' . $this->optionValue;
                    }
                }
            }
        }

        // Remove existing WHERE clause completely for selected value lookup (bypass all filters)
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
        $escapedId = is_numeric($this->selectedValue) ? $this->selectedValue : "'" . addslashes($this->selectedValue) . "'";

        // Create new WHERE clause with ONLY the ID condition
        $finalQuery = $queryWithoutWhere . " WHERE {$whereField} = {$escapedId}";



        return $finalQuery;
    }

    /**
     * Enhanced search logic based on DropdownSearchController
     */
    public function searchViaEnhancedLogic()
    {
        try {
            if (!$this->query) {
                $this->options = [];
                $this->isSearching = false;
                return;
            }

            // Build enhanced search query
            $searchQuery = $this->buildEnhancedSearchQuery();

            // Get database connection
            $connection = (strtolower($this->connection) === 'default')
                ? Session::get('app_code')
                : $this->connection;

            // Execute query with LIMIT 50
            $results = DB::connection($connection)->select($searchQuery . ' LIMIT 50');

            // Format results using enhanced display text logic for option list
            $this->options = collect($results)->map(function($item) {
                $id = (string)$this->getNestedProperty($item, $this->optionValue);
                $text = $this->formatOptionListDisplayText($item);

                return [
                    'id' => $id,
                    'text' => $text,
                    'label' => $text // For compatibility
                ];
            })->filter(function($item) {
                return $item['id'] !== null && $item['id'] !== 'null' && $item['id'] !== '';
            })->toArray();        } catch (\Exception $e) {
            $this->options = [];
            Log::error('UiDropdownSelect enhanced search error', [
                'message' => $e->getMessage(),
                'query' => $this->query,
                'searchTerm' => $this->searchTerm
            ]);
        }

        $this->isSearching = false;
    }

    /**
     * Extract searchable fields from optionLabel, handling both simple fields and placeholders
     * All fields are now treated as if they have {} placeholders for consistent search behavior
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
    }    /**
     * Helper method to sanitize and validate SQL query
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
            return null;
        }

        return $query;
    }

    /**
     * Build enhanced search query with improved logic from DropdownSearchController
     */
    private function buildEnhancedSearchQuery()
    {
        $sqlQuery = $this->sanitizeSqlQuery($this->query);

        if (!$sqlQuery) {
            return "SELECT * FROM dual WHERE 1=0"; // Return empty query
        }

        // Prevent search if term is empty, contains only spaces, or is whitespace-only
        if (empty($this->searchTerm) || trim($this->searchTerm) === '' || ctype_space($this->searchTerm)) {
            return $sqlQuery; // Return original query without search filter
        }

        // Minimum search term length validation - use the same minSearchLength as updatedTextFieldSearch
        if (strlen(trim($this->searchTerm)) < $this->minSearchLength) {
            return $sqlQuery; // Return original query without search filter for short terms
        }

        // Convert search term to uppercase for case-insensitive search
        $upperSearchTerm = strtoupper($this->searchTerm);
        $escapedSearchTerm = addslashes($upperSearchTerm);

        // Check if the query already has a WHERE clause
        $hasWhere = stripos($sqlQuery, ' WHERE ') !== false;

        // Check if the query already contains search placeholders
        $hasSearchPlaceholder = stripos($sqlQuery, ':search') !== false;

        if ($hasSearchPlaceholder) {
            // The query already has search placeholders, replace them
            if (stripos($sqlQuery, 'UPPER(') === false) {
                $sqlQuery = preg_replace(
                    '/([a-zA-Z0-9_\.]+)\s+LIKE\s+:search/i',
                    'UPPER($1) LIKE :search',
                    $sqlQuery
                );
            }
            return str_replace(':search', '%' . $escapedSearchTerm . '%', $sqlQuery);
        }

        // Extract searchable fields from optionLabel
        $searchableFields = $this->extractSearchableFields($this->optionLabel);

        Log::info('Extracted searchable fields', [
            'optionLabel' => $this->optionLabel,
            'searchableFields' => $searchableFields
        ]);

        // If no searchable fields from optionLabel, use defaults or detect from query
        if (empty($searchableFields)) {
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

        // Build search conditions for all searchable fields using UPPER for case-insensitive search
        $searchConditions = [];
        foreach ($searchableFields as $field) {
            // Handle aliased fields - if field doesn't contain dot, check if it's an alias in SELECT
            $searchField = $field;

            // Check if this field is an alias by looking at the SELECT clause
            if (strpos($field, '.') === false) {
                // Look for the actual field that creates this alias
                if (preg_match('/SELECT\s+.*?FROM/is', $sqlQuery, $selectMatch)) {
                    $selectClause = $selectMatch[0];

                    // Look for pattern: actual_field alias_name or actual_field AS alias_name
                    if (preg_match('/(\w+\.\w+|\w+)\s+(?:AS\s+)?' . preg_quote($field, '/') . '\b/i', $selectClause, $aliasMatch)) {
                        $searchField = $aliasMatch[1];
                    }
                }
            }

            // PostgreSQL syntax: UPPER(field::TEXT) instead of CAST(field AS CHAR)
            $searchConditions[] = "UPPER({$searchField}::TEXT) LIKE '%{$escapedSearchTerm}%'";
        }

        Log::info('Built search conditions', [
            'searchConditions' => $searchConditions,
            'escapedSearchTerm' => $escapedSearchTerm
        ]);

        // Check if searchConditions is empty
        if (empty($searchConditions)) {
            return $sqlQuery; // Return original query if no search conditions
        }

        $searchClause = '(' . implode(' OR ', $searchConditions) . ')';

        // Add search condition to query
        if ($hasWhere) {
            return $sqlQuery . " AND " . $searchClause;
        } else {
            return $sqlQuery . " WHERE " . $searchClause;
        }
    }



    /**
     * Get nested property value from object/array
     * Follow DropdownSearchController logic for field extraction
     */
    private function getNestedProperty($item, $property)
    {
        // Extract actual field name using same logic as DropdownSearchController
        $actualFieldName = $this->extractFieldFromPlaceholder($property);

        // Handle simple property access
        $result = null;
        if (is_object($item)) {
            $result = $item->$actualFieldName ?? null;
        } elseif (is_array($item)) {
            $result = $item[$actualFieldName] ?? null;
        }

        return $result;
    }

    /**
     * Extract actual field name from placeholder format
     * Handles cases like "{item_units.id}" -> "id" or "{item_units.id}" -> "item_units.id"
     * Same logic as DropdownSearchController
     */
    private function extractFieldFromPlaceholder($optionValue)
    {
        // Check if optionValue is in placeholder format {field_name}
        if (preg_match('/^\{([^}]+)\}$/', $optionValue, $matches)) {
            $fieldName = trim($matches[1]);

            // If field contains a dot (table.field), extract just the field name for property access
            if (strpos($fieldName, '.') !== false) {
                $parts = explode('.', $fieldName);
                return end($parts); // Return just the field name (e.g., "id" from "item_units.id")
            }

            return $fieldName;
        }

        // Not a placeholder, return as-is
        return $optionValue;
    }

    /**
     * Enhanced format display text with placeholder support from DropdownSearchController
     * Used for selected value display (always uses dash)
     */
    private function formatEnhancedDisplayText($item)
    {
        // Check if the optionLabel contains placeholders {}
        if (preg_match('/\{[^}]+\}/', $this->optionLabel)) {
            // Handle template format with placeholders
            return $this->formatDisplayTextWithPlaceholders($item, $this->optionLabel);
        }

        // Convert simple field format to placeholder format for consistent handling
        $convertedLabel = $this->convertToPlaceholderFormat($this->optionLabel);
        return $this->formatDisplayTextWithPlaceholders($item, $convertedLabel);
    }

    /**
     * Format display text for option list (uses line breaks for semicolon, dash for comma)
     */
    private function formatOptionListDisplayText($item)
    {
        // Check if the optionLabel contains placeholders {}
        if (preg_match('/\{[^}]+\}/', $this->optionLabel)) {
            // Handle template format with placeholders
            return $this->formatOptionListTextWithPlaceholders($item, $this->optionLabel);
        }

        // Convert simple field format to placeholder format for consistent handling
        $convertedLabel = $this->convertToPlaceholderFormat($this->optionLabel);
        return $this->formatOptionListTextWithPlaceholders($item, $convertedLabel);
    }

    /**
     * Convert simple field format to placeholder format
     * Example: "m.code, m.name, qtyoh" becomes "{m.code}, {m.name}, {qtyoh}"
     * Also handles separators: semicolon for line breaks, comma for dash separation
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

        // Return with original separator to preserve formatting intention
        return implode($separator, $convertedParts);
    }

    /**
     * Normalize selected label for single-line display
     */
    private function normalizeSelectedLabel($text)
    {
        // Strip HTML tags (including <br>)
        $text = strip_tags($text);
        // Replace multiple whitespace (including newlines) with single space
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim and truncate
        $text = trim($text);
        return $this->truncateText($text, 60);
    }

    /**
     * Truncate long text for better UI display
     */
    private function truncateText($text, $maxLength = 50)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Format display text with template placeholders for selected value
     * Always uses dash separator for consistent selected value display
     */
    private function formatDisplayTextWithPlaceholders($item, $optionLabel)
    {
        // Determine separator for different display contexts
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
                        return '';
                    }
                    return (string)$value; // Ensure string conversion for display
                } else {
                    return ''; // Return empty string for missing fields
                }
            }, $segment);

            // Add this segment if it has content (after placeholder replacement)
            if (!empty($processedSegment)) {
                if ($index > 0 && $foundAny) {
                    // Always use dash for selected value display - consistent format
                    $displayText .= " - ";
                }
                $displayText .= $processedSegment;
                $foundAny = true;
            }
        }

        // If no segments with valid data were processed, return a fallback
        if (!$foundAny) {
            return '[No Data Available]';
        }

        // Remove HTML tags and normalize whitespace for selected value display (single line)
        $displayText = strip_tags($displayText);
        $displayText = preg_replace('/\s+/', ' ', $displayText); // Replace multiple whitespace with single space
        $displayText = trim($displayText);

        // Truncate the final display text if it's too long for selected value display
        return $this->truncateText($displayText, 60);
    }

    /**
     * Format display text with template placeholders for option list
     * Uses line breaks for semicolon, dash for comma
     */
    private function formatOptionListTextWithPlaceholders($item, $optionLabel)
    {
        // Determine separator for line breaks in option list
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
                        return '';
                    }
                    return (string)$value; // Ensure string conversion for display
                } else {
                    return ''; // Return empty string for missing fields
                }
            }, $segment);

            // Add this segment if it has content (after placeholder replacement)
            if (!empty($processedSegment)) {
                if ($index > 0 && $foundAny) {
                    if ($useSemicolon) {
                        // Use HTML line break for semicolon separator in option list
                        $displayText .= "<br>";
                    } else {
                        // Use dash for comma separator in option list
                        $displayText .= " - ";
                    }
                }
                $displayText .= $processedSegment;
                $foundAny = true;
            }
        }

        // If no segments with valid data were processed, return a fallback
        if (!$foundAny) {
            return '[No Data Available]';
        }

        // Keep original length for dropdown options, only truncate selected value
        return $displayText;
    }

    /**
     * Format option label from raw result (legacy method for compatibility)
     */
    private function formatOptionLabel($item)
    {
        return $this->formatEnhancedDisplayText($item);
    }

    /**
     * Format option label from Eloquent model
     */
    private function formatOptionLabelFromModel($model)
    {
        $labelFields = explode(',', $this->optionLabel);
        $labelParts = [];

        foreach ($labelFields as $field) {
            $field = trim($field);
            $value = $this->getNestedProperty($model, $field);
            if ($value) {
                $labelParts[] = $value;
            }
        }

        return implode(' - ', $labelParts);
    }

    /**
     * Open dropdown and focus search input like Select2
     */
    public function openDropdown()
    {
        $this->showDropdown = true;
        $this->textFieldSearch = '';
        $this->searchTerm = '';
        $this->highlightIndex = 0;
        $this->options = [];

        // Dispatch event to focus search input
        $this->dispatch('focus-search-input');
    }

    /**
     * Close dropdown
     */
    public function closeDropdown()
    {
        $this->showDropdown = false;
        $this->textFieldSearch = '';
        $this->searchTerm = '';
        $this->highlightIndex = 0;
        $this->options = [];
    }

    /**
     * Increment the highlight index.
     */
    public function incrementHighlight()
    {
        if (empty($this->options)) return;

        if ($this->highlightIndex === count($this->options) - 1) {
            $this->highlightIndex = 0;
            return;
        }
        $this->highlightIndex++;
    }

    /**
     * Decrement the highlight index.
     */
    public function decrementHighlight()
    {
        if (empty($this->options)) return;

        if ($this->highlightIndex === 0) {
            $this->highlightIndex = count($this->options) - 1;
            return;
        }
        $this->highlightIndex--;
    }

    /**
     * Select an option from list by index (called from blade template)
     */
    public function selectOptionFromList($index)
    {
        $selectedOption = $this->options[$index] ?? null;

        if ($selectedOption) {
            // Handle DropdownSearchController response format
            $this->selectedValue = $selectedOption['id'] ?? $selectedOption['value'] ?? null;
            $rawLabel = $selectedOption['text'] ?? $selectedOption['label'] ?? '';
            // Strip HTML tags and normalize for single-line display
            $this->selectedLabel = $this->normalizeSelectedLabel($rawLabel);
            $this->labelLoaded = true; // Mark as loaded since we just set it

            $this->closeDropdown();

            // Dispatch dropdown-selected event to parent
            $this->dispatch('DropdownSelected', [
                'model' => $this->model,
                'value' => $this->selectedValue,
                'label' => $this->selectedLabel,
                'onChanged' => $this->onChanged
            ]);
        }
    }

    /**
     * Select option with specific value and label (called from Alpine.js)
     */
    public function selectOption($value, $label)
    {
        $this->selectedValue = $value;
        // Strip HTML tags and normalize for single-line display
        $this->selectedLabel = $this->normalizeSelectedLabel($label);
        $this->labelLoaded = true; // Mark as loaded since we just set it
        $this->closeDropdown();

        // Dispatch dropdown-selected event to parent
        $this->dispatch('DropdownSelected', [
            'model' => $this->model,
            'value' => $this->selectedValue,
            'label' => $this->selectedLabel,
            'onChanged' => $this->onChanged
        ]);
    }

    /**
     * Clear selection
     */
    public function clearSelection()
    {
        $this->selectedValue = null;
        $this->selectedLabel = '';
        $this->labelLoaded = true; // Mark as loaded since we just cleared it
        $this->closeDropdown();

        // Dispatch DropdownSelected event with null value (same as selectOption methods)
        $blankValue = isset($this->type) && $this->type === 'int' ? '0' : '';
        $this->dispatch('DropdownSelected', [
            'model' => $this->model,
            'value' => $blankValue,
            'label' => '',
            'onChanged' => $this->onChanged
        ]);
    }

    /**
     * Parse onChanged parameter and dispatch with correct arguments
     */
    private function dispatchOnChanged($selectedValue)
    {
        $onChangedStr = $this->onChanged;

        // Check if it contains parentheses (function with parameters)
        if (strpos($onChangedStr, '(') !== false && strpos($onChangedStr, ')') !== false) {
            // Extract function name: "changeItem({{ $key }}, $event.target.value)" -> "changeItem"
            $functionName = substr($onChangedStr, 0, strpos($onChangedStr, '('));

            // Extract parameters: "changeItem({{ $key }}, $event.target.value)" -> "{{ $key }}, $event.target.value"
            $paramsStr = substr($onChangedStr, strpos($onChangedStr, '(') + 1, strpos($onChangedStr, ')') - strpos($onChangedStr, '(') - 1);

            // Split parameters by comma
            $params = array_map('trim', explode(',', $paramsStr));

            // Process each parameter
            $processedParams = [];
            foreach ($params as $param) {
                if ($param === '$event.target.value') {
                    // Replace $event.target.value with actual selected value
                    $processedParams[] = $selectedValue;
                } else {
                    // Keep other parameters as they are (like {{ $key }} which will be processed by Blade)
                    $processedParams[] = $param;
                }
            }

            // Dispatch with function name and parameters
            $this->dispatch($functionName, ...$processedParams);
        } else {
            // Simple function name without parameters
            $this->dispatch($onChangedStr, $selectedValue);
        }
    }

    public function render()
    {
        // Lazy load the selected label on first render only if there's a selected value
        if (!$this->labelLoaded && !empty($this->selectedValue)) {
            $this->loadSelectedLabel();
        }
        
        return view('livewire.component.ui-dropdown-select');
    }
}
