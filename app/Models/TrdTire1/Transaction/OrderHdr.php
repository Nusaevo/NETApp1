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
        'tr_code',
        'tr_type',
        'tax_code',
        'tax_pct',
        'tax_id',
        'partner_id',
        'partner_code',
        'payment_term_id',
        'payment_due_days',
        'tr_date',
        'print_date',
        'curr_rate',
        'curr_id',
        'curr_code',
        'note',
        'sales_type',
        'tax_doc_flag',
        'payment_term',
        'ship_to_name',
        'ship_to_addr',
        'npwp_name',
        'npwp_addr',
        'npwp_code',
        'total_amt',
        'total_amt_tax',
        'reff_code'
    ];

    protected $casts = [
        'tax_doc_flag' => 'boolean',
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

    public static function generateTransactionId($sales_type, $tr_type, $tax_doc_flag = false)
    {
        if ($tr_type == 'PO') {
            return self::generatePurchaseOrderId();
        }

        $year = date('y');
        $monthNumber = date('n');
        $monthLetter = chr(64 + $monthNumber);
        $sequenceNumber = self::getSequenceNumber($sales_type, $tax_doc_flag);

        if ($tax_doc_flag) {
            switch ($sales_type) {
                case 'O':
                    return sprintf('%s%s%05d', $monthLetter, $year, $sequenceNumber);
                case 'I':
                    return sprintf('%s%s%s%05d', $monthLetter, $monthLetter, $year, $sequenceNumber);
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        } else {
            switch ($sales_type) {
                case 'O': // MOTOR tanpa tax invoice: Format: [A-Z][yy]8[5-digit]
                    return sprintf('%s%s8%05d', $monthLetter, $year, $sequenceNumber);
                case 'I': // MOBIL tanpa tax invoice: Format: [A-Z]{2}[yy]8[5-digit]
                    return sprintf('%s%s%s8%05d', $monthLetter, $monthLetter, $year, $sequenceNumber);
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        }
    }

    private static function generatePurchaseOrderId()
    {
        $lastId = ConfigSnum::where('code', 'PURCHORDER_LASTID')->first();
        $newId = (int)$lastId->last_cnt + 1;
        $lastId->last_cnt = $newId;
        $lastId->save();

        return sprintf('PO%04d', $newId); // Contoh: PO0001
    }

    /**
     * Fungsi ini mengambil nomor urut berdasarkan entri terakhir.
     * Regex disesuaikan berdasarkan jenis kendaraan (MOTOR/MOBIL) dan flag tax invoice.
     */
    private static function getSequenceNumber($sales_type, $tax_doc_flag)
    {
        $currentYear = date('y');
        $currentMonth = date('n');
        $currentMonthLetter = chr(64 + $currentMonth);

        $taxInvoiceFlag = $tax_doc_flag ? 1 : 0;

        $lastOrder = OrderHdr::where('tr_type', 'SO')
            ->where('sales_type', $sales_type)
            ->where('tax_doc_flag', $taxInvoiceFlag)
            ->orderBy('id', 'desc')
            ->first();

        if ($sales_type == 'O') {
            if ($tax_doc_flag) {
                $pattern = '/^([A-Z])(\d{2})(\d{5})$/';
                $expectedPrefix = $currentMonthLetter;
            } else {
                $pattern = '/^([A-Z])(\d{2})8(\d{5})$/';
                $expectedPrefix = $currentMonthLetter;
            }
        } elseif ($sales_type == 'I') {
            // MOBIL
            if ($tax_doc_flag) {
                $pattern = '/^([A-Z]{2})(\d{2})(\d{5})$/';
                $expectedPrefix = $currentMonthLetter . $currentMonthLetter;
            } else {
                $pattern = '/^([A-Z]{2})(\d{2})8(\d{5})$/';
                $expectedPrefix = $currentMonthLetter . $currentMonthLetter;
            }
        } else {
            throw new \InvalidArgumentException('Invalid sales type');
        }

        if ($lastOrder && preg_match($pattern, $lastOrder->tr_code, $matches)) {
            if ($matches[1] === $expectedPrefix && $matches[2] == $currentYear) {
                return (int)$matches[3] + 1;
            }
        }
        return 1;
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
    #endregion
}
