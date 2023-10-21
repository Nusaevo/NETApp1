<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigVar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_vars';

    protected $fillable = [
        'appl_code',
        'var_group',
        'var_code',
        'descr',
        'seq',
        'type_code',
        'default_value'
    ];
}
