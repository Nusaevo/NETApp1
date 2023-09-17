@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row' => true,
    'allow_details' => true,
    'allow_edit' => true,
    'allow_delete' => true,
    'wire_click_show' => "\$emit('settings_config_group_detail', $row->id)",
    'wire_click_edit' => "\$emit('settings_config_group_edit', $row->id)",
    'wire_click_delete' => "\$emit('settings_config_group_delete', $row->id)",
])
