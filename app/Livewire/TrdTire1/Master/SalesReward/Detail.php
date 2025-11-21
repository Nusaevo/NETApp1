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
    public $salesRewardOptions = []; // Tambahan untuk dropdown kode program
    public $selectedSalesRewardCode = ''; // Tambahan untuk menyimpan kode yang dipilih
    public $selectedSalesRewardItems = []; // Tambahan untuk menyimpan item dari sales reward yang dipilih
    public $selectAll = false; // Tambahan untuk select all checkbox
    public $isEditMode = false; // Tambahan untuk menandakan mode edit

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
    protected $listeners = [
        'DropdownSelected' => 'DropdownSelected'
    ];
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

        // Load sales reward options untuk dropdown
        $this->loadSalesRewardOptions();

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

            // Set filterBrand dari data yang ada
            $this->filterBrand = $salesReward->brand;

            // Reload sales reward options setelah filterBrand di-set
            $this->loadSalesRewardOptions();

            // Dispatch event untuk refresh dropdown brand
            $this->dispatch('refreshBrandDropdown', brand: $this->filterBrand);

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
        // Validasi: Pastikan field Merk sudah diisi terlebih dahulu
        if (empty($this->filterBrand)) {
            $this->dispatch('error', 'Silakan pilih Merk terlebih dahulu sebelum menambahkan multiple items.');
            return;
        }

        $this->searchTerm = '';
        $this->materialList = [];
        $this->selectedMaterials = [];
        $this->selectedSalesRewardCode = ''; // Reset dropdown kode program
        $this->selectedSalesRewardItems = []; // Reset selected sales reward items
        $this->selectAll = false; // Reset select all
        $this->isEditMode = false; // Reset mode edit
        $this->filterCategory = ''; // Reset category filter
        $this->filterType = ''; // Reset type filter
        $this->dispatch('openItemDialogBox');
    }

    public function editItemDialogBox()
    {
        // Reset data
        $this->searchTerm = '';
        $this->materialList = [];
        $this->selectedMaterials = [];
        $this->selectedSalesRewardCode = '';
        $this->selectedSalesRewardItems = [];
        $this->selectAll = false;
        $this->isEditMode = true; // Set mode edit

        // Load semua item yang sudah ada di input_details
        if (!empty($this->input_details)) {
            foreach ($this->input_details as $detail) {
                if (isset($detail['matl_id']) && $detail['matl_id']) {
                    $material = Material::find($detail['matl_id']);
                    if ($material) {
                        $this->materialList[] = $material;
                        $this->selectedMaterials[] = $detail['matl_id'];

                        // Simpan data item untuk diproses nanti
                        $this->selectedSalesRewardItems[] = [
                            'matl_id' => $detail['matl_id'],
                            'grp' => $detail['grp'] ?? '',
                            'qty' => $detail['qty'] ?? 0,
                            'reward' => $detail['reward'] ?? 0,
                            'material' => $material
                        ];
                    }
                }
            }

            // Set select all jika semua item ada
            if (count($this->selectedMaterials) === count($this->materialList)) {
                $this->selectAll = true;
            }
        }

        $this->dispatch('openEditItemDialogBox');
    }

    public function closeItemDialogBox()
    {
        $this->dispatch('closeItemDialogBox');
    }

    public function closeEditItemDialogBox()
    {
        $this->dispatch('closeEditItemDialogBox');
    }


    public function onValidateAndSave()
    {
        $headerCode = $this->inputs['code'];

        foreach ($this->input_details as $detail) {
            if (isset($detail['id']) && $detail['id']) {
                // Update jika ada id
                $salesReward = SalesReward::find($detail['id']);
                if (!$salesReward) {
                    // Jika id tidak ditemukan, buat baru
                    $salesReward = new SalesReward();
                }
            } else {
                // Create baru
                $salesReward = new SalesReward();
            }

            // Set data header
            $salesReward->code     = $headerCode;
            $salesReward->descrs   = $this->inputs['descrs'];
            $salesReward->beg_date = $this->inputs['beg_date'];
            $salesReward->end_date = $this->inputs['end_date'];
            $salesReward->brand    = $this->filterBrand;

            // Set data detail
            $salesReward->matl_id = $detail['matl_id'];
            $material = Material::find($detail['matl_id']);
            $salesReward->matl_code = $material ? $material->code : null;
            $salesReward->qty    = $detail['qty'];
            $salesReward->reward = $detail['reward'];
            $salesReward->grp    = $detail['grp'];

            $salesReward->save();
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
        // Jika ada Kode Program yang dipilih, gunakan material list dari sales reward tersebut
        if (!empty($this->selectedSalesRewardCode)) {
            // Ambil data sales reward berdasarkan kode yang dipilih
            $salesRewards = SalesReward::where('code', $this->selectedSalesRewardCode)->get();

            if ($salesRewards->count() > 0) {
                $materialIds = $salesRewards->pluck('matl_id')->toArray();

                // Query material berdasarkan ID yang ada di sales reward
                $query = Material::whereIn('id', $materialIds);

                // Apply search filters pada material list yang sudah difilter
                if (!empty($this->searchTerm)) {
                    $searchTermUpper = strtoupper($this->searchTerm);
                    $query->where(function ($query) use ($searchTermUpper) {
                        $query
                            ->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                            ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%']);
                    });
                }

                // Apply additional filters
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

                // Reset selected materials dan select all
                $this->selectedMaterials = [];
                $this->selectAll = false;

                $this->dispatch('success', 'Pencarian dilakukan pada material list dari Kode Program: ' . $this->selectedSalesRewardCode);
                return;
            }
        }

        // Jika tidak ada Kode Program yang dipilih, gunakan pencarian normal
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

        // Reset selected materials dan select all
        $this->selectedMaterials = [];
        $this->selectAll = false;
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

        // Update select all state
        $this->updateSelectAllState();
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

            // Cari data dari selectedSalesRewardItems jika ada
            $salesRewardItem = collect($this->selectedSalesRewardItems)->firstWhere('matl_id', $matl_id);

            // Tentukan nilai untuk grp, qty, dan reward
            // Prioritas: input form > data sales reward > default
            $grp = !empty($this->groupInput) ? $this->groupInput : ($salesRewardItem['grp'] ?? '');
            $qty = !empty($this->qtyInput) ? $this->qtyInput : ($salesRewardItem['qty'] ?? 0);
            $reward = !empty($this->rewardInput) ? $this->rewardInput : ($salesRewardItem['reward'] ?? 0);

            $key = count($this->input_details);
            $this->input_details[] = [
                'matl_id' => $matl_id,
                'grp' => $grp,
                'qty' => $qty,
                'reward' => $reward,
                'price' => 0.0
            ];

            $this->onMaterialChanged($key, $matl_id);
        }

        $this->dispatch('success', 'Item berhasil dipilih. Nilai Group, Qty, dan Reward yang diisi di form telah diterapkan.');
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
            return redirect()->route($this->appCode . '.Master.SalesReward.PrintPdf', [
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

    public function onFilterBrandChanged($value)
    {
        $this->filterBrand = $value;
        // Reset dan reload dropdown kode program sesuai merk yang dipilih
        $this->selectedSalesRewardCode = '';
        $this->selectedSalesRewardItems = [];
        $this->loadSalesRewardOptions();
        // dd($this->salesRewardOptions);
    }

    /**
     * Load data sales reward untuk dropdown kode program
     */
    public function loadSalesRewardOptions()
    {
        $query = SalesReward::select('code', 'descrs', 'brand')
            ->distinct()
            ->whereNotNull('code')
            ->where('code', '!=', '');

        // Filter berdasarkan merk jika ada
        if (!empty($this->filterBrand)) {
            $query->where('brand', $this->filterBrand);
        }

        $salesRewards = $query->orderBy('code', 'asc')->get();

        $options = [];
        foreach ($salesRewards as $salesReward) {
            $options[] = [
                'value' => $salesReward->code,
                'label' => $salesReward->code . ' - ' . $salesReward->descrs
            ];
        }

        $this->salesRewardOptions = $options;
        // dd($this->salesRewardOptions);
    }

    /**
     * Handle perubahan pada dropdown kode program
     */
    public function onSalesRewardCodeChanged()
    {
        if (empty($this->selectedSalesRewardCode)) {
            $this->selectedSalesRewardItems = [];
            $this->materialList = [];
            $this->selectedMaterials = [];
            $this->selectAll = false;
            $this->isEditMode = false; // Reset mode edit
            return;
        }

        // Ambil data sales reward berdasarkan kode yang dipilih
        $salesRewards = SalesReward::where('brand', $this->filterBrand)->get();

        if ($salesRewards->count() > 0) {
            // Reset selected items
            $this->selectedSalesRewardItems = [];
            $this->materialList = [];
            $this->selectedMaterials = [];
            $this->selectAll = false;
            $this->isEditMode = false; // Reset mode edit

            // Proses setiap item dari sales reward yang dipilih
            foreach ($salesRewards as $salesReward) {
                // Ambil data material
                $material = Material::find($salesReward->matl_id);
                if ($material) {
                    // Tambahkan ke materialList untuk ditampilkan di dialog box
                    $this->materialList[] = $material;

                    // Simpan data item untuk diproses nanti
                    $this->selectedSalesRewardItems[] = [
                        'matl_id' => $salesReward->matl_id,
                        'grp' => $salesReward->grp,
                        'qty' => $salesReward->qty,
                        'reward' => $salesReward->reward,
                        'material' => $material
                    ];
                }
            }

            // Setelah semua data dimuat, otomatis select all
            $this->selectedMaterials = collect($this->materialList)->pluck('id')->toArray();
            $this->selectAll = true;
            // dd($this->selectedMaterials);

            $this->dispatch('success', 'Material list dari Kode Program: ' . $this->selectedSalesRewardCode . ' berhasil dimuat. Sekarang Anda dapat melakukan pencarian pada material list ini.');
        } else {
            $this->dispatch('error', 'Tidak ada data sales reward untuk kode tersebut.');
        }
    }

    /**
     * Clear data sales reward yang dipilih
     */
    public function clearSalesRewardData()
    {
        $this->selectedSalesRewardCode = '';
        $this->selectedSalesRewardItems = [];
        $this->materialList = [];
        $this->selectedMaterials = [];
        $this->selectAll = false;
        $this->isEditMode = false; // Reset mode edit
        $this->searchTerm = ''; // Reset search term
        $this->filterCategory = ''; // Reset category filter
        $this->filterType = ''; // Reset type filter
        $this->dispatch('success', 'Filter Kode Program telah dibersihkan. Sekarang Anda dapat melakukan pencarian normal.');
    }

    /**
     * Handle konfirmasi selection dari dialog edit
     */
    public function confirmEditSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Silakan pilih setidaknya satu material terlebih dahulu.');
            return;
        }

        // Reset input_details
        $this->input_details = [];

        foreach ($this->selectedMaterials as $matl_id) {
            // Cari data dari selectedSalesRewardItems
            $salesRewardItem = collect($this->selectedSalesRewardItems)->firstWhere('matl_id', $matl_id);

            // Gunakan nilai dari input form jika ada, jika tidak gunakan data yang sudah ada
            $grp = !empty($this->groupInput) ? $this->groupInput : ($salesRewardItem['grp'] ?? '');
            $qty = !empty($this->qtyInput) ? $this->qtyInput : ($salesRewardItem['qty'] ?? 0);
            $reward = !empty($this->rewardInput) ? $this->rewardInput : ($salesRewardItem['reward'] ?? 0);

            $key = count($this->input_details);
            $this->input_details[] = [
                'matl_id' => $matl_id,
                'grp' => $grp,
                'qty' => $qty,
                'reward' => $reward,
                'price' => 0.0
            ];

            $this->onMaterialChanged($key, $matl_id);
        }

        $this->dispatch('success', 'Item berhasil diperbarui.');
        $this->dispatch('closeEditItemDialogBox');
        $this->isEditMode = false; // Reset mode edit
    }

    /**
     * Handle select all checkbox
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Select all materials
            $this->selectedMaterials = collect($this->materialList)->pluck('id')->toArray();
        } else {
            // Deselect all materials
            $this->selectedMaterials = [];
        }
    }

    /**
     * Update select all state based on individual selections
     */
    public function updateSelectAllState()
    {
        if (empty($this->materialList)) {
            $this->selectAll = false;
            return;
        }

        $totalMaterials = count($this->materialList);
        $selectedCount = count($this->selectedMaterials);

        $this->selectAll = ($selectedCount === $totalMaterials);
    }
}
