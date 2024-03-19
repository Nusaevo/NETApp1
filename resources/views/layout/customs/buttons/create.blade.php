@if(isset($permissions['create']) && $permissions['create'])
<x-ui-button
    visible="true"
    enabled="true"
    :click-event="route($route, ['action' => encryptWithSessionKey('Create')])"
    cssClass="btn btn-success mb-5"
    type="Route"
    loading="true"
    iconPath="images/create-icon.png"
    button-name="Create" />
@endif
