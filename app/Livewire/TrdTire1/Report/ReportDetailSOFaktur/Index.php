<?php

namespace App\Livewire\TrdTire1\Report\ReportDetailSO;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\Util\GenericExcelExport;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Index extends BaseComponent
{
    public $startCode;
    public $endCode;
    public $filterStatus = '';
    public $filterPartner = '';
    public $filterMaterialId = '';
    public $filterSalesType = '';
    public $filterTrCode = '';
    public $results = [];

    public $ddPartner = [
        'placeHolder' => "Ketik untuk cari customer ...",
        'optionLabel' => "code,name,address,city",
        'query' => "SELECT id,code,name,address,city
                    FROM partners
                    WHERE deleted_at IS NULL AND grp = 'C'",
    ];

    public $materialQuery = "
        SELECT m.id, m.code, m.name
        FROM materials m
        WHERE m.status_code = 'A'
        AND m.deleted_at IS NULL
    ";

    protected function onPreRender()
    {
        $this->resetFilters();
    }

    public function search()
    {
        // Validasi: minimal harus ada salah satu filter (tanggal, partner, kode barang, tipe penjualan, atau nomor nota)
        if (isNullOrEmptyNumber($this->startCode) && empty($this->filterPartner) && empty($this->filterMaterialId) && empty($this->filterTrCode)) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Mohon memilih salah satu filter untuk melakukan pencarian'
            ]);
            // $this->addError('startCode',  "Mohon lengkapi tanggal awal atau pilih customer");
            return;
        }

        $this->resetErrorBag();

        // Jika tanggal kosong, gunakan range yang sangat luas
        if (isNullOrEmptyNumber($this->startCode)) {
            $startDate = '1900-01-01';
            $endDate = '2099-12-31';
        } else {
            $startDate = addslashes($this->startCode);
            $endDate = addslashes($this->endCode ?: $this->startCode);
        }

        // Build filter conditions
        $statusFilter = "";
        switch ($this->filterStatus) {
            case 'batal':
                $statusFilter = "AND oh.status_code = 'X'";
                break;
            case 'belum_terkirim':
                $statusFilter = "AND dh.tr_date IS NULL";
                break;
            case 'belum_lunas':
                $statusFilter = "AND ph.tr_date IS NULL";
                break;
        }

        $partnerFilter = $this->filterPartner ? "AND p.id = {$this->filterPartner}" : "";
        $materialCodeFilter = $this->filterMaterialId ? "AND od.matl_id = " . intval($this->filterMaterialId) : "";
        $salesTypeFilter = $this->filterSalesType ? "AND oh.sales_type = '{$this->filterSalesType}'" : "";
        $trCodeFilter = $this->filterTrCode ? "AND oh.tr_code LIKE '%" . addslashes($this->filterTrCode) . "%'" : "";

        // Query untuk mendapatkan data sales order
        $query = "
            SELECT oh.tr_code, oh.tr_date, p.name, p.city,
            od.matl_code, od.matl_descr, od.qty, od.price, od.disc_pct, od.amt, oh.status_code, oh.npwp_name,
            dh.tr_date as ship_date,
            bh.tr_date as billing_date,
            bh.print_date as collect_date,
            ph.tr_date as paid_date
            FROM order_hdrs oh
            JOIN order_dtls od on od.trhdr_id=oh.id
            JOIN partners p ON p.id=oh.partner_id
            LEFT OUTER JOIN deliv_packings dp on dp.reffdtl_id=od.id
            LEFT OUTER JOIN deliv_hdrs dh on dh.id=dp.trhdr_id
            LEFT OUTER JOIN billing_orders bo on bo.reffdtl_id=od.id
            LEFT OUTER JOIN billing_hdrs bh on bh.id=bo.trhdr_id
            LEFT OUTER JOIN payment_dtls pd on pd.billhdr_id=bh.id
            LEFT OUTER JOIN payment_hdrs ph on ph.id=pd.trhdr_id
            WHERE oh.tr_type='SO'
                AND oh.tr_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$statusFilter}
                {$partnerFilter}
                {$materialCodeFilter}
                {$salesTypeFilter}
                {$trCodeFilter}
                AND oh.deleted_at IS NULL
                AND od.deleted_at IS NULL
            ORDER BY oh.tr_date ASC, oh.tr_code, od.tr_seq
        ";

        // Execute query
        $rows = DB::connection(Session::get('app_code'))->select($query);

        // Group data by nota untuk format yang sesuai
        $groupedResults = [];
        $currentNota = null;
        $notaData = null;

        foreach ($rows as $row) {
            if ($currentNota !== $row->tr_code) {
                // Save previous nota if exists
                if ($notaData) {
                    $groupedResults[] = $notaData;
                }

                // Start new nota
                $currentNota = $row->tr_code;
                $notaData = [
                    'no_nota' => $row->tr_code,
                    'tgl_nota' => $row->tr_date,
                    'nama_customer' => $row->name . ($row->city ? ' - ' . $row->city : ''),
                    'items' => []
                ];
            }

            // Add item to current nota
            $notaData['items'][] = [
                'kode' => $row->matl_code,
                'nama_barang' => $row->matl_descr,
                'qty' => $row->qty,
                'harga' => $row->price,
                'disc' => $row->disc_pct,
                'total' => $row->amt,
                't_kirim' => $row->ship_date,
                'tgl_tagih' => $row->collect_date,
                's' => $row->status_code,
                'wajib_pajak' => $row->npwp_name,
                'tgl_lunas' => $row->paid_date,
                'customer_name' => $row->name . ($row->city ? ' - ' . $row->city : '')
            ];
        }

        // Add last nota
        if ($notaData) {
            $groupedResults[] = $notaData;
        }

        // Assign results
        $this->results = $groupedResults;
    }

    public function resetFilters()
    {
        $this->startCode = '';
        $this->endCode = '';
        $this->filterStatus = '';
        $this->filterPartner = '';
        $this->filterMaterialId = '';
        $this->filterSalesType = '';
        $this->filterTrCode = '';
        $this->results = [];
    }

    public function getStatusOptions()
    {
        return [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => 'batal', 'label' => 'Batal'],
            ['value' => 'belum_terkirim', 'label' => 'Belum Terkirim'],
            ['value' => 'belum_lunas', 'label' => 'Belum Lunas'],
        ];
    }

    public function getSalesTypeOptions()
    {
        return [
            ['value' => '', 'label' => 'Semua Tipe'],
            ['value' => 'O', 'label' => 'Mobil'],
            ['value' => 'I', 'label' => 'Motor'],
        ];
    }

    public function getPartnerOptions()
    {
        $partners = Partner::query()
            ->select('id', 'name', 'city')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $options = [['value' => '', 'label' => 'Semua Customer']];

        foreach ($partners as $partner) {
            $label = $partner->name;
            if ($partner->city) {
                $label .= ' - ' . $partner->city;
            }
            $options[] = [
                'value' => $partner->id,
                'label' => $label
            ];
        }

        return $options;
    }

    public function getMaterialCodeOptions()
    {
        $materials = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->select('code', 'name')
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get();

        $options = [['value' => '', 'label' => 'Semua Kode Barang']];

        foreach ($materials as $material) {
            $options[] = [
                'value' => $material->code,
                'label' => $material->code . ' - ' . $material->name
            ];
        }

        return $options;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function onPartnerChanged()
    {
        // Method ini akan dipanggil ketika partner dipilih dari dropdown search
        // Tidak perlu melakukan apa-apa khusus karena filterPartner sudah ter-update otomatis
    }

    public function onMaterialChanged()
    {
        // Method ini akan dipanggil ketika material dipilih dari dropdown search
        // Tidak perlu melakukan apa-apa khusus karena filterMaterialId sudah ter-update otomatis
    }

    public function downloadExcel()
    {
        // Validasi: pastikan ada data untuk di-export
        if (empty($this->results)) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Tidak ada data untuk di-export. Mohon lakukan pencarian terlebih dahulu.'
            ]);
            return;
        }

        try {
            // Siapkan data untuk Excel
            $excelData = [];
            $rowStyles = [];
            $currentRowIndex = 0;

            foreach ($this->results as $nota) {
                $isFirstItem = true;
                $subTotalQty = 0;
                $subTotalAmount = 0;

                foreach ($nota['items'] as $item) {
                    $subTotalQty += $item['qty'];
                    $subTotalAmount += $item['total'];

                    $excelData[] = [
                        $isFirstItem ? $nota['no_nota'] : '',
                        $isFirstItem ? ($nota['tgl_nota'] ? \Carbon\Carbon::parse($nota['tgl_nota'])->format('d-M-Y') : '') : '',
                        $isFirstItem ? $item['customer_name'] : '',
                        $item['kode'],
                        $item['nama_barang'],
                        $item['qty'],
                        $item['harga'],
                        $item['disc'],
                        $item['total'],
                        $isFirstItem ? $item['s'] : '',
                        $isFirstItem ? $item['wajib_pajak'] : '',
                        $isFirstItem ? ($item['t_kirim'] ? \Carbon\Carbon::parse($item['t_kirim'])->format('d-M-Y') : '') : '',
                        $isFirstItem ? ($item['tgl_tagih'] ? \Carbon\Carbon::parse($item['tgl_tagih'])->format('d-M-Y') : '') : '',
                        $isFirstItem ? ($item['tgl_lunas'] ? \Carbon\Carbon::parse($item['tgl_lunas'])->format('d-M-Y') : '') : '',
                    ];

                    $currentRowIndex++;
                    $isFirstItem = false;
                }

                // Tambahkan baris sub total
                $excelData[] = [
                    '',
                    '',
                    '',
                    '',
                    'Sub Total (' . $nota['no_nota'] . '):',
                    $subTotalQty,
                    '',
                    '',
                    $subTotalAmount,
                    '',
                    '',
                    '',
                    '',
                    '',
                ];

                // Tambahkan styling untuk baris subtotal (border top dan bold)
                // Border top dari kolom Qty sampai Total (F sampai I)
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'rangeColumns' => ['F', 'I'], // Dari kolom F sampai I
                    'borderTop' => true,
                    'bold' => true
                ];

                $currentRowIndex++;
            }

            // Buat title dan subtitle
            $title = 'DAFTAR NOTA JUAL';
            $subtitle = 'Periode: ' .
                ($this->startCode ? \Carbon\Carbon::parse($this->startCode)->format('d-M-Y') : '-') .
                ' s/d ' .
                ($this->endCode ? \Carbon\Carbon::parse($this->endCode)->format('d-M-Y') : '-');

            // Tambahkan filter info jika ada
            if ($this->filterPartner || $this->filterStatus || $this->filterMaterialId || $this->filterSalesType || $this->filterTrCode) {
                $filters = [];
                if ($this->filterPartner) {
                    $partner = Partner::find($this->filterPartner);
                    $filters[] = $partner ? $partner->name : 'Customer Tidak Ditemukan';
                }
                if ($this->filterMaterialId) {
                    $material = Material::find($this->filterMaterialId);
                    $filters[] = 'Kode: ' . ($material ? $material->code : 'Material Tidak Ditemukan');
                }
                if ($this->filterStatus) {
                    $filters[] = ucfirst(str_replace('_', ' ', $this->filterStatus));
                }
                if ($this->filterSalesType) {
                    $salesTypeLabel = $this->filterSalesType === 'O' ? 'Mobil' : 'Motor';
                    $filters[] = 'Tipe: ' . $salesTypeLabel;
                }
                if ($this->filterTrCode) {
                    $filters[] = 'Nota: ' . $this->filterTrCode;
                }
                $subtitle .= ' | ' . implode(' | ', $filters);
            }

            // Konfigurasi sheet Excel
            $sheets = [[
                'name' => 'Daftar_Nota_Jual',
                'headers' => [
                    'No. Nota',
                    'Tgl Nota',
                    'Nama Customer',
                    'Kode',
                    'Nama Barang',
                    'Qty',
                    'Harga',
                    '% Disc',
                    'Total',
                    'S',
                    'Wajib Pajak',
                    'Tgl Kirim',
                    'Tgl Tagih',
                    'Tgl Lunas'
                ],
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => $title,
                'subtitle' => $subtitle,
                'titleAlignment' => Alignment::HORIZONTAL_LEFT,
                'subtitleAlignment' => Alignment::HORIZONTAL_LEFT,
                'rowStyles' => $rowStyles,
            ]];

            $filename = 'Daftar_Nota_Jual_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();

        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }
}
