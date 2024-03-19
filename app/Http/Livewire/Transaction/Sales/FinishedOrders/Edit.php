<?php

namespace App\Http\Livewire\Transaction\Sales\FinishedOrders;

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
use Exception;

class Edit extends Component
{
    public $sales_order;
    public $sales_order_detail = [];
    public $input_items = [];
    public $input_headers = [];
    public $total_amount = 0;
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
        $this->input_headers['category_id'] = $this->sales_order->customer->price_category->id;
        $this->input_headers['payment_id'] = $this->sales_order->payment_id;
        $this->input_headers['is_finished'] = $this->sales_order->is_finished;
        foreach ($this->sales_order_detail as $key => $detail) {
            array_push($this->inputs, $key);
            $this->input_items[$key]['sales_order_detail_id'] = $detail->id;
            $this->input_items[$key]['item_name'] = $detail->item_name;
            $this->input_items[$key]['unit_name'] = $detail->unit_name;
            $this->input_items[$key]['price'] = round($detail->price, 0);
            $this->input_items[$key]['qty'] = round($detail->qty, 0);
            $subtotal = $detail->price  * $detail->qty;
            $this->input_items[$key]['total'] = rupiah($subtotal);
            $this->input_items[$key]['discount'] = round($detail->discount, 0);
            $this->input_items[$key]['warehouse_id'] = $detail->item_warehouse->warehouse_id;
            $this->input_items[$key]['item_warehouse_id'] = $detail->item_warehouse->id;
            $this->total_amount +=  $this->input_items[$key]['price'] *   $this->input_items[$key]['qty'];
        }

        $this->warehouse = Warehouse::orderByName()->get();
        $this->payment = Payment::orderByName()->get();
    }

    public function render()
    {
        return view('livewire.transaction.sales.finishedorders.edit');
    }

    protected $listeners = [
        'sales_order_detail_destroy'  => 'destroy',
        'sales_order_detail_change_customer'  => 'changeCustomer',
        'sales_order_detail_change_item'  => 'changeItem',
        'sales_order_detail_change_qty'  => 'changeQty',
        'sales_order_detail_change_price'  => 'changePrice',
        'sales_order_detail_remove_input'  => 'removeInput'

    ];

    protected $rules = [
        'input_headers'              => 'required|array',
        'input_items.*.price'      => 'required|integer|min:0|max:9999999999',
        'input_items.*.qty'          => 'required|integer|min:0|max:9999999999',
    ];

    protected $messages = [
        'input_headers.required'          => ':attribute harus di-isi.',
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
        'input_items.*.qty'          => 'Qty Barang',
        'input_items.*.price'          => 'Harga Barang'
    ];

    public function store()
    {
        $this->validate();
        $this->sales_order->is_finished = $this->input_headers['is_finished'];
        DB::beginTransaction();
        if ($this->input_headers['is_finished'] == 0) {
            try {
                foreach ($this->sales_order->sales_order_details as $sales_order_detail) {
                    $item_warehouse = ItemWarehouse::findOrFail($sales_order_detail->item_warehouse_id);
                    $item_warehouse->qty += $sales_order_detail->qty_wo;
                    $item_warehouse->save();
                    $sales_order_detail->qty_wo = 0;
                    $sales_order_detail->save();
                }
                $this->sales_order->is_finished = 0;
                $this->sales_order->wo_date = null;
                $this->sales_order->save();
                DB::commit();
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' => "Nota berhasil dikembalikkan!"]);
                return redirect()->route('sales_order_final.index');
            } catch (Exception $e) {
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' => "Gagal mengembalikan Nota!"]);
                DB::rollBack();
            }
        } else {
            $this->sales_order->save();
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Nota berhasil disimpan!"]);
        }
        return redirect()->route('sales_order_final.index');
    }
}
