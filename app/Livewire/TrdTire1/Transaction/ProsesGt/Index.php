<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Master\Partner;
use App\Models\SysConfig1\ConfigAppl;
// use App\Models\ConfigAppl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $selectedItems = [];
    public $selectedItemsForDisplay = [];
    public $gt_tr_code = '';
    public $gt_partner_code = '';
    public $partners = [];
    public $useSimpleDropdown = false;
    public $start_date;
    public $end_date;
    public $sr_code;
    public $sr_codes = [];
    protected $listeners = [
        'openProsesDateModal',
        'refreshTable' => 'render',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);

        $this->partners = Partner::select('id', 'code', 'name', 'city')
            ->orderBy('name')
            ->get()
            ->map(function ($partner) {
                return [
                    'label' => $partner->code . ' - ' . $partner->name . ' - ' . ($partner->city ?? ''),
                    'value' => $partner->code,
                    'id' => $partner->id
                ];
            })
            ->toArray();

        // Fetch SR Codes - gunakan DISTINCT untuk menghilangkan duplikasi
        $this->sr_codes = SalesReward::selectRaw('DISTINCT code, descrs')
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get()
            ->map(function ($sr) {
                return [
                    'label' => $sr->code . ' - ' . $sr->descrs, // Gabungkan code dan descrs
                    'value' => $sr->code, // Tetap gunakan code sebagai value
                ];
            })
            ->toArray();
    }
    public function onSrCodeChanged()
    {
        // sekarang $this->sr_code sudah berisi value terbaru
        $salesReward = SalesReward::where('code', $this->sr_code)->first();

        if ($salesReward) {
            $this->start_date = Carbon::parse($salesReward->beg_date)->format('Y-m-d');
            $this->end_date   = Carbon::parse($salesReward->end_date)->format('Y-m-d');
        } else {
            $this->start_date = $this->end_date = null;
            $this->dispatch('error', 'Sales Reward tidak ditemukan.');
        }
    }


    public function openProsesDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems = $selectedItems;

        // Reset form fields ketika modal dibuka
        $this->gt_tr_code = '';
        $this->gt_partner_code = '';
        $this->useSimpleDropdown = false; // Reset ke dropdown search

        // Load selected items with relationships for display
        // Use join to ensure relationships are loaded properly
        $this->selectedItemsForDisplay = OrderDtl::whereIn('order_dtls.id', $orderIds)
            ->join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')
            ->leftJoin('partners', 'order_hdrs.partner_id', '=', 'partners.id')
            ->select(
                'order_dtls.id',
                'order_dtls.matl_code',
                'order_hdrs.tr_code',
                'partners.name as partner_name',
                'partners.city as partner_city'
            )
            ->get()
            ->map(function ($item) {
                $namaPembeli = '';
                if ($item->partner_name) {
                    $namaPembeli = $item->partner_name;
                    if ($item->partner_city) {
                        $namaPembeli .= ' - ' . $item->partner_city;
                    }
                }

                return [
                    'id' => $item->id,
                    'nama_pembeli' => $namaPembeli,
                    'no_nota' => $item->tr_code ?? '',
                    'kode_barang' => $item->matl_code ?? '',
                ];
            })
            ->toArray();
        // Validasi: semua pilihan harus dari customer yang sama atau semua adalah "customer lain-lain"
        if (!$this->validateSameCustomerForSelection($orderIds)) {
            $this->dispatch('error', 'Customer berbeda. Pilih data dengan customer yang sama atau semua adalah customer lain-lain.');
            // Jangan buka modal jika validasi gagal
            return;
        }

        $this->dispatch('open-modal-proses-gt');
    }

    public function setNotaGT()
    {
        // Get year and month from gt_process_date - ambil dari record yang dipilih
        $processDate = null;

        if (!empty($this->selectedOrderIds)) {
            // Ambil gt_process_date dari record pertama yang dipilih
            $firstRecord = OrderDtl::where('id', $this->selectedOrderIds[0])->first();
            if ($firstRecord && $firstRecord->gt_process_date) {
                $processDate = $firstRecord->gt_process_date;
            }
        }

        if ($processDate) {
            // Parse tanggal dari record (format: YYYY-MM-DD atau datetime)
            $date = Carbon::parse($processDate);
            $year = $date->format('y'); // Two-digit year
            $month = $date->format('m'); // Two-digit month
        } else {
            // Fallback ke bulan sekarang jika tidak ada gt_process_date
            $year = now()->format('y'); // Two-digit year
            $month = now()->format('m'); // Two-digit month
        }

        // Get the last sequence number for the selected month
        $lastSequence = OrderDtl::whereNotNull('gt_tr_code')
            ->where('gt_tr_code', 'like', $year . $month . '%')
            ->orderBy('gt_tr_code', 'desc')
            ->value('gt_tr_code');

        // Extract the last sequence number or start from 0
        $sequence = $lastSequence ? (int)substr($lastSequence, -4) : 0;
        $sequence++;

        // Format GT number
        $this->gt_tr_code = $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function submitProsesGT()
    {
        // Pastikan gt_partner_code adalah string, bukan array
        if (is_array($this->gt_partner_code)) {
            $this->gt_partner_code = '';
        }

        $this->validate([
            'gt_tr_code' => 'nullable', // Allow null values
            'gt_partner_code' => 'nullable', // Allow null values
        ]);

        // Validasi ulang sebelum proses: semua pilihan harus dari customer yang sama atau semua adalah "customer lain-lain"
        if (!empty($this->selectedOrderIds)) {
            if (!$this->validateSameCustomerForSelection($this->selectedOrderIds)) {
                $this->dispatch('error', 'Customer berbeda. Pilih data dengan customer yang sama atau semua adalah customer lain-lain.');
                return;
            }
        }

        DB::beginTransaction();

        try {
            $partner = null;
            // Pastikan gt_partner_code adalah string sebelum digunakan
            $partnerCode = is_string($this->gt_partner_code) ? trim($this->gt_partner_code) : '';

            if (!empty($partnerCode)) {
                $partner = Partner::where('code', $partnerCode)->first();
                if (!$partner) {
                    throw new \Exception('Partner tidak ditemukan');
                }
            }

            // Prepare update data without gt_process_date
            $updateData = [
                'gt_tr_code' => $this->gt_tr_code ?: '', // Set null if empty
                'gt_partner_code' => $partner ? $partner->code : '', // Set null if no partner
                'gt_partner_id' => $partner ? $partner->id : 0, // Set null if no partner
            ];

            // Update OrderDtl without updating gt_process_date
            OrderDtl::whereIn('id', $this->selectedOrderIds)
                ->update($updateData);

            DB::commit();

            // Reset form fields setelah berhasil
            $this->gt_tr_code = '';
            $this->gt_partner_code = '';
            $this->useSimpleDropdown = false;

            $this->dispatch('close-modal-proses-gt');
            $this->dispatch('success', ['Proses GT berhasil disimpan']);
            // $this->dispatch('refreshTable');
            $this->dispatch('clearSelections'); // Clear selection after successful process
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function submitProsesNota()
    {
        // Ambil data SalesReward (atau SalesOrder) berdasarkan sr_code
        $salesReward = SalesReward::where('code', $this->sr_code)->first();
        if (!$salesReward) {
            $this->dispatch('error', 'Sales Reward tidak ditemukan.');
            return;
        }

        // Parsing ke Carbon untuk perbandingan
        $begDate = Carbon::parse($salesReward->beg_date)->format('Y-m-d');
        $endDate = Carbon::parse($salesReward->end_date)->format('Y-m-d');

        // Validasi dengan Closure
        $this->validate([
            'start_date' => [
                'required',
                'date',
                // harus >= beg_date
                function ($attribute, $value, $fail) use ($begDate) {
                    if ($value < $begDate) {
                        $fail("Tanggal Nota Awal tidak boleh kurang dari {$begDate}.");
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                // harus <= end_date
                function ($attribute, $value, $fail) use ($endDate) {
                    if ($value > $endDate) {
                        $fail("Tanggal Nota Akhir tidak boleh melebihi {$endDate}.");
                    }
                },
            ],
        ]);

        DB::beginTransaction();

        try {
            $result = $this->callUpdateGTProcessDateByAppCode(
                $this->appCode,     // app code
                $this->sr_code,     // SR code (from dropdown)
                $this->start_date,  // begin date
                $this->end_date     // end date dari form
            );

            if ($result >= 0) {
                DB::commit();
                $this->dispatch('close-modal-proses-nota');
                $this->dispatch('success', ["Berhasil update: $result baris."]);
                $this->dispatch('refreshTable');
            } else {
                throw new \Exception("Terjadi kesalahan saat update.");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['Terjadi kesalahan saat memproses data: ' . $e->getMessage()]);
        }
    }

    public static function callUpdateGTProcessDateByAppCode(string $appCode, string $srCode, string $beginDate, string $endDate): int
    {
        try {
            // Ambil db_name dari model ConfigAppl
            $config = ConfigAppl::where('code', $appCode)->whereNull('deleted_at')->first();

            if (!$config || empty($config->db_name)) {
                Log::warning("ConfigAppl tidak ditemukan atau db_name kosong untuk appCode: $appCode");
                return -1;
            }

            $connectionName = $config->db_name;

            // Panggil function update_gt_process_date dari koneksi tersebut
            $result = DB::connection($connectionName)->selectOne("
                SELECT update_gt_process_date(?, ?, ?)
            ", [$srCode, $beginDate, $endDate]);

            // dd($result);
            return $result->update_gt_process_date ?? 0;
        } catch (\Exception $e) {
            Log::error("Gagal panggil update_gt_process_date: " . $e->getMessage());
            return -1;
        }
    }

    public function fillCustomerPoint()
    {
        if (!empty($this->selectedOrderIds)) {
            // Ambil data OrderDtl berdasarkan ID yang dipilih
            $orderDtl = OrderDtl::where('id', $this->selectedOrderIds[0])->first();

            if ($orderDtl && $orderDtl->trhdr_id) {
                // Ambil data OrderHdr menggunakan trhdr_id
                $orderHdr = OrderHdr::with('Partner')->where('id', $orderDtl->trhdr_id)->first();

                if ($orderHdr && $orderHdr->Partner) {
                    $this->gt_partner_code = $orderHdr->Partner->code; // Isi dengan code partner
                    $this->useSimpleDropdown = true; // Ubah ke dropdown biasa
                } else {
                    $this->dispatch('error', 'Partner tidak ditemukan untuk OrderHdr yang dipilih.');
                }
            } else {
                $this->dispatch('error', 'OrderDtl atau trhdr_id tidak ditemukan.');
            }
        } else {
            $this->dispatch('error', 'Tidak ada nota yang dipilih.');
        }
    }

    protected function onPreRender()
    {
        // Tambahkan logika pra-render jika diperlukan
    }

    /**
     * Validasi apakah semua item yang dipilih boleh di-set nota barengan
     * - Jika semua adalah "customer lain-lain", izinkan
     * - Jika tidak semua "customer lain-lain", partner harus sama
     */
    private function validateSameCustomerForSelection(array $orderIds): bool
    {
        if (empty($orderIds)) {
            return false;
        }

        // Load OrderDtl dengan relasi yang diperlukan
        $orderDetails = OrderDtl::whereIn('id', $orderIds)
            ->with(['OrderHdr.Partner', 'SalesReward'])
            ->get();

        // Cek apakah semua adalah "customer lain-lain"
        $allLainLain = true;
        foreach ($orderDetails as $detail) {
            if (!$this->isCustomerLainLain($detail)) {
                $allLainLain = false;
                break;
            }
        }

        // Jika semua adalah "customer lain-lain", izinkan set nota barengan
        if ($allLainLain) {
            return true;
        }

        // Jika tidak semua "customer lain-lain", validasi partner harus sama
        $partnerIds = $orderDetails
            ->pluck('OrderHdr.partner_id')
            ->filter()
            ->unique()
            ->values();

        return $partnerIds->count() <= 1;
    }

    /**
     * Cek apakah OrderDtl adalah "customer lain-lain"
     * berdasarkan brand dan partner_chars
     */
    private function isCustomerLainLain($orderDtl): bool
    {
        if (!$orderDtl->OrderHdr || !$orderDtl->OrderHdr->Partner || !$orderDtl->SalesReward) {
            return false;
        }

        $partner = $orderDtl->OrderHdr->Partner;
        $salesReward = $orderDtl->SalesReward;
        $partnerChars = is_string($partner->partner_chars)
            ? json_decode($partner->partner_chars, true)
            : $partner->partner_chars;

        // Logika CUSTOMER LAIN-LAIN berdasarkan brand
        if ($salesReward->brand && $partnerChars && is_array($partnerChars)) {
            // GT RADIAL atau GAJAH TUNGGAL
            if (in_array($salesReward->brand, ['GT RADIAL', 'GAJAH TUNGGAL']) &&
                (($partnerChars['GT'] ?? null) === false || ($partnerChars['GT'] ?? null) === null)) {
                return true;
            }
            // IRC
            if ($salesReward->brand === 'IRC' &&
                (($partnerChars['IRC'] ?? null) === false || ($partnerChars['IRC'] ?? null) === null)) {
                return true;
            }
            // ZENEOS
            if ($salesReward->brand === 'ZENEOS' &&
                (($partnerChars['ZN'] ?? null) === false || ($partnerChars['ZN'] ?? null) === null)) {
                return true;
            }
        }

        return false;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
