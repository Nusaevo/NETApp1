<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_menus'; // Update the table name

    protected $fillable = [
        'appl_code',
        'menu_code',
        'menu_caption',
        'status_code',
        'is_active'
    ];
}
