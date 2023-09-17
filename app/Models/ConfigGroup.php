<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_groups';

    protected $fillable = [
        'appl_code',
        'group_code',
        'user_code',
        'note1',
        'status_code',
        'is_active',
        'created_user_id',
        'updated_user_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'last_user',
        'last_user_id',
    ];
}
