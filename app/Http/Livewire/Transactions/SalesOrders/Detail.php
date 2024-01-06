<?php

namespace App\Http\Livewire\Transactions\SalesOrders;

use Livewire\Component;
use App\Models\OrderHdr;
use App\Models\OrderDtl;
use App\Models\ItemUnit;
use App\Models\Partner;
use App\Models\Payment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use App\Models\Unit;
use App\Models\Material;
use App\Models\Settings\ConfigConst;
use App\Models\IvtBal;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $material;
    public $object_detail;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $inputs = [];
    public $input_details = [];
    public $materials = [];
    public $material_details = [];
    public $status = '';

    public $suppliers;
    public $payments;

    public $unit_row = 0;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "PO";


    public $actionValue = 'Create';
    public $objectIdValue;

    public $materialDialogVisible = false;
    public function mount($action, $objectId = null)
    {
        $this->actionValue = Crypt::decryptString($action);
        $this->refreshSupplier();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $this->objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->get();
            $this->status = $this->object->status_code;
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            foreach ($this->object_detail as $index => $detail) {
                $this->input_details[$index] = populateArrayFromModel($detail);
                $this->input_details[$index]['id'] = $detail->id;
                $this->input_details[$index]['price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$index]['qty'] = ceil(currencyToNumeric($detail->qty));
                $this->input_details[$index]['amt'] = ceil(currencyToNumeric($detail->amt));
                $this->input_details[$index]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
                $this->input_details[$index]['matl_id'] = $detail->matl_id;
                $this->input_details[$index]['matl_id'] = $detail->matl_id;
                $this->input_details[$index]['matl_code'] = $detail->matl_code;
                $this->input_details[$index]['matl_descr'] = $detail->matl_descr;
                $this->input_details[$index]['barcode'] = $detail->materials->uoms[0]->barcode;
                $this->input_details[$index]['image_path'] = $detail->materials->attachments[0]->path;
            }
            $this->countTotalAmount();
            $this->dispatchBrowserEvent('reApplySelect2');
        } else {

            $this->material = new Material();
            $this->object = new OrderHdr();
            $this->inputs['tr_date']  = date('Y-m-d');
            $this->inputs['tr_type']  = $this->trType;
        }
    }

    public function render()
    {
        return view('livewire.transactions.sales-orders.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem',
        'materialCreated' => 'materialCreated',
        'materialUpdated' => 'materialUpdated'
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

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        return $objectData;
    }

    protected function populateItemArray()
    {
        $objectData =  populateModelFromForm($this->material, $this->materials);
        return $objectData;
    }

    public function addDetails($material_id = null)
    {
        $detail = [
            'tr_seq' => $this->unit_row + 1,
            'tr_type' => $this->trType,
        ];

        $material = Material::find($material_id);

        if ($material) {
            $detail['matl_id'] = $material->id;
            $detail['matl_code'] = $material->code;
            $detail['matl_descr'] = $material->descr;
            $detail['image_path'] = $material->attachments[0]->path;
            $detail['barcode'] = $material->uoms[0]->barcode;
            $detail['price'] = $material->jwl_buying_price ?? 0;
        }
        array_push($this->input_details, $detail);

        $newDetail = end($this->input_details);
        $this->newItems[] = $newDetail;
        $this->unit_row++;
        $this->dispatchBrowserEvent('reApplySelect2');
    }

    public function materialCreated($material_id)
    {
        DB::beginTransaction();
        try {
            $this->addDetails($material_id);
            $this->emit('closeMaterialDialog');
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "Material", 'message' => $e->getMessage()])
            ]);
        }
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
            $material = Material::findorFail($value);
            $this->input_details[$param[1]]['matl_id'] = $material->id;
            $this->input_details[$param[1]]['matl_code'] = $material->code;
            $this->input_details[$param[1]]['matl_descr'] = $material->descr;
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

    // public function changePrice($id, $value)
    // {
    //     if (isset($this->input_details[$id]['qty'])) {
    //         $total = $this->input_details[$id]['qty'] * $value;
    //         $this->input_details[$id]['amt'] = $total;
    //         $this->input_details[$id]['sub_total'] = rupiah($total);
    //         $this->countTotalAmount();
    //         $this->dispatchBrowserEvent('reApplySelect2');
    //     }
    // }

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $item_id => $input_details) {
            if (isset($input_details['matl_id'])) {
                if (isset($input_details['qty']) && isset($input_details['price']))
                    $this->total_amount += $input_details['price'] * $input_details['qty'];
            }
        }
        $this->inputs['amt']  =  $this->total_amount;
    }

    public function validateOrders()
    {
        $rules = [
            'inputs.tr_date' => 'required',
            'input_details.*.matl_id' => 'required',
            'input_details.*.qty' => 'required|integer|min:0|max:9999999999',
        ];
        $attributes = [
            'inputs'                => 'Input',
            'inputs.*'              => 'Input',
            'inputs.tr_date'      => 'Tanggal Transaksi',
            'inputs.partner_id'      => 'Supplier',
            'input_details.*'              => 'Inputan Barang',
            'input_details.*.matl_id' => 'Item',
            'input_details.*.qty' => 'Item Qty',
            'input_details.*.price' => 'Item Price',
        ];

        try {
            $this->validate($rules, $attributes);
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
        $messageAction = $this->actionValue === 'Create' ? 'create' : 'update';
        // Run validations first
        $this->validateOrders();

        // Wrap operations in a transaction for atomicity
        DB::beginTransaction();

        try {
            // Validate if units are correct
            $this->validateUnits();

            // Prepare the data array for creating/updating the OrderHdr
            $objectData = $this->populateObjectArray();
            $objectData['status_code'] = $status_code;

            // Create or Update the OrderHdr based on the action
            if ($this->actionValue === 'Create') {
                $this->object = OrderHdr::create($objectData);
            } elseif ($this->actionValue === 'Edit') {
                $this->object->update($objectData);
            }

            // Handle the details of the order
            foreach ($this->input_details as $inputDetail) {
                if (isset($inputDetail['id']) && $this->action === 'Edit') {
                    $detail = OrderDtl::find($inputDetail['id']);
                    $detail->update($inputDetail);
                } else {
                    $inputDetail['trhdr_id'] = $this->object->id;
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

            if ($this->actionValue === 'Create') {
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
        $this->total_amount = 0;
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
    }

    public function openModal()
    {
        $this->materialDialogVisible = true;
    }

    public function closeModal()
    {
        $this->materialDialogVisible = false;
    }
}
