<?php

namespace App\Livewire\TrdTire1\Transaction\ReceivablesSettlement;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{BillingDtl, BillingHdr, OrderHdr, OrderDtl, PaymentDtl, PaymentHdr};
use App\Models\TrdTire1\Master\MatlUom; // Add this import
use Exception;
use Carbon\Carbon; // Add this import

class DebtListComponent extends DetailComponent
{
    public $materials;
    public $codeBill;
    protected $masterService;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];

    protected $rules = [
        'input_details.*.amt' => 'required',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);

        // Jika objectIdValue tersedia (Edit), load PaymentHdr dari DB.
        if ($objectIdValue) {
            $this->object = PaymentHdr::find($objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Payment header tidak ditemukan.');
            }
        }
    }


    public function onReset()
    {
        $this->reset('input_details'); // Reset input_details instead of inputs
        // $this->object = new PaymentHdr();
        // $this->object = new PaymentDtl();
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->materials = $this->masterService->getMaterials();
        $this->codeBill = $this->masterService->getBillCode();

        // Selalu tampilkan detail PaymentDtl jika objectIdValue tersedia
        if (!empty($this->objectIdValue)) {
            $this->object = PaymentHdr::withTrashed()->find($this->objectIdValue);
            if ($this->object) {
                $this->inputs = populateArrayFromModel($this->object);
                $this->loadDetails(); // Load details regardless of action
            } else {
                $this->dispatch('error', 'Object not found');
                return;
            }
        }
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $this->input_details[] = [
                    'matl_id' => null,
                    'qty' => null,
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Header terlebih dahulu']));
        }
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $amtbill = 0;
                $billhdrtr_code = null;
                $tr_date = null;

                // Cari BillingDtl dan BillingHdr terkait
                if ($detail->billdtl_id) {
                    $billingDtl = BillingDtl::find($detail->billdtl_id);
                    if ($billingDtl) {
                        $amtbill = $billingDtl->amt;
                        // Cari BillingHdr berdasarkan billingDtl->trhdr_id
                        $billingHdr = BillingHdr::find($billingDtl->trhdr_id);
                        if ($billingHdr) {
                            $billhdrtr_code = $billingHdr->id; // <-- gunakan ID, bukan tr_code
                            $tr_date = $billingHdr->tr_date;
                        }
                    }
                }

                // Fallback jika tidak ada BillingDtl/BillingHdr, gunakan data dari PaymentDtl
                if (!$billhdrtr_code) {
                    // Coba cari BillingHdr berdasarkan tr_code yang tersimpan di PaymentDtl
                    if (!empty($detail->billhdrtr_code)) {
                        $billingHdr = BillingHdr::where('tr_code', $detail->billhdrtr_code)->first();
                        $billhdrtr_code = $billingHdr ? $billingHdr->id : null;
                        $tr_date = $billingHdr ? $billingHdr->tr_date : ($detail->tr_date ?? null);
                    } else {
                        $billhdrtr_code = null;
                        $tr_date = $detail->tr_date ?? null;
                    }
                }

                $this->input_details[$key] = [
                    'billhdrtr_code' => $billhdrtr_code,
                    'tr_date'        => $tr_date,
                    'amtbill'        => $amtbill,
                    'amt'            => $detail->amt ?? null,
                    // tambahkan field lain sesuai kebutuhan
                ];
            }
            // dd($this->input_details); // Uncomment untuk debug seluruh array
        }
    }

    public function SaveItem()
    {
        // Pastikan object selalu diisi jika objectIdValue ada
        if (!$this->object && $this->objectIdValue) {
            $this->object = PaymentHdr::find($this->objectIdValue);
        }

        $this->onValidateAndSave();

        if (!$this->object || !$this->object->id) {
            $this->dispatch('error', 'Payment header belum disimpan atau tidak ditemukan.');
            return;
        }

        return redirect()->route($this->appCode . '.Transaction.ReceivablesSettlement.Detail', [
            'action' => encryptWithSessionKey('Edit'),
            'objectId' => encryptWithSessionKey($this->object->id)
        ]);
    }

    public function onValidateAndSave()
    {
        // $this->validate(); // Pastikan validasi dijalankan jika diperlukan

        foreach ($this->input_details as $key => $detail) {
            $tr_seq = $key + 1;

            // Ambil billing header berdasarkan input (yang berisi ID BillingHdr)
            $billingHdr = BillingHdr::find($detail['billhdrtr_code']);

            if ($billingHdr) {
                // Ambil data PaymentHdr dari database untuk mendapatkan tr_type dan tr_code
                $paymentHdr = PaymentHdr::find($this->objectIdValue);
                $tr_type = $paymentHdr ? $paymentHdr->tr_type : null;
                $tr_code = $paymentHdr ? $paymentHdr->tr_code : null;

                if ($tr_type && $tr_code && $this->objectIdValue && $tr_seq) {
                    // Ambil billing detail berdasarkan billing header
                    $billingDtl = BillingDtl::where('trhdr_id', $billingHdr->id)->first();

                    $data = [
                        'tr_type'        => $tr_type,
                        'tr_code'        => $tr_code,
                        'amt'            => $detail['amt'], // Input dari user
                        // Simpan tr_code dari billingHdr ke kolom billhdrtr_code
                        'billhdrtr_code' => $billingHdr->tr_code,
                        'billdtl_id'     => $billingDtl ? $billingDtl->id : null,
                        'billhdrtr_type' => $billingHdr->tr_type,
                        'billdtltr_seq'  => $billingDtl ? $billingDtl->tr_seq : null,
                    ];

                    // Debug log (bisa diganti dengan logger jika perlu)
                    // \Log::info('Saving PaymentDtl', ['key' => $key, 'trhdr_id' => $this->objectIdValue, 'tr_seq' => $tr_seq, 'data' => $data]);

                    $paymentDtl = PaymentDtl::updateOrCreate(
                        [
                            'trhdr_id' => $this->objectIdValue,
                            'tr_seq'   => $tr_seq,
                        ],
                        $data
                    );

                    // Jika gagal simpan, tampilkan error
                    if (!$paymentDtl) {
                        $this->dispatch('error', 'Gagal menyimpan PaymentDtl.');
                    }
                } else {
                    $this->dispatch('error', __('Payment header not found or data tidak lengkap.'));
                }
            }
        }

        $this->dispatch('success', __('Data Payment berhasil disimpan.'));
    }

    public function onCodeChanged($key, $billHdrId)
    {
        // Cari BillingHdr beserta relasi BillingDtl-nya
        $billHdr = BillingHdr::with('BillingDtl')->find($billHdrId);

        if ($billHdr) {
            // Set format tanggal dari billing header
            $tr_date = Carbon::parse($billHdr->tr_date);
            $this->input_details[$key]['tr_date'] = $tr_date->format('Y-m-d');

            // Ambil seluruh BillingDtl yang sesuai dengan trhdr_id
            $billingDetails = BillingDtl::where('trhdr_id', $billHdr->id)->get();
            if ($billingDetails->isNotEmpty()) {
                // Jumlahkan nilai 'amt' dari seluruh record yang ditemukan
                $totalAmt = $billingDetails->sum('amt');
                $this->input_details[$key]['amtbill'] = $totalAmt;
            } else {
                // Jika tidak ditemukan detail, set ke 0
                $this->input_details[$key]['amtbill'] = 0;
            }
        } else {
            $this->dispatch('error', __('Bill not found.'));
        }
    }

    /**
     * Save the PaymentHdr object if it exists.
     * You can expand this logic as needed.
     */
    public function Save()
    {
        if ($this->object) {
            $this->object->save();
        }
    }

    /**
     * Determine if the current action is Edit or View.
     * You may adjust the logic as needed based on your application's conventions.
     */
    private function isEditOrView()
    {
        // Assuming $this->action is set to 'Edit' or 'View' for those actions
        return in_array($this->action, ['Edit', 'View']);
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
