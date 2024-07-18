@props([
    'permissions' => [],
    'route' => ''
])
@if(isset($permissions['create']) && $permissions['create'])
<div style="padding: 5px;">
    <x-ui-button
        :visible="true"
        :enabled="true"
        :clickEvent="route($route, ['action' => encryptWithSessionKey('Create')])"
        cssClass="btn btn-primary mb-5"
        type="Route"
        :loading="false"
        iconPath=""
        button-name="Create"/>
</div>
@endif