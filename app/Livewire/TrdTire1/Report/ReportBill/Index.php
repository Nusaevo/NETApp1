<?php

namespace App\Livewire\TrdTire1\Report\ReportBill;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;

class Index extends BaseComponent
{
    public $selectedPrintDate; // Tanggal tagih yang dipilih
    protected $masterService;

    public $results = [];

    protected $listeners = [
        'onSrCodeChanged'
    ];

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        // $this->resetFilters();
    }

    // public function onSrCodeChanged() {
    //     // Method ini sudah tidak dipakai karena dropdown diganti menjadi tanggal tagih
    // }

    public function search()
    {
        // Validasi tanggal tagih wajib diisi
        if (isNullOrEmptyNumber($this->selectedPrintDate)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Tanggal Tagih"]));
            $this->addError('selectedPrintDate', "Mohon lengkapi");
            return;
        }

        // $this->resetErrorBag();

        $printDate = addslashes($this->selectedPrintDate);
        $query = "
            SELECT
                bh.tr_code AS no_nota,
                bh.print_date AS tanggal_tagih,
                oh.tr_date as tanggal_nota,
                p.name AS nama_pelanggan,
                p.city AS kota_pelanggan,
                bh.amt AS total_tagihan
            FROM billing_hdrs bh
            LEFT JOIN order_hdrs oh ON oh.tr_code = bh.tr_code AND oh.tr_type = 'SO'
            JOIN partners p ON p.id = bh.partner_id
            WHERE bh.tr_type = 'ARB'
                AND bh.deleted_at IS NULL
                AND bh.print_date = '{$printDate}'
            ORDER BY p.name, oh.tr_date, bh.tr_code
        ";

        $rows = DB::connection(Session::get('app_code'))->select($query);
        $this->results = $rows;
    }

    // public function resetFilters()
    // {
    //     $this->selectedPrintDate = '';
    //     $this->results = [];
    // }

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
