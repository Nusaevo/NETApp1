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
    public $connection;             // Database connection (default: 'Default' - akan menggunakan session app_code)
    public $query;                  // Raw SQL query untuk pencarian data

    /**
     * Create a new UiDropdownSearch component instance.
     *
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
     * @param string $searchWhereCondition WHERE conditions (AND: &, OR: |)
     *                                     Example: "status_code=A&deleted_at=null" or "status=A|status=I"
     * @param string $optionValue    Field untuk value
     * @param string $optionLabel    Field untuk label. Multiple fields separated by comma
     *                              Example: "code,name" will display "ABC123 - Product Name"
     * @param string $connection     Database connection name. Use 'Default' to use session app_code
     * @param string $query          Raw SQL query untuk pencarian data.
     *                              Example: "SELECT id, code, name FROM partner WHERE deleted_at IS NULL"
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
        $optionLabel = 'name',
        $connection = 'Default',
        $query = ''
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
        $this->connection            = $connection;
        $this->query                 = $query;
    }

    /**
     * Render the view for the UiDropdownSearch component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Check if query is empty
        if (empty($this->query)) {
            // \Log::warning('UiDropdownSearch has empty query', [
            //     'component' => $this->model,
            //     'connection' => $this->connection,
            //     'selectedValue' => $this->selectedValue
            // ]);
        }

        // Log query data with more detail for debugging
        // \Log::debug('UiDropdownSearch component render', [
        //     'component' => $this->model,
        //     'query' => $this->query,
        //     'query_length' => strlen($this->query ?? ''),
        //     'query_type' => gettype($this->query),
        //     'hasQuery' => !empty($this->query),
        //     'connection' => $this->connection
        // ]);

        // Untuk backward compatibility:
        // Jika menggunakan format lama (model-based) tapi juga menyediakan query,
        // prioritaskan query (format baru)

        // Untuk kasus konversi otomatis model ke query jika keduanya diberikan
        if (!empty($this->searchModel) && empty($this->query)) {
            // Tampilkan peringatan jika masih menggunakan format lama tanpa query
            // \Log::warning('UiDropdownSearch using deprecated model-based format', [
            //     'model' => $this->searchModel,
            //     'component' => $this->model
            // ]);
        }

        return view('components.ui-dropdown-search');
    }
}
