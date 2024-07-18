@props([
    'permissions' => [],
    'route' => ''
])

@if(isset($permissions['create']) && $permissions['create'])
    <x-ui-button
        :visible="true"
        :enabled="true"
        :clickEvent="route($route, ['action' => encryptWithSessionKey('Create')])"
        cssClass="btn btn-primary mb-5"
        type="Route"
        :loading="false"
        iconPath="add.svg"
        button-name="Create" />
@endif
