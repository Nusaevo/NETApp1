<?php

namespace App\Livewire\TrdRetail1\Inventory\InventoryAdjustment;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Inventories\{IvttrHdr, IvttrDtl, IvtBal};
use App\Models\TrdRetail1\Master\{Material, MatlUom};
use App\Models\SysConfig1\{ConfigConst, ConfigSnum};
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use App\Services\TrdRetail1\InventoryService;
use Illuminate\Support\Facades\{Session, DB};
use Exception;

class Detail extends BaseComponent
{
    // Header properties
    public $inputs = [];
    public $input_details = [];
    public $warehouses = [];
    public $warehousesType = [];

    // Material search properties
    public $materialQuery = "";
    public $materials = [];
    public $materialList = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $materialCategories = [];
    public $filterCategory = '';
    public $filterBrand = '';
    public $filterType = '';

    // Dynamic material query for dropdown search
    public $materialSearchQuery = "";

    // Component properties
    public $trType = 'SA'; // Stock Adjustment
    public $isPanelEnabled = "false";
    public $isEdit = "false"; // For edit mode state
    public $total_qty_adjustment = 0;
    public $total_items = 0;

    // Options
    public $warehouseOptions = [];
    public $adjustmentTypes = [];
    public $categoryOptions = [];
    public $brandOptions = [];
    public $typeOptions = [];

    protected $masterService;
    protected $inventoryService;

