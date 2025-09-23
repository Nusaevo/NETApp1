<?php

namespace App\Livewire\TrdTire1\Transaction\DebtSettlement;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{PaymentHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $suppliers = [];
    public $selectedPartners = [];
    public $warehouses;
    public $partners;
    public $sales_type;
    public $tax_doc_flag;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $total_amount;
    public $total_tax;
    public $total_dpp;
    public $total_discount;
    public $trType = "ARP";
    public $versionNumber = "0.0";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    public $shipOptions = [];
    protected $masterService;
    public $isPanelEnabled = "false";
    public $notaCount = 0; // x: jumlah nota jual dicetak
    public $suratJalanCount = 0; // y: jumlah surat jalan dicetak
    public $partnerSearchText = ''; // Add this line to define the property

    public $rules  = [
        // 'inputs.tr_code' => 'required',
        'inputs.partner_id' => 'required',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
        'updateDiscount' => 'updateDiscount',
        'updateDPP' => 'updateDPP',
        'updatePPN' => 'updatePPN',
        'updateTotalTax' => 'updateTotalTax',
    ];
    #endregion

    #region Populate Data methods

    public function getTransactionCode()
    {
        if (!isset($this->inputs['sales_type']) || !isset($this->trType)) {
            $this->dispatch('warning', 'Tipe Kendaraan dan Jenis Transaksi harus diisi');
            return;
        }

        $sales_type = $this->inputs['sales_type'];
        $tax_doc_flag = !empty($this->inputs['tax_doc_flag']); // Konversi ke boolean
        $tr_type = $this->trType;

        $this->inputs['tr_code'] = PaymentHdr::generateTransactionId($sales_type, $tr_type, $tax_doc_flag);
        // dd($this->inputs['tr_code']);
    }

