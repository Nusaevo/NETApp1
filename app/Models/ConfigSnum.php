<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigSnum extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_snums';
    protected $connection = 'config';
    protected $fillable = [
        'app_code',
        'snum_group',
        'last_cnt',
        'wrap_low',
        'wrap_high',
        'step_cnt',
        'remark',
        'status_code',
        'is_active'
    ];
}
