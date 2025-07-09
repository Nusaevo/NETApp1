<?php

namespace App\Traits;

/**
 * DropdownRefreshTrait
 *
 * Helper trait for Livewire components to manage dropdown search refreshes
 */
trait DropdownRefreshTrait
{
    /**
     * Refresh a specific dropdown by its model field name
     *
     * @param string $fieldName The model field name (e.g., 'partner_id')
     */
    public function refreshDropdown($fieldName)
    {
        $this->dispatch('refreshDropdown-' . $fieldName);
    }

    /**
     * Refresh multiple dropdowns at once
     *
     * @param array $fieldNames Array of model field names
     */
    public function refreshDropdowns(array $fieldNames)
    {
        foreach ($fieldNames as $fieldName) {
            $this->refreshDropdown($fieldName);
        }
    }

    /**
     * Set a dropdown value and refresh its display
     *
     * @param string $fieldName The model field name
     * @param mixed $value The value to set
     */
    public function setDropdownValue($fieldName, $value)
    {
        $this->{$fieldName} = $value;
        $this->refreshDropdown($fieldName);
    }

    /**
     * Clear a dropdown value and refresh its display
     *
     * @param string $fieldName The model field name
     * @param mixed $defaultValue The default value ('' for string, 0 for int)
     */
    public function clearDropdownValue($fieldName, $defaultValue = '')
    {
        $this->{$fieldName} = $defaultValue;
        $this->refreshDropdown($fieldName);
    }

    /**
     * Cascade refresh - clear dependent dropdowns when parent changes
     *
     * @param string $parentField The parent field that changed
     * @param array $dependentFields Array of dependent field names to clear and refresh
     * @param mixed $clearValue Value to set for cleared fields ('' or 0)
     */
    public function cascadeDropdownRefresh($parentField, array $dependentFields, $clearValue = '')
    {
        // Refresh the parent dropdown
        $this->refreshDropdown($parentField);

        // Clear and refresh dependent dropdowns
        foreach ($dependentFields as $dependentField) {
            $this->{$dependentField} = $clearValue;
            $this->refreshDropdown($dependentField);
        }
    }
}
