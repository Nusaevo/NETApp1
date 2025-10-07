<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, DelivPacking, OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Models\TrdTire1\Transaction\DelivPicking;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\OrderService;
use Exception;
use App\Models\TrdRetail1\Inventories\IvtBal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TrdTire1\DeliveryService;
use App\Services\TrdTire1\BillingService;
use Illuminate\Support\Carbon;

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
    public $isPanelEnabled = "true";
    public $purchaseOrders = [];
    public $ddPurchaseOrder = [
        'placeHolder' => "Ketik untuk cari purchase order ...",
        'optionLabel' => "h.tr_code",
        'query' => "SELECT DISTINCT h.tr_code FROM order_hdrs h LEFT JOIN order_dtls d ON h.id = d.trhdr_id WHERE h.tr_type = 'PO' AND d.qty > d.qty_reff",
    ];

    public $ddPartner = [
        'placeHolder' => "Ketik untuk cari customer ...",
        'optionLabel' => "code,name,address,city",
        'query' => "SELECT id,code,name,address,city
                    FROM partners
                    WHERE deleted_at IS NULL AND grp = 'C'",
    ];

    public $isDeliv;

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
        // $this->partners = $this->masterService->getCustomers();
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

            // Load reffhdrtr_code from DelivPacking if not set in DelivHdr
            if (empty($this->inputs['reffhdrtr_code'])) {
                $delivDtl = DelivPacking::where('trhdr_id', $this->object->id)->first();
                if ($delivDtl) {
                    $this->inputs['reffhdrtr_code'] = $delivDtl->reffhdrtr_code;
                    $this->inputs['reffhdr_id'] = $delivDtl->reffhdr_id;
                    // $this->inputs['qty'] = $delivDtl->qty;
                    // $this->inputs['wh_code'] = $delivDtl->wh_code;
                }
            } else {
                // Force load from DelivPacking even if already set
                $delivDtl = DelivPacking::where('trhdr_id', $this->object->id)->first();
                if ($delivDtl && !empty($delivDtl->reffhdrtr_code)) {
                    $this->inputs['reffhdrtr_code'] = $delivDtl->reffhdrtr_code;
                    $this->inputs['reffhdr_id'] = $delivDtl->reffhdr_id;
                }
            }

            // Debug logging for reffhdrtr_code
            Log::info('Purchase Delivery Edit - reffhdrtr_code loaded', [
                'reffhdrtr_code' => $this->inputs['reffhdrtr_code'] ?? 'empty',
                'object_id' => $this->object->id ?? 'no_object'
            ]);
            // Load partner data
            $partner = Partner::find($this->object->partner_id);
            if ($partner) {
                $this->inputs['partner_id'] = $partner->id;
                $this->inputs['partner_name'] = $partner->name;
            }

            // Add existing reffhdrtr_code to purchaseOrders array for edit mode
            if (!empty($this->inputs['reffhdrtr_code'])) {
                $existingCode = $this->inputs['reffhdrtr_code'];

                // Add to purchaseOrders array
                $this->purchaseOrders[] = [
                    'label' => $existingCode,
                    'value' => $existingCode,
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
        $this->inputs['wh_code'] = null;
        $this->inputs['wh_id'] = 0;
        $this->inputs['reffhdrtr_code'] = ''; // Inisialisasi key reffhdrtr_code
    }

    #endregion

    #region Material List Methods
    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = DelivPacking::GetByDelivHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();
            $this->input_details = $this->object_detail->toArray();
            foreach ($this->object_detail as $key => $detail) {
                $order = OrderDtl::find($detail->reffdtl_id);
                $this->input_details[$key]['order_date'] = $order->OrderHdr->tr_date;
                $this->input_details[$key]['qty_order'] = $order->qty - $order->qty_reff + $detail->qty;
                $picking = DelivPicking::where('trpacking_id', $detail->id)->first();
                $this->inputs['wh_id'] = $picking->wh_id;
                $this->inputs['wh_code'] = $picking->wh_code;
                $this->input_details[$key]['wh_id'] = $picking->wh_id;
                $this->input_details[$key]['wh_code'] = $picking->wh_code;
                $this->input_details[$key]['matl_id'] = $picking->matl_id;
                $this->input_details[$key]['matl_code'] = $picking->matl_code;
                $this->input_details[$key]['matl_uom'] = $picking->matl_uom;
                $this->input_details[$key]['trpacking_id'] = $picking->trpacking_id;
                // $this->input_details[$key]['picking_id'] = $picking->id;

                // $this->input_details[$key]['ivt_id'] = $picking->ivt_id;
                // $this->input_details[$key]['batch_code'] = $picking->batch_code;

            }
        }
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
        $this->input_details = [];

        if ($value) {
            // $this->loadPurchaseOrderDetails($value);
            $orderDetail = OrderDtl::selectRaw('
                order_hdrs.partner_id,
                order_hdrs.partner_code,
                order_hdrs.tr_date as order_date,
                partners.name,
                partners.city,
                order_dtls.matl_id,
                order_dtls.matl_code,
                order_dtls.matl_uom,
                order_dtls.matl_descr,
                order_dtls.qty,
                order_dtls.qty_reff,
                order_dtls.id as reffdtl_id,
                order_dtls.trhdr_id as reffhdr_id,
                order_dtls.tr_type as reffhdrtr_type,
                order_dtls.tr_code as reffhdrtr_code,
                order_dtls.tr_seq as reffdtltr_seq
            ')
                ->join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')
                ->join('partners', 'order_hdrs.partner_id', '=', 'partners.id')
                ->where('order_hdrs.tr_type', 'PO')
                ->where('order_hdrs.tr_code', $value)
                ->get();

            //     $data = \App\Models\TrdTire1\Transaction\OrderDtl::join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')
            //     ->where('order_hdrs.tr_type', 'PO')
            //     ->where('order_hdrs.tr_code', 'PO000500')
            //     ->get();
            // dd($data->toArray());
            if ($orderDetail->isEmpty()) {
                $this->dispatch('error', 'Tidak ada detail order yang ditemukan untuk kode pembelian ini.');
                return;
            }

            $this->inputs['partner_id'] = $orderDetail[0]->partner_id;
            $this->inputs['partner_name'] = $orderDetail[0]->name . ' - ' . $orderDetail[0]->city;
            $this->inputs['partner_code'] = $orderDetail[0]->partner_code;

            // dd($orderDetail);
            $delivPacking = new DelivPacking();
            $index = 0;
            foreach ($orderDetail as $detail) {
                $qty_remaining = $detail->qty - $detail->qty_reff;
                if ($qty_remaining > 0) {
                    $this->input_details[] = populateArrayFromModel($delivPacking);
                    $this->input_details[$index]['reffdtl_id'] = $detail->reffdtl_id;
                    $this->input_details[$index]['reffhdr_id'] = $detail->reffhdr_id;
                    $this->input_details[$index]['reffhdrtr_type'] = $detail->reffhdrtr_type;
                    $this->input_details[$index]['reffhdrtr_code'] = $detail->reffhdrtr_code;
                    $this->input_details[$index]['reffdtltr_seq'] = $detail->reffdtltr_seq;
                    $this->input_details[$index]['matl_descr'] = $detail->matl_descr;
                    $this->input_details[$index]['qty'] = 0;
                    // // Tambahan Order Detail
                    $this->input_details[$index]['qty_order'] = $qty_remaining;
                    $this->input_details[$index]['matl_id'] = $detail->matl_id;
                    $this->input_details[$index]['matl_code'] = $detail->matl_code;
                    $this->input_details[$index]['matl_uom'] = $detail->matl_uom;
                    $this->input_details[$index]['wh_id'] = $this->inputs['wh_id'];
                    $this->input_details[$index]['wh_code'] = $this->inputs['wh_code'];
                    $this->input_details[$index]['order_date'] = $detail->order_date;
                    $index++;
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

            // Validasi tanggal terima barang tidak boleh lebih besar dari tanggal sekarang
            if (!empty($this->inputs['tr_date'])) {
                $deliveryDate = Carbon::parse($this->inputs['tr_date']);
                $today = Carbon::now()->startOfDay();

                if ($deliveryDate->gt($today)) {
                    throw new Exception('Tanggal terima barang tidak boleh lebih besar dari tanggal sekarang.');
                }
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

            if ($this->object->isNew()) {
                $this->object->status_code = Status::OPEN;
            }

            $headerData = $this->inputs;
            $detailData = $this->input_details;

            $deliveryService = app(DeliveryService::class);
            $result = $deliveryService->saveDelivery($headerData, $detailData);
            $this->object = $result['header'];

            // Hanya buat billing baru saat create
            if ($this->actionValue === 'Create') {
                $billingService = app(BillingService::class);
                $billingHeaderData = [
                    'id' => 0,
                    'tr_type' => 'APB',
                    'tr_code' => $this->inputs['tr_code'],
                    'tr_date' => $this->inputs['tr_date'],
                ];
                // Ambil delivery_id dari hasil saveDelivery
                $deliveryDetails = [];
                if (!empty($result['header'])) {
                    $deliveryDetails[] = [
                        'deliv_id' => $result['header']->id,
                    ];
                }

                $billingResult = $billingService->saveBilling($billingHeaderData, $deliveryDetails);

                // Cek hasil billing
                if (!empty($billingResult['billing_hdr'])) {
                    // Billing berhasil dibuat
                } else {
                    $this->dispatch('error', 'Gagal membuat Billing untuk delivery order ' . $this->inputs['tr_code']);
                }
            }

            $this->dispatch('success', 'Purchase Delivery berhasil ' .
                ($this->actionValue === 'Create' ? 'disimpan' : 'diperbarui') . '.');

            return redirect()->route(
                $this->appCode . '.Transaction.PurchaseDelivery.Detail',
                [
                    'action'   => encryptWithSessionKey('Create'),
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
            // Validasi apakah object masih ada sebelum dihapus
            if (!$this->object || !$this->object->id) {
                $this->dispatch('error', 'Data Purchase Delivery tidak ditemukan.');
                return;
            }

            // Panggil service untuk hapus billing terlebih dahulu
            $billingService = app(BillingService::class);
            $billingService->delBilling($this->object->billhdr_id);

            // Kemudian hapus delivery
            $deliveryService = app(DeliveryService::class);
            $deliveryService->delDelivery($this->object->id);

            $this->dispatch('success', 'Purchase Delivery berhasil dihapus.');
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
