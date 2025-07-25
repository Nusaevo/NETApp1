<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Transaction\DelivHdr;
use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Models\TrdTire1\Transaction\BillingHdr;
use App\Models\TrdTire1\Transaction\BillingDtl;
use App\Enums\TrdTire1\Status;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\TrdTire1\Master\PartnerDetail;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHdr extends BaseModel
{
    use SoftDeletes;


    protected $fillable = [
        'tr_type',
        'tr_code',
        'sales_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'ship_to_name',
        'ship_to_addr',
        'npwp_code',
        'npwp_name',
        'npwp_addr',
        'sales_id',
        'sales_code',
        'deliv_by',
        'payment_term_id',
        'payment_term',
        'payment_due_days',
        'curr_id',
        'curr_code',
        'curr_rate',
        'tax_id',
        'tax_code',
        'tax_pct',
        'tax_doc_flag',
        'tax_doc_num',
        'tax_process_date',
        'amt',
        'amt_beforetax',
        'amt_tax',
        'amt_adjustdtl',
        'amt_shipcost',
        'print_setting',       
        'print_remarks',       
        'print_date',
        'note',
    ];

    protected $casts = [
        'tax_doc_flag' => 'boolean',
        'print_remarks' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Hook untuk menghapus relasi saat header dihapus
        // static::deleting(function ($orderHdr) {
        //     $orderHdr->deleteDeliveryAndBilling();
        //     $orderHdr->deleteOrderDetails();
        // });


    }

    #region Relasi
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }
    public function partnerDetail()
    {
        // Misalnya, jika relasi adalah one-to-one dari tabel partner_details yang menggunakan partner_id sebagai foreign key:
        return $this->hasOne(PartnerDetail::class, 'partner_id', 'partner_id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_code', 'tr_code')->orderBy('tr_seq');
    }


    public function DelivHdr()
    {
        return $this->hasOne(DelivHdr::class, 'tr_code', 'tr_code')
            ->where('tr_type', $this->getDeliveryTrType());
    }

    public function BillingHdr()
    {
        return $this->hasOne(BillingHdr::class, 'tr_code', 'tr_code')
            ->where('tr_type', $this->getBillingTrType());
    }
    #endregion

    public function saveOrderHeader($appCode, $trType, $inputs, $configCode)
    {
        // Jangan isi due_date dari $inputs
        $inputsFiltered = $inputs;
        unset($inputsFiltered['due_date']);

        $this->fill($inputsFiltered);
        $this->tr_type = $trType; // Pastikan tr_type terisi

        // Set partner_code berdasarkan partner_id
        if (!empty($inputs['partner_id'])) {
            $partner = Partner::find($inputs['partner_id']);
            $this->partner_code = $partner->code;
        }

        // Set tax_pct berdasarkan tax_flag
        if (!empty($inputs['tax_flag'])) {
            $configData = ConfigConst::select('num1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $inputs['tax_flag'])
                ->first();
            $this->tax_pct = $configData->num1 ?? 0;
        }

        // Set default status jika baru
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
            $this->print_date = null; // Set default value for print_date
        }

        // Simpan header
        $this->save();
    }

    public function saveOrderDetails($inputDetails, $trType, $inputs, $createBillingDelivery = false)
    {
        foreach ($inputDetails as $detail) {
            $orderDtl = $this->createOrUpdateOrderDetail($detail, $trType);

            if ($createBillingDelivery) {
                $this->createDeliveryDetail($orderDtl, $inputs);
                $this->createBillingDetail($orderDtl);
            }
        }
    }
    #endregion

    #region Logika Billing dan Delivery
    public function createOrUpdateBilling()
    {
        $billingHdr = BillingHdr::firstOrNew([
            'tr_code' => $this->tr_code,
            'tr_type' => $this->getBillingTrType(),
        ]);

        $billingHdr->fill([
            'tr_date'         => $this->tr_date,
            'partner_id'      => $this->partner_id,
            'partner_code'    => $this->partner_code,
            'payment_term_id' => $this->payment_term_id,
        ]);

        if ($billingHdr->isNew()) {
            $billingHdr->status_code = Status::OPEN;
        }

        $billingHdr->save();
    }

    public function isOrderCompleted()
    {
        return $this->status_code == Status::COMPLETED;
    }

    public function isOrderEnableToDelete(): bool
    {
        // Cek apakah ada order detail yang sudah memiliki qty_reff > 0 (sudah diproses)
        $orderDtlWithQtyReff = OrderDtl::where('tr_code', $this->tr_code)
            ->where('qty_reff', '>', 0)
            ->count();
        if ($orderDtlWithQtyReff > 0) {
            // $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena sudah diproses (memiliki qty_reff > 0).');
            throw new Exception('Nota ini tidak bisa dihapus karena sudah diproses');
            // return false;
        }

        return true; // Tidak ada qty_reff yang terisi, bisa dihapus
    }
    #endregion

    #region Utility
    private function getDeliveryTrType()
    {
        return $this->tr_type == "PO" ? "PD" : "SD";
    }

    private function getBillingTrType()
    {
        return $this->tr_type == "PO" ? "APB" : "ARB";
    }

    private function deleteOrderDetails()
    {
        foreach ($this->OrderDtl as $detail) {
            $detail->forceDelete();
        }
    }
    #endregion

    #region Attributes
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

            /**
     * Update print counter untuk nota jual
     */
    public function updatePrintCounter()
    {
        $currentData = $this->getPrintCounterData();
        $currentData['nota']++;
        $this->print_remarks = $currentData;
        $this->save();
        return $this->getDisplayFormat();
    }

    /**
     * Update print counter untuk surat jalan
     */
    public function updateDeliveryPrintCounter()
    {
        $currentData = $this->getPrintCounterData();
        $currentData['surat_jalan']++;
        $this->print_remarks = $currentData;
        $this->save();
        return $this->getDisplayFormat();
    }

    /**
     * Ambil data counter dalam format array
     */
    private function getPrintCounterData()
    {
        if (empty($this->print_remarks)) {
            return ['nota' => 0, 'surat_jalan' => 0];
        }

        if (is_array($this->print_remarks) && isset($this->print_remarks['nota']) && isset($this->print_remarks['surat_jalan'])) {
            return $this->print_remarks;
        }

        // Fallback: parse format lama (1.0, 1.1, dst)
        if (is_string($this->print_remarks) && strpos($this->print_remarks, '.') !== false) {
            $parts = explode('.', $this->print_remarks);
            if (count($parts) >= 2) {
                return [
                    'nota' => (int)$parts[0],
                    'surat_jalan' => (int)$parts[1]
                ];
            }
        }

        return ['nota' => 0, 'surat_jalan' => 0];
    }

    /**
     * Ambil format tampilan (1.1, 2.1, dst)
     */
    public function getDisplayFormat()
    {
        $data = $this->getPrintCounterData();
        return $data['nota'] . '.' . $data['surat_jalan'];
    }

    /**
     * Ambil data counter lengkap
     */
    public function getPrintCounterArray()
    {
        return $this->getPrintCounterData();
    }
    #endregion

    /**
     * Update print counter untuk nota jual (static method)
     */
    public static function updatePrintCounterStatic($orderId)
    {
        $orderHdr = self::find($orderId);
        if ($orderHdr) {
            return $orderHdr->updatePrintCounter();
        }
        return '0.0';
    }

    /**
     * Update print counter untuk surat jalan (static method)
     */
    public static function updateDeliveryPrintCounterStatic($orderId)
    {
        $orderHdr = self::find($orderId);
        if ($orderHdr) {
            return $orderHdr->updateDeliveryPrintCounter();
        }
        return '0.0';
    }
}

