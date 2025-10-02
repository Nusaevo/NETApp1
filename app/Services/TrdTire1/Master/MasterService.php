<?php

namespace App\Services\TrdTire1\Master;

use App\Services\Base\BaseService;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\BillingHdr;

class MasterService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getConfigData($constGroup)
    {
        return $this->mainConnection
            ->table('config_consts')
            ->select('id', 'str1', 'str2', 'note1', 'num1', 'num2')
            ->where('const_group', $constGroup)
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get();
    }

    public function getCurrencyData()
    {
        $data = $this->getConfigData('MCURRENCY_CODE');

        $currencies = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
            ];
        })->toArray();

        $defaultCurrency = $currencies[0] ?? null;
        return [
            'currencies' => $currencies,
            'defaultCurrency' => $defaultCurrency
        ];
    }

    public function getPartnerTypes()
    {
        $data = $this->getConfigData('PARTNERS_TYPE');

        return $this->mapData($data);
    }

    public function getWarehouse()
    {
        $data = $this->getConfigData('TRX_WAREHOUSE');
        return $this->mapData($data);
    }

    public function getWarehouseType()
    {
        $data = $this->getConfigData('TRX_WH_TYPE');
        return $this->mapData($data);
    }

    public function getUOMData()
    {
        $data = $this->getConfigData('MMATL_UOM');
        return $this->mapData($data);
    }

    public function getMatlCategory1Data()
    {
        $data = $this->getConfigData('MMATL_CATEGL1');
        return $this->mapData($data);
    }

    public function getMatlCategory1String($str1)
    {
        $data = $this->mainConnection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL1')
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str2 : null;
    }

    public function getMatlTypeData()
    {
        $data = $this->getConfigData('MMATL_TYPE');
        return $this->mapData($data);
    }
    public function getMatlJenisData()
    {
        $data = $this->getConfigData('MMATL_JENIS');
        return $data->map(function ($data) {
            return [
                'label' => $data->str2,
                'value' => $data->str2,
            ];
        })->toArray();
    }
    // public function getPaymentTermsData()
    // {
    //     $data = $this->getConfigData('MPAYMENT_TERMS');
    //     return $data->map(function ($data) {
    //         return [
    //             'label' => $data->str1,
    //             'value' => $data->id,
    //         ];
    //     })->toArray();
    // }
    public function getMatlCategoryData()
    {
        $data = $this->getConfigData('MMATL_CATEGORY');
        return $data->map(function ($data) {
            return [
                'label' => $data->str2,
                'value' => $data->str2,
            ];
        })->toArray();
    }
    public function getMatlCategoryOptionsForSelectFilter()
    {
        $data = $this->getConfigData('MMATL_CATEGORY');
        $options = $data->pluck('str2', 'str2')->toArray();
        // Tambahkan opsi kosong dengan label "All" sebagai pilihan default
        return ['' => 'All'] + $options;
    }
    public function getMatlBrandOptionsForSelectFilter()
    {
        $data = $this->getConfigData('MMATL_MERK');
        $options = $data->mapWithKeys(function ($item) {
            return [$item->str2 => $item->str1 . " - " . $item->str2];
        })->toArray();
        // Tambahkan opsi kosong dengan label "All" sebagai pilihan default
        return ['' => 'All'] + $options;
    }



    public function getMatlMerkData()
    {
        $data = $this->getConfigData('MMATL_MERK');
        return $data->map(function ($data) {
            return [
                'label' => $data->str1 . " - " . $data->str2,
                'value' => $data->str2,
            ];
        })->toArray();
    }
    public function getMatlPatternData()
    {
        $data = $this->getConfigData('MMATL_PATTERN');
        return $data->map(function ($data) {
            return [
                'label' => $data->str2,
                'value' => $data->str2,
            ];
        })->toArray();
    }

    public function getMatlUOMData()
    {
        $data = $this->getConfigData('MMATL_UOM');
        return $data->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->str1,
            ];
        })->toArray();
    }
    public function getPartnerTypeData()
    {
        $data = $this->getConfigData('MPARTNER_TYPE');
        return $this->mapData($data);
    }
    public function getSOTaxData()
    {
        $data = $this->getConfigData('TRX_SO_TAX');
        return $this->mapData($data);
    }
    public function getPaymentTypeData()
    {
        $data = $this->getConfigData('TRX_PAYMENT_SRCS');
        return $this->mapData($data);
    }

    public function getSOSendData()
    {
        $data = $this->getConfigData('TRX_SO_SEND');
        return $data->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->str1,
            ];
        })->toArray();
    }


    public function getMatlCategory2String($str1)
    {
        $data = $this->mainConnection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL2')
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str2 : null;
    }

    public function getMatlSideMaterialOriginData()
    {
        $data = $this->getConfigData('MMATL_ORIGINS');
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
            ];
        })->toArray();
    }

    public function getPaymentTerm()
    {
        $data = $this->getConfigData('MPAYMENT_TERMS');

        $payments = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
                'num1' => $item->num1,
            ];
        })->toArray();
        return $payments;
    }
    public function getChequeType()
    {
        $data = $this->getConfigData('TRX_PARTNER_TYPES');

        $payments = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->str1,
            ];
        })->toArray();
        return $payments;
    }
    // public function getSuppliers()
    // {
    //     $suppliersData = Partner::GetByGrp(Partner::SUPPLIER);
    //     return $suppliersData->map(function ($data) {
    //         return [
    //             'label' => $data->code . " - " . $data->name. " - " . $data->address . " - " . $data->city,
    //             'value' => $data->id,
    //         ];
    //     })->toArray();
    // }



    public function getCustomers()
    {
        $suppliersData = Partner::GetByGrp(Partner::CUSTOMER);
        return $suppliersData->map(function ($data) {
            return [
                'label' => $data->code . " - " . $data->name . " - " . $data->address . " - " . $data->city,
                'value' => $data->id,
            ];
        })->toArray();
    }

    public function getMaterials()
    {
        $materialsData = Material::whereNull('deleted_at')->get(); // Pastikan untuk hanya mengambil yang tidak dihapus
        return $materialsData->map(function ($data) {
            return [
                'label' => $data->code . " - " . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
    }
    public function getAvaliableMaterials()
    {
        $materialsData = Material::getAvailableMaterials()->get();
        return $materialsData->map(function ($data) {
            return [
                'label' => $data->code . " - " . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
    }
    public function getBillCode()
    {
        $billingData = BillingHdr::getBillCode()->get();
        return $billingData->map(function ($data) {
            return [
                'label' => $data->tr_code,
                'value' => $data->id,
            ];
        })->toArray();
    }

    public function getWarehouses()
    {
        return $this->mainConnection
            ->table('config_consts')
            ->select('id', 'str1')
            ->where('const_group', 'WAREHOUSE_LOC')
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get()
            ->map(function ($data) {
                return [
                    'label' => $data->str1,
                    'value' => $data->id,
                ];
            })->toArray();
    }

    public function getPrintSettings()
    {
        $data = $this->getConfigData('TRX_NJ_PRINT_OPTIONS');

        $options = $data->map(function ($item) {
            return [
                'code' => $item->str1,
                'label' => $item->str2,
                'value' => $item->id,
                'checked' => false
            ];
        })->toArray();

        return $options;
    }

    public function getPrintRemarks()
    {
        $data = $this->getConfigData('TRX_NJ_REMARK');

        $options = $data->map(function ($item) {
            return [
                'code' => $item->str1,
                'label' => $item->str2,
                'value' => $item->id,
                'checked' => false
            ];
        })->toArray();

        return $options;
    }

    public function getDefaultCurrencyStr1(): string
    {
        $defaultCurrency = $this->mainConnection
            ->table('config_consts')
            ->select('str1')
            ->where('const_group', 'MCURRENCY_CODE')
            ->whereNull('deleted_at')
            ->orderByDesc('num1')
            ->first();

        return $defaultCurrency ? $defaultCurrency->str1 : '';
    }

    public function globalCurrency($price = 0, $use_name = true): string
    {
        $currencyStr1 = $this->getDefaultCurrencyStr1();
        $formattedPrice = number_format($price, 2, ',', '.');
        if ($use_name) {
            return $currencyStr1 . ' ' . $formattedPrice;
        } else {
            return $formattedPrice;
        }
    }

    public function getPurchaseOrders()
    {
        $purchaseOrders = OrderHdr::where('tr_type', 'PO')->get();
        return $purchaseOrders->map(function ($order) {
            return [
                'label' => $order->tr_code,
                'value' => $order->tr_code,
            ];
        })->toArray();
    }

    public function getNewTrCode($trType, $salesType, $taxDocFlag, $trDate = null): string
    {
        if ($trType == 'SO'){
            // Generate SO code based on sales type and tax document flag
            return self::getNewTrCodeSo($salesType, $taxDocFlag, $trDate);
        } else {
            switch ($trType) {
                case 'PO':
                    $code = 'PURCHORDER_LASTID';
                    $format = 'PO%06d';
                    break;
                case 'TW':
                    $code = 'DO';
                    break;
                case 'IA':
                    $code = 'INV';
                    break;
                default:
                    return "";
            }

            $configSnum = ConfigSnum::where('code', $code)->first();
            if (!$configSnum) {
                throw new \Exception("Configuration for code {$code} not found.");
            }
            $newId = $configSnum->last_cnt + $configSnum->step_cnt;
            if ($newId > $configSnum->wrap_high) {
                $newId = $configSnum->wrap_low;
            }
            $configSnum->last_cnt = $newId;
            $configSnum->save();
            return sprintf($format, $newId); // Contoh: PO000001
        }
    }

    private static function getNewTrCodeSo($salesType, $taxDocFlag = true, $trDate = null)
    {
        // Gunakan tanggal yang dipilih atau tanggal saat ini
        $date = $trDate ? \Carbon\Carbon::parse($trDate) : \Carbon\Carbon::now();

        $year = $date->format('y');
        $monthNumber = $date->month;
        $monthLetter = chr(64 + $monthNumber);
        $sequenceNumber = self::getNewSeqNumSo($salesType, $taxDocFlag, $trDate);

        if ($taxDocFlag) {
            switch ($salesType) {
                case 'O': // MOTOR dengan faktur pajak: Format: [A-Z]{2}[yy][5-digit]
                    return sprintf('%s%s%s%05d', $monthLetter, $monthLetter, $year, $sequenceNumber);
                case 'I': // MOBIL dengan faktur pajak: Format: [A-Z][yy][5-digit]
                    return sprintf('%s%s%05d', $monthLetter, $year, $sequenceNumber);
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        } else {
            switch ($salesType) {
                case 'O': // MOTOR tanpa tax invoice: Format: [A-Z]{2}[yy]8[5-digit]
                    return sprintf('%s%s%s8%05d', $monthLetter, $monthLetter, $year, $sequenceNumber);
                case 'I': // MOBIL tanpa tax invoice: Format: [A-Z][yy]8[5-digit]
                    return sprintf('%s%s8%05d', $monthLetter, $year, $sequenceNumber);
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        }
    }

    /**
     * Fungsi ini mengambil nomor urut berdasarkan entri terakhir.
     * Regex disesuaikan berdasarkan jenis kendaraan (MOTOR/MOBIL) dan flag tax invoice.
     */
    private static function getNewSeqNumSo($sales_type, $tax_doc_flag, $trDate = null)
    {
        // Gunakan tanggal yang dipilih atau tanggal saat ini
        $date = $trDate ? \Carbon\Carbon::parse($trDate) : \Carbon\Carbon::now();

        $currentYear = $date->format('y');
        $currentMonth = $date->month;
        $currentMonthLetter = chr(64 + $currentMonth);

        $taxInvoiceFlag = $tax_doc_flag ? 1 : 0;

        $lastOrder = OrderHdr::where('tr_type', 'SO')
            ->where('sales_type', $sales_type)
            ->where('tax_doc_flag', $taxInvoiceFlag)
            ->orderBy('id', 'desc')
            ->first();

        if ($sales_type == 'O') {
            // MOTOR - sekarang menggunakan format [A-Z]{2}
            if ($tax_doc_flag) {
                $pattern = '/^([A-Z]{2})(\d{2})(\d{5})$/';
                $expectedPrefix = $currentMonthLetter . $currentMonthLetter;
            } else {
                $pattern = '/^([A-Z]{2})(\d{2})8(\d{5})$/';
                $expectedPrefix = $currentMonthLetter . $currentMonthLetter;
            }
        } elseif ($sales_type == 'I') {
            // MOBIL - sekarang menggunakan format [A-Z]
            if ($tax_doc_flag) {
                $pattern = '/^([A-Z])(\d{2})(\d{5})$/';
                $expectedPrefix = $currentMonthLetter;
            } else {
                $pattern = '/^([A-Z])(\d{2})8(\d{5})$/';
                $expectedPrefix = $currentMonthLetter;
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

    // Perhitungan amount
    public function calculateAmounts(float $qty, float $price, float $discPct, float $taxPct, string $taxCode)
    {
        // Calculate basic amount with discount
        $discount = $discPct / 100;
        $tax = $taxPct / 100;
        $priceAfterDisc = $price * (1 - $discount);
        $priceBeforeTax = round($priceAfterDisc / (1 + $tax),0);
        $amtDiscount = round($qty * $price * $discount,0);

        $amt = 0;
        $amtBeforeTax = 0;
        $amtTax = 0;
        if ($taxCode === 'I') {
            // Catatan: khusus untuk yang include PPN
            // DPP dihitung dari harga setelah disc dikurangi PPN dibulatkan ke rupiah * qty
            $amtBeforeTax = $priceBeforeTax * $qty ;
            // PPN dihitung dari DPP * PPN dibulatkan ke rupiah
            $amtTax = round($amtBeforeTax * $tax,0);
            // Total Nota dihiitung dari harga setelah disc * qty
            // selisih yang timbul antara Total Nota dan DPP + PPN diabaikan
            // priceAdjustment
            $amt = $priceAfterDisc * $qty;
        } else if ($taxCode === 'E') {
            $priceBeforeTax = $priceAfterDisc;
            $amtBeforeTax = $priceAfterDisc * $qty;
            $amtTax = round($priceAfterDisc * $qty * $tax,0);
            $amt = $amtBeforeTax + $amtTax;
        } else if ($taxCode === 'N') {
            $priceBeforeTax = $priceAfterDisc;
            $amtBeforeTax = $priceAfterDisc * $qty;
            $amtTax = 0;
            $amt = $amtBeforeTax;
        }
        $amtAdjust = $amt - $amtBeforeTax - $amtTax;

        return [
            'price_afterdisc' => $priceAfterDisc,
            'price_beforetax' => $priceBeforeTax,
            'amt' => $amt,
            'amt_beforetax' => $amtBeforeTax,
            'amt_tax' => $amtTax,
            'amt_adjust' => $amtAdjust,
            'amt_discout' => $amtDiscount,
        ];
    }



}
