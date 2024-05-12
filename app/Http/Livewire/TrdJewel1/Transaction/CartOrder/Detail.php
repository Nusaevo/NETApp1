<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\CartOrder;

use Livewire\Component;
use App\Models\Transactions\OrderHdr;
use App\Models\Transactions\OrderDtl;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\Payment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $object_detail;
    public $VersionNumber;
    public $actionValue = 'Create';
    public $objectIdValue;
    public $inputs = [];
    public $input_details = [];
    public $status = '';

    public $suppliers;
    public $payments;

    public $unit_row = 0;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "CART";
    public function mount($action)
    {
        $this->actionValue = decryptWithSessionKey($action);
        $this->populateDropdowns();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View')) {
            $usercode = Auth::check() ? Auth::user()->code : '';

            $this->object = OrderHdr::where('created_by', $usercode)
                                ->where('tr_type', 'CART')
                                ->first();
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->get();
            $this->status = Status::getStatusString( $this->object->status_code);
            $this->VersionNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            foreach ($this->object_detail as $index => $detail) {
                $this->input_details[$index] = populateArrayFromModel($detail);
                $this->input_details[$index]['id'] = $detail->id;
                $this->input_details[$index]['detail_item_name'] = $detail->item_name . '-' . $detail->unit_name;
            }
            $this->countTotalAmount();
            $this->dispatchBrowserEvent('reApplySelect2');
        } else {
            $this->inputs['tr_date']  = date('Y-m-d');
            $this->inputs['tr_type']  = $this->trType;
        }
    }

    public function render()
    {
        return view('livewire.transaction.purchases-orders.edit');
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
        'input_details.*.price' => 'Item Price',
    ];

    public function refreshSupplier()
    {
        $suppliersData = Partner::GetByGrp('SUPP');
        $this->suppliers = $suppliersData->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->id,
            ];
        })->toArray();
        $this->inputs['partner_id'] = null;
    }

    public function refreshPayment()
    {
        $paymentsData = Payment::All();
        $this->payments = $paymentsData->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->id,
            ];
        })->toArray();
        $this->inputs['payment_term_id'] = null;
    }

    protected function populateDropdowns()
    {
        $this->refreshSupplier();
        $this->refreshPayment();
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
        // if ($duplicated == false) {
        //     $itemUnit = ItemUnit::findorFail($value);
        //     $this->input_details[$param[1]]['item_unit_id'] = $itemUnit->id;
        //     $this->input_details[$param[1]]['detail_item_name'] = $itemUnit->item->name . '-' . $itemUnit->from_unit->name;
        //     $this->input_details[$param[1]]['item_name'] = $itemUnit->item->name;
        //     $this->input_details[$param[1]]['unit_name'] = $itemUnit->from_unit->name;
        //     $this->input_details[$param[1]]['qty'] = 1;
        //     $indexOfInputs = count($this->input_details) - 1;

        //     if ($index ==  $indexOfInputs) {
        //         $this->addDetails();
        //     }
        // } else {
        //     $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' =>  "Produk dan satuan telah dibuat sebelumnya, mohon dicek kembali!"]);
        //     $this->dispatchBrowserEvent('reApplySelect2');
        // }
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

    public function validateForm()
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

    public function validateLogic()
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

    public function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
            $this->reset('input_details');
            $this->populateDropdowns();
            $this->total_amount = 0;
            $this->inputs['tr_date']  = date('Y-m-d');
            $this->inputs['tr_type']  = $this->trType;
        }elseif ($this->actionValue == 'Edit') {
            $this->VersionNumber = $this->object->version_number;
        }
    }

    public function Submit()
    {
        if ($this->object->status_code === "ACT") {
            return redirect()->route('purchases_deliveries.detail', ['action' => encryptWithSessionKey('Create'), 'objectId' => encryptWithSessionKey($this->object->id)]);
        }else{
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Nota tidak bisa dibuat karena status selesai!']);
        }
    }

    public function Print()
    {
        if ($this->object->status_code === "ACT") {
            return redirect()->route('purchases_orders.printpdf', ['objectId' => encryptWithSessionKey($this->object->id)]);
        }else{
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Nota tidak bisa dibuat karena status selesai!']);
        }
    }

    public function Save()
    {
        $this->validateForm();
        DB::beginTransaction();

        try {
            $this->validateLogic();

            if ($this->actionValue == 'Create') {
                $this->object = OrderHdr::create($this->inputs);
            } elseif ($this->actionValue == 'Edit') {
                if ($this->object) {
                    $this->object->updateObject($this->VersionNumber);
                    $this->object->update($this->inputs);
                }
            }

            $this->saveDetails();

            DB::commit();
            $this->resetForm();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.string.save', ['object' => "Nota Beli"])
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.save', ['object' => "Nota Beli", 'message' => $e->getMessage()])
            ]);
        }
    }

    private function saveDetails()
    {
        foreach ($this->deletedItems as $deletedId) {
            OrderDtl::where('id', $deletedId)->delete();
        }
        $this->deletedItems = [];

        foreach ($this->input_details as $inputDetail) {
            if (isset($inputDetail['id']) && $this->actionValue === 'Edit') {
                $detail = OrderDtl::find($inputDetail['id']);
                $detail->update($inputDetail);
            } else {
                $inputDetail['trhdr_id'] = $this->object->id;
                $inputDetail['qty_reff'] = $inputDetail['qty'];
                OrderDtl::create($inputDetail);
            }
        }
    }
}
