<?php

namespace App\Models\TrdRetail2\Inventories;
use Illuminate\Database\Eloquent\Model;
use App\Models\TrdRetail2\Base\TrdRetail2BaseModel;
use App\Enums\Constant;

class IvtBal extends TrdRetail2BaseModel
{
    protected $table = 'ivt_bals';
    public $timestamps = false;


    public static function boot()
    {
        parent::boot();
        static::saving(function ($IvtBal) {
            $qty_oh = currencyToNumeric($IvtBal->qty_oh);
            $IvtBal->qty_oh = $qty_oh;
        });
    }

    protected $fillable = [
        'matl_id',
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

}
