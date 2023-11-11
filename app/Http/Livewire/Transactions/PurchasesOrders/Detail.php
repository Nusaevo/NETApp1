<?php

namespace App\Http\Livewire\Transactions\PurchasesOrders;

use Livewire\Component;
use App\Models\OrderHdr;
use App\Models\OrderDtl;
use App\Models\ItemUnit;
use App\Models\Partner;
use App\Models\Payment;
use Illuminate\Validation\Rule;
use App\Models\Unit;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $object_detail;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $inputs = [];
    public $input_details = [];
    public $status = '';

    public $suppliers;
    public $payments;

    public $unit_row = 0;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "PO";
    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        $this->refreshSupplier();
        $this->refreshPayment();
        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = OrderHdr::withTrashed()->find($this->objectId);
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->get();
            $this->status = $this->object->status_code;
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            foreach ($this->object_detail as $index => $detail) {
                $this->input_details[$index] = populateArrayFromModel($detail);
                $this->input_details[$index]['id'] = $detail->id;
                $this->input_details[$index]['price'] = round($detail->price, 0);
                $this->input_details[$index]['qty'] = round($detail->qty, 0);
                $this->input_details[$index]['amt'] = round($detail->amt, 0);
                $this->input_details[$index]['sub_total'] = rupiah($detail->amt);
                $this->input_details[$index]['detail_item_name'] = $detail->item_name . '-' . $detail->unit_name;
            }
            $this->countTotalAmount();
            $this->dispatchBrowserEvent('reApplySelect2');
        } else {
            $this->object = new OrderHdr();
            $this->inputs['tr_date']  = date('Y-m-d');
            $this->inputs['tr_type']  = $this->trType;
        }
    }

    public function render()
    {
        return view('livewire.transactions.purchases-orders.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem'
    ];

    protected function rules()
    {
        $rules = [
            'inputs.tr_date' => 'required',
            'inputs.partner_id' => 'required',
            'inputs.payment_term_id' => 'required',
            'input_details.*.item_unit_id' => 'required',
            'input_details.*.qty' => 'required|integer|min:0|max:9999999999',
            // 'input_details.*.price' => 'required|integer|min:0|max:9999999999',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input',
        'inputs.*'              => 'Input',
        'inputs.tr_date'      => 'Tanggal Transaksi',
        'inputs.partner_id'      => 'Supplier',
        'inputs.payment_term_id'      => 'Payment',
        'input_details.*'              => 'Inputan Barang',
        'input_details.*.item_unit_id' => 'Item',
        'input_details.*.qty' => 'Item Qty',
        // 'input_details.*.price' => 'Item Price',
    ];

    public function refreshSupplier()
    {
        $suppliersData = Partner::GetByGrp('SUPP');
        if (!$suppliersData->isEmpty()) {
            $this->suppliers = $suppliersData->map(function ($data) {
                return [
                    'label' => $data->name,
                    'value' => $data->id,
                ];
            })->toArray();
            $this->inputs['partner_id'] = $this->suppliers[0]['value'];
        } else {
            $this->suppliers = [];
            $this->inputs['partner_id'] = null;
        }
    }

    public function refreshPayment()
    {
        $paymentsData = Payment::All();
        if (!$paymentsData->isEmpty()) {
            $this->payments = $paymentsData->map(function ($data) {
                return [
                    'label' => $data->name,
                    'value' => $data->id,
                ];
            })->toArray();
            $this->inputs['payment_term_id'] = $this->payments[0]['value'];
        } else {
            $this->payments = [];
            $this->inputs['payment_term_id'] = null;
        }
    }
    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        return $objectData;
    }

    public function addDetails()
    {
        $detail = [
            'tr_seq' => $this->unit_row + 1,
            'tr_type' => $this->trType
        ];
        array_push($this->input_details, $detail);

        $newDetail = end($this->input_details);
        $this->newItems[] = $newDetail;
        $this->unit_row++;
        $this->dispatchBrowserEvent('reApplySelect2');
    }

    public function deleteDetails($index)
    {
        if (isset($this->input_details[$index]['id'])) {
            $this->deletedItems[] = $this->input_details[$index]['id'];
        }
        unset($this->input_details[$index]);
        $this->unit_row = $this->unit_row - 1;
        $this->input_details = array_values($this->input_details);
    }

    public function changeItem($id, $value, $index)
    {
        $duplicated = false;
        $param = explode("-", $id);
        foreach ($this->input_details as $item_id => $input_details) {
            if ($item_id != $param[1]) {
                if (isset($input_details['item_unit_id'])) {
                    if ($input_details['item_unit_id'] == $value) {
                        $duplicated = true;
                    }
                }
            }
        }
        if ($duplicated == false) {
            $itemUnit = ItemUnit::findorFail($value);
            $this->input_details[$param[1]]['item_unit_id'] = $itemUnit->id;
            $this->input_details[$param[1]]['detail_item_name'] = $itemUnit->item->name . '-' . $itemUnit->from_unit->name;
            $this->input_details[$param[1]]['item_name'] = $itemUnit->item->name;
            $this->input_details[$param[1]]['unit_name'] = $itemUnit->from_unit->name;
            $indexOfInputs = count($this->input_details) - 1;

            if ($index ==  $indexOfInputs) {
                $this->addDetails();
            }
        } else {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' =>  "Produk dan satuan telah dibuat sebelumnya, mohon dicek kembali!"]);
        }
    }

    public function changeQty($id, $value)
    {
        if (isset($this->input_details[$id]['price'])) {
            $total = $this->input_details[$id]['price'] * $value;
            $this->input_details[$id]['amt'] = $total;
            $this->input_details[$id]['sub_total'] = rupiah($total);
            $this->countTotalAmount();
            $this->dispatchBrowserEvent('reApplySelect2');
        }
    }

    public function changePrice($id, $value)
    {
        if (isset($this->input_details[$id]['qty'])) {
            $total = $this->input_details[$id]['qty'] * $value;
            $this->input_details[$id]['amt'] = $total;
            $this->input_details[$id]['sub_total'] = rupiah($total);
            $this->countTotalAmount();
            $this->dispatchBrowserEvent('reApplySelect2');
        }
    }

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $item_id => $input_details) {
            if (isset($input_details['item_unit_id'])) {
                if (isset($input_details['qty']) && isset($input_details['price']))
                    $this->total_amount += $input_details['price'] * $input_details['qty'];
            }
        }
        $this->inputs['amt']  =  $this->total_amount;
    }

    public function validateForms()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "PO", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }
    public function validateUnits()
    {
        if (empty($this->input_details)) {
            throw new Exception("Harap pilih item");
        }
        if (!empty($this->input_details)) {
            $unitIds = array_column($this->input_details, 'item_unit_id');
            if (count($unitIds) !== count(array_flip($unitIds))) {
                throw new Exception("Ditemukan duplikasi Item.");
            }
        }
    }

    // Inside the Detail class

    // Refactor Create and Edit to support draft versions
    public function save($status_code, $isDraft = false)
    {
        $messageAction = $this->action === 'Create' ? 'create' : 'update';
        // Run validations first
        $this->validateForms();

        // Wrap operations in a transaction for atomicity
        DB::beginTransaction();

        try {
            // Validate if units are correct
            $this->validateUnits();

            // Prepare the data array for creating/updating the OrderHdr
            $objectData = $this->populateObjectArray();
            $objectData['status_code'] = $status_code;

            // Create or Update the OrderHdr based on the action
            if ($this->action === 'Create') {
                $this->object = OrderHdr::create($objectData);
            } elseif ($this->action === 'Edit') {
                $this->object->update($objectData);
            }

            // Handle the details of the order
            foreach ($this->input_details as $inputDetail) {
                // If detail id is set and we're editing, find and update
                if (isset($inputDetail['id']) && $this->action === 'Edit') {
                    $detail = OrderDtl::find($inputDetail['id']);
                    $detail->update($inputDetail);
                } else { // else, we create new details
                    $inputDetail['trhdr_id'] = $this->object->id; // link to header
                    $inputDetail['qty_reff'] = $inputDetail['qty']; // additional operations or calculations
                    OrderDtl::create($inputDetail);
                }
            }

            if ($this->action === 'Edit' && count($this->deletedItems) > 0) {
                OrderDtl::destroy($this->deletedItems);
                $this->deletedItems = [];
            }

            if ($this->action === 'Edit') {
                $this->VersioNumber = $this->object->version_number;
            }

            DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get("generic.success.$messageAction", ['object' => "PO"])
            ]);

            if ($this->action === 'Create') {
                $this->postOrderProcessing();
            }
        } catch (Exception $e) {
            DB::rollBack();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get("generic.error.$messageAction", ['object' => "PO", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function CreateDraft()
    {
        $this->save('Draft', true);
    }

    public function EditDraft()
    {
        $this->save('Draft', true);
    }

    public function Create()
    {
        $this->save('Open', false);
    }

    public function Edit()
    {
        $this->save('Open', false);
    }

    private function postOrderProcessing()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->refreshSupplier();
        $this->refreshPayment();
        $this->total_amount = 0;
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
    }
}
