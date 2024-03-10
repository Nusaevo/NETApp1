<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Bases\Attachment;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Schema;


class BaseModel extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

    protected $fillable = [];
    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

    public function isNew()
    {
        $isNew = empty($this->id);
        return  $isNew;
    }

    public function setStatus($value)
    {
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'status_code')) {
            $this->attributes['status_code'] = $value;
        }
    }

    public function Attachment()
    {
        return $this->hasMany(Attachment::class, 'attached_objectid')
                    ->where('attached_objecttype', class_basename($this));
    }
}
