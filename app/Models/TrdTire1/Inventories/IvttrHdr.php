<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class IvttrHdr extends BaseModel
{
    protected $table = 'ivttr_hdrs';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_type',
        'tr_date'
    ];
    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function delivDtl()
    {
        return $this->belongsTo(DelivDtl::class, 'trdtl_id');
    }

    public function ivttrDtls()
    {
        return $this->hasMany(IvttrDtl::class, 'trhdr_id');
    }

    public function saveOrderHeader($appCode, $trType, $inputs, $lastIdKey)
    {
        // Implement the logic to save the order header
        // Example:
        $this->fill($inputs);

        // Generate tr_id with incremented value only if it's a new record
        if (!$this->exists) {
            $lastRecord = self::orderBy('tr_id', 'desc')->first();
            $lastId = $lastRecord ? intval($lastRecord->tr_id) : 0;
            $this->tr_id = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
        }

        $this->tr_type = $inputs['tr_type'];
        $this->save();

        IvttrDtl::updateOrCreate(
            ['trhdr_id' => $this->id],
            [
                // 'tr_type' => $this->trType,
                // 'tr_id' => $this->tr_id,
                'wh_code' => $inputs['wh_code'],
                'tr_descr' => $inputs['tr_descr']
            ]
        );
    }

    public function isOrderCompleted()
    {
        // Contoh logika untuk mengecek apakah order telah selesai
        return $this->status == 'completed';
    }
}
