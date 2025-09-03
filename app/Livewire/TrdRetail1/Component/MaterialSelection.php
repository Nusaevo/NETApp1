<?php

namespace App\Livewire\TrdRetail1\Component;

use Livewire\Component;
use App\Models\TrdRetail1\Master\Material;
use App\Services\TrdRetail1\Master\MasterService;

class MaterialSelection extends Component
{
    public $isOpen = false;
    public $materialList = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $filterCategory = '';
    public $filterBrand = '';
    public $filterType = '';

    // Configuration props
    public $dialogId = 'materialSelectionDialog';
    public $title = 'Search Materials';
    public $width = '800px';
    public $height = '600px';
    public $enableFilters = true;
    public $multiSelect = true;

    protected $listeners = [
        'openMaterialSelection' => 'open',
        'closeMaterialSelection' => 'close'
    ];

    public function mount(
        $dialogId = 'materialSelectionDialog',
        $title = 'Search Materials',
        $width = '800px',
        $height = '600px',
        $enableFilters = true,
        $multiSelect = true
    ) {
        $this->dialogId = $dialogId;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
        $this->enableFilters = $enableFilters;
        $this->multiSelect = $multiSelect;
    }

    public function render()
    {
        return view('livewire.trd-retail1.component.material-selection');
    }

    public function open()
    {
        $this->reset(['searchTerm', 'materialList', 'selectedMaterials', 'filterCategory', 'filterBrand', 'filterType']);
        $this->isOpen = true;
        $this->dispatch('open' . ucfirst($this->dialogId));
    }

    public function close()
    {
        $this->isOpen = false;
        $this->dispatch('close' . ucfirst($this->dialogId));
    }

    public function searchMaterials()
    {
        $query = Material::query()
            ->leftJoin('matl_uoms', function($join) {
                $join->on('materials.id', '=', 'matl_uoms.matl_id');
            })
            ->select('materials.*',
                     'matl_uoms.buying_price as buying_price',
                     'matl_uoms.selling_price as selling_price');

        if (!empty($this->searchTerm)) {
            $searchTermUpper = strtoupper($this->searchTerm);
            $query->where(function ($query) use ($searchTermUpper) {
                $query
                    ->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%']);
            });
        }

        // Apply filters if enabled
        if ($this->enableFilters) {
            if (!empty($this->filterCategory)) {
                $query->where('materials.category', $this->filterCategory);
            }
            if (!empty($this->filterBrand)) {
                $query->where('materials.brand', $this->filterBrand);
            }
            if (!empty($this->filterType)) {
                $query->where('materials.class_code', $this->filterType);
            }
        }

        $this->materialList = $query->get();
    }

    public function selectMaterial($materialId)
    {
        if (!$this->multiSelect) {
            // Single selection mode
            $this->selectedMaterials = [$materialId];
            return;
        }

        // Multi selection mode
        $key = array_search($materialId, $this->selectedMaterials);

        if ($key !== false) {
            unset($this->selectedMaterials[$key]);
            $this->selectedMaterials = array_values($this->selectedMaterials);
        } else {
            $this->selectedMaterials[] = $materialId;
        }
    }

    public function confirmSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Silakan pilih setidaknya satu material terlebih dahulu.');
            return;
        }

        // Dispatch event to parent component with selected materials
        $this->dispatch('materialsSelected', $this->selectedMaterials);

        $this->close();
    }

    public function isSelected($materialId)
    {
        return in_array($materialId, $this->selectedMaterials);
    }
}
