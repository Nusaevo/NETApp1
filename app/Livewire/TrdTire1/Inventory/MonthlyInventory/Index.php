<?php

namespace App\Livewire\TrdTire1\Inventory\MonthlyInventory;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use Carbon\Carbon;

class Index extends BaseComponent
{
    public $period = ''; // Format: YYYYMM (contoh: 202510)
    public $periodLabel = ''; // Label periode untuk ditampilkan
    public $nextPeriod = ''; // Periode berikutnya
    public $nextPeriodLabel = ''; // Label periode berikutnya
    public $dateFrom = ''; // Tanggal mulai periode
    public $dateTo = ''; // Tanggal akhir periode
    public $isProcessing = false;
    public $processMessage = '';

    protected function onPreRender()
    {
        // Set default periode ke bulan ini jika belum diisi
        if (empty($this->period)) {
            $this->period = Carbon::now()->format('Ym');
            $this->updatePeriodInfo();
        }
    }

    public function updatedPeriod()
    {
        $this->updatePeriodInfo();
    }

    protected function updatePeriodInfo()
    {
        if (empty($this->period) || strlen($this->period) !== 6) {
            $this->periodLabel = '';
            $this->nextPeriod = '';
            $this->nextPeriodLabel = '';
            $this->dateFrom = '';
            $this->dateTo = '';
            return;
        }

        try {
            $year = (int) substr($this->period, 0, 4);
            $month = (int) substr($this->period, 4, 2);

            // Validasi bulan
            if ($month < 1 || $month > 12) {
                $this->periodLabel = 'Periode tidak valid';
                return;
            }

            $dateFrom = Carbon::create($year, $month, 1);
            $dateTo = $dateFrom->copy()->endOfMonth();
            $nextPeriodDate = $dateFrom->copy()->addMonth();

            $this->dateFrom = $dateFrom->format('Y-m-d');
            $this->dateTo = $dateTo->format('Y-m-d');
            $this->periodLabel = $dateFrom->format('F Y'); // Contoh: October 2025
            $this->nextPeriod = $nextPeriodDate->format('Ym');
            $this->nextPeriodLabel = $nextPeriodDate->format('F Y');
        } catch (\Exception $e) {
            $this->periodLabel = 'Periode tidak valid';
        }
    }

