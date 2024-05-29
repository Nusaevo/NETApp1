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
use Carbon\Carbon;
use DB;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use Illuminate\Support\Facades\Auth;


use function PHPUnit\Framework\throwException;

class Detail extends BaseComponent
{
    public $object_detail;
    public $inputs = [];
    public $input_details = [];

    public $partners;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "SO";

    public $matl_action = 'Create';
    public $matl_objectId = null;

    public $materialDialogVisible = false;
    public $returnIds = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $currencyRate = 0;
    public $currency = [];
    public $materials = [];
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tr_date'      => $this->trans('tr_date'),
            'inputs.payment_term_id'      => $this->trans('payment'),
            'inputs.partner_id'      => $this->trans('partner'),
            'input_details.*'              => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('price'),
        ];
        $this->customRules  = [
            'inputs.payment_term_id' =>  'required',
            'inputs.partner_id' =>  'required',
            // 'inputs.wh_code' =>  'required',
            'inputs.tr_date' => 'required',
            'input_details.*.price' => 'required',
        ];
    }

    protected function onLoadForEdit()
    {
        $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
        $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id)->orderBy('tr_seq')->get();
        $this->inputs = populateArrayFromModel($this->object);

        // dd($this->object,  $this->inputs);

        // if ($this->object) {
        //     $this->returnIds = $this->object->ReturnHdr->pluck('id')->toArray();
        // }
        foreach ($this->object_detail as $key => $detail) {
            $this->input_details[$key] =  populateArrayFromModel($detail);
            $this->input_details[$key]['id'] = $detail->id;
            $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
            $this->input_details[$key]['qty'] = ceil(currencyToNumeric($detail->qty));
            $this->input_details[$key]['amt'] = ceil(currencyToNumeric($detail->amt));
            $this->input_details[$key]['selling_price'] = ceil(currencyToNumeric($detail->price));
            $this->input_details[$key]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
            $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode;
            $this->input_details[$key]['image_path'] = $detail->Material->Attachment->first() ? $detail->Material->Attachment->first()->getUrl() : null;
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

    public function refreshPayment()
    {
        $data = ConfigConst::GetPaymentTerm($this->appCode);
        $this->payments = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->id,
            ];
        })->toArray();
        $this->inputs['payment_terms_id'] = 129;

    }

    protected function onPopulateDropdowns()
    {
        $this->refreshPayment();
        $this->refreshPartner();
    }

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
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_code'] = $application->code;
        $this->inputs['status_code'] = STATUS::OPEN;
        $this->object->fillAndSanitize($this->inputs);
        // $this->object->saveOrder($this->appCode, $this->trType, $this->inputs, $this->input_details, $this->object_detail, true);
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
        $this->refreshPartner();
        $this->total_amount = 0;
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['curr_rate'] = GoldPriceLog::GetTodayCurrencyRate();
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
            $deletedItemId = $this->input_details[$index]['id'];
            $orderDtl = OrderDtl::withTrashed()->find($deletedItemId);
            if ($orderDtl) {
                $orderDtl->forceDelete();
            }
        }
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
        $this->SaveWithoutNotification();
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
            $total = toNumberFormatter($this->input_details[$id]['qty']) * toNumberFormatter($value);
            $this->input_details[$id]['amt'] = numberFormat($total) ;
            $this->input_details[$id]['price'] = $total;
            $this->countTotalAmount();
            $this->SaveWithoutNotification();
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

    public function searchMaterials()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->notify('warning', Lang::get('generic.string.currency_needed'));
            return;
        }

        $query = Material::query()
            ->join('ivt_bals', 'materials.id', '=', 'ivt_bals.matl_id')
            ->where('ivt_bals.qty_oh', '>', 0)
            ->select('materials.*');

        if (!empty($this->searchTerm)) {
            $query->where(function($query) {
                $query->where('materials.code', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('materials.name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('materials.descr', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $this->materials =  $query->get();
    }

    public function addSelectedToCart()
    {
        if ($this->currencyRate == 0) {
            $this->notify('warning', Lang::get('generic.string.currency_needed'));
            return;
        }

        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            $cartHdr = OrderHdr::firstOrCreate([
                'created_by' => $usercode,
                'tr_type' => 'C',
            ], [
                'tr_date' => Carbon::now(),
            ]);

            foreach ($this->selectedMaterials as $material_id) {
                $material = Material::find($material_id);
                if (!$material) {
                    continue;
                }

                $existingOrderDtl = $cartHdr->CartDtl()->where('matl_id', $material_id)->first();

                if ($existingOrderDtl) {
                    DB::rollback();
                    $this->dispatchBrowserEvent('notify-swal', [
                        'type' => 'error',
                        'message' => "Item {$material->code} sudah ada di cart"
                    ]);
                    return;
                }

                $price = currencyToNumeric($material->jwl_selling_price) * $this->currencyRate;
                $maxTrSeq = $cartHdr->CartDtl()->max('tr_seq') ?? 0;
                $maxTrSeq++;

                $cartHdr->CartDtl()->create([
                    'trhdr_id' => $cartHdr->id,
                    'qty_reff' => 1,
                    'matl_id' => $material_id,
                    'matl_code' => $material->code,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_type' => 'C',
                    'tr_seq' => $maxTrSeq,
                    'price' => $price,
                ]);
            }

            DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => 'Berhasil menambahkan item ke cart'
            ]);
            $this->selectedMaterials = [];
            $this->retrieveMaterials();
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan item ke cart'
            ]);
        }
    }
}
