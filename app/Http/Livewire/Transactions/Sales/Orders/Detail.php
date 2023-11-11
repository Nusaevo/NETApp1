<?php

namespace App\Http\Livewire\Transactions\Sales\Orders;

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

class Detail extends Component
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
        foreach ($this->sales_order_detail as $key => $detail) {
            array_push($this->inputs, $key);
            $this->input_items[$key]['sales_order_detail_id'] = $detail->id;
            $this->input_items[$key]['item_name'] = $detail->item_warehouse->item_unit->item->name;
            $this->input_items[$key]['unit_name'] = $detail->item_warehouse->item_unit->from_unit->name;
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
        return view('livewire.transactions.sales.orders.detail');
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


    public function changeItem($id, $value, $index)
    {
        if (isset($this->input_headers['category_id'])) {
            $duplicated = false;
            //compare this index with all existing items
            $param = explode("-", $id);
            foreach ($this->input_items as $item_id => $input_item) {
                if ($item_id != $param[1]) {
                    if (isset($input_item['item_unit_id'])) {
                        if ($input_item['item_unit_id'] == $value) {
                            $duplicated = true;
                        }
                    }
                }
            }
            //check if the item is duplicate
            if ($duplicated == false) {
                $item = ItemPrice::where('item_unit_id', '=', $value)->where('price_category_id',  $this->input_headers['category_id'])->first();
                $itemUnit = ItemUnit::findorFail($value);
                $this->input_items[$param[1]]['item_unit_id'] = $itemUnit->id;
                $this->input_items[$param[1]]['item_name'] = $itemUnit->item->name . '-' . $itemUnit->from_unit->name;
                $this->input_items[$param[1]]['item_name_only'] = $itemUnit->item->name;
                $this->input_items[$param[1]]['unit_name'] = $itemUnit->from_unit->name;
                $this->input_items[$param[1]]['price'] = round($item['price'], 0);
                //$this->input_items[$param[1]]['qty'] = 1;
                $this->input_items[$param[1]]['discount'] = 0;
                $this->input_items[$param[1]]['total'] = rupiah($item['price']);
                $this->input_items[$param[1]]['warehouse_id'] = 1;
                $indexOfInputs = count($this->inputs) - 1;

                if ($index ==  $indexOfInputs) {
                    $this->addInput();
                }
            } else {
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' =>  "Produk dan satuan telah dibuat sebelumnya, mohon dicek kembali!"]);
            }
            //  $this->dispatchBrowserEvent('reApplySelect2');
        } else {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' =>  "Mohon isi customer terlebih dahulu!"]);
        }
    }

    public function changeQty($id, $value)
    {
        if (isset($this->input_items[$id]['price'])) {
            $total = $this->input_items[$id]['price'] * $value;
            $this->input_items[$id]['total'] = rupiah($total);
            $this->dispatchBrowserEvent('reApplySelect2');
            $this->countTotalAmount();
        }
    }

    public function changePrice($id, $value)
    {
        if (isset($this->input_items[$id]['qty'])) {
            $total = $this->input_items[$id]['qty'] * $value;
            $this->input_items[$id]['total'] = rupiah($total);
            $this->dispatchBrowserEvent('reApplySelect2');
            $this->countTotalAmount();
        }
    }
    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_items as $item_id => $input_item) {
            if (isset($input_item['item_unit_id'])) {
                if (isset($input_item['qty']) && isset($input_item['price']))
                    $this->total_amount += $input_item['price'] * $input_item['qty'];
            }
        }
    }
    //add new item
    public function addInput()
    {
        if (isset($this->input_headers['category_id'])) {
            array_push($this->inputs, $this->i);
            $this->i = $this->i + 1;
            $this->dispatchBrowserEvent('reApplySelect2');
        } else {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Mohon isi customer terlebih dahulu!"]);
        }
    }

    //remove item
    public function removeInput($i, $id)
    {
        unset($this->inputs[$i]);
        unset($this->input_items[$i]);
        $this->i = $this->i - 1;
        if ($id != 0) {
            $item = SalesOrderDetail::findOrFail($id);
            $item->delete();
        }
        $this->dispatchBrowserEvent('reApplySelect2');
    }

    public function store()
    {
        if (count($this->input_items) > 0) {
            $this->validate();
            $total_amount = 0;
            $total_discount = 0;
            DB::beginTransaction();
            try {

                $this->sales_order->payment_id = $this->input_headers['payment_id'];
                $this->sales_order->save();
                foreach ($this->input_items as $item_id => $input_item) {
                    if (isset($input_item['sales_order_detail_id'])) {
                        $item = SalesOrderDetail::findOrFail($input_item['sales_order_detail_id']);
                        $item->update([
                            'sales_order_id' => $this->sales_order->id,
                            'price'   => $input_item['price'],
                            'qty'              => $input_item['qty'],
                            'qty_wo'              => $input_item['qty'],
                            'item_warehouse_id'       =>   $input_item['item_warehouse_id'],
                            'item_name'       => $input_item['item_name'],
                            'unit_name'       => $input_item['unit_name'],
                            'discount'       => $input_item['discount']
                        ]);
                    } else {
                        if (isset($input_item['item_unit_id'])) {
                            $item_warehouse = ItemWarehouse::FindItemWarehouse($input_item['item_unit_id'], $input_item['warehouse_id'])->first();
                            SalesOrderDetail::create([
                                'sales_order_id' => $this->sales_order->id,
                                'price'   => $input_item['price'],
                                'qty'              => $input_item['qty'],
                                'qty_wo'              => $input_item['qty'],
                                'item_warehouse_id'       =>  $item_warehouse->id,
                                'item_name'       => $input_item['item_name_only'],
                                'unit_name'       => $input_item['unit_name'],
                                'discount'       => $input_item['discount']
                            ]);
                        }
                    }
                    $total_discount += $input_item['discount'];
                }
                // dd($total_amount);
                $this->sales_order->update([
                    'total_discount' =>  $total_discount,
                    'total_amount' => $this->total_amount
                ]);
                DB::commit();
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Nota berhasil diperbarui!"]);
                return redirect()->route('sales.order.index');
            } catch (Exception $e) {
                $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Nota error mohon kontak IT! " . "<br>" . $e]);
                DB::rollBack();
            }
        } else {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Mohon isi produk terlebih dahulu!"]);
        }
    }

    public function destroy()
    {
        $this->output_sales_request->delete();
        return redirect()->route('sales.order.index');
    }
}
