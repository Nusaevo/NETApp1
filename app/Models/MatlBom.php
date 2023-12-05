<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class MatlBom extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;

    protected $table = 'matl_boms';

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::creating(function ($model) {
            $maxId = SequenceUtility::getCurrentSequenceValue($model);
            $model->code = 'UOM' ."_". ($maxId + 1);
        });
    }

    protected $fillable = [
        'matl_id',
        'matl_code',
        'base_matl_id',
        'base_matl_code',
        'seq',
        'jwl_sides_carat',
        'jwl_sides_cnt',
        'jwl_sides_matl',
        'jwl_sides_parcel',
        'jwl_sides_price',
        'jwl_sides_amt'
    ];


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
}
