<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Master\MatlUom;
use App\Models\TrdTire1\Master\PartnerBal;
use App\Models\TrdTire1\Master\PartnerLog;

class DelivHdr extends BaseModel
{
    use SoftDeletes;

    protected $table = 'deliv_hdrs';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'tr_date',
        'tr_type',
        'tr_code',
        'reff_code',
        'reff_date',
        'partner_id',
        'partner_code',
        'deliv_by',
        'amt_shipcost',
        'note',
        'billhdr_id',
    ];
    protected $casts = [
        'tr_code' => 'string',
        'amt_shipcost' => 'float',
    ];

    protected $appends = ['total_qty'];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function DelivPacking()
    {
        return $this->hasMany(DelivPacking::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_code', 'tr_code')->where('tr_type', 'PO');
    }

    public function OrderHdr()
    {
        return $this->belongsTo(OrderHdr::class, 'tr_code', 'tr_code');
    }
    #endregion

    #region Metode Utama
    protected static function boot()
    {
        parent::boot();

    }

    public function getTotalQtyAttribute()
    {
        return (int) $this->DelivPacking()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->DelivPacking()->sum('qty');
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->DelivPacking()->pluck('matl_descr')->toArray();
        return implode(', ', $matlCodes);
    }
    #endregion
    public static function updateBillHdrId(int $delivId, int $billHdrId)
    {
        $delivHdr = self::find($delivId);
        if ($delivHdr) {
            $delivHdr->billhdr_id = $billHdrId;
            $delivHdr->save();
        }
    }

}
