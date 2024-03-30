<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Transaction\OrderDtl;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Support\Facades\Crypt;
use App\Models\TrdJewel1\Master\Material;
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
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "SO";

    public $matl_action = 'Create';
    public $matl_objectId = null;

    public $materialDialogVisible = false;
    public $returnIds = [];

    protected function onLoad()
    {
        $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
        $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->orderBy('tr_seq')->get();
        $this->inputs = populateArrayFromModel($this->object);
        if ($this->object) {
            $this->returnIds = $this->object->ReturnHdr->pluck('id')->toArray();
        }

        foreach ($this->object_detail as $key => $detail) {
            $this->input_details[$key] =  populateArrayFromModel($detail);
            $this->input_details[$key]['id'] = $detail->id;
            $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
            $this->input_details[$key]['qty'] = ceil(currencyToNumeric($detail->qty));
            $this->input_details[$key]['amt'] = ceil(currencyToNumeric($detail->amt));
            $this->input_details[$key]['selling_price'] =int_qty( $detail->Material->jwl_selling_price) ?? 0;
            $this->input_details[$key]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
            $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode;
            $this->input_details[$key]['image_path'] = $detail->Material->Attachment[0]->getUrl();
        }
        $this->countTotalAmount();
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem',
        'materialSaved' => 'materialSaved'
    ];


    public function refreshSupplier()
    {
        $suppliersData = Partner::GetByGrp(Partner::SUPPLIER);
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

        $application = Partner::find($this->inputs['partner_id']);
        $this->inputs['partner_code'] = $application->code;
        $this->object->fill($this->inputs);
        $this->object->save();

        foreach ($this->input_details as $index => $inputDetail) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new OrderDtl();
            }
            $inputDetail['tr_seq'] = $index + 1;
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

    public function addDetails($material_id = null)
    {
        $detail = [
            'tr_type' => $this->trType,
        ];
        $material = Material::find($material_id);
        if ($material) {
            $detail['matl_id'] = $material->id;
            $detail['matl_code'] = $material->code;
            $detail['matl_descr'] = $material->descr ?? "";
            $detail['matl_uom'] = $material->MatlUom[0]->id;
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
        $this->countTotalAmount();
    }

    public function Add()
    {
        $this->emit('materialSaved', 211);
        $this->emit('materialSaved', 212);
        $this->emit('materialSaved', 213);
        $this->emit('materialSaved', 214);
        $this->emit('materialSaved', 215);
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
}