    public function onSOTaxChange()
    {
        try {
            // Ambil data konfigurasi berdasarkan konstanta pajak
            $configData = ConfigConst::select('num1', 'str1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax_flag'])
                ->first();

            $this->inputs['tax_value'] = $configData->num1 ?? 0; // Nilai pajak default 0 jika tidak ditemukan
            $taxType = $configData->str1 ?? ''; // Tipe pajak (str1)

            // Simpan tax_pct
            $this->inputs['tax_pct'] = $this->inputs['tax_value'];

            // Hitung DPP dan PPN berdasarkan tipe pajak
            $this->calculateDPPandPPN($taxType);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function calculateDPPandPPN($taxType)
    {
        try {
            $taxValue = (float)($this->inputs['tax_value'] ?? 0); // Nilai pajak (persentase)
            $totalAmount = (float)$this->total_amount; // Total amount dari input

            if ($taxType === 'I') {
                $dpp = $totalAmount / (1 + $taxValue / 100); // Rumus DPP
                $ppn = $totalAmount - $dpp; // Rumus PPN
            } elseif ($taxType === 'E') {
                $dpp = $totalAmount; // DPP sama dengan total amount
                $ppn = ($taxValue / 100) * $totalAmount; // Rumus PPN
            } else {
                $dpp = $totalAmount; // DPP sama dengan total amount
                $ppn = 0; // PPN nol
            }

            // Simpan hasil perhitungan
            $this->total_dpp = rupiah(round($dpp, 2));
            $this->total_tax = rupiah(round($ppn, 2));

            // Dispatch event untuk memperbarui UI
            $this->dispatch('updateDPP', $this->total_dpp);
            // $this->dispatch('updateTotalTax', $this->total_tax);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function onPartnerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        $this->npwpOptions = $partner ? $this->listNpwp($partner) : null;
        $this->shipOptions = $partner ? $this->listShip($partner) : null;
    }

    private function listNpwp($partner)
    {
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->wp_details)) {
            return [];
        }
        $wpDetails = $partner->PartnerDetail->wp_details;

        if (is_string($wpDetails)) {
            $wpDetails = json_decode($wpDetails, true);
        }
        // Jika gagal decode atau bukan array, return array kosong untuk mencegah error
        if (!is_array($wpDetails)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'label' => ($item['npwp']),
                'value' => $item['npwp'],
            ];
        }, $wpDetails);
    }
    private function listShip($partner)
    {
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->shipping_address)) {
            return [];
        }
        $shipDetail = $partner->PartnerDetail->shipping_address;

        if (is_string($shipDetail)) {
            $shipDetail = json_decode($shipDetail, true);
        }
        // Jika gagal decode atau bukan array, return array kosong untuk mencegah error
        if (!is_array($shipDetail)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'label' => ($item['name']),
                'value' => $item['name'],
            ];
        }, $shipDetail);
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
            'inputs.tr_code'      => $this->trans('tr_code'),
            'inputs.partner_id'      => $this->trans('partner_id'),
            'inputs.send_to_name'      => $this->trans('send_to_name'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        // $this->suppliers = $this->masterService->getSuppliers();
        $this->warehouses = $this->masterService->getWarehouse();
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = PaymentHdr::withTrashed()->find($this->objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Object not found');
                return;
            }
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_doc_flag'] = $this->object->tax_doc_flag;
            $this->inputs['partner_name'] = $this->object->partner->code;
            $this->inputs['tr_code'] = $this->object->tr_code;
        }
        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
        }
        // Panggil perhitungan DPP dan PPN saat halaman dimuat
        if (!empty($this->inputs['tax_flag'])) {
            $this->onSOTaxChange();
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new PaymentHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;

    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        if ($this->actionValue == 'Edit') {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }
        }

        if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner->code;
        }

        // Ensure payment_term is set
        if (!empty($this->inputs['payment_term_id'])) {
            $paymentTerm = ConfigConst::find($this->inputs['payment_term_id']);
            $this->inputs['payment_term'] = $paymentTerm->str1;
            $this->inputs['payment_due_days'] = $paymentTerm->num1; // Save payment_due_days from num1
        }

        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'DebtSettlement_LASTID');
        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Transaction.DebtSettlement.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
    }

    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.delete';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

    private function updateVersionNumber2()
    {
        $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
    }

    public function openPartnerDialogBox()
    {
        $this->partnerSearchText = '';
        $this->suppliers = [];
        $this->selectedPartners = [];
        $this->dispatch('openPartnerDialogBox');
    }
    public function searchPartners()
    {
        if (!empty($this->partnerSearchText)) {
            $searchTerm = strtoupper($this->partnerSearchText);
            $this->suppliers = Partner::where('grp', Partner::CUSTOMER)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("UPPER(code) LIKE ?", ["%{$searchTerm}%"])
                        ->orWhereRaw("UPPER(name) LIKE ?", ["%{$searchTerm}%"]);
                })
                ->get();
        } else {
            $this->dispatch('error', "Mohon isi kode atau nama supplier");
        }
    }

    public function selectPartner($partnerId)
    {
        $key = array_search($partnerId, $this->selectedPartners);

        if ($key !== false) {
            unset($this->selectedPartners[$key]);
            $this->selectedPartners = array_values($this->selectedPartners);
        } else {
            $this->selectedPartners[] = $partnerId;
        }
    }


    public function confirmSelection()
    {
        if (empty($this->selectedPartners)) {
            $this->dispatch('error', "Silakan pilih satu supplier terlebih dahulu.");
            return;
        }
        if (count($this->selectedPartners) > 1) {
            $this->dispatch('error', "Hanya boleh memilih satu supplier.");
            return;
        }
        $partner = Partner::find($this->selectedPartners[0]);

        if ($partner) {
            $this->inputs['partner_id'] = $partner->id;
            $this->inputs['partner_code'] = $partner->code; // Set partner_code
            $this->inputs['partner_name'] = $partner->code;
            $this->inputs['textareacustommer'] = $partner->name . "\n" . $partner->address . "\n" . $partner->city;

            // Set npwpOptions with data from JSON wp_details
            if ($partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
                $wpDetails = $partner->PartnerDetail->wp_details;
                if (is_string($wpDetails)) {
                    $wpDetails = json_decode($wpDetails, true);
                }
                if (is_array($wpDetails) && !empty($wpDetails)) {
                    $this->npwpOptions = array_map(function ($item) {
                        return [
                            'label' => $item['npwp'],
                            'value' => $item['npwp'],
                        ];
                    }, $wpDetails);
                    // Automatically select the first npwpOption
                    $firstNpwpOption = $this->npwpOptions[0] ?? null;
                    if ($firstNpwpOption) {
                        $this->inputs['npwp_code'] = $firstNpwpOption['value'];
                        $this->onTaxPayerChanged();
                    }
                }
            }
            // Set shipOptions with data from JSON shipping_address
            if ($partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
                $shipDetail = $partner->PartnerDetail->shipping_address;
                if (is_string($shipDetail)) {
                    $shipDetail = json_decode($shipDetail, true);
                }
                if (is_array($shipDetail) && !empty($shipDetail)) {
                    $this->shipOptions = array_map(function ($item) {
                        return [
                            'label' => $item['name'],
                            'value' => $item['name'],
                        ];
                    }, $shipDetail);
                    // Automatically select the first shipOption
                    $firstShipOption = $this->shipOptions[0] ?? null;
                    if ($firstShipOption) {
                        $this->inputs['ship_to_name'] = $firstShipOption['value'];
                        $this->onShipToChanged();
                    }
                }
            }

            $this->dispatch('success', "Custommer berhasil dipilih.");
            $this->dispatch('closePartnerDialogBox');
        }
    }

    public function onTaxPayerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
            $wpDetails = $partner->PartnerDetail->wp_details;
            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }
            if (is_array($wpDetails)) {
                foreach ($wpDetails as $detail) {
                    if ($detail['npwp'] == $this->inputs['npwp_code']) {
                        $this->inputs['textarea_npwp'] = $detail['wp_name'] . "\n" . $detail['wp_location'];
                        $this->inputs['npwp_code'] = $detail['npwp'];
                        $this->inputs['npwp_name'] = $detail['wp_name'];
                        $this->inputs['npwp_addr'] = $detail['wp_location'];
                        break;
                    }
                }
            }
        }
    }

    public function onShipToChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
            $shipDetails = $partner->PartnerDetail->shipping_address;
            if (is_string($shipDetails)) {
                $shipDetails = json_decode($shipDetails, true);
            }
            if (is_array($shipDetails)) {
                foreach ($shipDetails as $detail) {
                    if ($detail['name'] == $this->inputs['ship_to_name']) {
                        $this->inputs['textareasend_to'] = $detail['address'];
                        $this->inputs['ship_to_name'] = $detail['name'];
                        $this->inputs['ship_to_addr'] = $detail['address'];
                        break;
                    }
                }
            }
        }
    }

    #endregion

    #region Component Events
    // Update total amount based on changes
    public function updateAmount($data)
    {
        $this->total_amount = $data['total_amount'];
        $this->total_discount = ($data['total_discount']);

        // Recalculate DPP and PPN when amount or discount changes
        $this->calculateDPPandPPN($this->inputs['tax_flag'] ?? '');
    }

    // Update discount percentage
    // public function updateDiscount($discount)
    // {
    //     $this->total_discount = $discount;
    //     $this->calculateDPPandPPN($this->inputs['tax'] ?? '');
    // }

    // Update DPP
    // public function updateDPP($dpp)
    // {
    //     $this->total_dpp = $dpp;
    // }
    #endregion
}
