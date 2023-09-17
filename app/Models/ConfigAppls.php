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
        'appl_name',
        'status_code',
        'is_active',
        'created_user_id',
        'updated_user_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
