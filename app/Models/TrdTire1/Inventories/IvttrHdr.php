<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\SysConfig1\ConfigSnum;
use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class IvttrHdr extends BaseModel
{
    protected $table = 'ivttr_hdrs';
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tr_code)) {
                $model->tr_code = self::generateInventoryTransactionId();
            }
        });
    }

    protected $fillable = [
        'tr_type',
        'tr_date',
        'tr_code',
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
            $this->tr_code = self::generateInventoryTransactionId();
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

    public static function generateSimpleTrCode()
    {
        $last = self::orderBy('tr_code', 'desc')->whereNotNull('tr_code')->where('tr_code', '!=', '')->first();
        $lastCode = $last ? intval($last->tr_code) : 0;
        return strval($lastCode + 1);
    }

    public function isOrderCompleted()
    {
        // Contoh logika untuk mengecek apakah order telah selesai
        return $this->status == 'completed';
    }
}