    public function processPeriod()
    {
        $this->resetErrorBag();
        $this->isProcessing = true;
        $this->processMessage = '';

        // Validasi periode
        if (empty($this->period) || strlen($this->period) !== 6) {
            $this->dispatch('warning', __('Periode harus dalam format YYYYMM (contoh: 202510)'));
            $this->isProcessing = false;
            return;
        }

        try {
            $year = (int) substr($this->period, 0, 4);
            $month = (int) substr($this->period, 4, 2);

            if ($month < 1 || $month > 12) {
                $this->dispatch('warning', __('Bulan tidak valid'));
                $this->isProcessing = false;
                return;
            }

            // Set execution time limit
            set_time_limit(3600);
            ini_set('max_execution_time', 3600);
            ini_set('memory_limit', '512M');

            // Set database timeout for PostgreSQL
            DB::connection(Session::get('app_code'))->statement('SET statement_timeout = 3600000');
            DB::connection(Session::get('app_code'))->statement('SET idle_in_transaction_session_timeout = 3600000');

            // Build SQL procedure
            // Sanitize period input (should already be validated, but double-check)
            $period = preg_replace('/[^0-9]/', '', $this->period);
            if (strlen($period) !== 6) {
                throw new \Exception('Format periode tidak valid');
            }

            $sql = "
                DO \$\$
                DECLARE
                    v_period TEXT := '{$period}';
                    v_year INT;
                    v_month INT;
                    v_next_period TEXT;
                    v_date_from DATE;
                    v_date_to DATE;
                BEGIN
                    -- Hitung tanggal awal dan akhir dari v_period
                    v_year := LEFT(v_period, 4)::INT;
                    v_month := RIGHT(v_period, 2)::INT;
                    v_date_from := make_date(v_year, v_month, 1);
                    v_date_to := (v_date_from + INTERVAL '1 month - 1 day')::DATE;

                    -- Hitung periode berikutnya
                    v_next_period := to_char(v_date_from + INTERVAL '1 month', 'YYYYMM');

                    RAISE NOTICE 'Processing current period: % (From % to %)', v_period, v_date_from, v_date_to;
                    RAISE NOTICE 'Next period will be: %', v_next_period;

                    -- ==============================================================
                    -- 1️⃣ UPDATE qty_10, qty_20, qty_80, qty_89 untuk data yang SUDAH ADA
                    -- ==============================================================
                    UPDATE ivt_bal_periods p
                    SET
                        qty_10 = l.qty_10,
                        qty_20 = l.qty_20,
                        qty_80 = l.qty_80,
                        qty_89 = l.qty_89,
                        updated_at = now()
                    FROM (
                        SELECT
                            ivt_id,
                            SUM(CASE WHEN tr_type = 'PD' THEN qty ELSE 0 END)::int AS qty_10,
                            SUM(CASE WHEN tr_type = 'SD' THEN qty ELSE 0 END)::int AS qty_20,
                            SUM(CASE WHEN tr_type = 'TW' THEN qty ELSE 0 END)::int AS qty_80,
                            SUM(CASE WHEN tr_type = 'IA' THEN qty ELSE 0 END)::int AS qty_89
                        FROM ivt_logs
                        WHERE tr_date BETWEEN v_date_from AND v_date_to
                            AND tr_type IN ('PD', 'SD', 'TW', 'IA')
                        GROUP BY ivt_id
                    ) l
                    WHERE p.ivt_id = l.ivt_id
                        AND p.period_code = v_period;

                    RAISE NOTICE '✅ Updated existing qty_10, qty_20, qty_80, qty_89 for %', v_period;

                    -- ==============================================================
                    -- 1️⃣.5️⃣ INSERT data BARU dari ivt_logs yang belum ada di ivt_bal_periods
                    -- ==============================================================
                    INSERT INTO ivt_bal_periods (
                        period_code, ivt_id, matl_id, matl_code, matl_uom,
                        wh_id, wh_code, wh_bin, batch_code,
                        qty_10, qty_20, qty_80, qty_89,
                        qty_00, qty_11, qty_21, qty_30, qty_31, qty_99,
                        created_at, updated_at, created_by, updated_by, version_number
                    )
                    SELECT
                        v_period, l.ivt_id, l.matl_id, l.matl_code, l.matl_uom,
                        l.wh_id, l.wh_code, '', l.batch_code,
                        l.qty_10, l.qty_20, l.qty_80, l.qty_89,
                        0, 0, 0, 0, 0, 0,
                        now(), now(), 'sa', 'sa', 1
                    FROM (
                        SELECT
                            ivt_id, matl_id, matl_code, matl_uom,
                            wh_id, wh_code, batch_code,
                            SUM(CASE WHEN tr_type = 'PD' THEN qty ELSE 0 END)::int AS qty_10,
                            SUM(CASE WHEN tr_type = 'SD' THEN qty ELSE 0 END)::int AS qty_20,
                            SUM(CASE WHEN tr_type = 'TW' THEN qty ELSE 0 END)::int AS qty_80,
                            SUM(CASE WHEN tr_type = 'IA' THEN qty ELSE 0 END)::int AS qty_89
                        FROM ivt_logs
                        WHERE tr_date BETWEEN v_date_from AND v_date_to
                            AND tr_type IN ('PD', 'SD', 'TW', 'IA')
                        GROUP BY ivt_id, matl_id, matl_code, matl_uom,
                            wh_id, wh_code, batch_code
                    ) l
                    WHERE NOT EXISTS (
                        SELECT 1 FROM ivt_bal_periods p
                        WHERE p.ivt_id = l.ivt_id AND p.period_code = v_period
                    );

                    RAISE NOTICE '✅ Inserted missing ivt_id into period %', v_period;

                    -- ==============================================================
                    -- 2️⃣ UPDATE qty_99 (Total)
                    -- ==============================================================
                    UPDATE ivt_bal_periods
                    SET qty_99 = qty_00 + qty_10 + qty_11 + qty_20 + qty_21 + qty_30 + qty_31 + qty_80 + qty_89
                    WHERE period_code = v_period;

                    RAISE NOTICE '✅ Updated qty_99 for period %', v_period;

                    -- ==============================================================
                    -- 3️⃣ DELETE existing data on next period (prevent duplication)
                    -- ==============================================================
                    DELETE FROM ivt_bal_periods WHERE period_code = v_next_period;

                    RAISE NOTICE '🗑  Deleted existing records for next period %', v_next_period;

                    -- ==============================================================
                    -- 4️⃣ INSERT saldo awal ke periode berikut (Next period)
                    -- ==============================================================
                    INSERT INTO ivt_bal_periods(
                        period_code, ivt_id, matl_id, matl_code, matl_uom,
                        wh_id, wh_code, wh_bin, batch_code,
                        qty_00, qty_10, qty_11, qty_20, qty_21, qty_30, qty_31, qty_80, qty_89, qty_99,
                        created_at, updated_at, deleted_at, created_by, updated_by, version_number
                    )
                    SELECT
                        v_next_period AS period_code,
                        ivt_id, matl_id, matl_code, matl_uom,
                        wh_id, wh_code, wh_bin, batch_code,
                        qty_99 AS qty_00,
                        0 AS qty_10, 0 AS qty_11, 0 AS qty_20, 0 AS qty_21, 0 AS qty_30, 0 AS qty_31, 0 AS qty_80, 0 AS qty_89, 0 AS qty_99,
                        now() AS created_at, now() AS updated_at, NULL AS deleted_at,
                        'sa' AS created_by, 'sa' AS updated_by, 1 AS version_number
                    FROM ivt_bal_periods
                    WHERE period_code = v_period;

                    RAISE NOTICE '✅ Inserted next period % from %', v_next_period, v_period;
                END \$\$;
            ";

            // Execute SQL
            DB::connection(Session::get('app_code'))->unprepared($sql);

            $this->processMessage = "Proses selesai untuk periode {$this->periodLabel}";
            $this->dispatch('success', __('Proses inventory bulanan berhasil dilakukan'));

        } catch (\Exception $e) {
            $this->processMessage = 'Error: ' . $e->getMessage();
            $this->dispatch('error', __('Terjadi kesalahan: ') . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
