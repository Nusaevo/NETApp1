@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row' => !$row->trashed(),
    'allow_details' => false,
    'allow_edit' => false,
    'allow_delete' => false,
    'wire_click_edit' => "\$emit('master_payment_edit',  $row->id)",
    'wire_click_delete' => "\$emit('master_payment_delete',  $row->id)",
    ]
)
