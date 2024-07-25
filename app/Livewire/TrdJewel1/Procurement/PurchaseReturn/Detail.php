<?php

namespace App\Livewire\TrdJewel1\Procurement\PurchaseReturn;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdJewel1\Inventories\IvtBal;
use App\Models\TrdJewel1\Transaction\ReturnHdr;
use App\Models\TrdJewel1\Transaction\ReturnDtl;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Transaction\OrderDtl;
use Exception;
use App\Enums\Status;
use Illuminate\Support\Facades\Lang;

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

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $warehousesData = ConfigConst::GetWarehouse();

        $this->warehouses = $warehousesData->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->id,
            ];
        })->toArray();

        if (($this->actionValue === 'Create')) {
            if(isset($this->objectIdValue))
            {
                $this->reff = OrderHdr::withTrashed()->find($this->objectIdValue);
                $this->reffDetail = OrderDtl::GetByOrderHdr($this->reff->id)->get();
                $this->reff->tr_id = NULL;
                $this->inputs = populateArrayFromModel($this->reff);
                $this->inputs['tr_type'] = $this->trType;
                foreach ($this->reffDetail as $key => $detail) {
                    $detail->tr_id = NULL;
                    $this->input_details[$key] = populateArrayFromModel($detail);
                    $this->input_details[$key]['id'] = $detail->id;
                    $this->input_details[$key]['order_qty'] = ceil(currencyToNumeric($detail->qty_reff));
                    $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
                    $this->input_details[$key]['qty'] = 0;
                    $this->input_details[$key]['amt'] = ceil(currencyToNumeric($detail->amt));
                    $this->input_details[$key]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
                    // $this->input_details[$key]['warehouse_id'] =  $this->warehouses[0]['value'];
                    $this->input_details[$key]['dlvdtl_id'] = $detail->id;
                    $this->input_details[$key]['dlvhdrtr_type'] = $detail->tr_type;
                    $this->input_details[$key]['dlvhdrtr_id'] =  $this->reff->id;
                    $this->input_details[$key]['dlvdtltr_seq'] = $detail->tr_seq;
                    $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode;
                    $this->input_details[$key]['image_path'] = $detail->Material->Attachment[0]->getUrl();
                }
            }
        }else{
            $this->actionValue = "View";
            $this->object = ReturnHdr::withTrashed()->find($this->objectIdValue);
            $this->object_detail = ReturnDtl::GetByOrderHdr($this->object->id)->orderBy('tr_seq')->get();
            $this->inputs = populateArrayFromModel($this->object);
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$key]['order_qty'] = ceil(currencyToNumeric($detail->OrderDtl->qty));
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
        'changeItem'  => 'changeItem',
        'delete'  => 'delete'
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
                'label' =>  $data->code." - ".$data->name,
                'value' => $data->id,
            ];
        })->toArray();

        $this->inputs['partner_id'] = "";
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshSupplier();
    }

    public function onValidateAndSave()
    {
        $totalReturnQty = 0;
        foreach ($this->input_details as $index => $inputDetail) {
            $totalReturnQty += $inputDetail['qty'];

            if ($inputDetail['qty'] > $inputDetail['order_qty']) {
                throw new Exception("Jumlah kuantitas untuk barang {$inputDetail['matl_descr']} melebihi kuantitas pesanan.");
            }
        }
        if ($totalReturnQty === 0) {
            throw new Exception("Tolong input qty pada retur setidaknya 1 item.");
        }

        $partner = Partner::find($this->inputs['partner_id']);
        $this->inputs['partner_code'] = $partner->code;
        $this->inputs['status_code'] = STATUS::OPEN;
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();

        foreach ($this->input_details as $index => $inputDetail) {
            if ($inputDetail['qty'] > 0) {
                if (!isset($this->object_detail[$index])) {
                    $this->object_detail[$index] = new ReturnDtl();
                }
                // $item_warehouse = IvtBal::FindItemWarehouse($inputDetail['matl_id'], $inputDetail['warehouse_id'])->first();
                // $inputDetail['ivt_id'] = $item_warehouse->id;
                $inputDetail['trhdr_id'] =  $this->object->id;
                $inputDetail['qty_reff'] = $inputDetail['qty'];
                $this->object_detail[$index]->fillAndSanitize($inputDetail);
                $this->object_detail[$index]->save();
            }
        }

        if (($this->actionValue === 'Create')) {
            return redirect()->route('TrdJewel1.Procurement.PurchaseReturn');
        }
    }

    public function delete()
    {
        try {
            $this->updateVersionNumber();
            if (isset($this->object->status_code)) {
                    $this->object->status_code =  Status::NONACTIVE;
                }
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.string.disable';
            $this->object->save();
            $this->notify('success', Lang::get($messageKey));
        } catch (Exception $e) {
            $this->notify('error',Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

          return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
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
    }

    public function Add()
    {
    }
}
