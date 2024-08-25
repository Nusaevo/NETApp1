<?php

namespace App\Models\TrdJewel1\Inventories;
use Illuminate\Database\Eloquent\Model;
use App\Models\TrdJewel1\Base\TrdJewel1BaseModel;
use App\Enums\Constant;

class IvtBal extends TrdJewel1BaseModel
{
    protected $table = 'ivt_bals';
    public $timestamps = false;
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::Trdjewel1_ConnectionString();
    }

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
