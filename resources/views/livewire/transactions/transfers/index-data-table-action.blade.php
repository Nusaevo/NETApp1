@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row' => !$row->trashed(),
    'allow_details' => true,
    'allow_edit' => true,
    'allow_delete' => true,
    'wire_click_show' => "\$emit('transaction_transfer_show',  $row->id)",
    'wire_click_edit' => "\$emit('transaction_transfer_edit',  $row->id)",
    'wire_click_delete' => "\$emit('transaction_transfer_delete',  $row->id)",
    ]
)
