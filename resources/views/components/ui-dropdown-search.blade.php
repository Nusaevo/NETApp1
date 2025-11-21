@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';

    // Menentukan class untuk kolom dan form-floating (sama seperti dropdown biasa)
    $colClass = 'col-sm' . (!empty($label) ? ' mb-4' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';

    // Determine input class based on whether there's a label (sama seperti dropdown biasa)
    $inputClass = !empty($label) ? 'form-select' : 'form-select form-select-sm';

    // Determine enabled state externally.
    $isEnabled = isset($enabled) && ($enabled === 'always' || $enabled === 'true') || $enabled === true;

    // Default to session app_code if connection is not specified
    $dbConnection = isset($connection) ? $connection : 'Default';

    // Handle SQL query
    $rawQuery = $query ?? '';
    $sqlQuery = !empty($rawQuery) ? (string)$rawQuery : '';

    // Generate a unique ID for this instance to avoid conflicts
    $uniqueId = uniqid('ui_dropdown_select_');

    // Use searchOnSpace directly from component property (already set in constructor)
    // Since it's a public property, Laravel automatically populates it from the attribute
    $searchOnSpaceValue = $searchOnSpace;
@endphp
@livewire('component.ui-dropdown-select', [
    'model' => $model,
    'query' => $query,
    'connection' => $connection,
    'optionValue' => $optionValue,
    'optionLabel' => $optionLabel,
    'selectedValue' => $selectedValue,
    'placeHolder' => $placeHolder,
    'onChanged' => $onChanged,
    'span' => $span,
    'inputClass' => $inputClass,
    'label' => $label ?? '',
    'required' => $required ?? 'false',
    'clickEvent' => $clickEvent ?? '',
    'buttonName' => $buttonName ?? '',
    'action' => $action ?? '',
    'buttonEnabled' => $buttonEnabled ?? 'true',
    'enabled' => $enabled ?? 'true',
    'visible' => $visible ?? 'true',
    'searchOnSpace' => $searchOnSpaceValue
], key($uniqueId))
