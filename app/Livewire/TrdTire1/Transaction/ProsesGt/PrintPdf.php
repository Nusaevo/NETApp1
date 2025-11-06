<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Master\Partner;
use Illuminate\Support\Collection;

class PrintPdf extends BaseComponent
{
    public $orderIds;
    public $selectedProcessDate;
    public $selectedSrCode;
    public $orders = [];

    protected function onPreRender()
    {
        // --- HILANGKAN pengecekan isEditOrView() ---
        if (empty($this->objectIdValue)) {
            $this->dispatch('error', 'Invalid object ID');
            return;
        }

        // Ambil parameter dari additionalParam (decrypt jika di-encrypt)
        $params = $this->additionalParam;

        // Coba decrypt dulu (jika di-encrypt)
        try {
            $decryptedParam = decryptWithSessionKey($params);
            $params = $decryptedParam;
        } catch (\Exception $e) {
            // Jika gagal decrypt, berarti tidak di-encrypt, gunakan langsung
        }

        // Parse JSON jika string JSON
        if (is_string($params) && json_decode($params) !== null) {
            $decodedParams = json_decode($params, true);
            if (is_array($decodedParams)) {
                $this->selectedProcessDate = $decodedParams['gt_process_date'] ?? null;
                $this->selectedSrCode = $decodedParams['sr_code'] ?? null;
            } else {
                // Fallback: jika JSON decode success tapi bukan array
                $this->selectedProcessDate = $params;
                $this->selectedSrCode = null;
            }
        } else {
            // Fallback untuk kompatibilitas: jika hanya string tanggal saja
            $this->selectedProcessDate = $params;
            $this->selectedSrCode = null;
        }

        if (!$this->selectedProcessDate) {
            $this->dispatch('error', 'Tanggal proses tidak valid');
            return;
        }

        // Ambil OrderHdr beserta OrderDtl yang sesuai dengan filter tanggal dan SR code
        // Load SalesReward untuk setiap OrderDtl
        $orders = OrderHdr::with([
                'OrderDtl' => function($q) {
                    $q->whereDate('gt_process_date', $this->selectedProcessDate);

                    // Filter berdasarkan SR code jika ada
                    if ($this->selectedSrCode) {
                        $q->whereHas('SalesReward', function($query) {
                            $query->where('code', $this->selectedSrCode);
                        });
                    }

                    $q->with('SalesReward');
                },
                'Partner'
            ])
            ->whereHas('OrderDtl', function($q) {
                $q->whereDate('gt_process_date', $this->selectedProcessDate);

                // Filter berdasarkan SR code jika ada
                if ($this->selectedSrCode) {
                    $q->whereHas('SalesReward', function($query) {
                        $query->where('code', $this->selectedSrCode);
                    });
                }
            })
            ->get();

        // Ambil semua gt_partner_code yang unik untuk eager loading partner
        $gtPartnerCodes = collect();
        foreach ($orders as $order) {
            foreach ($order->OrderDtl as $detail) {
                if ($detail->gt_partner_code) {
                    $gtPartnerCodes->push($detail->gt_partner_code);
                }
            }
        }
        $gtPartnerCodes = $gtPartnerCodes->unique()->filter()->values();

        // Load semua partner yang mungkin dibutuhkan (berdasarkan code)
        $partnersByCode = collect();
        if ($gtPartnerCodes->isNotEmpty()) {
            $partnersByCode = Partner::whereIn('code', $gtPartnerCodes->toArray())
                ->get()
                ->keyBy('code');
        }

        // Flatten semua detail dengan informasi customer name untuk sorting
        $detailsWithCustomer = [];
        foreach ($orders as $order) {
            foreach ($order->OrderDtl as $detail) {
                $customerName = $this->getCustomerNameForSorting($order, $detail);
                $detailsWithCustomer[] = [
                    'order' => $order,
                    'detail' => $detail,
                    'customer_name' => $customerName,
                    'is_lain_lain' => $customerName === 'CUSTOMER LAIN-LAIN'
                ];
            }
        }

        // Urutkan: CUSTOMER LAIN-LAIN muncul terakhir, kemudian berdasarkan nama partner
        // Untuk CUSTOMER LAIN-LAIN, urutkan berdasarkan nomor nota (tr_code)
        usort($detailsWithCustomer, function($a, $b) {
            // Jika keduanya LAIN-LAIN, urutkan berdasarkan nomor nota (tr_code)
            if ($a['is_lain_lain'] && $b['is_lain_lain']) {
                $trCodeA = $a['order']->tr_code ?? '';
                $trCodeB = $b['order']->tr_code ?? '';
                return strcmp($trCodeA, $trCodeB);
            }

            // Jika keduanya bukan LAIN-LAIN, urutkan berdasarkan nama customer
            if (!$a['is_lain_lain'] && !$b['is_lain_lain']) {
                return strcmp($a['customer_name'], $b['customer_name']);
            }

            // Jika berbeda, LAIN-LAIN muncul terakhir
            return $a['is_lain_lain'] ? 1 : -1;
        });

        // Group kembali berdasarkan order untuk kompatibilitas dengan view
        // Urutkan orders berdasarkan urutan detail pertama yang muncul
        $groupedOrders = [];
        $orderDetailsMap = [];

        foreach ($detailsWithCustomer as $item) {
            $orderId = $item['order']->id;
            if (!isset($groupedOrders[$orderId])) {
                // Clone order untuk menghindari modifikasi objek asli
                $order = clone $item['order'];
                $order->setRelation('OrderDtl', collect([]));
                $groupedOrders[$orderId] = $order;
                $orderDetailsMap[$orderId] = [];
            }
            $orderDetailsMap[$orderId][] = $item['detail'];
        }

        // Set detail ke setiap order dengan urutan yang benar
        foreach ($groupedOrders as $orderId => $order) {
            $order->setRelation('OrderDtl', collect($orderDetailsMap[$orderId]));
        }

        // Urutkan orders berdasarkan urutan detail pertama yang muncul
        $sortedOrderIds = array_unique(array_map(function($item) {
            return $item['order']->id;
        }, $detailsWithCustomer));

        $sortedOrders = [];
        foreach ($sortedOrderIds as $orderId) {
            if (isset($groupedOrders[$orderId])) {
                $sortedOrders[] = $groupedOrders[$orderId];
            }
        }

        $this->orders = collect($sortedOrders);

        // Simpan partnersByCode ke property untuk digunakan di view (tetap sebagai collection)
        $this->partnersByCode = $partnersByCode;
    }

