@if(isset($permissions['create']) && $permissions['create'] && isset($objectId))
    <x-ui-button
        visible="true"
        enabled="true"
        :clickEvent="route($route, ['action' => encryptWithSessionKey('Create'), 'objectId' => encryptWithSessionKey($objectId)])"
        cssClass="btn btn-primary mb-5"
        type="Route"
        loading="true"
        iconPath="add.svg"
        button-name="Create" />
@elseif(isset($permissions['create']) && $permissions['create'] && !isset($objectId))
    <x-ui-button
        visible="true"
        enabled="true"
        :clickEvent="route($route, ['action' => encryptWithSessionKey('Create')])"
        cssClass="btn btn-primary mb-5"
        type="Route"
        loading="false"
        iconPath="add.svg"
        button-name="Create" />
@endif
