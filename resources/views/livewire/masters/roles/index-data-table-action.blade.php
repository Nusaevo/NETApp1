@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row' => true,
    'allow_details' => true,
    'allow_edit' => $row->id > 5 ? true : false,
    'allow_delete' => $row->id > 5 ? true : false,
    'wire_click_show' => "\$emit('master_role_show',  $row->id)",
    'wire_click_edit' => "\$emit('master_role_edit',  $row->id)",
    'wire_click_delete' => "\$emit('master_role_delete',  $row->id)",
    ]
)
