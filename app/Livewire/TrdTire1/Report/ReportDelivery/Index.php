<?php

namespace App\Livewire\TrdTire1\Report\ReportDelivery;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Models\TrdTire1\Master\Partner;

class Index extends BaseComponent
{
    public $startCode;
    public $endCode;
    public $filterPartner = '';
    public $filterBrand = '';
    public $results = [];
    protected $listeners = [
        'onSrCodeChanged',
        'selectedPrintDate' => 'onDateChanged',
        'DropdownSelected' => 'DropdownSelected'
    ];
    public $ddPartner = [
        'placeHolder' => "Ketik untuk cari customer ...",
        'optionLabel' => "code,name",
        'query' => "SELECT id,code,name,address,city
                    FROM partners
                    WHERE deleted_at IS NULL AND grp = 'C'",
    ];

    protected function onPreRender()
    {
        $this->resetFilters();
    }

    public function search()
    {
        // Validasi: minimal harus ada salah satu filter (tanggal, partner, atau brand)
        if (isNullOrEmptyNumber($this->startCode) && empty($this->filterPartner) && empty($this->filterBrand)) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Mohon lengkapi tanggal awal, pilih supplier, atau pilih brand untuk melakukan pencarian'
            ]);
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
        $partnerFilter = $this->filterPartner ? "AND p.id = {$this->filterPartner}" : "";
        $brandFilter = $this->filterBrand ? "AND m.brand = '" . addslashes($this->filterBrand) . "'" : "";

        // Query untuk mendapatkan data Penerimaan Barang (Delivery)
        // Data diambil dari deliv_hdrs dan deliv_packings dengan referensi ke order_dtls untuk harga dan diskon

        $query = "
            SELECT
                dh.tr_date AS tgl_kirim,
                dh.tr_code AS no_nota,
                p.name AS nama_supplier,
                dp.tr_seq,
                od.matl_code AS kode_brg,
                od.matl_descr AS nama_barang,
                dp.qty,
                od.price,
                od.disc_pct,
                od.amt AS total,
                od.amt_tax AS ppn
            FROM deliv_hdrs dh
            JOIN deliv_packings dp ON dp.trhdr_id = dh.id AND dp.tr_type = dh.tr_type
            JOIN partners p ON p.id = dh.partner_id
            LEFT JOIN order_dtls od ON od.id = dp.reffdtl_id
            LEFT JOIN materials m ON m.code = od.matl_code AND m.deleted_at IS NULL
            WHERE dh.tr_type = 'PD'
                AND dh.tr_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$partnerFilter}
                {$brandFilter}
                AND dh.deleted_at IS NULL
                AND dp.deleted_at IS NULL
            ORDER BY dh.tr_code, dp.tr_seq
        ";

        // Execute query
        $rows = DB::connection(Session::get('app_code'))->select($query);

        // Group data by nota untuk format yang sesuai
        $groupedResults = [];
        $currentNota = null;
        $notaData = null;

        foreach ($rows as $row) {
            if ($currentNota !== $row->no_nota) {
                // Save previous nota if exists
                if ($notaData) {
                    $groupedResults[] = $notaData;
                }

                // Start new nota
                $currentNota = $row->no_nota;
                $notaData = [
                    'no_nota' => $row->no_nota,
                    'tgl_kirim' => $row->tgl_kirim,
                    'nama_supplier' => $row->nama_supplier,
                    'items' => []
                ];
            }

            // Tambahkan item ke nota saat ini
            $notaData['items'][] = [
                'kode_brg' => $row->kode_brg,
                'nama_barang' => $row->nama_barang,
                'qty' => $row->qty,
                'harga' => $row->price,
                'disc' => $row->disc_pct,
                'total' => $row->total,
                'ppn' => $row->ppn,
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
        $this->filterPartner = '';
        $this->filterBrand = '';
        $this->results = [];
    }


    public function getPartnerOptions()
    {
        $partners = Partner::query()
            ->select('id', 'name', 'city')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $options = [['value' => '', 'label' => 'Semua Supplier']];

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

    public function getBrandOptions()
    {
        $brands = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->select('brand')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('brand')
            ->get();

        $options = [['value' => '', 'label' => 'Semua Brand']];

        foreach ($brands as $b) {
            $options[] = [
                'value' => $b->brand,
                'label' => $b->brand
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
}
