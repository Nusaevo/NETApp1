<?php

namespace App\Livewire\Component;

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;

class UiDropdownSelect extends Component
{
    public $options;
    public $selectedValue;
    public $searchTerm = '';
    public $highlightIndex = 0;
    public $isSearching = false;
    public $model;
    public $columns;
    public $orderby;
    public $placeHolder;
    public $span;

    /**
     * Mount method to initialize dynamic queries and parameters.
     */
    public function mount(
        $model,
        $columns = ['name'],
        $orderby = ['name', 'asc'],
        $selectedValue = null,
        $type = 'string',
        $placeHolder = '',
        $span = 'Full'
    )
    {
        // Initialize dynamic properties
        $this->model = $model;
        $this->columns = $columns;
        $this->orderby = $orderby;
        $this->placeHolder = $placeHolder;
        $this->span = $span;
        $this->selectedValue = $selectedValue;

        // Fetch initial data based on the parameters
        $this->searchData();
    }

    /**
     * Update search term and perform search.
     */
    public function updatedSearchTerm()
    {
        $this->isSearching = true;
        $this->searchData();
    }

    /**
     * Fetch options based on the model query with filters.
     */
    public function searchData()
    {
        $this->options = $this->applyQueries()->get();
        $this->isSearching = false;
    }

    /**
     * Apply dynamic queries to the model (with order and filtering).
     *
     * @return Builder
     */
    public function applyQueries(): Builder
    {
        // Get the model dynamically
        $query = app($this->model)->query();

        // Apply search filter based on searchTerm (check across all columns)
        if ($this->searchTerm) {
            foreach ($this->columns as $column) {
                $query->orWhere($column, 'like', '%' . $this->searchTerm . '%');
            }
        }

        // Apply ordering if specified
        if ($this->orderby) {
            $query->orderBy($this->orderby[0], $this->orderby[1]);
        }

        return $query;
    }

    /**
     * Increment the highlight index.
     */
    public function incrementHighlight()
    {
        if ($this->highlightIndex === count($this->options) - 1) {
            $this->highlightIndex = 0;
            return;
        }
        $this->highlightIndex++;
    }

    /**
     * Decrement the highlight index.
     */
    public function decrementHighlight()
    {
        if ($this->highlightIndex === 0) {
            $this->highlightIndex = count($this->options) - 1;
            return;
        }
        $this->highlightIndex--;
    }

    /**
     * Select a contact (option) based on the highlighted index.
     */
    public function selectOption()
    {
        $selectedOption = $this->options[$this->highlightIndex] ?? null;
        if ($selectedOption) {
            $this->selectedValue = $selectedOption['value'];
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
