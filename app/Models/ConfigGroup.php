<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_groups'; // Update the table name

    protected $fillable = [
        'appl_code',
        'group_code',
        'user_code',
        'note1',
        'status_code',
        'is_active'
    ];

    public function scopeGetConfigGroup()
    {
        return $this->orderBy('note1', 'asc')->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_code', 'code');
    }
}
