<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseDataTableComponent;
use App\Models\TrdTire1\Master\SalesReward;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\SysConfig1\ConfigSnum;

class IndexDataTable extends BaseDataTableComponent
{
    public $print_date;
    public $selectedItems = [];
    public $deletedRemarks = [];
    public $filters = [];
    public array $appliedFilters = [
        'gt_process_date' => null,
        'sr_code' => null,
    ];
    protected $listeners = [
        'refreshTable' => 'render',
        'onSrCodeChanged',
        'clearSelections',
    ];
    protected $model = OrderDtl::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('orderHdr.tr_code', 'desc');

        // dd(request()->query('table-filters'));

    }

    public function builder(): Builder
    {
        $query = OrderDtl::query()
            ->with(['OrderHdr', 'OrderHdr.Partner', 'SalesReward', 'Material'])
            ->where('order_dtls.tr_type', 'SO')
            ->select('order_dtls.*')
            ->join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')
            ->join('partners', 'order_hdrs.partner_id', '=', 'partners.id')
            ->leftJoin('sales_rewards', function($join) {
                $join->on('order_dtls.matl_id', '=', 'sales_rewards.matl_id')
                     ->whereNull('sales_rewards.deleted_at')
                     ->whereRaw('sales_rewards.id = (
                         SELECT MIN(sr2.id)
                         FROM sales_rewards sr2
                         WHERE sr2.matl_id = order_dtls.matl_id
                         AND sr2.deleted_at IS NULL
                     )');
            })
            ->whereRaw('1=0'); // Default: Tidak menampilkan data

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make('Nama Pembeli', 'orderHdr.Partner.name')
                ->sortable()
                ->format(function ($value, $row) {
                    if (!$row->orderHdr->Partner || !$row->SalesReward) {
                        return '';
                    }

                    $partner = $row->orderHdr->Partner;
                    $salesReward = $row->SalesReward;
                    $partnerChars = $partner->partner_chars;

                    // Logika CUSTOMER LAIN-LAIN berdasarkan brand
                    if ($salesReward->brand && $partnerChars) {
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
                }),
            Column::make('No. Nota', 'orderHdr.tr_code')
                ->sortable()
                ->format(fn($value, $row) =>
                    $row->orderHdr
                        ? '<a href="'.route($this->appCode.'.Transaction.SalesOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->orderHdr->id)
                        ]).'">'.$row->orderHdr->tr_code.'</a>'
                        : ''
                )->html(),

            Column::make('Kode barang', 'matl_code')
                ->sortable(),
            Column::make('Nama barang', 'matl_descr')
                ->sortable(),
            Column::make('Qty', 'qty')
                ->sortable(),

            // Kolom point reward
            Column::make('Point', 'id')
                ->label(function ($row) {
                    if ($row->SalesReward && $row->SalesReward->qty > 0) {
                        return round(($row->qty / $row->SalesReward->qty) * $row->SalesReward->reward, 2);
                    }
                    return 0;
                })
                ->sortable(),

            Column::make('No Nota GT', 'gt_tr_code')
                ->label(fn($row) => ($row->gt_tr_code))
                ->sortable(),
            Column::make('Custommer Point', 'gt_partner_code')
                ->label(fn($row) => $row->gt_partner_code ? $row->gt_partner_code : '')
                ->sortable(),
            Column::make('Tgl Proses GT', 'gt_process_date')
                ->sortable(),
            Column::make('CID Point', 'gt_partner_code')
                ->label(fn($row) => $row->gt_partner_code ? ($row->orderHdr->Partner->code ?? '') : '') // Show code only if gt_partner_code is filled
                ->sortable(),
            Column::make('CID Nota', 'orderHdr.partner_code')
                ->label(fn($row) => $row->orderHdr->partner_code ?? '')
                ->sortable(),
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'custom_actions' => [],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions
                    ]);
                }),
        ];
    }

    public function filters(): array
    {
        $configDetails = $this->getConfigDetails();
        $processDates = OrderDtl::select(DB::raw('DATE(gt_process_date) as gt_process_date')) // Format hanya tanggal
            ->distinct()
            ->whereNotNull('gt_process_date')
            ->orderBy('gt_process_date', 'desc')
            ->pluck('gt_process_date', 'gt_process_date')
            ->toArray();

        // Add "Not Selected" option for print_date
        $processDates = ['' => 'Not Selected'] + $processDates;


        // Get SR code options from sales_rewards table
        $srCodeOptions = SalesReward::select('code', 'descrs')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('code')
            ->get()
            ->pluck('descrs', 'code')
            ->toArray();

        // Add "Pilih SR Code..." option
        $srCodeOptions = ['' => 'Pilih SR Code...'] + $srCodeOptions;

        return [
            SelectFilter::make('Tanggal Proses', 'gt_process_date')
                ->options($processDates)
                ->filter(function (Builder $builder, string $value) {
                    if ($value) {
                        // simpan ke state persis seperti TaxInvoice
                        $this->filters['gt_process_date'] = $value;
                        $this->appliedFilters['gt_process_date'] = $value;
                        // Cek apakah semua filter sudah dipilih sebelum menampilkan data
                        $this->checkAllFiltersApplied($builder);
                        $builder->whereDate('order_dtls.gt_process_date', $value); // Gunakan whereDate untuk mencocokkan hanya tanggal
                    } else {
                        $this->appliedFilters['gt_process_date'] = null;
                    }
                }),
            SelectFilter::make('SR Code')
                ->options($srCodeOptions)
                ->filter(function (Builder $builder, $value) {
                    if (!empty($value)) {
                        $this->appliedFilters['sr_code'] = $value;
                        // Cek apakah semua filter sudah dipilih sebelum menampilkan data
                        $this->checkAllFiltersApplied($builder);
                        $builder->whereHas('SalesReward', function ($query) use ($value) {
                            $query->where('code', $value);
                        });
                    } else {
                        $this->appliedFilters['sr_code'] = null;
                    }
                }),
        ];
    }

    public function bulkActions(): array
    {
        return [
            'prosesNota' => 'Proses Nota',
            'prosesNotadanPoint' => 'No Nota dan Point',
            'cetakNota' => 'Cetak Nota',
        ];
    }
    public function getConfigDetails()
    {
        $config = Configsnum::where('code', 'SO_FPAJAK_LASTID')->first();
        if ($config) {
            return [
                'last_cnt' => $config->last_cnt,
                'wrap_high' => $config->wrap_high,
            ];
        }
        return [
            'last_cnt' => 'N/A',
            'wrap_high' => 'N/A',
        ];
    }

    /**
     * Remove the default whereRaw('1=0') condition to allow data to be displayed
     */
    private function removeDefaultNoDataCondition(Builder $builder): void
    {
        $builder->getQuery()->wheres = array_filter($builder->getQuery()->wheres, function($where) {
            return $where['type'] !== 'raw' || $where['sql'] !== '1=0';
        });
    }

    /**
     * Check if all required filters are applied before showing data
     */
    private function checkAllFiltersApplied(Builder $builder): void
    {
        // Check if all required filters are applied (Tanggal Proses, SR Code)
        $hasProcessDate = !empty($this->appliedFilters['gt_process_date']);
        $hasSrCode = !empty($this->appliedFilters['sr_code']);

        // Only remove the default no-data condition if all filters are applied
        if ($hasProcessDate && $hasSrCode) {
            $this->removeDefaultNoDataCondition($builder);

            // Add custom sorting to make CUSTOMER LAIN-LAIN appear last
            $builder->orderByRaw("
                CASE
                    WHEN sales_rewards.brand IN ('GT RADIAL', 'GAJAH TUNGGAL') AND (partners.partner_chars->>'GT' = 'false' OR partners.partner_chars->>'GT' IS NULL)
                    THEN 1
                    WHEN sales_rewards.brand = 'IRC' AND (partners.partner_chars->>'IRC' = 'false' OR partners.partner_chars->>'IRC' IS NULL)
                    THEN 1
                    WHEN sales_rewards.brand = 'ZENEOS' AND (partners.partner_chars->>'ZN' = 'false' OR partners.partner_chars->>'ZN' IS NULL)
                    THEN 1
                    ELSE 0
                END,
                order_dtls.gt_process_date DESC,
                partners.name,
                order_hdrs.tr_code
            ");
        }
    }

    public function setNotaGT()
    {
        // Validasi: semua pilihan harus dari customer yang sama
        if (!$this->validateSameCustomerForSelection()) {
            $this->dispatch('error', 'Customer berbeda. Pilih data dengan customer yang sama.');
            return;
        }

        // Get the current year and month
        $year = now()->format('y'); // Two-digit year
        $month = now()->format('m'); // Two-digit month

        // Get the last sequence number for the current month
        $lastSequence = OrderDtl::whereNotNull('gt_tr_code')
            ->where('gt_tr_code', 'like', $year . $month . '%')
            ->orderBy('gt_tr_code', 'desc')
            ->value('gt_tr_code');

        // Extract the last sequence number or start from 0
        $sequence = $lastSequence ? (int)substr($lastSequence, -4) : 0;

        // Iterate over selected items and assign GT numbers
        foreach ($this->getSelected() as $id) {
            $sequence++; // Increment the sequence number
            $gtTrCode = $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT); // Format GT number

            // Update the record with the generated GT number
            OrderDtl::where('id', $id)->update(['gt_tr_code' => $gtTrCode]);
        }

        // Dispatch a success message
        $this->dispatch('success', 'Nomor Nota GT berhasil diatur.');
    }

    public function prosesNotadanPoint()
    {
        if (count($this->getSelected()) > 0) {
            // Validasi: semua pilihan harus dari customer yang sama
            if (!$this->validateSameCustomerForSelection()) {
                $this->dispatch('error', 'Customer berbeda. Pilih data dengan customer yang sama.');
                return;
            }

            $selectedItems = OrderDtl::whereIn('id', $this->getSelected())
                ->with('OrderHdr')
                ->get()
                ->map(function ($order) {
                    return [
                        'nomor_nota' => $order->OrderHdr->tr_code ?? '',
                    ];
                })
                ->toArray();

            $this->dispatch('openProsesDateModal', orderIds: $this->getSelected(), selectedItems: $selectedItems);

            // Refresh page setelah membuka modal
            $this->dispatch('refreshTable');
        } else {
            $this->dispatch('error', 'Pilih minimal satu data untuk proses nota dan point.');
        }
    }

    public function prosesNota()
    {
        $this->dispatch('open-modal-proses-nota'); // Dispatch event to open the modal
    }

    public function cetakNota()
    {
        $selectedProcessDate = $this->filters['gt_process_date'] ?? null;

        if ($selectedProcessDate) {
            $orderIds = OrderDtl::where('gt_process_date', $selectedProcessDate)
                ->where('tr_type', 'SO')
                ->pluck('id')
                ->toArray();

            if (empty($orderIds)) {
                logger()->info('No data found for the selected process date.', ['gt_process_date' => $selectedProcessDate]);
                $this->dispatch('error', 'Tidak ada data untuk dicetak.');
                return;
            }

            return redirect()->route($this->appCode . '.Transaction.ProsesGt.PrintPdf', [
                'action' => encryptWithSessionKey('Print'),
                'objectId' => encryptWithSessionKey(json_encode($orderIds)),
                'additionalParam' => $selectedProcessDate,
            ]);
        }

        $this->dispatch('error', 'Tanggal proses belum dipilih.');
    }

    public function clearSelections()
    {
        $this->clearSelected();
    }

    private function validateSameCustomerForSelection(): bool
    {
        $selectedIds = $this->getSelected();
        if (empty($selectedIds)) {
            return false;
        }

        $partnerIds = OrderDtl::whereIn('id', $selectedIds)
            ->with('OrderHdr')
            ->get()
            ->pluck('OrderHdr.partner_id')
            ->filter()
            ->unique()
            ->values();

        return $partnerIds->count() <= 1;
    }

    // protected function isFirstFilterApplied(Builder $query): bool
    // {
    //     // Check if the query has only one where condition (whereRaw('1=0'))
    //     return count($query->getQuery()->wheres) === 1 && $query->getQuery()->wheres[0]['type'] === 'raw';
    // }
}
