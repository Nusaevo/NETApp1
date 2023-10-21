<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigAudit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_audits'; // Update the table name

    protected $fillable = [
        'appl_code',
        'key_code',
        'log_time',
        'action_code',
        'audit_trail',
    ];

    // If you want to specify default values for some columns, you can use the $attributes property:
    protected $attributes = [
        'appl_code' => '',
        'key_code' => '',
        'action_code' => '',
        'audit_trail' => '',
    ];
}
