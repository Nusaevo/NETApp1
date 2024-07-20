<?php
namespace App\Http\Livewire\TrdJewel1\Transaction\Buyback;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\ReturnHdr;
use App\Models\TrdJewel1\Transaction\ReturnDtl;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\Crypt;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Master\MatlUom;
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
    public $trType = "BB";

    public $matl_action = 'Create';
    public $matl_objectId = null;

    public $materialDialogVisible = false;
    public $returnIds = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $currencyRate = 0;
    public $currency = [];
    public $orderHdr = [];

    protected function onPreRender()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(431, Lang::get('generic.string.currency_needed'));
        }
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
            // 'inputs.payment_term_id' =>  'required',
            // 'inputs.partner_id' =>  'required',
            // 'inputs.wh_code' =>  'required',
            'inputs.tr_date' => 'required',
            //'input_details.*.price' => 'required',
        ];
    }

    protected function onLoadForEdit()
    {
        $this->object = ReturnHdr::withTrashed()->find($this->objectIdValue);
        $this->object_detail = ReturnDtl::GetByReturnHdr($this->object->id)->orderBy('tr_seq')->get();
        $this->inputs = populateArrayFromModel($this->object);

        // dd($this->object,  $this->inputs);

        // if ($this->object) {
        //     $this->returnIds = $this->object->ReturnHdr->pluck('id')->toArray();
        // }

        $this->retrieveMaterials();
    }

    protected function retrieveMaterials()
    {
        if ($this->object) {
            $this->object_detail = ReturnDtl::GetByReturnHdr($this->object->id)->orderBy('tr_seq')->get();
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                $this->input_details[$key]['name'] = $detail->Material->name;
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
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem',
        'materialSaved' => 'materialSaved',
        'delete' => 'delete',
        'tagScanned' => 'tagScanned',
    ];

    public function OpenDialogBox(){
        if ($this->inputs['curr_rate'] == 0) {
            $this->notify('warning',Lang::get('generic.string.currency_needed'));
            return;
        }
        if (empty($this->inputs['partner_id'])) {
            $this->notify('warning', Lang::get('generic.error.field_required', ['field' => "Customer"]));
            return;
        }
        $this->searchMaterials();
        $this->dispatchBrowserEvent('openMaterialDialog');
    }

    public function refreshPartner()
    {
        $partnersdata = Partner::GetByGrp(Partner::CUSTOMER);
        $this->partners = $partnersdata->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->id,
            ];
        })->toArray();

        $this->inputs['partner_id'] = "";
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshPartner();
    }

    public function onValidateAndSave()
    {
        if ($this->inputs['curr_rate'] == 0) {
            throw new Exception(Lang::get('generic.string.currency_needed'));
        }
        if (!empty($this->input_details)) {
            $unitIds = array_column($this->input_details, 'item_unit_id');
            if (count($unitIds) !== count(array_flip($unitIds))) {
                throw new Exception("Ditemukan duplikasi Item.");
            }
        }
        $this->inputs['wh_code'] = 18;
        if(isset($this->inputs['partner_code'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner->code;
        }
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
        foreach ($this->input_details as $index => $data) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new ReturnDtl();
            }
            $this->object_detail[$index]->fillAndSanitize($data);
            $this->object_detail[$index]->save();
        }
        if($this->actionValue == 'Create')
        {
            return redirect()->route('TrdJewel1.Transaction.Buyback.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
        $this->retrieveMaterials();
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->object = new ReturnHdr();
        $this->object_detail = [];
        $this->refreshPartner();
        $this->total_amount = 0;
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['curr_rate'] = GoldPriceLog::GetTodayCurrencyRate();
    }

    public function Add()
    {
    }

    public function delete()
    {
        try {
            if(!$this->object->isSalesEnableToEdit())
            {
                $this->notify('warning', 'Nota ini tidak bisa dihapus.');
                return;
            }
            //$this->updateVersionNumber();
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


    public function deleteDetails($index)
    {
        if (isset($this->input_details[$index]['id'])) {
            $deletedItemId = $this->input_details[$index]['id'];
            $returnDtl = ReturnDtl::withTrashed()->find($deletedItemId);
            if ($returnDtl) {
                $returnDtl->forceDelete();
            }
        }
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
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

        $partnerId = $this->inputs['partner_id'];
        if (empty($partnerId)) {
            $this->notify('warning', 'Partner ID is required');
            return;
        }
        $searchTermUpper = strtoupper($this->searchTerm ?? '');
        $this->orderHdr = OrderHdr::where('partner_id', $partnerId)
        ->where('tr_type', 'SO')
        ->where(function ($query) use ($searchTermUpper) {
            if ($searchTermUpper) {
                $query->whereRaw('UPPER(tr_id) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereHas('OrderDtl.Material', function ($q) use ($searchTermUpper) {
                        $q->whereRaw('UPPER(code) LIKE ?', ['%' . $searchTermUpper . '%'])
                          ->orWhereRaw('UPPER(name) LIKE ?', ['%' . $searchTermUpper . '%'])
                          ->orWhereRaw('UPPER(descr) LIKE ?', ['%' . $searchTermUpper . '%']);
                    });
            }
        })
        ->get();
    }


    public function SaveCheck()
    {
        if (!empty($this->input_details) && !$this->object->isNew()) {
            $this->SaveWithoutNotification();
        }
    }

    public function addSelectedToCart()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->notify('warning', Lang::get('generic.string.currency_needed'));
            return;
        }

        if (empty($this->selectedMaterials)) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Harap pilih item dahulu sebelum menambahkan ke cart'
            ]);
            return;
        }

        if (empty($this->inputs['payment_term_id'])) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Payment term is required.'
            ]);
            return;
        }

        if (empty($this->inputs['partner_id'])) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Partner is required.'
            ]);
            return;
        }

        DB::beginTransaction();

        try {
            $returnHdr = ReturnHdr::firstOrNew([
                'id' => $this->objectIdValue,
            ]);

            $newDetails = [];

            $maxTrSeq = $returnHdr->ReturnDtl()->max('tr_seq') ?? 0;
            foreach ($this->selectedMaterials as $material_id) {
                $material = Material::find($material_id);
                if (!$material) {
                    continue;
                }

                $existingReturnDtl = $returnHdr->ReturnDtl()->where('matl_id', $material_id)->first();

                if ($existingReturnDtl) {
                    DB::rollback();
                    $this->dispatchBrowserEvent('notify-swal', [
                        'type' => 'error',
                        'message' => "Item {$material->code} sudah ada di Order"
                    ]);
                    return;
                }

                $price = currencyToNumeric($material->jwl_selling_price) * $this->currencyRate;
                $maxTrSeq++;

                $newDetails[] = [
                    'qty_reff' => 1,
                    'matl_id' => $material_id,
                    'matl_descr' => $material->descr,
                    'matl_code' => $material->code,
                    'matl_uom' => $material->MatlUom[0]->id,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_seq' => $maxTrSeq,
                    'price' => $price,
                ];
            }
            $this->input_details = array_merge($this->input_details, $newDetails);

            $this->SaveWithoutNotification();
            DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => 'Berhasil menambahkan item ke cart'
            ]);
            $this->selectedMaterials = [];
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan item ke Order'
            ]);
        }
    }

}
