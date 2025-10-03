<?php

namespace App\Models\TrdTire2\Master;
use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseModel;
use App\Enums\Constant;

class PartnerBal extends BaseModel
{
    protected $table = 'partner_bals';
    public $timestamps = false;


    protected $fillable = [
        'partner_id',
        'partner_code',
        'reff_id',
        'reff_type',
        'reff_code',
        'amt_bal',
        'amt_adv',
        'descr',
    ];

    protected $casts = [
        'amt_bal' => 'float',
        'amt_adv' => 'float',
        'partner_id' => 'integer',
        'reff_id' => 'integer',
    ];

    protected $primaryKey = 'id';

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

    /**
     * Get the partner that owns the balance
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
