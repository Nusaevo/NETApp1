<?php

namespace App\Livewire\TrdTire1\Master\SalesReward;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom}; // Add MatlUom import
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    protected $masterService;
    public $inputs = [];
    public $deletedItems = [];
    public $newItems = [];
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $materialList = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $filterCategory = '';
    public $filterBrand = '';
    public $filterType = '';
    public $kategoriOptions = '';
    public $brandOptions = '';
    public $typeOptions = '';

    public $rules  = [
        'inputs.code' => 'required',
    ];

    public $materials;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $total_amount = 0;
    public $total_discount = 0;
    public $total_tax = 0; // New property for total tax
    public $total_dpp = 0; // New property for total tax
    public $groupInput = '';
    public $qtyInput = 0;
    public $rewardInput = 0;
    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'         => $this->trans('tax'),
            'inputs.tr_code'     => $this->trans('tr_code'),
            'inputs.partner_id'  => $this->trans('partner_id'),
        ];

        $this->masterService = new MasterService();
        $this->materials = $this->masterService->getMaterials(); // Load materials
        $this->kategoriOptions = $this->masterService->getMatlCategoryData();
        $this->brandOptions =   $this->masterService->getMatlMerkData();
        $this->typeOptions =   $this->masterService->getMatlTypeData();
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            // Ambil salah satu record sebagai acuan header
            $salesReward = SalesReward::find($this->objectIdValue);
            if (!$salesReward) {
                $this->dispatch('error', 'Object not found');
                return;
            }

            // Set data header ke property inputs
            $this->inputs = populateArrayFromModel($salesReward);
            $this->inputs['status_code_text'] = $salesReward->status_Code_text;
            $this->inputs['matl_id'] = $salesReward->matl_id; // Set nilai matl_id dari header

            // Format tanggal tanpa jam
            $this->inputs['beg_date'] = date('Y-m-d', strtotime($salesReward->beg_date));
            $this->inputs['end_date'] = date('Y-m-d', strtotime($salesReward->end_date));

            // Ambil seluruh detail berdasarkan kode yang sama
            $details = SalesReward::where('code', $salesReward->code)->get();
            $this->input_details = $details->toArray();
            // dd($this->input_details);
        }
    }


    public function onReset()
    {
        $this->reset('inputs', 'input_details'); // Reset inputs and input_details
        $this->object = new SalesReward();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['beg_date']  = date('Y-m-d');
        $this->inputs['end_date']  = date('Y-m-d');
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods
    public function addItem()
    {
            try {
                $this->input_details[] = [
                    'matl_id' => null,
                    'qty' => null,
                    'price' => 0.0
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
    }
    public function openItemDialogBox()
    {
        $this->searchTerm = '';
        $this->materialList = [];
        $this->selectedMaterials = [];
        $this->dispatch('openItemDialogBox');
    }


    public function onValidateAndSave()
    {
        // Validasi data header dan detail
        // $this->validate([
        //     'inputs.code'               => 'required',
        //     'inputs.descrs'             => 'required',
        //     'inputs.beg_date'           => 'required|date',
        //     'inputs.end_date'           => 'required|date',
        //     'input_details.*.matl_id'   => 'required',
        //     'input_details.*.qty'       => 'required|numeric',
        //     'input_details.*.reward'    => 'required|numeric',
        //     'input_details.*.grp'       => 'required',
        // ]);

        $headerCode = $this->inputs['code'];

        // Ambil seluruh record yang sudah tersimpan berdasarkan code header
        $existingRecords = SalesReward::where('code', $headerCode)->get();
        $existingIds = $existingRecords->pluck('id')->toArray();

        $submittedIds = [];

        // Iterasi setiap item detail dari input form
        foreach ($this->input_details as $detail) {
            // Cek apakah record detail sudah ada, misalnya via id
            if (isset($detail['id'])) {
                $salesReward = SalesReward::find($detail['id']);
            } else {
                // Jika tidak ada id, coba cari berdasarkan kombinasi code dan matl_id
                $salesReward = SalesReward::where('code', $headerCode)
                    ->where('matl_id', $detail['matl_id'])
                    ->first();

                if (!$salesReward) {
                    $salesReward = new SalesReward();
                }
            }

            // Set data header yang sama untuk setiap record
            $salesReward->code     = $headerCode;
            $salesReward->descrs   = $this->inputs['descrs'];
            $salesReward->beg_date = $this->inputs['beg_date'];
            $salesReward->end_date = $this->inputs['end_date'];

            // Set data detail spesifik item
            $salesReward->matl_id = $detail['matl_id'];
            $material = Material::find($detail['matl_id']);
            $salesReward->matl_code = $material ? $material->code : null;
            $salesReward->qty    = $detail['qty'];
            $salesReward->reward = $detail['reward'];
            $salesReward->grp    = $detail['grp'];

            // Simpan record (insert atau update)
            $salesReward->save();

            // Simpan id record yang diproses
            $submittedIds[] = $salesReward->id;
        }

        // Hapus record yang sudah ada di database tapi tidak ada di input form (jika ada penghapusan detail)
        $idsToDelete = array_diff($existingIds, $submittedIds);
        if (!empty($idsToDelete)) {
            SalesReward::destroy($idsToDelete);
        }

        $this->dispatch('success', 'Data Sales Reward berhasil disimpan.');
    }
    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }
    public function searchMaterials()
    {
        $query = Material::query();

        if (!empty($this->searchTerm)) {
            $searchTermUpper = strtoupper($this->searchTerm);
            $query->where(function ($query) use ($searchTermUpper) {
                $query
                    ->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%']);
            });
        }

        // Apply filters
        if (!empty($this->filterCategory)) {
            $query->where('category', $this->filterCategory);
        }
        if (!empty($this->filterBrand)) {
            $query->where('brand', $this->filterBrand);
        }
        if (!empty($this->filterType)) {
            $query->where('type_code', $this->filterType);
        }

        $this->materialList = $query->get();
    }
    public function selectMaterial($materialID)
    {
        $key = array_search($materialID, $this->selectedMaterials);

        if ($key !== false) {
            unset($this->selectedMaterials[$key]);
            $this->selectedMaterials = array_values($this->selectedMaterials);
        } else {
            $this->selectedMaterials[] = $materialID;
        }
    }

    public function confirmSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Silakan pilih setidaknya satu material terlebih dahulu.');
            return;
        }

        foreach ($this->selectedMaterials as $matl_id) {
            $exists = collect($this->input_details)->contains('matl_id', $matl_id);

            if ($exists) {
                $this->dispatch('error', "Material dengan ID $matl_id sudah ada dalam daftar.");
                continue;
            }

            // Jika tidak duplikat, tambahkan ke daftar
            $key = count($this->input_details);
            $this->input_details[] = [
                'matl_id' => $matl_id,
                'grp' => $this->groupInput,
                'qty' => $this->qtyInput,
                'reward' => $this->rewardInput,
                'price' => 0.0
            ];
            $this->onMaterialChanged($key, $matl_id);
        }

        $this->dispatch('success', 'Item berhasil dipilih.');
        $this->dispatch('closeItemDialogBox');
    }
    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $duplicate = collect($this->input_details)->contains(function ($detail, $index) use ($key, $matl_id) {
                return $index != $key && isset($detail['matl_id']) && $detail['matl_id'] == $matl_id;
            });

            if ($duplicate) {
                $this->dispatch('error', 'Material sudah ada dalam daftar.');
                return;
            }

            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
            } else {
                $this->dispatch('error', 'Material_not_found');
            }
        }
    }
    public function printInvoice()
    {
        try {
            // $this->notaCount++;
            $this->updateVersionNumber2();
            // Logika cetak nota jual
            return redirect()->route('TrdTire1.Master.SalesReward.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey((string)$this->object->id)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }
    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.disable';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }
}
