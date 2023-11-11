<x-ui-button
    click-event="{{ route('purchases_deliveries.detail', ['action' => 'Create', 'objectId' => $row->id]) }}"
    cssClass="btn btn-success"
    type="Route"
    loading="true"
    iconPath="images/create-icon.png"
    button-name="Order Terima Gudang"
/>
