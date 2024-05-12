<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Transaction\OrderDtl;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\Crypt;
use App\Models\TrdJewel1\Master\Material;
use App\Enums\Status;
use App\Models\TrdJewel1\Transaction\BillingDtl;
use App\Models\TrdJewel1\Transaction\BillingHdr;
use App\Models\TrdJewel1\Transaction\DelivDtl;
use App\Models\TrdJewel1\Transaction\DelivHdr;
use Lang;
use Exception;
use DB;

use function PHPUnit\Framework\throwException;

class Detail extends BaseComponent
{
    public $object_detail;
    public $inputs = [];
    public $input_details = [];

    public $partners;
    public $warehouses;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "SO";
    public $delivTrType = "SD";
    public $billingTrType = "ARB";

    public $matl_action = 'Create';
    public $matl_objectId = null;

    public $materialDialogVisible = false;
    public $returnIds = [];
    public $barcode = '';

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
        $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->orderBy('tr_seq')->get();
        $this->inputs = populateArrayFromModel($this->object);
        // if ($this->object) {
        //     $this->returnIds = $this->object->ReturnHdr->pluck('id')->toArray();
        // }
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
        'materialSaved' => 'materialSaved',
        'delete' => 'delete'
    ];


    public function refreshPartner()
    {
        $partnersdata = Partner::GetByGrp(Partner::CUSTOMER);
        $this->partners = $partnersdata->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->id,
            ];
        })->toArray();

        $this->inputs['partner_id'] = null;
    }

    public function refreshWarehouses()
    {
        $data = ConfigConst::where('app_code', $this->appCode)
            ->where('const_group', 'WAREHOUSE_LOC')
            ->orderBy('seq')
            ->get();
        $this->warehouses = $data->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->id,
            ];
        })->toArray();
        $this->inputs['wh_code'] = 18;
    }


    protected function onPopulateDropdowns()
    {
        $this->refreshPartner();
        $this->refreshWarehouses();
    }

    protected function rules()
    {
        $rules = [
            'inputs.partner_id' =>  'required|integer|min:0|max:9999999999',
            'inputs.wh_code' =>  'required|integer|min:0|max:9999999999',
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
        'inputs.wh_code'      => 'Warehouse',
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
        if($this->actionValue == 'Edit')
        {
            if(!$this->object->isEnableToEdit())
            {
                throw new Exception("Nota ini tidak bisa di edit lagi.");
            }
        }
        $application = Partner::find($this->inputs['partner_id']);
        $this->inputs['partner_code'] = $application->code;
        $this->inputs['status_code'] = STATUS::OPEN;
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
        $delivHdr = DelivHdr::firstOrNew(['tr_id' => $this->object->tr_id,'tr_type' => $this->delivTrType]);
        $delivHdr->fillAndSanitize([
            'tr_id' => $this->object->tr_id,
            'tr_type' =>  $this->delivTrType,
            'tr_date' => $this->object->tr_date,
            'reff_code' => $this->object->reff_code,
            'partner_id' => $this->object->partner_id,
            'partner_code' => $this->object->partner_code,
            'deliv_by' => $this->inputs['deliv_by'] ?? '',
            'status_code' => $this->object->status_code,
        ]);
        $delivHdr->save();

        $billingHdr = BillingHdr::firstOrNew(['tr_id' => $this->object->tr_id,'tr_type' =>  $this->billingTrType]);
        $billingHdr->fillAndSanitize([
            'tr_id' => $this->object->tr_id,
            'tr_type' => $this->billingTrType,
            'tr_date' => $this->object->tr_date,
            'reff_code' => $this->object->reff_code,
            'partner_id' => $this->object->partner_id,
            'partner_code' => $this->object->partner_code,
            'payment_term_id' => 1,
            'payment_term' => '',
            'payment_due_days' => 0,
            'status_code' => $this->object->status_code,
        ]);
        $billingHdr->save();

        foreach ($this->input_details as $index => $inputDetail) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new OrderDtl();
            }
            $inputDetail['tr_id'] = $this->object->tr_id;
            $inputDetail['tr_seq'] = $index + 1;
            $inputDetail['trhdr_id'] = $this->object->id;
            $inputDetail['qty_reff'] = $inputDetail['qty'];
            $this->object_detail[$index]->fillAndSanitize($inputDetail);
            $this->object_detail[$index]->save();

            $delivDtl = DelivDtl::firstOrNew([
                'trhdr_id' =>  $this->object_detail[$index]->trhdr_id,
                'tr_seq' =>  $this->object_detail[$index]->tr_seq,
                'tr_type' => $this->delivTrType,
            ]);
            $delivDtl->fillAndSanitize([
                'trhdr_id' =>  $this->object_detail[$index]->trhdr_id,
                'tr_type' =>  $this->delivTrType,
                'tr_id' =>  $this->object->tr_id,
                'tr_seq' =>  $this->object_detail[$index]->tr_seq,
                'reffdtl_id' =>  $this->object_detail[$index]->id,
                'reffhdrtr_type' =>  $this->object_detail[$index]->tr_type,
                'reffhdrtr_id' =>  $this->object->tr_id,
                'reffdtltr_seq' =>  $this->object_detail[$index]->tr_seq,
                'matl_id' =>  $this->object_detail[$index]->matl_id,
                'matl_code' =>  $this->object_detail[$index]->matl_code,
                'matl_descr' =>  $this->object_detail[$index]->matl_descr,
                'wh_code' =>   $this->inputs['wh_code'],
                'qty' =>  $this->object_detail[$index]->qty,
                'qty_reff' =>  $this->object_detail[$index]->qty_reff,
                'status_code' =>  $this->object_detail[$index]->status_code,
            ]);
            $delivDtl->save();
            $billingDtl = BillingDtl::firstOrNew([
                'trhdr_id' => $delivDtl->trhdr_id,
                'tr_seq' => $delivDtl->tr_seq,
                'tr_type' => $this->billingTrType,
            ]);
            $billingDtl->fillAndSanitize([
                'trhdr_id' => $delivDtl->trhdr_id,
                'tr_type' => $this->billingTrType,
                'tr_id' => $delivDtl->tr_id,
                'tr_seq' => $delivDtl->tr_seq,
                'dlvdtl_id' => $delivDtl->id,
                'dlvhdrtr_type' => $delivDtl->tr_type,
                'dlvhdrtr_id' => $delivDtl->tr_id,
                'dlvdtltr_seq' => $delivDtl->tr_seq,
                'matl_id' => $delivDtl->matl_id,
                'matl_code' => $delivDtl->matl_code,
                'matl_uom' => $this->object_detail[$index]->matl_uom,
                'descr' => '',
                'qty' => $delivDtl->qty,
                'qty_uom' => '',
                'qty_base' => $delivDtl->qty,
                'price' =>  $this->object_detail[$index]->price,
                'price_uom' => '',
                'price_base' =>  $this->object_detail[$index]->trhdr_id,
                'amt' =>  $this->object_detail[$index]->amt,
                'amt_reff' =>  $this->object_detail[$index]->amt,
                'status_code' =>  $this->object_detail[$index]->status_code,
            ]);
            $billingDtl->save();
        }

        if (!$this->object->isNew()) {
            foreach ($this->deletedItems as $deletedItemId) {
                $this->object_detail::find($deletedItemId)->delete();
            }
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->object = new OrderHdr();
        $this->object_detail = [];
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


    public function delete()
    {
        try {
            if(!$this->object->isEnableToEdit())
            {
                throw new Exception("Nota ini tidak bisa di edit lagi.");
            }
            $this->updateVersionNumber();
            if (isset($this->object->status_code)) {
                    $this->object->status_code =  Status::DEACTIVATED;
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

    public function createReturn()
    {
        if (!$this->object->isEnableToEdit()) {
            throw new Exception("Nota ini tidak bisa di edit lagi.");
        }

        return redirect()->route('TrdJewel1.Transaction.SalesReturn.Detail', [
            'action' => encryptWithSessionKey('Create'),
            'objectId' => encryptWithSessionKey($this->object->id)
        ]);
    }

    public function scanBarcode()
    {
    //    $itemBarcode = ItemUnit::where('barcode', $this->barcode)->first();
    //         if (isset($itemBarcode)) {
    //             $this->addDetails();
    //             $this->changeItem($itemBarcode->id, is_null($this->input_details) ? 0 : count($this->input_details) - 1, "true");
    //         } else {
    //             $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' =>  "Kode barang tidak ditemukan, mohon reset dan scan kembali!"]);
    //         }

    //     $this->dispatchBrowserEvent('barcode-processed');
    //     $this->barcode = '';
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
