@include('layout.customs.data-table-action', [
    'enable_this_row' => true,
    'allow_details' => true,
    'allow_edit' => true,
    'allow_disable' => !$row->trashed() && auth()->id() !== $row->id,
    'allow_delete' => false,
    'wire_click_show' => "\$emit('settings_user_detail',  $row->id)",
    'wire_click_edit' => "\$emit('settings_user_edit',  $row->id)",
    'wire_click_disable' => "\$emit('settings_user_select',  $row->id)",
])