    // Validation rules
    public $rules = [
        'inputs.tr_date' => 'required',
        'inputs.wh_code' => 'required',
        'inputs.tr_type' => 'required',
        'input_details.*.matl_id' => 'required',
        'input_details.*.qty_add' => 'nullable|numeric|min:0',
        'input_details.*.qty_subtract' => 'nullable|numeric|min:0',
    ];

    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        'refreshData' => 'refreshData',
    ];
    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
        ];

        $this->isEdit = $this->isEditOrView() ? 'true' : 'false';
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehousesType = $this->masterService->getWarehouseType();

        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = IvttrHdr::with('IvttrDtl')->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['tr_type'] = $this->object->tr_type;
            $this->inputs['tr_date'] = $this->object->tr_date;
            $this->inputs['tr_id'] = $this->object->tr_id;
            $this->inputs['wh_code'] = $this->object->IvttrDtl->first()->wh_code ?? null;
            $this->inputs['tr_descr'] = $this->object->IvttrDtl->first()->tr_descr ?? null;

            // Load material details
            $this->loadDetails();
        }

        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";

            // Set default warehouse if not already set
            if (empty($this->inputs['wh_code']) && !empty($this->warehouses)) {
                $defaultWarehouse = collect($this->warehouses)->first();
                if ($defaultWarehouse) {
                    $this->inputs['wh_code'] = $defaultWarehouse['value'];
                }
            }
        }

        // Load materials based on warehouse
        if (!empty($this->inputs['wh_code'])) {
            $this->loadMaterialsByWarehouse($this->inputs['wh_code']);
            $this->updateMaterialSearchQuery($this->inputs['wh_code']);
        }

        if($this->actionValue == 'Edit')
        {
            $this->actionValue = "View";
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new IvttrHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['wh_code'] = "";
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'filteredMaterials' => $this->materials,
        ]);
    }
    #endregion

    #region Material List Methods

    public function addItem()
    {
        if (empty($this->inputs['wh_code'])) {
            $this->dispatch('error', 'Silakan pilih warehouse terlebih dahulu.');
            return;
        }

        try {
            $this->input_details[] = [
                'matl_id'        => null,
                'matl_code'      => '',
                'matl_name'      => '',
                'qty_add'        => 0,
                'qty_subtract'   => 0,
                'current_stock'  => 0,
                'final_stock'    => 0,
                'wh_code'        => $this->inputs['wh_code'],
                'is_editable'    => true,
            ];
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

    public function onMaterialChanged($index, $materialId)
    {
        if (empty($materialId)) {
            return;
        }

        try {
            $material = Material::find($materialId);
            if (!$material) {
                $this->dispatch('error', 'Material tidak ditemukan.');
                return;
            }

            // Get current stock from inventory balance
            $currentStock = IvtBal::where('matl_id', $materialId)
                ->where('wh_code', $this->inputs['wh_code'])
                ->sum('qty_oh');
            // Update the material details
            $this->input_details[$index]['matl_id'] = $materialId;
            $this->input_details[$index]['matl_code'] = $material->code;
            $this->input_details[$index]['matl_name'] = $material->name;
            $this->input_details[$index]['current_stock'] = $currentStock;
            $this->input_details[$index]['qty_add'] = 0;
            $this->input_details[$index]['qty_subtract'] = 0;
            $this->input_details[$index]['final_stock'] = $currentStock;

        } catch (Exception $e) {
            $this->dispatch('error', 'Error loading material: ' . $e->getMessage());
        }
    }

    public function updateItemAmount($key)
    {
        if (isset($this->input_details[$key]['current_stock']) &&
            (isset($this->input_details[$key]['qty_add']) || isset($this->input_details[$key]['qty_subtract']))) {

            $currentStock = floatval($this->input_details[$key]['current_stock']);
            $qtyAdd = floatval($this->input_details[$key]['qty_add'] ?? 0);
            $qtySubtract = floatval($this->input_details[$key]['qty_subtract'] ?? 0);

            // Calculate final stock: current + addition - subtraction
            $this->input_details[$key]['final_stock'] = $currentStock + $qtyAdd - $qtySubtract;

            // Calculate net adjustment for backward compatibility
            $this->input_details[$key]['qty_adjustment'] = $qtyAdd - $qtySubtract;
        }

        // Update totals
        $this->calculateTotals();
    }

    protected function calculateTotals()
    {
        $this->total_qty_adjustment = 0;
        $this->total_items = count($this->input_details);

        foreach ($this->input_details as $detail) {
            $qtyAdd = floatval($detail['qty_add'] ?? 0);
            $qtySubtract = floatval($detail['qty_subtract'] ?? 0);
            $netAdjustment = $qtyAdd - $qtySubtract;
            $this->total_qty_adjustment += $netAdjustment;
        }
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            $detail = $this->input_details[$index];

            if (!empty($detail['id'])) {
                $pos = IvttrDtl::find($detail['id']);

                if ($pos) {
                    // Hapus kedua record: tr_seq positif (misalnya 1) dan negatif (misalnya -1)
                    IvttrDtl::where('trhdr_id', $pos->trhdr_id)
                        ->whereIn('tr_seq', [$pos->tr_seq, -$pos->tr_seq])
                        ->delete();
                }
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            // Ambil detail transaksi inventory adjustment
            $inventoryDetails = IvttrDtl::where('trhdr_id', $this->object->id)
                ->orderBy('tr_seq')
                ->get();

            $this->input_details = [];
            foreach ($inventoryDetails as $detail) {
                // Ambil data material
                $material = Material::find($detail->matl_id);

                // Get current stock from inventory balance
                $currentStock = IvtBal::where('matl_id', $detail->matl_id)
                    ->where('wh_code', $detail->wh_code)
                    ->sum('qty_oh');

                // Convert qty_adjustment back to qty_add or qty_subtract
                $qtyAdjustment = $detail->qty;
                $qtyAdd = $qtyAdjustment > 0 ? $qtyAdjustment : 0;
                $qtySubtract = $qtyAdjustment < 0 ? abs($qtyAdjustment) : 0;

                $this->input_details[] = [
                    'id'             => $detail->id,
                    'matl_id'        => $detail->matl_id,
                    'matl_code'      => $material->code ?? $detail->matl_code,
                    'matl_name'      => $material->name ?? '',
                    'qty_add'        => $qtyAdd,
                    'qty_subtract'   => $qtySubtract,
                    'qty_adjustment' => $qtyAdjustment, // Keep for backward compatibility
                    'current_stock'  => $currentStock - $qtyAdjustment, // Reverse calculation
                    'final_stock'    => $currentStock,
                    'wh_code'        => $detail->wh_code,
                    'is_editable'    => false,
                ];
            }
        }
    }

    public function onWarehouseChanged($whCode)
    {
        $this->inputs['wh_code'] = $whCode;
        $this->loadMaterialsByWarehouse($whCode);
        $this->updateMaterialSearchQuery($whCode);

        if (!empty($this->input_details)) {
            $lastIndex = count($this->input_details) - 1;
            $firstMaterial = collect($this->materials)->first();
            $this->input_details[$lastIndex]['matl_id'] = $firstMaterial['value'] ?? null;
        }
    }

    protected function updateMaterialSearchQuery($whCode)
    {
        if (empty($whCode)) {
            $this->materialSearchQuery = "";
            return;
        }

        // Get materials that exist in the selected warehouse
        $this->materialSearchQuery = "
            SELECT DISTINCT m.id, m.code, m.name, m.category, m.brand
            FROM materials m
            INNER JOIN ivt_bals ib ON m.id = ib.matl_id
            WHERE m.status_code = 'A'
            AND m.deleted_at IS NULL
            AND ib.wh_code = '$whCode'
            AND ib.qty > 0
            ORDER BY m.code, m.name
        ";
    }

    protected function loadMaterialsByWarehouse($whCode)
    {
        $materialIds = IvtBal::where('wh_code', $whCode)->pluck('matl_id')->toArray();
        $this->materials = Material::whereIn('id', $materialIds)->get()
            ->map(fn($m) => [
                'value' => $m->id,
                'label' => $m->code . " - " . $m->name,
            ]);
    }

    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        // Validasi setidaknya ada 1 item
        if (empty($this->input_details)) {
            $this->dispatch('warning', 'Tidak dapat menyimpan transaksi. Silakan tambahkan setidaknya 1 item adjustment.');
            return;
        }

        // Validasi ada setidaknya 1 item dengan adjustment yang tidak nol
        $hasValidAdjustment = false;
        foreach ($this->input_details as $detail) {
            $qtyAdd = floatval($detail['qty_add'] ?? 0);
            $qtySubtract = floatval($detail['qty_subtract'] ?? 0);

            if (!empty($detail['matl_id']) && ($qtyAdd > 0 || $qtySubtract > 0)) {
                $hasValidAdjustment = true;
                break;
            }
        }

        if (!$hasValidAdjustment) {
            $this->dispatch('warning', 'Tidak dapat menyimpan transaksi. Silakan masukkan qty penambahan atau pengurangan pada setidaknya 1 item.');
            return;
        }

        // dd($this->inputs, $this->input_details);
        if ($this->actionValue == 'Edit') {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }
        }

        $warehouseType = ConfigConst::where('str1', $this->inputs['tr_type'])->first();
        if ($warehouseType) {
            $this->inputs['tr_type'] = $warehouseType->str1;
        }

        if ($this->actionValue == 'Create') {
            // Generate proper transaction ID using the same method
            $trId = IvttrHdr::generateInventoryTransactionId();

            // Create inventory transaction header
            $ivtHdr = IvttrHdr::create([
                'tr_id' => $trId,
                'tr_type' => 'IA',
                'tr_date' => $this->inputs['tr_date'] ?? date('Y-m-d'),
                'remark' => $this->inputs['remark'] ?? '',
                'status_code' => Status::OPEN
            ]);

            // Set the object to the newly created header
            $this->object = $ivtHdr;
            $this->inputs['id'] = $ivtHdr->id;
            $this->inputs['tr_id'] = $ivtHdr->tr_id;
        } else {
            // For Edit: Update existing header
            $this->object->update([
                'tr_date' => $this->inputs['tr_date'] ?? date('Y-m-d'),
                'remark' => $this->inputs['remark'] ?? '',
            ]);

            // Delete existing details to recreate them
            IvttrDtl::where('trhdr_id', $this->object->id)->delete();

            $trId = $this->object->tr_id;
        }

        $seq = 1;
        // Save inventory adjustment details based on input_details
        foreach ($this->input_details as $detail) {
            $qtyAdd = floatval($detail['qty_add'] ?? 0);
            $qtySubtract = floatval($detail['qty_subtract'] ?? 0);
            $netAdjustment = $qtyAdd - $qtySubtract;

            // Only save if there's an actual adjustment and material is selected
            if (!empty($detail['matl_id']) && $netAdjustment != 0) {
                // Get material info for matl_code
                $material = Material::find($detail['matl_id']);
                $matlCode = $material ? $material->code : '';

                IvttrDtl::create([
                    'trhdr_id' => $this->object->id,
                    'tr_id' => $trId,
                    'tr_seq' => $seq++,
                    'matl_id' => $detail['matl_id'],
                    'matl_code' => $matlCode,
                    'wh_code' => $this->inputs['wh_code'],
                    'qty' => $netAdjustment, // Store net adjustment (positive or negative)
                    'tr_descr' => $this->inputs['remark'] ?? 'Stock Adjustment',
                    'tr_type' => 'IA',
                ]);

                // Update inventory balance
                $balance = IvtBal::where('matl_id', $detail['matl_id'])
                    ->where('wh_code', $this->inputs['wh_code'])
                    ->first();

                if ($balance) {
                    $balance->qty_oh += $netAdjustment;
                    $balance->save();
                } else {
                    IvtBal::create([
                        'matl_id' => $detail['matl_id'],
                        'wh_code' => $this->inputs['wh_code'],
                        'qty_oh' => $netAdjustment,
                    ]);
                }
            }
        }

        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Inventory.InventoryAdjustment.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
    }

    public function delete()
    {
        try {
            // if ($this->object->isOrderCompleted()) {
            //     $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
            //     return;
            // }

            // if (!$this->object->isOrderEnableToDelete()) {
            //     $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
            //     return;
            // }

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

    public function deleteTransaction()
    {
        try {
            app(InventoryService::class)->delInventory($this->object->id);
            $this->dispatch('success', __('generic.string.disable'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.disable', ['message' => $e->getMessage()]));
        }
        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

    public function onTypeChanged($value)
    {
        $this->inputs['tr_type'] = $value;
    }

    public function isEditOrView() {
        return $this->actionValue === 'Edit' || $this->actionValue === 'View';
    }

    protected function prepareBatchCode()
    {
        $result = [];
        $whIdFrom = $this->inputs['wh_id'];
        $whCodeFrom = $this->inputs['wh_code'];
        $whIdTo = $this->inputs['wh_id2'];
        $whCodeTo = $this->inputs['wh_code2'];

        if (!$whIdFrom || !$whIdTo || empty($this->input_details)) {
            return $result;
        }

        $seq = 1;
        foreach ($this->input_details as $item) {
            $matlId = $item['matl_id'];
            $qtyNeeded = $item['qty'];
            if (!$matlId || $qtyNeeded <= 0) continue;

            // Ambil material info
            $material = Material::find($matlId);
            $matlUom = $material->uom ?? null;
            $matlCode = $material->code ?? null;

            $batches = IvtBal::where('wh_id', $whIdFrom)
                ->where('matl_id', $matlId)
                ->where('qty_oh', '>', 0)
                ->orderBy('batch_code')
                ->orderBy('id')
                ->get();

            foreach ($batches as $batch) {
                if ($qtyNeeded <= 0) break;
                $takeQty = min($batch->qty_oh, $qtyNeeded);
                $result[] = [
                    'tr_seq'    => $seq,
                    'matl_id'   => $matlId,
                    'matl_code' => $matlCode,
                    'matl_uom'  => $matlUom,
                    'batch_code'=> $batch->batch_code,
                    'qty'       => $takeQty,
                    'wh_id'     => $whIdFrom,
                    'wh_code'   => $whCodeFrom,
                    'wh_id2'    => $whIdTo,
                    'wh_code2'  => $whCodeTo,
                ];
                $qtyNeeded -= $takeQty;
                $seq++;
            }
            // dd($result);
        }
        return $result;
    }

    #region Add Multiple Items Methods

    public function openItemDialogBox()
    {
        $this->searchTerm = '';
        $this->filterCategory = '';
        $this->filterBrand = '';
        $this->filterType = '';
        $this->materialList = [];
        $this->selectedMaterials = [];

        // Load initial materials for the selected warehouse
        if (!empty($this->inputs['wh_code'])) {
            $this->searchMaterials();
        }
    }

    public function closeItemDialogBox()
    {
        $this->searchTerm = '';
        $this->filterCategory = '';
        $this->filterBrand = '';
        $this->filterType = '';
        $this->materialList = [];
        $this->selectedMaterials = [];
    }

    public function searchMaterials()
    {
        if (empty($this->inputs['wh_code'])) {
            $this->dispatch('warning', 'Please select a warehouse first.');
            return;
        }

        try {
            $query = Material::query()
                ->leftJoin('ivt_bals', function($join) {
                    $join->on('materials.id', '=', 'ivt_bals.matl_id')
                         ->where('ivt_bals.wh_code', $this->inputs['wh_code']);
                })
                ->leftJoin('matl_uoms', function($join) {
                    $join->on('materials.id', '=', 'matl_uoms.matl_id');
                })
                ->where('materials.status_code', 'A')
                ->whereNull('materials.deleted_at')
                ->select([
                    'materials.id',
                    'materials.code',
                    'materials.name',
                    'materials.category',
                    'materials.brand',
                    'materials.class_code as type', // Gunakan class_code sebagai type
                    'matl_uoms.buying_price',
                    'matl_uoms.selling_price',
                    DB::raw('COALESCE(SUM(ivt_bals.qty_oh), 0) as current_stock')
                ])
                ->groupBy(
                    'materials.id',
                    'materials.code',
                    'materials.name',
                    'materials.category',
                    'materials.brand',
                    'materials.class_code',
                    'matl_uoms.buying_price',
                    'matl_uoms.selling_price'
                );

            // Apply search filters
            if (!empty($this->searchTerm)) {
                $searchTerm = '%' . strtoupper($this->searchTerm) . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->whereRaw('UPPER(materials.code) LIKE ?', [$searchTerm])
                      ->orWhereRaw('UPPER(materials.name) LIKE ?', [$searchTerm]);
                });
            }

            if (!empty($this->filterCategory)) {
                $query->where('materials.category', $this->filterCategory);
            }

            if (!empty($this->filterBrand)) {
                $query->where('materials.brand', $this->filterBrand);
            }

            if (!empty($this->filterType)) {
                $query->where('materials.class_code', $this->filterType);
            }

            $materials = $query->limit(50)->get();

            $this->materialList = $materials->map(function ($material) {
                return [
                    'id' => $material->id,
                    'code' => $material->code,
                    'name' => $material->name,
                    'category' => $material->category,
                    'brand' => $material->brand,
                    'type' => $material->type,
                    'buying_price' => $material->buying_price ?? 0,
                    'selling_price' => $material->selling_price ?? 0,
                    'current_stock' => number_format($material->current_stock, 2),
                    'image_url' => null, // Add image logic if needed
                ];
            })->toArray();

        } catch (Exception $e) {
            $this->dispatch('error', 'Error searching materials: ' . $e->getMessage());
        }
    }

    public function selectMaterial($materialId)
    {
        if (in_array($materialId, $this->selectedMaterials)) {
            // Remove from selection
            $this->selectedMaterials = array_filter($this->selectedMaterials, function($id) use ($materialId) {
                return $id != $materialId;
            });
        } else {
            // Add to selection
            $this->selectedMaterials[] = $materialId;
        }
    }

    public function confirmSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('warning', 'Please select at least one material.');
            return;
        }

        try {
            foreach ($this->selectedMaterials as $materialId) {
                // Check if material already exists in input_details
                $exists = false;
                foreach ($this->input_details as $detail) {
                    if ($detail['matl_id'] == $materialId) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    // Get material details
                    $material = Material::find($materialId);
                    if ($material) {
                        // Get current stock
                        $currentStock = IvtBal::where('matl_id', $materialId)
                            ->where('wh_code', $this->inputs['wh_code'])
                            ->sum('qty_oh');

                        // Add to input_details
                        $this->input_details[] = [
                            'matl_id' => $materialId,
                            'matl_code' => $material->code,
                            'matl_name' => $material->name,
                            'qty_add' => 0,
                            'qty_subtract' => 0,
                            'current_stock' => $currentStock,
                            'final_stock' => $currentStock,
                            'wh_code' => $this->inputs['wh_code'],
                            'is_editable' => true,
                        ];
                    }
                }
            }

            // Reset selection and close dialog
            $this->selectedMaterials = [];
            $this->materialList = [];

            $this->dispatch('success', 'Selected materials added successfully!');
            $this->dispatch('close-modal', 'itemDialogBox');

        } catch (Exception $e) {
            $this->dispatch('error', 'Error adding materials: ' . $e->getMessage());
        }
    }

    #endregion
    #endregion
}
