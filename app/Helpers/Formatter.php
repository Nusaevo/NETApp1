<?php

if (!function_exists('rupiah')) {
    function rupiah($price = 0, $use_name = true)
    {
        $price = numberFormat($price, 0, ',', '.');
        if ($use_name)  return 'IDR ' . $price;
        else return $price;
    }
}

if (!function_exists('dollar')) {
    function dollar($price = 0, $use_name = true)
    {
        $price = numberFormat($price, 2, ',', '.');
        if ($use_name)  return 'USD ' . $price;
        else return $price;
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
        $numericValue = str_replace('.', '', $formattedNumber);
        $numericValue = str_replace(',', '.', $numericValue);
        return $numericValue;
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($number, $decimals = 0, $decPoint = ',', $thousandsSep = '.')
    {
        $formattedNumber = number_format($number, $decimals, $decPoint, $thousandsSep);
        // Remove trailing zeros after the decimal point
        $formattedNumber = rtrim($formattedNumber, '0');
        // If the number ends with the decimal point, remove it as well
        $formattedNumber = rtrim($formattedNumber, $decPoint);
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

if (!function_exists('isDateAttribute')) {
    function isDateAttribute($attribute) {
        $dateRegex = '/\d{2}-\d{2}-\d{4}/'; // Checks for date format dd-mm-yyyy
        return preg_match($dateRegex, $attribute);
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
