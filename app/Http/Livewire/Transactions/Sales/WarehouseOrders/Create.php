<?php

namespace App\Http\Livewire\Transactions\Sales\WarehouseOrders;

use App\Models\Customer;
use App\Models\PriceCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ItemPrice;
use App\Models\Warehouse;
use App\Models\Payment;
use App\Models\ItemUnit;
use App\Models\ItemWarehouse;
use DB;
use Livewire\Component;
use Illuminate\Support\Collection;
use Exception;

class Create extends Component
{
    public $sales_order;
    public $sales_order_detail = [];
    public $input_items = [];
    public $input_headers = [];

    public $inputs = [];
    public $i = 0;
    public $category;
    public $warehouse;
    public $payment;
    public $items = [];
    public function mount($id)
    {
        $this->sales_order = SalesOrder::findOrFail($id);
        $this->sales_order_detail = SalesOrderDetail::where('sales_order_id', '=', $this->sales_order->id)->get();

        foreach ($this->sales_order_detail as $key => $detail) {
            array_push($this->inputs, $key);

            $this->input_items[$key]['sales_order_detail_id'] = $detail->id;
            $this->input_items[$key]['item_name'] = $detail->item_name;
            $this->input_items[$key]['unit_name'] = $detail->unit_name;
            $this->input_items[$key]['qty_avail'] = round($detail->item_warehouse->qty, 0);
            $this->input_items[$key]['qty_sell'] = round($detail->qty, 0);
            if ($detail->qty_wo == 0) {
                $this->input_items[$key]['qty_pick'] = round($detail->qty, 0);
            } else {
                $this->input_items[$key]['qty_pick'] = round($detail->qty_wo, 0);
            }
            $this->input_items[$key]['item_unit_id'] = $detail->item_warehouse->item_unit_id;
            $this->input_items[$key]['warehouse_id'] = $detail->item_warehouse->warehouse_id;
            $this->input_items[$key]['item_warehouse_id'] = $detail->item_warehouse_id;
        }


        $this->warehouse = Warehouse::orderByName()->get();
        $this->payment = Payment::orderByName()->get();
    }

    public function render()
    {
        return view('livewire.transactions.sales.warehouseorders.detail');
    }

    protected $listeners = [
        'sales_order_detail_destroy'  => 'destroy',
        'sales_order_detail_change_warehouse'  => 'changeWarehouse'

    ];

    protected $rules = [
        'input_items.*.qty_pick'      => 'required|integer'
    ];

    protected $messages = [
        'input_items.array'               => ':attribute harus berupa larik.',
        'input_items.*.*.boolean'         => ':attribute harus benar atau salah.',
        'input_items.*.*.required'        => ':attribute harus di-isi.',
        'input_items.*.*.integer'         => ':attribute harus bilangan bulat.',
        'input_items.*.*.min'             => ':attribute tidak boleh kurang dari :min.',
        'input_items.*.*.max'             => ':attribute tidak boleh lebih dari :max.'
    ];

    protected $validationAttributes = [
        'input_items'                => 'Inputan Barang',
        'input_items.*'              => 'Inputan ID Barang',
        'input_items.*.qty_pick'          => 'Qty Barang'
    ];

    public function changeWarehouse($id, $value)
    {
        $item_warehouse = ItemWarehouse::where('warehouse_id', '=', $value)
            ->where('item_unit_id', '=', $this->input_items[$id]['item_unit_id'])
            ->first();

        $this->input_items[$id]['item_warehouse_id'] = $item_warehouse->id;
        $this->input_items[$id]['qty_avail'] = round($item_warehouse->qty, 0);
    }


    public function store()
    {
        if (count($this->input_items) > 0) {
            $this->validate();
            $total_amount = 0;
            DB::beginTransaction();
            try {
                foreach ($this->input_items as $item_id => $input_item) {
                    if (isset($input_item['sales_order_detail_id'])) {
                        $sales_order_detail  = SalesOrderDetail::findOrFail($input_item['sales_order_detail_id']);
                        $sales_order_detail->qty_wo = $input_item['qty_pick'];
                        $sales_order_detail->item_warehouse_id = $input_item['item_warehouse_id'];
                        if ($input_item['qty_pick'] > $input_item['qty_avail']) {
                            DB::rollBack();
                            return $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Stock " . $input_item['item_name'] . ' '
                                . $input_item['unit_name'] . " kurang dari jumlah yang diambil dari gudang!!"]);
                        } else {
                            $item_warehouse = ItemWarehouse::findOrFail($input_item['item_warehouse_id']);
                            $item_warehouse->qty = $input_item['qty_avail'] - $input_item['qty_pick'];
                            $item_warehouse->save();
                            $sales_order_detail->save();
                            $total_amount += $input_item['qty_pick'] * $sales_order_detail->price;
                        }
                    }
                }
                $this->sales_order->is_finished = 1;
                $this->sales_order->wo_date = Now();
                $this->sales_order->total_amount =  $total_amount;
                $this->sales_order->save();
                DB::commit();
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Nota berhasil selesai!"]);
                return redirect()->route('sales_warehouseorder.index');
            } catch (Exception $e) {
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Nota error mohon kontak IT! " . "<br>" . $e]);
                DB::rollBack();
            }
        } else {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Mohon isi produk terlebih dahulu!"]);
        }
    }
}
