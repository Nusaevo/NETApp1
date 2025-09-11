<?php

namespace App\Livewire\TrdTire1\Report\ReportDetailSO;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Models\TrdTire1\Master\Partner;

class Index extends BaseComponent
{
    public $startCode;
    public $endCode;
    public $filterStatus = '';
    public $filterPartner = '';
    public $results = [];

    protected function onPreRender()
    {
        $this->resetFilters();
    }

    public function search()
    {
        if (isNullOrEmptyNumber($this->startCode)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Tanggal Awal"]));
            $this->addError('startCode',  "Mohon lengkapi");
            return;
        }

        $this->resetErrorBag();

        $startDate = addslashes($this->startCode);
        $endDate = addslashes($this->endCode ?: $this->startCode);

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
                'tgl_lunas' => $row->paid_date
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
