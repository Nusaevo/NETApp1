<?php

namespace App\Traits;

trait ModelTrait
{
    public function scopeOrderByName($query, $order = 'asc')
    {
        return $query->orderBy('name', $order);
    }
}
