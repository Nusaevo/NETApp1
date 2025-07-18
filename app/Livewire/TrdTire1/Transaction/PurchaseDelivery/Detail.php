<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\OrderService;
use Exception;
use App\Models\TrdRetail1\Inventories\IvtBal;
use Illuminate\Support\Facades\DB;
use App\Services\TrdTire1\DeliveryService;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $input_details = [];
    public $suppliers;
    public $warehouses;
    public $partners;
    public $vehicle_type;
    public $tax_invoice;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $materials;
    public $object;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $reffhdrtr_code;

    public $total_amount = 0;
    public $trType = "PD";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    protected $masterService;
    public $isPanelEnabled = true;
    public $purchaseOrders = [];

    protected $rules = [
        'inputs.tr_code' => 'required',
        'inputs.wh_code' => 'required',
        'inputs.partner_id' => 'required',
        'input_details.*.qty' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        // 'load-purchase-order-details' => 'loadPurchaseOrderDetails',
        'onPurchaseOrderChanged' => 'onPurchaseOrderChanged'
    ];
    #endregion

    #region Component Lifecycle Methods
    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs.tax' => $this->trans('tax'),
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('matl_id'),
            'input_details.*.qty_order' => $this->trans('qty_order'),
            'input_details.*.price' => $this->trans('price'),
            'input_details.*.qty' => $this->trans('qty'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->purchaseOrders = app(OrderService::class)->getOutstandingPO();

        if ($this->isEditOrView()) {
            $this->object = DelivHdr::withTrashed()->find($this->objectIdValue);
            $this->isPanelEnabled = "false";
            // Populate inputs array
            $this->inputs = populateArrayFromModel($this->object);
            // $this->inputs['status_code'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;

            // Load reffhdrtr_code from DelivDtl if not set in DelivHdr
            if (empty($this->inputs['reffhdrtr_code'])) {
                $delivDtl = DelivDtl::where('trhdr_id', $this->object->id)->first();
                // dd($delivDtl);
                if ($delivDtl) {
                    $this->inputs['reffhdrtr_code'] = $delivDtl->reffhdrtr_code;
                    $this->inputs['reffhdr_id'] = $delivDtl->reffhdr_id;
                    // $this->inputs['qty'] = $delivDtl->qty;
                    $this->inputs['wh_code'] = $delivDtl->wh_code;
                }
            }
            // Load partner data
            $partner = Partner::find($this->object->partner_id);
            if ($partner) {
                $this->inputs['partner_id'] = $partner->id;
                $this->inputs['partner_name'] = $partner->name;
            }

             if (
                !empty($this->inputs['reffhdrtr_code']) &&
                !collect($this->purchaseOrders)->pluck('value')->contains($this->inputs['reffhdrtr_code'])
            ) {
                $this->purchaseOrders[] = [
                    'label' => $this->inputs['reffhdrtr_code'],
                    'value' => $this->inputs['reffhdrtr_code'],
                ];
            }

            // Load details and purchase order details
            $this->loadDetails();
            $this->whCodeOnChanged($this->inputs['wh_code']);
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new DelivHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['reff_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['send_to'] = "Pelanggan";
        $this->inputs['wh_code'] = null;
        $this->inputs['wh_id'] = 0;
        $this->inputs['reffhdrtr_code'] = ''; // Inisialisasi key reffhdrtr_code
    }

    #endregion

    #region Material List Methods
    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = DelivDtl::GetByDelivHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();
            // dd($this->object_detail, $this->object->id, $this->object->tr_type);

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['order_id'] = $detail->OrderDtl->id;
                $this->input_details[$key]['qty'] = $detail->qty;
                $this->input_details[$key]['qty_order'] = ($detail->OrderDtl->qty - $detail->OrderDtl->qty_reff) + $detail->qty; // Adjust qty_order
                // dd($this->input_details[$key]);
                // $this->inputs['reffhdr_id'] = $this->object->reffhdr_id;
                // $this->inputs['refhdrtr_code'] = $this->object->reffhdrtr_code;               
            }
        }
    }

    public function addItem()
    {
        if (empty($this->inputs['reffhdrtr_code'])) {
            $this->dispatch('error', 'Mohon pilih nota pembelian terlebih dahulu.');
            return;
        }

        $this->input_details[] = [
            'matl_id' => null,
            'qty_order' => null,
            'matl_descr' => null,
            'matl_uom' => null,
            'order_id' => null,
            'qty' => null,
        ];
    }

    public function deleteItem($index)
    {
        try {
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            // Jika tidak ada item lagi di input_details, enable kolom reffhdrtr_code dan wh_code
            if (empty($this->input_details)) {
                $this->isPanelEnabled = true; // Enable warehouse and reffhdrtr_code fields
                $this->inputs['reffhdrtr_code'] = null; // Set reffhdrtr_code to null
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function onPurchaseOrderChanged($value)
    {
        $this->inputs['reffhdr_id'] = $value;

        if ($value) {
            // $this->loadPurchaseOrderDetails($value);
            $orderDetail = OrderDtl::selectRaw('
                order_hdrs.partner_id,
                order_hdrs.partner_code,
                partners.name,
                partners.city,
                order_dtls.matl_id,
                order_dtls.matl_code,
                order_dtls.matl_uom,
                order_dtls.matl_descr,
                order_dtls.qty,
                order_dtls.qty_reff,
                order_dtls.id as reffdtl_id,
                order_dtls.tr_type as reffhdrtr_type,
                order_dtls.tr_code as reffhdrtr_code
            ')
                ->join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')
                ->join('partners', 'order_hdrs.partner_id', '=', 'partners.id')
                ->where('order_hdrs.tr_type', 'PO')
                ->where('order_hdrs.tr_code', $value)
                ->get();

            // dd($orderDetail);
            if (!$orderDetail) {
                $this->dispatch('error', 'Tidak ada detail order yang ditemukan untuk kode pembelian ini.');
                return;
            }

            $this->inputs['partner_id'] = $orderDetail[0]->partner_id;
            $this->inputs['partner_name'] = $orderDetail[0]->name . ' - ' . $orderDetail[0]->city;

            // dd($orderDetail);
            $this->input_details = [];
            foreach ($orderDetail as $detail) {
                $qty_remaining = $detail->qty - $detail->qty_reff;
                // dd($qty_remaining);
                if ($qty_remaining > 0) {
                    // Hanya tambahkan detail jika qty_remaining lebih besar dari 0
                    $this->input_details[] = [
                        'matl_id' => $detail->matl_id,
                        'qty_order' => $qty_remaining,
                        'matl_descr' => $detail->matl_descr,
                        'matl_uom' => $detail->matl_uom,
                        'reffhdr_id' => $value,
                        'reffdtl_id' => $detail->reffdtl_id,
                        'reffhdrtr_type' => $detail->reffhdrtr_type,
                        'reffhdrtr_code' => $detail->reffhdrtr_code,
                        'wh_code' => $this->inputs['wh_code'],
                        'wh_id' => $this->inputs['wh_id'],
                        'qty' => 0, // Inisialisasi qty sebagai null
                    ];
                }
            }
        }
    }

    public function whCodeOnChanged($value)
    {
        // dd($value);
        if (empty($value)) {
            $this->dispatch('error', 'Mohon pilih gudang terlebih dahulu.');
            return;
        }

        // Cari wh_id dari ConfigConst berdasarkan wh_code
        $warehouse = ConfigConst::where('str1', $value)->first();
        $wh_id = $warehouse ? $warehouse->id : null;

        $this->inputs['wh_id'] = $warehouse->id;
        $this->inputs['wh_code'] = $warehouse->str1;
        // dd($inputs['wh_id']);
        // Update input_details dengan wh_id
        foreach ($this->input_details as &$detail) {
            $detail['wh_id'] = $wh_id;
            $detail['wh_code'] = $value;
        }
        unset($detail);
        // dd($this->input_details, $this->inputs);
    }

    #endregion

    #region CRUD Operations
    public function onValidateAndSave()
    {
        // dd($this->inputs, $this->input_details);
        try {
            $this->validate();
            // Validasi header
            if (empty($this->inputs['tr_code']) && empty($this->inputs['reffhdrtr_code']) && empty($this->inputs['partner_id'])) {
                $this->dispatch('error', 'Semua field header wajib diisi');
                return;
            }

            // Cek duplikasi tr_code
            $existingDelivery = DelivHdr::where([
                'tr_type' => $this->trType,
                'tr_code' => $this->inputs['tr_code']
            ])->first();

            if ($existingDelivery && $existingDelivery->id !== $this->object->id) {
                $this->dispatch('error', 'Nomor Surat Jalan ' . $this->inputs['tr_code'] . ' sudah ada. Silakan gunakan nomor yang berbeda.');
                return;
            }

            // Update data partner jika ada
            if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
                $partner = Partner::find($this->inputs['partner_id']);
                $this->inputs['partner_code'] = $partner->code;
            }
            $this->inputs['tr_type'] = $this->trType;
            // dd($this->inputs['wh_id']);

            if ($this->object->isNew()) {
                $this->object->status_code = Status::OPEN;
            }

            // dd($this->input_details);
            // dd($this->inputs, $this->input_details);
            // Persiapkan data untuk service
            // $orderHdr = OrderHdr::where('tr_code', $this->inputs['reffhdrtr_code'])->first();
            $headerData = array_merge($this->inputs, [
                'status_code' => $this->object->status_code,
                // 'reff_code' => $orderHdr ? $orderHdr->id : null,
                // 'reffhdrtr_id' => $orderHdr ? $orderHdr->id : null,
            ]);
            // dd($headerData);

            if ($this->actionValue === 'Edit') {
                $headerData['id'] = $this->object->id;
            }

            $detailData = [];
            foreach ($this->input_details as $key => $detail) {
                // dd($detail);
                $orderDtl = OrderDtl::find($detail['reffdtl_id']);
                $material = Material::find($detail['matl_id']);
                // Ambil orderHdr dari orderDtl, karena reffhdrtr_id tidak tersimpan di input_details
                $orderHdr = $orderDtl ? $orderDtl->OrderHdr : null;

                // dd($headerData);
                $detailData[] = [
                    'trhdr_id' => $headerData['id'],
                    'tr_code' => $headerData['tr_code'],
                    'tr_type' => $headerData['tr_type'],
                    'tr_seq' => $key + 1,
                    'matl_id' => $detail['matl_id'],
                    'matl_code' => $material->code,
                    'matl_descr' => $detail['matl_descr'],
                    'matl_uom' => $detail['matl_uom'],
                    'qty' => $detail['qty'],
                    'wh_id' => $this->inputs['wh_id'],
                    'wh_code' => $this->inputs['wh_code'],
                    'reffdtl_id' => $orderDtl->id ?? null,
                    'reffhdr_id' => $orderHdr->id,
                    'reffhdrtr_type' => $orderHdr->tr_type,
                    'reffhdrtr_code' => $orderHdr->tr_code,
                    'reffdtltr_seq' => $orderDtl->tr_seq,
                    'batch_code' => date('ymd', strtotime($orderHdr->tr_date)),
                ];
                // dd($detailData);
            }

            // Panggil service untuk memproses purchase delivery
            $deliveryService = app(DeliveryService::class);

            // dd($headerData, $detailData);
            if ($this->actionValue === 'Create') {
                $result = $deliveryService->addDelivery($headerData, $detailData);
                // dd($result);
                $this->object = $result['header'];

                // Update headerData dengan id dari PD yang baru
                $headerData['id'] = $this->object->id;

                // // Hitung total_amt dari detailData (price dari OrderDtl dikurangi disc_pct, dikali qty dari delivdtl)
                // $total_amt = 0;
                // foreach ($detailData as $detail) {
                //     if (isset($detail['reffdtl_id']) && isset($detail['qty'])) {
                //         $orderDtl = OrderDtl::find($detail['reffdtl_id']);
                //         if ($orderDtl) {
                //             $price = $orderDtl->price;
                //             $disc_pct = $orderDtl->disc_pct ?? 0;
                //             $qty = $detail['qty'];
                //             $price_after_disc = $price - ($price * $disc_pct / 100);
                //             $total_amt += $price_after_disc * $qty;
                //         }
                //     }
                // }
                // $headerData['total_amt'] = $total_amt;

                // Update juga trhdr_id pada setiap detail
                foreach ($detailData as &$detail) {
                    $detail['trhdr_id'] = $this->object->id;
                }
                unset($detail);


                // Tambahkan update ivt_id pada setiap DelivDtl setelah simpan
                foreach ($detailData as $detail) {
                    $delivDtl = DelivDtl::where([
                        'trhdr_id' => $this->object->id,
                        'matl_id' => $detail['matl_id'],
                        'tr_seq' => $detail['tr_seq'],
                    ])->first();
                    if ($delivDtl) {
                        $ivtBal = IvtBal::where([
                            'matl_id' => $delivDtl->matl_id,
                            'wh_id' => $delivDtl->wh_id,
                            'batch_code' => $delivDtl->batch_code,
                        ])->first();
                        if ($ivtBal) {
                            $delivDtl->ivt_id = $ivtBal->id;
                            $delivDtl->save();
                        }
                    }
                }
            } else {
                // dd($detailData, $headerData);
                $deliveryService->updDelivery($this->object->id, $headerData, $detailData);


                // Hitung total_amt dari detailData (price dari OrderDtl dikurangi disc_pct, dikali qty dari delivdtl)
                $total_amt = 0;
                foreach ($detailData as $detail) {
                    if (isset($detail['reffdtl_id']) && isset($detail['qty'])) {
                        $orderDtl = OrderDtl::find($detail['reffdtl_id']);
                        if ($orderDtl) {
                            $price = $orderDtl->price;
                            $disc_pct = $orderDtl->disc_pct ?? 0;
                            $qty = $detail['qty'];
                            $price_after_disc = $price - ($price * $disc_pct / 100);
                            $total_amt += $price_after_disc * $qty;
                        }
                    }
                }
                $headerData['total_amt'] = $total_amt;

                // Update juga trhdr_id pada setiap detail
                foreach ($detailData as &$detail) {
                    $detail['trhdr_id'] = $this->object->id;
                }
                unset($detail);
            }

            // dd($this->object);
            // DB::commit();

            $this->dispatch('success', 'Purchase Delivery berhasil ' .
                ($this->actionValue === 'Create' ? 'disimpan' : 'diperbarui') . '.');

            return redirect()->route(
                $this->appCode . '.Transaction.PurchaseDelivery.Detail',
                [
                    'action'   => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id),
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal menyimpan Purchase Delivery: ' . $e->getMessage());
            // $this->dispatch('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function onMaterialChanged($index, $matl_id)
    {
        if (empty($this->inputs['reffhdrtr_code'])) {
            $this->dispatch('error', 'Mohon pilih nota pembelian terlebih dahulu.');
            return;
        }

        $orderDetail = OrderDtl::where('tr_code', $this->inputs['reffhdrtr_code'])
            ->where('matl_id', $matl_id)
            ->first();

        if ($orderDetail) {
            $qty_remaining = $orderDetail->qty - $orderDetail->qty_reff;

            $this->input_details[$index] = array_merge($this->input_details[$index], [
                'matl_id' => $orderDetail->matl_id,
                'qty_order' => $qty_remaining,
                'matl_descr' => $orderDetail->matl_descr,
                'matl_uom' => $orderDetail->matl_uom,
                'order_id' => $orderDetail->id,
            ]);
        } else {
            $this->dispatch('error', 'Material tidak ditemukan pada nota pembelian.');
        }
    }

    public function delete()
    {
        try {

            // Panggil service untuk hapus delivery beserta detail dan inventory
            $deliveryService = app(DeliveryService::class);
            $deliveryService->delDelivery($this->object->id);

            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    #endregion

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
