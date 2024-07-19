@if ($status === 'ACTIVE' || !$object->deleted_at)
<x-ui-button button-name="Non Active" clickEvent="" loading="true" :action="$actionValue" cssClass="btn-danger btn-dialog-box" iconPath="disable.svg" />
@else
<x-ui-button button-name="Active" clickEvent="" loading="true" :action="$actionValue" cssClass="btn-primary btn-dialog-box" iconPath="enable.svg" />
@endif
