@include('layout.customs.data-table-action', [
    'enable_this_row' => true,
    'allow_details' => true,
    'allow_edit' => true,
    'allow_disable' => !$row->trashed(),
    'allow_delete' => false,
    'wire_click_show' => "\$emit('viewData',  $row->id)",
    'wire_click_edit' => "\$emit('editData',  $row->id)",
    'wire_click_disable' => "\$emit('selectData',  $row->id)",
])
