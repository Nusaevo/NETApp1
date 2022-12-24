@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row' => !$row->trashed(),
    'allow_details' => false,
    'allow_edit' => true,
    'allow_delete' => true,
    'wire_click_edit' => "\$emit('master_unit_edit',  $row->id)",
    'wire_click_delete' => "\$emit('master_unit_delete',  $row->id)",
    ]
)
