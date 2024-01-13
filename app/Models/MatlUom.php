<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\SequenceUtility;
class MatlUom extends BaseModel
{
    protected $table = 'matl_uoms';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $maxId = SequenceUtility::getCurrentSequenceValue($model);
            $model->code = 'UOM' ."_". ($maxId + 1);
        });
    }

    protected $fillable = [
        'code',
        'name',
        'matl_id',
        'matl_code',
        'reff_uom',
        'reff_factor',
        'base_factor',
        'price_grp',
        'barcode',
        'qty_oh',
        'qty_fgr',
        'qty_fgi'
    ];

    public function scopeFindMaterialId($query, $matl_id)
    {
        return $query->where('matl_id', $matl_id);
    }
}
