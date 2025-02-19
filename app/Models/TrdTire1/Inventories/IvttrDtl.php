<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class IvttrDtl extends BaseModel
{
    protected $table = 'ivttr_dtls';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_code',
        'batch_code',
        'qty',
        'tr_descr'
    ];
    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function delivDtl()
    {
        return $this->belongsTo(DelivDtl::class, 'trdtl_id');
    }

    public function ivttrHdr()
    {
        return $this->belongsTo(IvttrHdr::class, 'trhdr_id');
    }
    // Pada model IvttrDtl
    public function ivtBal()
    {
        return $this->hasOne(IvtBal::class, 'wh_code', 'wh_code');
    }


    // SaveItem
    public function saveItem($data)
    {
        // $this->fill($data);
        $this->save();
    }

    public function isOrderCompleted()
    {
        // Contoh logika untuk mengecek apakah order telah selesai
        return $this->status == 'completed';
    }
}
