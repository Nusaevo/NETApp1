<?php

if (! function_exists('rupiah')) {
    function rupiah($price = 0, $use_name = true)
    {
        $price = number_format($price, 0, ',', '.');
        if ($use_name)  return 'Rp ' . $price . ',-';
        else return $price;
    }
}

if (! function_exists('qty')) {
    function qty($qty = 0, $behind_comma = 0)
    {
        return number_format($qty, $behind_comma, ',', '.');
    }
}

