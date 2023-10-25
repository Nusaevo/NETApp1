<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ConfigAppl extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
    protected $table = 'config_appls'; // Update the table name

    protected $fillable = [
        'code',
        'name',
        'version',
        'descr'
    ];
}
