<x-ui-button
    visible="true"
    enabled="true"
    :clickEvent="route($route, ['action' => encryptWithSessionKey('Edit')])"
    cssClass="btn btn-success mb-5"
    type="Route"
    loading="true"
    iconPath="edit.svg"
    button-name="Edit" />
