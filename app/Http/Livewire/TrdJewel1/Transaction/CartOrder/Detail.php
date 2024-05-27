<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\CartOrder;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\CartHdr;
use App\Models\TrdJewel1\Transaction\CartDtl;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdJewel1\Master\Material;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use Carbon\Carbon;
use DB;
use Exception;
use Lang;


class Detail extends BaseComponent
{
    public $trType = "SO";

    public $object_detail;
    public $inputs = [];
    public $input_details = [];

    public $inputsearches = [];
    public $materials = [];
    public $currencyRate = 0;

    public $suppliers;
    public $warehouses;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $showModal = false;
    public $currency = [];

    public $returnIds = [];

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'input_details.*'              => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('price'),
        ];
        $this->customRules  = [
            'input_details.*.price' => 'required',
            'input_details.*.qty' => 'required',
        ];
        $usercode = Auth::check() ? Auth::user()->code : '';

        if ($this->actionValue === 'Create') {
            $this->object = CartHdr::withTrashed()->where('created_by', $usercode)->first();
        }

        if ($this->object) {
            $this->object_detail = CartDtl::GetByCartHdr($this->object->id)->orderBy('tr_seq')->get();
            $this->inputs = populateArrayFromModel($this->object);

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                $this->input_details[$key]['checked'] = 1;
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$key]['qty'] = ceil(currencyToNumeric($detail->qty));
                $this->input_details[$key]['amt'] = ceil(currencyToNumeric($detail->amt));
                $this->input_details[$key]['name'] = $detail->Material->name ?? "";
                $this->input_details[$key]['matl_descr'] = $detail->Material->descr ?? "";
                $this->input_details[$key]['selling_price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$key]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
                $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode;
                $imagePath = $detail->Material->Attachment->first()?->getUrl() ?? null;
                $this->input_details[$key]['image_path'] = $imagePath;
            }

            $this->countTotalAmount();
        }
    }

    protected function onLoadForEdit()
    {
    }


    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'materialSaved' => 'materialSaved',
        'delete' => 'delete'
    ];

    protected function onPopulateDropdowns()
    {
    }

    public function onCheck()
    {

    }

    public function deleteDetailObject()
    {
        foreach($this->deletedItems as $deletedItem)
        {
            $this->object_detail->where('id', $deletedItem)->firstOrFail()->delete();
        }
    }

    public function onValidateAndSave()
    {
        $this->deleteDetailObject();
    }
    public function Checkout()
    {
        $selectedItems = array_filter($this->input_details, function ($item) {
            return $item['checked'] == 1;
        });

        foreach($selectedItems as &$selectedItem)
        {
            $selectedItem['price'] = $selectedItem['selling_price'];
            $selectedItem['amt'] = $selectedItem['selling_price'];
            $this->deletedItems[] = $selectedItem['id'];
        }

        $order_header = new OrderHdr();
        $this->inputs['wh_code'] = 18;
        $this->inputs['status_code'] = STATUS::OPEN;
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = "SO";
        $order_header->saveOrder($this->appCode, $this->trType, $this->inputs, $selectedItems, [], false);

        $this->deleteDetailObject();

        return redirect()->route('TrdJewel1.Transaction.SalesOrder.Detail', [
            'action' => encryptWithSessionKey('Edit'),
            'objectId' => encryptWithSessionKey($order_header->id)
        ]);
    }

    public function onReset()
    {
    }

    public function addDetails($material_id = null)
    {
        $this->showModal = true;
        $this->dispatchBrowserEvent('toggle-modal');
        $detail = [
            'tr_type' => $this->trType,
        ];
        $material = Material::find($material_id);
        if ($material) {
            $detail['matl_id'] = $material->id;
            $detail['matl_code'] = $material->code;
            $detail['matl_descr'] = $material->descr ?? "";
            $detail['name'] = $material->name ?? "";
            $detail['matl_uom'] = $material->MatlUom[0]->id;
            $detail['image_path'] = $material->Attachment->first() ? $material->Attachment->first()->getUrl() : null;
            $detail['barcode'] = $material->MatlUom[0]->barcode;
            $detail['price'] = currencyToNumeric($material->jwl_buying_price) ?? 0;
            $detail['selling_price'] = currencyToNumeric($material->jwl_selling_price) ?? 0;
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
    }

    public function materialSaved($material_id)
    {

    }


    public function deleteDetails($index)
    {
        if (isset($this->input_details[$index]['id'])) {
            $this->deletedItems[] = $this->input_details[$index]['id'];
        }
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
        $this->SaveWithoutNotification();
    }

    public function changePrice($id, $value)
    {
        if (isset($this->input_details[$id]['qty'])) {
            $total = toNumberFormatter($this->input_details[$id]['qty']) * toNumberFormatter($value);
            $this->input_details[$id]['amt'] = numberFormat($total) ;
            $this->countTotalAmount();
            $this->SaveWithoutNotification();
        }
    }

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $item_id => $input_detail) {
            if (isset($input_detail['qty']) && isset($input_detail['selling_price'])) {
                $this->total_amount += toNumberFormatter($input_detail['selling_price']) * toNumberFormatter($input_detail['qty']);
            }
        }
        $this->inputs['amt'] = numberFormat($this->total_amount);
    }

    public function search()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->notify('warning', Lang::get('generic.string.currency_needed'));
            return;
        }

        $query = Material::query();
        if (!empty($this->inputsearches['name'])) {
            $query->where('name', 'like', '%' . $this->inputsearches['name'] . '%');
        }
        if (!empty($this->inputsearches['description'])) {
            $query->where('descr', 'like', '%' . $this->inputsearches['description'] . '%');
        }
        if (!empty($this->inputsearches['selling_price1']) && !empty($this->inputsearches['selling_price2'])) {
            $query->whereBetween('jwl_selling_price', [$this->inputsearches['selling_price1'], $this->inputsearches['selling_price2']]);
        }
        if (!empty($this->inputsearches['code'])) {
            $query->where('code', 'like', '%' . $this->inputsearches['code'] . '%');
        }

        $this->materials = $query->get();
    }

    public function addToCart($material_id, $material_code)
    {
        if ($this->currencyRate == 0) {
            $this->notify('warning', Lang::get('generic.string.currency_needed'));
            return;
        }
        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            // Find the material by ID
            $material = Material::find($material_id);
            if (!$material) {
                DB::rollback();
                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'error',
                    'message' => 'Material not found'
                ]);
                return;
            }

            // Calculate the price
            $price = currencyToNumeric($material->jwl_selling_price) * $this->currencyRate;

            // Get the cartHdr by user code and tr_type = cart
            $cartHdr = CartHdr::where('created_by', $usercode)
                ->where('tr_type', 'C')
                ->first();

            // If cartHdr doesn't exist, create a new one
            if (!$cartHdr) {
                $cartHdr = CartHdr::create([
                    'tr_type' => 'C',
                    'tr_date' => Carbon::now(),
                    'created_by' => $usercode,
                ]);
            }

            // Get the maximum tr_seq from the current order detail for this order header
            $maxTrSeq = $cartHdr->CartDtl()->max('tr_seq');

            // If there are no existing order details, set the maxTrSeq to 1
            if (!$maxTrSeq) {
                $maxTrSeq = 1;
            } else {
                // Increment the maxTrSeq by 1
                $maxTrSeq++;
            }

            // Check if the material is already added to the OrderDtl
            $existingOrderDtl = $cartHdr->CartDtl()->where('matl_id', $material_id)->first();

            // If OrderDtl doesn't exist for the material, create a new one
            if (!$existingOrderDtl) {

                $cartHdr->CartDtl()->create([
                    'trhdr_id' => $cartHdr->id,
                    'qty_reff' => 1,
                    'matl_id' => $material_id,
                    'matl_code' => $material_code,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_type' => 'C',
                    'tr_seq' => $maxTrSeq,
                    'price' => $price,
                ]);

                DB::commit();

                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'success',
                    'message' => 'Berhasil menambahkan item ke cart'
                ]);
            } else {
                DB::rollback();

                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'error',
                    'message' => 'Item sudah dimasukkan ke cart'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan item ke cart'
            ]);
        }
    }
}
