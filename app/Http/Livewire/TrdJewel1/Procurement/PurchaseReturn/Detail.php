<?php

namespace App\Http\Livewire\TrdJewel1\Procurement\PurchaseReturn;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdJewel1\Inventories\IvtBal;
use App\Models\TrdJewel1\Transaction\ReturnHdr;
use App\Models\TrdJewel1\Transaction\ReturnDtl;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Transaction\OrderDtl;

class Detail extends BaseComponent
{
    public $object_detail;
    public $inputs = [];
    public $input_details = [];
    public $action = 'Create';
    public $suppliers;
    public $warehouses;
    public $trType = "PR";


    public $reff;
    public $reffDetail;

    protected function onLoad()
    {
        $warehousesData = ConfigConst::GetWarehouse();

        $this->warehouses = $warehousesData->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->id,
            ];
        })->toArray();

        if (($this->actionValue === 'Create')) {
            $this->reff = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->reffDetail = OrderDtl::GetByOrderHdr($this->reff->id)->get();
            $this->inputs = populateArrayFromModel($this->reff);
            $this->inputs['tr_type'] = $this->trType;
            $this->inputs['tr_id'] = $this->reff->id;
            foreach ($this->reffDetail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['order_qty'] = ceil(currencyToNumeric($detail->qty));
                $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$key]['qty'] = ceil(currencyToNumeric($detail->qty));
                $this->input_details[$key]['amt'] = ceil(currencyToNumeric($detail->amt));
                $this->input_details[$key]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
                // $this->input_details[$key]['warehouse_id'] =  $this->warehouses[0]['value'];
                $this->input_details[$key]['reffdtl_id'] = $detail->id;
                $this->input_details[$key]['reffhdrtr_type'] = $detail->tr_type;
                $this->input_details[$key]['reffhdrtr_id'] =  $this->reff->id;
                $this->input_details[$key]['reffdtltr_seq'] = $detail->tr_seq;
                $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode;
                $this->input_details[$key]['image_path'] = $detail->Material->Attachment[0]->getUrl();
            }
        }else{
            $this->object = ReturnHdr::withTrashed()->find($this->objectIdValue);
            $this->object_detail = ReturnDtl::GetByOrderHdr($this->object->id)->orderBy('tr_seq')->get();
            $this->inputs = populateArrayFromModel($this->object);
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
        }
    }

    public function render()
    {
        return view($this->renderRoute);
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
            'input_details.*.matl_id' => 'required',
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
        'input_details.*'              => 'Inputan Barang',
        'input_details.*.order_qty' => 'Item Qty',
        'input_details.*.qty' => 'Qty Retur',
        // 'input_details.*.price' => 'Item Price',
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

    public function onValidateAndSave()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        $this->inputs['partner_code'] = $partner->code;
        $this->object->fill($this->inputs);
        $this->object->save();

        foreach ($this->input_details as $index => $inputDetail) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new ReturnDtl();
            }
            // $item_warehouse = IvtBal::FindItemWarehouse($inputDetail['matl_id'], $inputDetail['warehouse_id'])->first();
            // $inputDetail['ivt_id'] = $item_warehouse->id;
            $inputDetail['trhdr_id'] =  $this->object->id;
            $inputDetail['qty_reff'] = $inputDetail['qty'];
            $this->object_detail[$index]->fill($inputDetail);
            $this->object_detail[$index]->save();
        }

        if (($this->actionValue === 'Create')) {
            return redirect()->route('TrdJewel1.Procurement.PurchaseOrder.Detail', ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($this->reff->id)]);
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->object = new ReturnHdr();
        $this->input_details = [];
        $this->object_detail = [];
        $this->refreshSupplier();
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['tr_id']  = $this->object->id;
    }
}
