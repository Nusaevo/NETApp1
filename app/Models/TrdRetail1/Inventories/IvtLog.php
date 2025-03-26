<?php

namespace App\Models\TrdRetail1\Inventories;

use App\Models\Base\BaseModel;

class IvtLog extends BaseModel
{
    public $timestamps = false;
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
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
        'price',
        'amt',
        'tr_desc',
        'price_cogs',
        'amt_cogs',
        'qty_running',
        'amt_running',
        'process_flag',
        'status_code'
    ];

    /**
     * Buat log IvtLog dengan parameter standar, supaya tidak duplikasi kode.
     */
    public static function createIvtLog(string $type, $delivDtl, int $ivtBalId, int $matlId, string $matlUom, float $qty, string $desc = '')
    {
        return IvtLog::create([
            'trhdr_id' => $delivDtl->trhdr_id,
            'tr_type' => $type,
            'tr_seq' => $delivDtl->tr_seq,
            'tr_id' => $delivDtl->tr_id,
            'trdtl_id' => $delivDtl->id,
            'ivt_id' => $ivtBalId,
            'matl_id' => $matlId,
            'matl_code' => $delivDtl->matl_code,
            'matl_uom' => $matlUom,
            'wh_id' => $delivDtl->wh_id,
            'wh_code' => $delivDtl->wh_code,
            'batch_code' => $delivDtl->batch_code ?? '',
            'tr_date' => date('Y-m-d'),
            'qty' => $qty,
            'price' => 0,
            'amt' => 0,
            'tr_desc' => $desc,
        ]);
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
