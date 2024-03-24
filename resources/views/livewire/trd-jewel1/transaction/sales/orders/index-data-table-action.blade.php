@include('layout.customs.data-table-action-detail-edit-delete', [
    'enable_this_row'   => !$row->trashed(),
    'allow_details'     => false,
    'allow_edit'        => true,
    'allow_delete'      => true,
    'wire_click_edit'   => "\$emit('sales_order_index_edit',  $row->id)",
    'wire_click_delete' => "\$emit('sales_order_index_delete',  $row->id)",
    ]
)





