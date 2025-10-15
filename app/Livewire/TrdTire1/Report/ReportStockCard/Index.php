<?php

namespace App\Livewire\TrdTire1\Report\ReportStockCard;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;

class Index extends BaseComponent
{
    public $wh_code;
    public $matl_code;
    public $matl_id;
    public $start_date;
    public $end_date;

    public $results = [];
    public $warehouses = [];
    public $materialQuery = "
        SELECT m.id, m.code, m.name
        FROM materials m
        WHERE m.status_code = 'A'
        AND m.deleted_at IS NULL
    ";
    protected $masterService;

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->wh_code = '';
        $this->matl_code = '';
        $this->matl_id = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->results = [];
    }

    public function onMaterialChanged()
    {
        // Get material code from selected material ID
        if ($this->matl_id) {
            $material = DB::connection(Session::get('app_code'))
                ->table('materials')
                ->select('code')
                ->where('id', $this->matl_id)
                ->first();

            if ($material) {
                $this->matl_code = $material->code;
            }
        } else {
            $this->matl_code = '';
        }
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function search()
    {
        if (!$this->wh_code || !$this->matl_code || !$this->start_date || !$this->end_date) {
            $this->dispatch('warning', 'Mohon lengkapi filter Gudang, Kode Barang, dan Periode.');
            return;
        }

        $whCode = addslashes($this->wh_code);
        $matlCode = addslashes($this->matl_code);
        $startDate = addslashes($this->start_date);
        $endDate = addslashes($this->end_date);

        $sql = "
                WITH
                params(start_date, end_date, wh_code, matl_code) AS (
                VALUES (DATE '{$startDate}', DATE '{$endDate}', '{$whCode}', '{$matlCode}')
                ),
                bal AS (
                SELECT b.qty_oh
                FROM ivt_bals b
                JOIN params p ON TRUE
                WHERE b.wh_code = p.wh_code AND b.matl_code = p.matl_code
                LIMIT 1
                ),
                opening_from_logs AS (
                SELECT SUM(il.qty)::numeric AS qty
                FROM ivt_logs il
                JOIN params p ON TRUE
                WHERE il.wh_code = p.wh_code
                    AND il.matl_code = p.matl_code
                    AND il.tr_type IN ('PD','SD','TWA','IA')
                    AND il.tr_date < p.start_date
                ),
                opening AS (
                SELECT COALESCE(o.qty, b.qty_oh, 0)::numeric AS opening_qty
                FROM (SELECT 1) x
                LEFT JOIN opening_from_logs o ON TRUE
                LEFT JOIN bal b ON TRUE
                ),
                tx AS (
                SELECT
                    il.tr_date, il.tr_code, il.tr_seq, il.tr_seq2, il.tr_type, il.tr_desc,
                    GREATEST(il.qty, 0) AS masuk,
                    GREATEST(-il.qty, 0) AS keluar,
                    il.qty AS net_qty
                FROM ivt_logs il
                JOIN params p ON TRUE
                WHERE il.wh_code = p.wh_code
                    AND il.matl_code = p.matl_code
                    AND il.tr_type IN ('PD','SD','TWA','IA')
                    AND il.tr_date BETWEEN p.start_date AND p.end_date
                ),
                final_balance AS (
                SELECT (SELECT opening_qty FROM opening) + COALESCE(SUM(net_qty),0) AS closing_qty
                FROM tx
                )
                SELECT
                0 AS urut,
                NULL::date AS tr_date,
                'Sisa Stok s/d ' || to_char(((SELECT start_date FROM params) - INTERVAL '1 day')::date,'DD-Mon-YYYY') AS tr_desc,
                NULL AS tr_code, NULL AS tr_seq, NULL AS tr_seq2, NULL AS tr_type,
                0 AS masuk, 0 AS keluar,
                (SELECT opening_qty FROM opening) AS sisa
                UNION ALL
                SELECT
                1, t.tr_date, t.tr_desc, t.tr_code, t.tr_seq, t.tr_seq2, t.tr_type,
                t.masuk, t.keluar, NULL
                FROM tx t
                UNION ALL
                SELECT
                2, NULL::date, 'Sisa Stok', NULL, NULL, NULL, NULL,
                0, 0, (SELECT closing_qty FROM final_balance)
                ORDER BY urut, tr_date, tr_code, tr_seq, tr_seq2
            ";

        $this->results = DB::connection(Session::get('app_code'))->select($sql);
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}


