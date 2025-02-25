<?php

namespace App\Models\TrdTire1\Inventories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseModel;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\Material;

class IvtBal extends BaseModel
{
    protected $table = 'ivt_bals';
    public $timestamps = false;


    public static function boot()
    {
        parent::boot();
        static::created(function ($ivtBal) {
            $ivtBalUnit = IvtBalUnit::firstOrNew([
                'ivt_id'    => $ivtBal->id,
                'matl_id'   => $ivtBal->matl_id,
                'wh_id'     => $ivtBal->wh_id,
                'batch_code' => $ivtBal->batch_code,
            ]);
            if (!$ivtBalUnit->exists) {
                $ivtBalUnit->qty_oh = 0;
            }
            $ivtBalUnit->qty_oh = $ivtBal->qty_oh;
            $ivtBalUnit->save();
        });
    }

    protected $fillable = [
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'qty_oh',
        'wh_id',
        'wh_code',
    ];


    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
    public function material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
}
