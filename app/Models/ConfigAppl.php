<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigAppls extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_appls'; // Update the table name

    protected $fillable = [
        'appl_code',
        'appl_ver',
        'appl_name',
        'appl_desc',
        'status_code'
    ];

    // If you want to specify default values for some columns, you can use the $attributes property:
    protected $attributes = [
        'appl_code' => '',
        'appl_ver' => '',
        'appl_name' => '',
        'appl_desc' => '',
        'status_code' => 'A', // Set the default status code
    ];
}
