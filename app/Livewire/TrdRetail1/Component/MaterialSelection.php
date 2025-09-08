<?php

namespace App\Livewire\TrdRetail1\Component;

use Livewire\Component;
use App\Models\TrdRetail1\Master\Material;
use App\Services\TrdRetail1\Master\MasterService;

class MaterialSelection extends Component
{
    public $isOpen = false;
    public $materialList = [];
    public $selectedMaterials = [];
    public $inputs = []; // Use inputs array with dynamic keys based on dialogId

    // Configuration props
    public $dialogId = 'materialSelectionDialog';
    public $title = 'Search Materials';
    public $width = '800px';
    public $height = '600px';
    public $enableFilters = true;
    public $multiSelect = true;
    public $eventName = 'materialsSelected'; // Configurable event name
    public $additionalParams = []; // Additional parameters to send with event

    public function getListeners()
    {
        return [
            'open' . ucfirst($this->dialogId) => 'open',
            'close' . ucfirst($this->dialogId) => 'close'
        ];
    }

    public function mount(
        $dialogId = 'materialSelectionDialog',
        $title = 'Search Materials',
        $width = '800px',
        $height = '600px',
        $enableFilters = true,
        $multiSelect = true,
        $eventName = 'materialsSelected',
        $additionalParams = []
    ) {
        $this->dialogId = $dialogId;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
        $this->enableFilters = $enableFilters;
        $this->multiSelect = $multiSelect;
        $this->eventName = $eventName;
        $this->additionalParams = $additionalParams;
    }

    public function render()
    {
        return view('livewire.trd-retail1.component.material-selection');
    }


    public function searchMaterials()
    {
        $query = Material::query()
            ->leftJoin('matl_uoms', function($join) {
                $join->on('materials.id', '=', 'matl_uoms.matl_id')
                     ->where('matl_uoms.matl_uom', '=', 'PCS'); // Default to PCS UOM for consistency
            })
            ->select('materials.*',
                     'matl_uoms.buying_price as buying_price',
                     'matl_uoms.selling_price as selling_price',
                     'matl_uoms.matl_uom as uom');

        // Get values from inputs array using dialogId prefix
        $searchTerm = $this->inputs[$this->dialogId . '_searchTerm'] ?? '';
        $filterCategory = $this->inputs[$this->dialogId . '_filterCategory'] ?? '';
        $filterBrand = $this->inputs[$this->dialogId . '_filterBrand'] ?? '';
        $filterType = $this->inputs[$this->dialogId . '_filterType'] ?? '';

        if (!empty($searchTerm)) {
            $searchTermUpper = strtoupper($searchTerm);
            $query->where(function ($query) use ($searchTermUpper) {
                $query
                    ->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%']);
            });
        }

        // Apply filters if enabled
        if ($this->enableFilters) {
            if (!empty($filterCategory)) {
                $query->where('materials.category', $filterCategory);
            }
            if (!empty($filterBrand)) {
                $query->where('materials.brand', $filterBrand);
            }
            if (!empty($filterType)) {
                $query->where('materials.class_code', $filterType);
            }
        }

        $this->materialList = $query->limit(100)->get();
    }

    public function selectMaterial($materialId, $matlUom = 'PCS')
    {
        if (!$this->multiSelect) {
            // Single selection mode - store as array with material info
            $this->selectedMaterials = [
                [
                    'matl_id' => $materialId,
                    'matl_uom' => $matlUom
                ]
            ];
            return;
        }

        // Multi selection mode - check if material already exists
        $existingIndex = $this->findSelectedMaterialIndex($materialId, $matlUom);

        if ($existingIndex !== false) {
            // Remove if already selected
            unset($this->selectedMaterials[$existingIndex]);
            $this->selectedMaterials = array_values($this->selectedMaterials);
        } else {
            // Add new material with UOM
            $this->selectedMaterials[] = [
                'matl_id' => $materialId,
                'matl_uom' => $matlUom
            ];
        }
    }

    private function findSelectedMaterialIndex($materialId, $matlUom = 'PCS')
    {
        foreach ($this->selectedMaterials as $index => $selected) {
            if (is_array($selected)) {
                if ($selected['matl_id'] == $materialId && $selected['matl_uom'] == $matlUom) {
                    return $index;
                }
            } else {
                // Backward compatibility - if it's just material ID
                if ($selected == $materialId) {
                    return $index;
                }
            }
        }
        return false;
    }

    public function confirmSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Silakan pilih setidaknya satu material terlebih dahulu.');
            return;
        }

        // Prepare parameters array starting with selected materials
        $params = [$this->selectedMaterials];

        // Add additional parameters if provided
        if (!empty($this->additionalParams)) {
            $params = array_merge($params, $this->additionalParams);
        }

        // Dispatch event to parent component with flexible parameters
        $this->dispatch($this->eventName, ...$params);

        // Reset state and close dialog
        $this->inputs[$this->dialogId . '_searchTerm'] = '';
        $this->inputs[$this->dialogId . '_filterCategory'] = '';
        $this->inputs[$this->dialogId . '_filterBrand'] = '';
        $this->inputs[$this->dialogId . '_filterType'] = '';

        $this->reset(['materialList', 'selectedMaterials']);
        $this->isOpen = false;

        // Dispatch close event to properly close the dialog
        $this->dispatch('close' . ucfirst($this->dialogId));
    }

    public function isSelected($materialId, $matlUom = 'PCS')
    {
        foreach ($this->selectedMaterials as $selected) {
            if (is_array($selected)) {
                if ($selected['matl_id'] == $materialId && $selected['matl_uom'] == $matlUom) {
                    return true;
                }
            } else {
                // Backward compatibility - if it's just material ID
                if ($selected == $materialId) {
                    return true;
                }
            }
        }
        return false;
    }
}
