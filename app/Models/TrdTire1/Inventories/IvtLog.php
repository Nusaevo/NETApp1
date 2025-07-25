<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class IvtLog extends BaseModel
{
    protected $table = 'ivt_logs';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'trdtl_id',
        'ivt_id',
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'reff_id',
        'tr_date',
        'qty',
        'price_beforetax',
        'tr_desc',
        'price_cogs',
        'qty_running',
        'process_flag',
        'tr_qty'
    ];
    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function delivDtl()
    {
        return $this->belongsTo(DelivDtl::class, 'trdtl_id');
    }

    public static function removeIvtLogIfExists($trhdr_id, $tr_type, $tr_seq)
    {
        $log = IvtLog::where([
            'trhdr_id' => $trhdr_id,
            'tr_type' => $tr_type,
            'tr_seq' => $tr_seq,
        ])->first();

        if ($log) {
            $log->delete();
        }
    }
}
