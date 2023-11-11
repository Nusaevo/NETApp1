<?php

namespace App\Http\Livewire\Transactions\PurchasesDeliveries;

use Livewire\Component;
use App\Models\DelivHdr;
use App\Models\DelivDtl;
use App\Models\OrderHdr;
use App\Models\OrderDtl;
use App\Models\ItemUnit;
use App\Models\Partner;
use App\Models\Warehouse;
use App\Models\ItemWarehouse;
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
    public $warehouses;

    public $unit_row = 0;

    public $trType = "PD";
    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        $this->refreshSupplier();

        $warehousesData = Warehouse::orderByName()->get();
        $this->warehouses = $warehousesData->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->id,
            ];
        })->toArray();

        if (($this->action === 'Edit' || $this->action === 'View' || $this->objectId != null) && $this->objectId) {
            $this->object = OrderHdr::withTrashed()->find($this->objectId);
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->get();
            $this->status = $this->object->status_code;
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['tr_date']  = date('Y-m-d');
            $this->inputs['tr_type']  = $this->trType;
            $this->inputs['tr_id']  = $this->object->id;
            foreach ($this->object_detail as $index => $detail) {
                $this->input_details[$index] = populateArrayFromModel($detail);
                $this->input_details[$index]['id'] = $detail->id;
                $this->input_details[$index]['order_qty'] = round($detail->qty, 0);
                $this->input_details[$index]['qty'] = round($detail->qty_reff, 0);
                $this->input_details[$index]['amt'] = round($detail->amt, 0);
                $this->input_details[$index]['detail_item_name'] = $detail->item_name . '-' . $detail->unit_name;
                $this->input_details[$index]['warehouse_id'] = 1;
                $this->input_details[$index]['reffdtl_id'] = $detail->id;
                $this->input_details[$index]['reffhdrtr_type'] = $detail->tr_type;
                $this->input_details[$index]['reffhdrtr_id'] =  $this->object->id;
                $this->input_details[$index]['reffdtltr_seq'] = $detail->tr_seq;
                $this->input_details[$index]['item_name'] =  $detail->item_name;
                $this->input_details[$index]['unit_name'] = $detail->unit_name;
            }
            $this->dispatchBrowserEvent('reApplySelect2');
        }
    }

    public function render()
    {
        return view('livewire.transactions.purchases-deliveries.edit');
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
            'input_details.*.qty' => [
                'required',
                'integer',
                'min:0',
                'max:9999999999',
            ],
            'input_details.*.order_qty' => 'required|integer|min:0|max:9999999999',
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
        'input_details.*.order_qty' => 'Item Qty',
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

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        return $objectData;
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

    }

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

            $objectData = $this->populateObjectArray();
            $objectData['status_code'] = $status_code;
            $DlvHdrObject = DelivHdr::create($objectData);

            foreach ($this->input_details as $inputDetail) {
                $item_warehouse = ItemWarehouse::FindItemWarehouse($inputDetail['item_unit_id'], $inputDetail['warehouse_id'])->first();

                $inputDetail['warehouse_name'] = $item_warehouse->warehouse->name;
                $inputDetail['item_warehouse_id	'] = $DlvHdrObject->id;
                $inputDetail['trhdr_id'] = $DlvHdrObject->id;
                $inputDetail['trhdr_id'] = $DlvHdrObject->id;
                $inputDetail['qty_reff'] = $inputDetail['qty'];
                DelivDtl::create($inputDetail);
            }

            DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get("generic.success.$messageAction", ['object' => "PD"])
            ]);
            return redirect()->route('purchases_deliveries.index');
        } catch (Exception $e) {
            DB::rollBack();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get("generic.error.$messageAction", ['object' => "PD", 'message' => $e->getMessage()])
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
}
