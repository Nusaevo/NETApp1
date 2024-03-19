<?php

namespace App\Http\Livewire\Transaction\PurchasesOrders;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\Transactions\OrderHdr;
use App\Models\Transactions\OrderDtl;
use App\Models\Master\Partner;
use Illuminate\Support\Facades\Crypt;
use App\Models\Master\Material;
use Lang;
use Exception;
use DB;

class Detail extends BaseComponent
{
    public $object_detail;
    public $inputs = [];
    public $input_details = [];

    public $suppliers;
    public $payments;
    public $unit_row = 0;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "PO";

    public $materialDialogVisible = false;

    protected function onLoad()
    {
        $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
        $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->get();
        $this->inputs = populateArrayFromModel($this->object);
        foreach ($this->object_detail as $index => $detail) {
            $formattedDetail = populateArrayFromModel($detail);
            $this->input_details[$index] =  $formattedDetail;
            $this->input_details[$index]['price'] = ceil(currencyToNumeric($detail->price));
            $this->input_details[$index]['qty'] = ceil(currencyToNumeric($detail->qty));
            $this->input_details[$index]['amt'] = ceil(currencyToNumeric($detail->amt));
            $this->input_details[$index]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
            $this->input_details[$index]['matl_id'] = $detail->matl_id;
            $this->input_details[$index]['matl_id'] = $detail->matl_id;
            $this->input_details[$index]['matl_code'] = $detail->matl_code;
            $this->input_details[$index]['matl_descr'] = $detail->matl_descr;
            $this->input_details[$index]['barcode'] = $detail->Material->MatlUom[0]->barcode;
            $this->input_details[$index]['image_path'] = $detail->Material->Attachment[0]->getUrl();
            $this->unit_row++;
        }
        $this->countTotalAmount();
    }


    public function render()
    {
        return view('livewire.transaction.purchases-orders.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem',
        'materialSaved' => 'materialSaved'
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

    protected function onPopulateDropdowns()
    {
        $this->refreshSupplier();
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
            $detail['matl_descr'] = $material->descr ?? "";
            $detail['image_path'] = $material->Attachment->first() ? $material->Attachment->first()->getUrl() : null;
            $detail['barcode'] = $material->MatlUom[0]->barcode;
            $detail['price'] = int_qty($material->jwl_buying_price) ?? 0;
            $detail['selling_price'] = int_qty($material->jwl_selling_price) ?? 0;
            $detail['qty'] = 1;
            $detail['amt'] = $detail['qty'] * $detail['price'];
        }
        array_push($this->input_details, $detail);
        $newDetail = end($this->input_details);
        $this->newItems[] = $newDetail;
        $this->unit_row++;
        $this->countTotalAmount();
    }

    public function Add()
    {
        $this->emit('materialSaved', 195);
    }

    public function materialSaved($material_id)
    {
        try {
            $this->addDetails($material_id);
            $this->emit('closeMaterialDialog');
        } catch (Exception $e) {
            $this->notify('error', Lang::get('generic.error.save', ['message' => $e->getMessage()]));
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
        $this->countTotalAmount();
    }

    public function changeQty($id, $value)
    {
        if (isset($this->input_details[$id]['price'])) {
            $total = $this->input_details[$id]['price'] * $value;
            $this->input_details[$id]['amt'] = $total;
            $this->countTotalAmount();
        }
    }

    public function changePrice($id, $value)
    {
        if (isset($this->input_details[$id]['qty'])) {
            $total = $this->input_details[$id]['qty'] * $value;
            $this->input_details[$id]['amt'] = $total;
            $this->countTotalAmount();
        }
    }

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $item_id => $input_detail) {
            if (isset($input_detail['qty']) && isset($input_detail['price'])) {
                $this->total_amount += $input_detail['price'] * $input_detail['qty'];
            }
        }
        $this->inputs['amt'] = $this->total_amount;
    }

    protected function rules()
    {
        $rules = [
            'inputs.partner_id' =>  'required|integer|min:0|max:9999999999',
            'inputs.tr_date' => 'required',
            'input_details.*.price' => 'required|integer|min:0|max:9999999999',
            'input_details.*.qty' => 'required|integer|min:0|max:9999999999',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input',
        'inputs.tr_date'      => 'Tanggal Transaksi',
        'inputs.partner_id'      => 'Supplier',
        'input_details.*'              => 'Inputan Barang',
        'input_details.*.matl_id' => 'Item',
        'input_details.*.qty' => 'Item Qty',
        'input_details.*.price' => 'Item Price',
    ];

    public function onValidateAndSave()
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

        $this->object->fill($this->inputs);
        $this->object->save();

        foreach ($this->input_details as $index => $inputDetail) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new OrderDtl();
            }
            $inputDetail['trhdr_id'] = $this->object->id;
            $inputDetail['qty_reff'] = $inputDetail['qty'];
            $this->object_detail[$index]->fill($inputDetail);
            $this->object_detail[$index]->save();
        }

        if (!$this->object->isNew()) {
            foreach ($this->deletedItems as $deletedItemId) {
                OrderDtl::find($deletedItemId)->delete();
            }
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->object = new OrderHdr();
        $this->object_detail = [];
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
