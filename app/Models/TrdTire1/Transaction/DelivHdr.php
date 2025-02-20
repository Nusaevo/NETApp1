<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Master\MatlUom;

class DelivHdr extends BaseModel
{
    use SoftDeletes;

    protected $table = 'deliv_hdrs'; // Update the table name

    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'tr_code',
        'tr_date',
        'reff_date',
        'partner_id',
        'partner_code',
        'wh_code',
        'wh_id',
        'tr_type',
        'note',
    ];
    protected $casts = [
        'tr_code' => 'string',
    ];

    protected $appends = ['total_qty', 'total_amt'];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function DelivDtl()
    {
        return $this->hasMany(DelivDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
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
        // create billingHdr when deliveryHdr is created
        // tr type if deliveryHdr is 'SD' => 'ARB'
        // tr type if deliveryHdr is 'PD' => 'APB'
        static::created(function ($delivHdr) {
            $billingHdr = new BillingHdr();
            $billingHdr->tr_code = $delivHdr->tr_code;
            $billingHdr->tr_date = $delivHdr->tr_date;
            $billingHdr->partner_id = $delivHdr->partner_id;
            $billingHdr->partner_code = $delivHdr->partner_code;
            // $billingHdr->payment_term_id = $delivHdr->OrderHdr->payment_term_id;
            // $billingHdr->payment_term = $delivHdr->OrderHdr->payment_term;
            // $billingHdr->payment_due_days = $delivHdr->OrderHdr->payment_due_days;
            $billingHdr->tr_type = $delivHdr->tr_type == 'SD' ? 'ARB' : 'APB';
            $billingHdr->save();
        });

        // Hook untuk menghapus relasi saat header dihapus
        static::deleting(function ($orderHdr) {
            $orderHdr->deleteDeliveryAndBilling();
            // $orderHdr->deleteOrderDetails();
            BillingHdr::where('tr_code', $orderHdr->tr_code)
                ->where('tr_type', $orderHdr->tr_type == 'SD' ? 'ARB' : 'APB')
                ->forceDelete();
        });
    }

    public function deleteDeliveryAndBilling()
    {
        // Delete related delivery details
        $this->DelivDtl()->delete();
    }

    public function savePurchaseHeader($appCode, $trType, $inputs, $configCode)
    {
        DB::transaction(function () use ($appCode, $trType, $inputs, $configCode) {
            $this->fill($inputs);
            $this->tr_type = $trType;

            // Set status default
            if ($this->isNew()) {
                $this->status_code = Status::OPEN;
            }

            // Ensure trhdr_id is filled
            if (isset($inputs['trhdr_id'])) {
                $this->id = $inputs['trhdr_id'];
            }

            // Save the delivery date
            $this->tr_date = $inputs['tr_date'];

            // Simpan data
            $this->save();

            // Pastikan model di-refresh dari database
            $this->refresh();
        });
    }
    public function isOrderCompleted()
    {
        // Logika untuk mengecek apakah order selesai
        return $this->status == 'completed'; // Misalnya, status 'completed' menandakan order selesai
    }
    public function getTotalQtyAttribute()
    {
        return (int) $this->OrderDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->OrderDtl()->sum('amt');
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->OrderDtl()->pluck('matl_code')->toArray();
        return implode(', ', $matlCodes);
    }
    #endregion
}