    public $partnersByCode;

    public function __construct()
    {
        // parent::__construct();
        $this->partnersByCode = collect();
    }

    /**
     * Get customer name untuk sorting (tanpa format " - city")
     */
    private function getCustomerNameForSorting($order, $detail): string
    {
        if (!$order->Partner || !$detail->SalesReward) {
            return $order->Partner ? $order->Partner->name : '';
        }

        $partner = $order->Partner;
        $salesReward = $detail->SalesReward;
        $partnerChars = is_string($partner->partner_chars)
            ? json_decode($partner->partner_chars, true)
            : $partner->partner_chars;

        // Logika CUSTOMER LAIN-LAIN berdasarkan brand
        if ($salesReward->brand && $partnerChars && is_array($partnerChars)) {
            if (in_array($salesReward->brand, ['GT RADIAL', 'GAJAH TUNGGAL']) &&
                (($partnerChars['GT'] ?? null) === false || ($partnerChars['GT'] ?? null) === null)) {
                return 'CUSTOMER LAIN-LAIN';
            }
            if ($salesReward->brand === 'IRC' &&
                (($partnerChars['IRC'] ?? null) === false || ($partnerChars['IRC'] ?? null) === null)) {
                return 'CUSTOMER LAIN-LAIN';
            }
            if ($salesReward->brand === 'ZENEOS' &&
                (($partnerChars['ZN'] ?? null) === false || ($partnerChars['ZN'] ?? null) === null)) {
                return 'CUSTOMER LAIN-LAIN';
            }
        }

        return $partner->name;
    }

    /**
     * Get customer name dengan logic CUSTOMER LAIN-LAIN
     */
    public function getCustomerName($order, $detail): string
    {
        if (!$order->Partner || !$detail->SalesReward) {
            return $order->Partner ? ($order->Partner->name . ' - ' . $order->Partner->city) : '';
        }

        $partner = $order->Partner;
        $salesReward = $detail->SalesReward;
        $partnerChars = is_string($partner->partner_chars)
            ? json_decode($partner->partner_chars, true)
            : $partner->partner_chars;

        // Logika CUSTOMER LAIN-LAIN berdasarkan brand
        if ($salesReward->brand && $partnerChars && is_array($partnerChars)) {
            if (in_array($salesReward->brand, ['GT RADIAL', 'GAJAH TUNGGAL']) &&
                (($partnerChars['GT'] ?? null) === false || ($partnerChars['GT'] ?? null) === null)) {
                return 'CUSTOMER LAIN-LAIN';
            }
            if ($salesReward->brand === 'IRC' &&
                (($partnerChars['IRC'] ?? null) === false || ($partnerChars['IRC'] ?? null) === null)) {
                return 'CUSTOMER LAIN-LAIN';
            }
            if ($salesReward->brand === 'ZENEOS' &&
                (($partnerChars['ZN'] ?? null) === false || ($partnerChars['ZN'] ?? null) === null)) {
                return 'CUSTOMER LAIN-LAIN';
            }
        }

        return $partner->name . ' - ' . $partner->city;
    }

    /**
     * Get customer point name dengan handle kedua versi:
     * - Jika gt_partner_code adalah code partner, ambil name dari partner
     * - Jika gt_partner_code adalah name langsung, gunakan langsung
     */
    public function getCustomerPointName($detail, $orderCity = ''): string
    {
        if (!$detail->gt_partner_code) {
            return '';
        }

        // Gunakan partnersByCode dari property jika sudah di-load
        // Handle kasus collection atau array (karena Livewire serialization)
        $partner = null;
        if ($this->partnersByCode) {
            // Convert ke collection jika masih array (setelah Livewire serialization)
            if (is_array($this->partnersByCode)) {
                $partnersCollection = collect($this->partnersByCode);
            } else {
                $partnersCollection = $this->partnersByCode instanceof Collection
                    ? $this->partnersByCode
                    : collect();
            }

            if ($partnersCollection->has($detail->gt_partner_code)) {
                $partner = $partnersCollection->get($detail->gt_partner_code);
            }
        }

        if ($partner) {
            // Pastikan partner adalah object, bukan array
            if (is_object($partner) && property_exists($partner, 'name')) {
                return $partner->name . ($orderCity ? ' - ' . $orderCity : '');
            } elseif (is_array($partner) && isset($partner['name'])) {
                return $partner['name'] . ($orderCity ? ' - ' . $orderCity : '');
            }
        }

        // Fallback: cek apakah gt_partner_code adalah code partner dengan mencari di database
        $partner = Partner::where('code', $detail->gt_partner_code)->first();

        if ($partner) {
            // gt_partner_code adalah code, ambil name dari partner
            return $partner->name . ($orderCity ? ' - ' . $orderCity : '');
        } else {
            // gt_partner_code sudah berisi name langsung
            return $detail->gt_partner_code . ($orderCity ? ' - ' . $orderCity : '');
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}

