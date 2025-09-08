<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Transaction\DelivPacking;

class IvtLog extends BaseModel
{
    protected $table = 'ivt_logs';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_date',
        'trdtl_id',
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'tr_seq2',
        'ivt_id',
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'tr_qty',
        'qty',
        'price_beforetax',
        'price_cogs',
        'qty_running',
        'tr_desc',
        'reff_id',
        'process_flag'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    /**
     * Get next tr_seq2 value for given tr_type, tr_code, and tr_seq
     */
    public static function getNextTrSeq2(string $trType, string $trCode, int $trSeq): int
    {
        $lastSeq2 = self::where('tr_type', $trType)
            ->where('tr_code', $trCode)
            ->where('tr_seq', $trSeq)
            ->max('tr_seq2');
        return ($lastSeq2 ?? 0) + 1;
    }

}
