<?php

namespace App\Models\TrdRetail1\Inventories;

use App\Models\TrdRetail1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

use App\Models\SysConfig1\ConfigSnum;
class IvttrHdr extends BaseModel
{
    protected $table = 'ivttr_hdrs';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_date',
        'remark',
    ];
    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function DelivDtl()
    {
        return $this->belongsTo(DelivDtl::class, 'trdtl_id');
    }

    public function IvttrDtl()
    {
        return $this->hasMany(IvttrDtl::class, 'trhdr_id');
    }

     public function saveOrderHeader($appCode, $trType, $inputs, $lastIdKey)
    {
        $this->fill($inputs);

        // Generate tr_code dengan ConfigSnum hanya jika record baru
        if (!$this->exists) {
            $this->tr_id = self::generateInventoryTransactionId();
        }

        $this->save();
    }

    public static function generateInventoryTransactionId()
    {
        $configSnum = ConfigSnum::where('code', 'IVT_LASTID')->first();
        $stepCnt = $configSnum->step_cnt;
        $proposedTrId = $configSnum->last_cnt + $stepCnt;
        if ($proposedTrId > $configSnum->wrap_high) {
            $proposedTrId = $configSnum->wrap_low;
        }
        $proposedTrId = max($proposedTrId, $configSnum->wrap_low);
        $configSnum->update(['last_cnt' => $proposedTrId]);
        return $proposedTrId;
    }

    public function getTrIdAttribute($value)
    {
        return sprintf('%03d', $value);
    }

    public function isOrderCompleted()
    {
        // Contoh logika untuk mengecek apakah order telah selesai
        return $this->status == 'completed';
    }
}
