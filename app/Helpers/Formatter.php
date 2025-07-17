<?php

if (!function_exists('rupiah')) {
    function rupiah($price = 0, $use_name = true)
    {
        if (is_numeric($price)) {
            $price = numberFormat($price, 0, ',', '.');
        }

        if ($use_name) {
            return 'IDR ' . $price;
        } else {
            return $price;
        }
    }
}


if (!function_exists('dollar')) {
    function dollar($price = 0, $use_name = true)
    {
        if (is_numeric($price)) {
            $price = numberFormat($price, 2, ',', '.');
        }

        if ($use_name) {
            return 'USD ' . $price;
        } else {
            return $price;
        }
    }
}

if (!function_exists('convertFormattedNumber')) {
    /**
     * Convert a formatted number (e.g., "30,000" or "30.000,50") to a numeric value.
     *
     * @param string $formattedNumber The number in string format with commas/dots.
     * @return float|int The numeric representation of the formatted number.
     */
    function convertFormattedNumber($formattedNumber)
    {
        if (is_null($formattedNumber) || $formattedNumber === '') {
            return 0; // Default to 0 if input is empty or null
        }

        // Remove thousand separators (comma or dot based on locale)
        $numberWithoutSeparators = str_replace(['.', ','], '', $formattedNumber);

        // Replace the final dot with a decimal point for float conversion
        if (strpos($formattedNumber, ',') !== false && strpos($formattedNumber, '.') !== false) {
            $numberWithoutSeparators = str_replace(',', '.', $formattedNumber);
        }

        // Return the numeric value
        return is_numeric($numberWithoutSeparators) ? (float) $numberWithoutSeparators : 0;
    }
}


if (!function_exists('qty')) {
    function qty($qty = 0, $behind_comma = 0)
    {
        return number_format($qty, $behind_comma, ',', '.');
    }
}

