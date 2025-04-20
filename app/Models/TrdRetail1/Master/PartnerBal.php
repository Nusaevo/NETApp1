<?php

namespace App\Models\TrdRetail1\Master;
use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseModel;
use App\Enums\Constant;

class PartnerBal extends BaseModel
{
    protected $table = 'partner_bals';
    public $timestamps = false;

    protected $fillable = [
        'partner_id',
    ];

    protected $primaryKey = 'partner_id'; // Ensure the primary key is defined

    public static function boot()
    {
        parent::boot();
        // static::created(function ($ivtBal) {
        //     $ivtBalUnit = IvtBalUnit::firstOrNew([
        //         'ivt_id'    => $ivtBal->id,
        //         'matl_id'   => $ivtBal->matl_id,
        //         'wh_id'     => $ivtBal->wh_id,
        //         'batch_code'=> $ivtBal->batch_code,
        //     ]);
        //     if (!$ivtBalUnit->exists) {
        //         $ivtBalUnit->qty_oh = 0;
        //     }
        //     $ivtBalUnit->qty_oh = $ivtBal->qty_oh;
        //     $ivtBalUnit->save();
        // });
    }

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
}
