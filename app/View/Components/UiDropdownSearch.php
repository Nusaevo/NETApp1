<?php

namespace App\View\Components;

class UIDropdownSearch extends UiBaseComponent
{
    public $selectedValue;          // Value yang terpilih
    public $span;                   // Lebar komponen (Full/Half dsb.)
    public $modelType;              // Tipe binding Livewire (lazy/debounce)
    public string $type;            // Jenis data (string/int)
    public $placeHolder;            // Placeholder
    public $buttonName;             // Nama tombol (jika ada)
    public $searchModel;            // Model Eloquent yang dipakai di Controller
    public $searchWhereCondition;   // Kondisi tambahan (WHERE)
    public $optionValue;            // Field yang dijadikan value (misal: 'id')
    public $optionLabel;            // Field yang dijadikan label (misal: 'name')

    /**
     * @param string $label
     * @param string $model
     * @param mixed  $selectedValue
     * @param string $required
     * @param string $enabled
     * @param string $visible
     * @param string $action
     * @param string $onChanged
     * @param string $span
     * @param string $modelType
     * @param string|null $clickEvent
     * @param string $type
     * @param string $placeHolder
     * @param string $buttonName
     * @param string $searchModel    Nama model Eloquent (namespace) untuk pencarian
     * @param string $searchWhereCondition
     * @param string $optionValue    Field untuk value
     * @param string $optionLabel    Field untuk label
     */
    public function __construct(
        $label = '',
        $model = '',
        $selectedValue = null,
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $onChanged = '',
        $span = 'Full',
        $modelType = '',
        $clickEvent = null,
        $type = 'string',
        $placeHolder = '',
        $buttonName = '',
        $searchModel = '',
        $searchWhereCondition = '',
        $optionValue = 'id',
        $optionLabel = 'name'
    ) {
        // Panggil parent untuk inisiasi umum
        parent::__construct(
            $label,
            $model,
            $required,
            $enabled,
            $visible,
            $action,
            $onChanged,
            $clickEvent,
            str_replace(['.', '[', ']'], '_', $model)
        );

        $this->selectedValue         = $selectedValue;
        $this->span                  = $span;
        $this->modelType             = $modelType;
        $this->type                  = $type;
        $this->placeHolder           = $placeHolder;
        $this->buttonName            = $buttonName;
        $this->searchModel           = $searchModel;
        $this->searchWhereCondition  = $searchWhereCondition;
        $this->optionValue           = $optionValue;
        $this->optionLabel           = $optionLabel;
    }

    /**
     * Render the view for the UiDropdownSearch component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Kita pakai route('partner.search') secara hard-coded (statis) di Blade
        return view('components.ui-dropdown-search');
    }
}
