<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigRight extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_rights';

    protected $fillable = [
        'appl_code',
        'group_code',
        'menu_code',
        'menu_seq',
        'trustee',
        'created_user_id',
        'updated_user_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
