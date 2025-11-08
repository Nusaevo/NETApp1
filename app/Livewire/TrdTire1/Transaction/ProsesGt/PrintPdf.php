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

        // Urutkan: CUSTOMER LAIN-LAIN muncul terakhir, kemudian urutkan berdasarkan gt_tr_code
        usort($detailsWithCustomer, function($a, $b) {
            // Jika berbeda jenis (satu LAIN-LAIN, satu bukan), LAIN-LAIN muncul terakhir
            if ($a['is_lain_lain'] && !$b['is_lain_lain']) {
                return 1; // LAIN-LAIN di bawah
            }
            if (!$a['is_lain_lain'] && $b['is_lain_lain']) {
                return -1; // Bukan LAIN-LAIN di atas
            }

            // Jika sama jenis (keduanya LAIN-LAIN atau keduanya bukan), urutkan berdasarkan gt_tr_code
            $gtTrCodeA = $a['detail']->gt_tr_code ?? '';
            $gtTrCodeB = $b['detail']->gt_tr_code ?? '';

            // Jika gt_tr_code kosong/null, pindahkan ke akhir
            if (empty($gtTrCodeA) && empty($gtTrCodeB)) {
                return 0;
            }
            if (empty($gtTrCodeA)) {
                return 1;
            }
            if (empty($gtTrCodeB)) {
                return -1;
            }

            // Urutkan berdasarkan gt_tr_code
            return strcmp($gtTrCodeA, $gtTrCodeB);
        });

        // Group dan aggregate berdasarkan matl_code dan gt_tr_code
        $aggregatedDetails = [];
        foreach ($detailsWithCustomer as $item) {
            $detail = $item['detail'];
            // Normalisasi key untuk grouping (trim dan handle null/empty)
            $matlCode = trim($detail->matl_code ?? '');
            $gtTrCode = trim($detail->gt_tr_code ?? '');
            // Gunakan '-' untuk empty string agar konsisten
            $matlCode = $matlCode === '' ? '-' : $matlCode;
            $gtTrCode = $gtTrCode === '' ? '-' : $gtTrCode;
            $key = $matlCode . '|' . $gtTrCode;

            if (!isset($aggregatedDetails[$key])) {
                // Clone detail pertama sebagai base
                $aggregatedDetail = clone $detail;

                // Pastikan relasi SalesReward tetap ada
                if ($detail->relationLoaded('SalesReward') && $detail->SalesReward) {
                    $aggregatedDetail->setRelation('SalesReward', $detail->SalesReward);
                }

                // Hitung total point untuk detail pertama
                $initialQty = (float)($detail->qty ?? 0);
                $initialTotalPoint = 0.0;
                if ($detail->SalesReward && $detail->SalesReward->qty > 0) {
                    $initialTotalPoint = ($initialQty / $detail->SalesReward->qty) * $detail->SalesReward->reward;
                }

                // Simpan informasi aggregate (untuk tracking)
                $aggregatedDetail->aggregated_qty = $initialQty;
                $aggregatedDetail->aggregated_total_point = $initialTotalPoint;

                // Update qty asli agar method getAggregatedQty bisa menggunakan nilai yang benar
                $aggregatedDetail->qty = $initialQty;

                $aggregatedDetails[$key] = [
                    'detail' => $aggregatedDetail,
                    'order' => $item['order'],
                    'customer_name' => $item['customer_name'],
                    'is_lain_lain' => $item['is_lain_lain']
                ];
            } else {
                // Sum qty dan total point
                $existing = $aggregatedDetails[$key];
                $existingDetail = $existing['detail'];

                // Hitung qty dan total point dari detail saat ini
                $currentQty = (float)($detail->qty ?? 0);
                $currentTotalPoint = 0.0;
                if ($detail->SalesReward && $detail->SalesReward->qty > 0) {
                    $currentTotalPoint = ($currentQty / $detail->SalesReward->qty) * $detail->SalesReward->reward;
                }

                // Update nilai aggregate
                $existingDetail->aggregated_qty += $currentQty;
                $existingDetail->aggregated_total_point += $currentTotalPoint;

                // Update qty asli dengan nilai yang sudah di-aggregate
                $existingDetail->qty = $existingDetail->aggregated_qty;
            }
        }

        // Konversi aggregated details kembali ke array untuk sorting ulang
        $finalDetails = array_values($aggregatedDetails);

        // Urutkan ulang setelah aggregation (LAIN-LAIN tetap di bawah)
        usort($finalDetails, function($a, $b) {
            // Jika berbeda jenis (satu LAIN-LAIN, satu bukan), LAIN-LAIN muncul terakhir
            if ($a['is_lain_lain'] && !$b['is_lain_lain']) {
                return 1;
            }
            if (!$a['is_lain_lain'] && $b['is_lain_lain']) {
                return -1;
            }

            // Urutkan berdasarkan gt_tr_code
            $gtTrCodeA = $a['detail']->gt_tr_code ?? '';
            $gtTrCodeB = $b['detail']->gt_tr_code ?? '';

            if (empty($gtTrCodeA) && empty($gtTrCodeB)) {
                return 0;
            }
            if (empty($gtTrCodeA)) {
                return 1;
            }
            if (empty($gtTrCodeB)) {
                return -1;
            }

            return strcmp($gtTrCodeA, $gtTrCodeB);
        });

        // Setelah aggregation, kita perlu memastikan hanya satu detail per kombinasi matl_code + gt_tr_code
        // Tetapi kita perlu mempertahankan struktur order untuk kompatibilitas view
        // Solusi: buat collection detail yang sudah di-aggregate, lalu assign ke order pertama yang muncul

        // Buat map untuk tracking detail yang sudah di-aggregate (untuk menghindari duplikasi)
        $uniqueAggregatedDetails = [];
        foreach ($finalDetails as $item) {
            $detail = $item['detail'];
            $matlCode = trim($detail->matl_code ?? '');
            $gtTrCode = trim($detail->gt_tr_code ?? '');
            $matlCode = $matlCode === '' ? '-' : $matlCode;
            $gtTrCode = $gtTrCode === '' ? '-' : $gtTrCode;
            $key = $matlCode . '|' . $gtTrCode;

            // Hanya simpan detail pertama untuk setiap key (karena sudah di-aggregate)
            if (!isset($uniqueAggregatedDetails[$key])) {
                $uniqueAggregatedDetails[$key] = $item;
            }
        }

        // Konversi ke array untuk processing selanjutnya
        $uniqueFinalDetails = array_values($uniqueAggregatedDetails);

        // Group kembali berdasarkan order untuk kompatibilitas dengan view
        $groupedOrders = [];
        $orderDetailsMap = [];

        foreach ($uniqueFinalDetails as $item) {
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
        }, $uniqueFinalDetails));

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
     * Get aggregated qty (T. Ban) - menggunakan nilai yang sudah di-sum
     */
    public function getAggregatedQty($detail): float
    {
        // Cek apakah property aggregated_qty ada (menggunakan multiple method untuk kompatibilitas)
        if (isset($detail->aggregated_qty)) {
            return (float)$detail->aggregated_qty;
        }
        // Jika qty sudah di-update dengan nilai aggregate (karena kita update qty = aggregated_qty), gunakan qty
        // Fallback ke qty asli jika belum di-aggregate
        return (float)($detail->qty ?? 0);
    }

    /**
     * Get aggregated point per unit - dihitung dari total point / total qty
     */
    public function getAggregatedPoint($detail): float
    {
        $aggregatedQty = $this->getAggregatedQty($detail);
        $aggregatedTotalPoint = $this->getAggregatedTotalPoint($detail);

        if ($aggregatedQty > 0) {
            return round($aggregatedTotalPoint / $aggregatedQty, 2);
        }
        return 0;
    }

    /**
     * Get aggregated total point (T. Point) - menggunakan nilai yang sudah di-sum
     */
    public function getAggregatedTotalPoint($detail): float
    {
        // Cek apakah property aggregated_total_point ada
        if (isset($detail->aggregated_total_point)) {
            return round((float)$detail->aggregated_total_point, 2);
        }
        // Fallback ke perhitungan asli jika belum di-aggregate
        if ($detail->SalesReward && $detail->SalesReward->qty > 0) {
            $qty = $this->getAggregatedQty($detail);
            return round(($qty / $detail->SalesReward->qty) * $detail->SalesReward->reward, 2);
        }
        return 0;
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