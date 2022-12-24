@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row' => !$row->trashed(),
    'allow_details' => true,
    'allow_edit' => true,
    'allow_delete' => true,
    'wire_click_show' => "\$emit('master_item_show',  $row->id)",
    'wire_click_edit' => "\$emit('master_item_edit',  $row->id)",
    'wire_click_delete' => "\$emit('master_item_delete',  $row->id)",
    ]
)
