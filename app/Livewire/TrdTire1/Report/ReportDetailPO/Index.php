<?php

namespace App\Livewire\TrdTire1\Report\ReportDetailPO;

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
        $partnerFilter = $this->filterPartner ? "AND p.id = {$this->filterPartner}" : "";
        $brandFilter = $this->filterBrand ? "AND m.brand = '" . addslashes($this->filterBrand) . "'" : "";

        // Query untuk mendapatkan data Purchase Order
        // Catatan: kolom Kirim/Sisa berasal dari ivt_bals (qty_oh dan qty_fgr) yang dicari berdasarkan matl_code

        $query = "
            SELECT
                oh.tr_code,
                oh.tr_date,
                p.name,
                p.city,
                od.tr_seq,
                od.matl_code,
                od.matl_descr,
                od.qty,
                od.price,
                od.disc_pct,
                od.amt,
                COALESCE(ib.qty_oh, 0)  AS kirim,
                COALESCE(ib.qty_fgr, 0) AS sisa
            FROM order_hdrs oh
            JOIN order_dtls od ON od.trhdr_id = oh.id
            JOIN partners p ON p.id = oh.partner_id
            LEFT JOIN materials m ON m.code = od.matl_code AND m.deleted_at IS NULL
            LEFT JOIN ivt_bals ib ON ib.matl_code = od.matl_code
            WHERE oh.tr_type = 'PO'
                AND oh.tr_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$partnerFilter}
                {$brandFilter}
                AND oh.deleted_at IS NULL
                AND od.deleted_at IS NULL
            ORDER BY oh.tr_code, od.tr_seq
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

            // Tambahkan item ke nota saat ini
            $notaData['items'][] = [
                'kode' => $row->matl_code,
                'nama_barang' => $row->matl_descr,
                'qty' => $row->qty,
                'harga' => $row->price,
                'disc' => $row->disc_pct,
                'total' => $row->amt,
                'kirim' => $row->kirim,
                'sisa' => $row->sisa,
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
}