if (!function_exists('toNumberFormatter')) {
    function toNumberFormatter($formattedNumber)
    {
        if ($formattedNumber === null || $formattedNumber === '') {
            return 0;
        }

        $numericValue = str_replace('.', '', $formattedNumber);
        $numericValue = str_replace(',', '.', $numericValue);
        return (float) $numericValue;
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($number, $decimals = 0, $decPoint = ',', $thousandsSep = '.')
    {
        // Check if the number is null
        if ($number === null) {
            return '0'; // or you can return an empty string or any default value you prefer
        }

        $formattedNumber = number_format($number, $decimals, $decPoint, $thousandsSep);

        // Find the position of the decimal point
        $decimalPosition = strpos($formattedNumber, $decPoint);

        if ($decimalPosition !== false) {
            // Trim trailing zeros after the decimal point
            $formattedNumber = rtrim($formattedNumber, '0');
            // If the number ends with the decimal point, remove it as well
            if (substr($formattedNumber, -1) === $decPoint) {
                $formattedNumber = substr($formattedNumber, 0, -1);
            }
        }

        return $formattedNumber;
    }
}

if (!function_exists('currencyToNumeric')) {
    function currencyToNumeric($formattedCurrency)
    {
        // Remove currency symbol and commas
        $numericValue = (float) str_replace(['$', ','], '', $formattedCurrency);

        return $numericValue;
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat($date, $format = 'd-m-Y')
    {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    }
}


if (!function_exists('int_qty')) {
    function int_qty($qty = 0)
    {
        return intval($qty);
    }
}

if (!function_exists('isFormattedNumeric')) {
    function isFormattedNumeric($value) {
        // Strip out commas and dots which could be used as thousand separators
        $testValue = str_replace([',', '.'], '', $value);
        // Check if the remaining string is numeric
        return is_numeric($testValue);
    }
}

if (!function_exists('isValidNumeric')) {
    /**
     * Validate if a value is numeric after normalizing separators (e.g., comma to dot).
     *
     * @param mixed $value The value to validate.
     * @return bool True if the value is numeric, false otherwise.
     */
    function isValidNumeric($value)
    {
        // Replace comma with dot (e.g., "12,34" becomes "12.34")
        $normalizedValue = str_replace(',', '.', $value);

        // Check if the normalized value is numeric
        return is_numeric($normalizedValue);
    }
}



if (!function_exists('isDateAttribute')) {
    if (!function_exists('isDateAttribute')) {
        function isDateAttribute($attribute) {
            if (is_array($attribute)) {
                // Handle the case where the input is an array
                return false; // Or you can loop through the array to check each element
            }

            $dateRegex = '/\d{2}-\d{2}-\d{4}/'; // Checks for date format dd-mm-yyyy
            return preg_match($dateRegex, $attribute);
        }
    }
}

if (!function_exists('sanitizeDate')) {
    function sanitizeDate($date) {
        $parts = explode('-', $date);
        if (count($parts) === 3) {
            // Reformat from dd-mm-yyyy to yyyy-mm-dd
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        return $date;  // Return the original if format does not match
    }
}

if (!function_exists('getSubdomain')) {
    function getSubdomain($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $hostParts = explode('.', $host);

        // Consider 'example.com' or 'www.example.com' to have no subdomain
        if (count($hostParts) > 2) {
            return $hostParts[0];
        }

        return null;
    }
}

// FUNGSI TERBILANG OLEH : MALASNGODING.COM
// WEBSITE : WWW.MALASNGODING.COM
// AUTHOR : https://www.malasngoding.com/author/admin

function penyebut($nilai)
{
    $nilai = abs($nilai);
    $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = penyebut($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = penyebut($nilai / 10) . " Puluh" . penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebut($nilai / 100) . " Ratus" . penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebut($nilai / 1000) . " Ribu" . penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebut($nilai / 1000000) . " Juta" . penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = penyebut($nilai / 1000000000) . " Milyar" . penyebut(fmod($nilai, 1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = penyebut($nilai / 1000000000000) . " Trilyun" . penyebut(fmod($nilai, 1000000000000));
    }
    return $temp;
}

function terbilang($nilai)
{
    if ($nilai < 0) {
        $hasil = "Minus " . trim(penyebut($nilai));
    } else {
        $hasil = trim(penyebut($nilai));
    }
    return $hasil;
}

if (!function_exists('sanitizeModelAttributes')) {
    /**
     * Sanitize model attributes before saving to DB.
     *
     * - Trim strings
     * - If value is an array:
     *   - Use '' if column type is string
     *   - Use 0 if column type is numeric (int, decimal, float)
     *   - Use null if column is nullable
     *
     * @param array $attributes Reference to model attributes array
     * @param array $columnTypes Optional array mapping column names to types ['column' => 'string|int|float|decimal']
     * @param array $nullableColumns Optional array of column names that allow null values
     */
    function sanitizeModelAttributes(&$attributes, $columnTypes = [], $nullableColumns = [])
    {
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                // Handle arrays based on column type
                if (in_array($key, $nullableColumns)) {
                    $attributes[$key] = null;
                } elseif (isset($columnTypes[$key])) {
                    $columnType = strtolower($columnTypes[$key]);
                    if (in_array($columnType, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double', 'numeric'])) {
                        $attributes[$key] = 0;
                    } elseif (in_array($columnType, ['json', 'jsonb'])) {
                        $attributes[$key] = '{}'; // Empty JSON object
                    } else {
                        $attributes[$key] = '';
                    }
                } else {
                    // Default: assume string type
                    $attributes[$key] = '';
                }
            } elseif (is_string($value)) {
                // Handle JSON/JSONB fields with empty strings
                if (isset($columnTypes[$key])) {
                    $columnType = strtolower($columnTypes[$key]);
                    if (in_array($columnType, ['json', 'jsonb']) && trim($value) === '') {

                        $attributes[$key] = '{}'; // Convert empty string to empty JSON object for JSON fields
                    } else {
                        // Trim string values for non-JSON fields
                        $attributes[$key] = trim($value);
                    }
                } else {
                    // Trim string values when column type is unknown
                    $attributes[$key] = trim($value);
                }
            } elseif (is_object($value)) {
                // Convert objects to string or handle appropriately
                $attributes[$key] = (string) $value;
            }
        }
    }
}

if (!function_exists('sanitizeModelAttributesAuto')) {
    /**
     * Automatically sanitize model attributes using model schema information.
     *
     * @param \Illuminate\Database\Eloquent\Model $model The Eloquent model instance
     * @param array $attributes Reference to model attributes array
     */
    function sanitizeModelAttributesAuto($model, &$attributes)
    {
        try {
            $table = $model->getTable();
            $connection = $model->getConnection();
            $columnTypes = [];
            $nullableColumns = [];
            $driver = $connection->getDriverName();

            // Use information_schema for cross-database compatibility
            if ($driver === 'pgsql') {
                // PostgreSQL
                $columns = $connection->select("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns
                    WHERE table_name = ? AND table_schema = 'public'
                    ORDER BY ordinal_position
                ", [$table]);
            } elseif ($driver === 'mysql') {
                // MySQL
                $databaseName = $connection->getDatabaseName();
                $columns = $connection->select("
                    SELECT COLUMN_NAME as column_name, DATA_TYPE as data_type,
                           IS_NULLABLE as is_nullable, COLUMN_DEFAULT as column_default
                    FROM information_schema.COLUMNS
                    WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?
                    ORDER BY ORDINAL_POSITION
                ", [$table, $databaseName]);
            } else {
                // Generic SQL standard fallback
                $columns = $connection->select("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns
                    WHERE table_name = ?
                    ORDER BY ordinal_position
                ", [$table]);
            }

            foreach ($columns as $column) {
                $columnName = $column->column_name;
                $columnType = strtolower($column->data_type);

                $columnTypes[$columnName] = $columnType;

                // Check if column is nullable
                if (isset($column->is_nullable) && strtolower($column->is_nullable) === 'yes') {
                    $nullableColumns[] = $columnName;
                }
            }
            // Call the main sanitize function with detected types
            sanitizeModelAttributes($attributes, $columnTypes, $nullableColumns);

        } catch (\Exception $e) {
            // Fallback to basic sanitization if schema detection fails
            \Log::warning('Schema detection failed for table: ' . ($model->getTable() ?? 'unknown'), [
                'error' => $e->getMessage(),
                'model' => get_class($model),
                'driver' => $connection->getDriverName() ?? 'unknown'
            ]);
            sanitizeModelAttributes($attributes);
        }
    }
}
