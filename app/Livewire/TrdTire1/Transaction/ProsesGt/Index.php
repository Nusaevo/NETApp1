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
                    'label' => $partner->name . ' - ' . $partner->city,
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

        $this->dispatch('open-modal-proses-gt');
    }

    public function setNotaGT()
    {
        $year = now()->format('y'); // Two-digit year
        $month = now()->format('m'); // Two-digit month

        // Get the last sequence number for the current month
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
        $this->validate([
            'gt_tr_code' => 'nullable', // Allow null values
            'gt_partner_code' => 'nullable', // Allow null values
        ]);

        DB::beginTransaction();

        try {
            $partner = null;
            if (!empty($this->gt_partner_code)) {
                $partner = Partner::where('code', $this->gt_partner_code)->first();
                if (!$partner) {
                    throw new \Exception('Partner tidak ditemukan');
                }
            }

            // Prepare update data without gt_process_date
            $partnerNameWithCity = '';
            if ($partner) {
                $partnerNameWithCity = $partner->name;
                if ($partner->city) {
                    $partnerNameWithCity .= ' - ' . $partner->city;
                }
            }

            $updateData = [
                'gt_tr_code' => $this->gt_tr_code ?: '', // Set null if empty
                'gt_partner_code' => $partnerNameWithCity, // Set nama - kota if partner exists
                'gt_partner_id' => $partner ? $partner->id : 0, // Set null if no partner
            ];

            // Update OrderDtl without updating gt_process_date
            OrderDtl::whereIn('id', $this->selectedOrderIds)
                ->update($updateData);

            DB::commit();
            $this->dispatch('close-modal-proses-gt');
            $this->dispatch('success', ['Proses GT berhasil disimpan']);
            $this->dispatch('refreshTable');
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

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
