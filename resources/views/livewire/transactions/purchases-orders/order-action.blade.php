

<x-ui-button click-event="{{ route('purchases_deliveries.detail', ['action' =>  Crypt::encryptString('Create'),
    'objectId' =>  Crypt::encryptString($row->id)]) }}" cssClass="btn btn-primary" type="Route" loading="true"
    iconPath="images/create-icon.png" button-name="Order Terima Gudang" />
