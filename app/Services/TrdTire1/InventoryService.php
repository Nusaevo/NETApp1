<?php

namespace App\Services\TrdTire1;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Master\{MatlUom, Material};
use App\Models\TrdTire1\Inventories\IvttrHdr;
use App\Models\TrdTire1\Inventories\{IvtLog, IvtBal, IvttrDtl};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, DelivPicking, OrderDtl, OrderHdr};
use Carbon\Carbon;

class InventoryService
{
    private function isMaterialJasa(int $matlId): bool
    {
        $material = Material::find($matlId);
        return $material && strtoupper(trim($material->type_code)) === 'S';
    }

    #endregion

    #region Reservation Methods

    public function addReservation(array $headerData, array $detailData)
    {
        // Skip reservation jika material category adalah JASA
        if ($this->isMaterialJasa($detailData['matl_id'])) {
            return;
        }

        // Skip reservation untuk Sales Return (SR) karena menggunakan delivery flow
        if ($headerData['tr_type'] === 'SR') {
            return;
        }

        $trQty = $detailData['qty'];
        $qty = 0;
        $priceBeforeTax = 0; // Default value

        if ($headerData['tr_type'] === 'PO' || $headerData['tr_type'] === 'SO') {
            $qty = $trQty;
            $priceBeforeTax = $detailData['price_beforetax'] ?? 0;
        } else if ($headerData['tr_type'] === 'PD' || $headerData['tr_type'] === 'SD') {
            $qty = -$trQty;
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            $priceBeforeTax = $orderDtl->price_beforetax ?? 0;
        }
        // dd($detailData);
        $ivtBal = IvtBal::updateOrCreate(
            [
                'matl_id' => $detailData['matl_id'],
                'matl_uom' => $detailData['matl_uom'],
                'wh_id' => 0,
                'batch_code' => ''
            ],
            [
                'matl_code' => $detailData['matl_code'] ?? ''
            ]
        );
        if ($headerData['tr_type'] === 'PO' || $headerData['tr_type'] === 'PD') {
            $ivtBal->qty_fgr +=  $qty;
        } else if ($headerData['tr_type'] === 'SO' || $headerData['tr_type'] === 'SD') {
            $ivtBal->qty_fgi +=  $qty;
        }
        $ivtBal->save();
        // dd($ivtBal);

        // Tentukan tr_type untuk log
        $trType = $headerData['tr_type'] . 'R';
        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trdtl_id' => $detailData['id'],
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $trType,
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'tr_seq2' => $detailData['tr_seq2'] ?? 0,
            'ivt_id' => $ivtBal->id,
            'matl_id' => $detailData['matl_id'],
            'matl_code' => $detailData['matl_code'] ?? '',
            'matl_uom' => $detailData['matl_uom'] ?? '',
            'wh_id' => 0,
            'wh_code' => '',
            'batch_code' => '',
            'tr_qty' => $trQty,
            'qty' => $qty,
            'price_beforetax' => $priceBeforeTax,
            'price_cogs' => 0,
            'qty_running' => 0,
            'tr_desc' => 'RESERVASI ' . $headerData['tr_type'] . ' ' . $detailData['tr_code'],
            'reff_id' => 0,
            'process_flag' => '',
        ];
        // dd($logData);

        // Simpan log inventory
        IvtLog::create($logData);
        // return $ivtBal->id;
    }

    public function addOnhand(array $headerData, array $detailData): int
    {
        // Skip onhand jika material category adalah JASA
        if ($this->isMaterialJasa($detailData['matl_id'])) {
            return 0;
        }

        // dd($detailData);
        $price = 0;
        $qty = 0;
        $trDesc = '';
        if ($headerData['tr_type'] === 'PD' || $headerData['tr_type'] === 'SD' || $headerData['tr_type'] === 'SRD') {
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            if (!$orderDtl) {
                throw new Exception('Order detail tidak ditemukan: ' . $detailData['reffdtl_id']);
            }
            $price = $orderDtl->price_beforetax;
        }

        switch ($headerData['tr_type']) {
            case 'PD': // Purchase Delivery: Tambah OH, Kurangi FGR
                $qty = $detailData['qty'];
                // $amt = $price * $qty;
                $trDesc = 'DELIVERY IN ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
            case 'SD': // Sales Delivery: Kurangi OH, Kurangi FGI
                $qty = -$detailData['qty'];
                // $amt = $price * $qty;
                $trDesc = 'DELIVERY OUT ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
            case 'SRD': // Sales Return Delivery: Tambah OH (return stock)
                $qty = $detailData['qty']; // POSITIF - menambah stok
                $trDesc = 'DELIVERY RETURN ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
            case 'TW':
                $qty = $detailData['qty'];
                if ($detailData['qty'] < 0) {
                    $trDesc = 'TRANSFER WH OUT ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                } else {
                    $trDesc = 'TRANSFER WH IN ' . ($detailData['wh_code2'] ?? '') . ' ' . $headerData['tr_code'];
                }
                break;
            case 'IA':
                $qty = $detailData['qty'];
                $trDesc = 'ADJUSTMENT ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
        }

        $ivtBal = IvtBal::updateOrCreate([
            'matl_id' => $detailData['matl_id'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => $detailData['wh_id'],
            'batch_code' => $detailData['batch_code'],
        ], [
            'matl_code' => $detailData['matl_code'],
            'wh_code' => $detailData['wh_code'],
        ]);
        $ivtBal->qty_oh += $qty;
        $ivtBal->save();
        $detailData['ivt_id'] = $ivtBal->id;

        // dd($detailData);
        // Siapkan data log
        $logData = [
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'tr_seq2' => $detailData['tr_seq2'] ?? 0,
            'trdtl_id' => $detailData['id'],
            'ivt_id' => $ivtBal->id,
            'matl_id' => $detailData['matl_id'],
            'matl_code' => $detailData['matl_code'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => $detailData['wh_id'],
            'wh_code' => $detailData['wh_code'],
            'batch_code' => $detailData['batch_code'],
            'reff_id' => $headerData['reff_id'] ?? 0,
            'tr_date' => $headerData['tr_date'],
            'tr_qty' => $detailData['qty'],
            'qty' => $qty,
            'price_beforetax' => $price,
            'tr_desc' => $trDesc,
            'price_cogs' => 0,
            'qty_running' => 0,
            'process_flag' => ''
        ];
        $ivtLog = IvtLog::create($logData);
        return $ivtBal->id;
    }

    public function delIvtLog(string $trType, int $trHdrId, ?int $trDtlId = null)
    {
        // Hapus log inventory berdasarkan trHdrId dan opsional trdtlId
        if ($trDtlId !== null) {
            // Jika ada trdtlId, cari berdasarkan trType, trhdrId, dan trdtlId untuk memastikan log yang tepat
            $query = IvtLog::where('tr_type', $trType)
                ->where('trhdr_id', $trHdrId)
                ->where('trdtl_id', $trDtlId);
        } else {
            // Jika tidak ada trdtlId, cari berdasarkan trType dan trhdrId
            $query = IvtLog::where('tr_type', $trType)
                ->where('trhdr_id', $trHdrId);
        }

        $logs = $query->get();

        foreach ($logs as $log) {
            $ivtBal = IvtBal::find($log->ivt_id);
            if ($ivtBal) {
                if ($log->tr_type === 'POR' || $log->tr_type === 'PDR') {
                    $ivtBal->qty_fgr -= $log->qty;
                } else if ($log->tr_type === 'SOR' || $log->tr_type === 'SDR') {
                    $ivtBal->qty_fgi -= $log->qty;
                } else {
                    $ivtBal->qty_oh -= $log->qty;
                }
                $ivtBal->save();
            }
            $log->delete();
        }
    }

    public function saveInventoryTrx(array $headerData, array $detailData)
    {
        if (empty($headerData) || empty($detailData)) {
            throw new Exception('Header data or detail data is empty');
        }
        try {
            $header = $this->saveHeaderTrx($headerData);
            $headerData['id'] = $header->id;

            $detail = $this->saveDetailTrx($headerData, $detailData);

            return [
                'header' => $header,
                'detail' => $detail,
            ];

        } catch (Exception $e) {
            throw new Exception('Error updating order: ' . $e->getMessage());
        }
    }

    #region saveHeader
    private function saveHeaderTrx(array $headerData)
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            $ivttrHdr = IvttrHdr::create($headerData);
        } else {
            $ivttrHdr = IvttrHdr::findOrFail($headerData['id']);
            $ivttrHdr->fill($headerData);
            if ($ivttrHdr->isDirty()) {
                $ivttrHdr->save();
            }
        }
        return $ivttrHdr;
    }
    #endregion

    private function saveDetailTrx(array $headerData, array $detailData)
    {
        // Hanya ada ADD tidak diperbolehkan UPDATE

        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] =  $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            if ($detail['tr_type'] === 'IA') {
                if (!isset($detail['id']) || empty($detail['id'])) {
                    $detail['tr_seq'] = IvttrDtl::getNextTrSeq($headerData['id']);

                    $ivttrDtl = new IvttrDtl();
                    $ivttrDtl->fill($detail);
                    $ivttrDtl->save();

                    // Set id pada detail untuk logging dan penentuan ivt_id
                    $detail['id'] = $ivttrDtl->id;
                    $ivtId = $this->addOnhand($headerData, $detail);
                    // Update ivt_id pada detail yang baru dibuat
                    $ivttrDtl->ivt_id = $ivtId;
                    $ivttrDtl->save();
                }
            } else if ($detail['tr_type'] === 'TW') {
                if (!isset($detail['id']) || empty($detail['id'])) {
                    $detail['tr_seq'] = IvttrDtl::getNextTrSeq($headerData['id']);

                    // Detail OUT (tr_seq negatif, qty negatif)
                    $detail1 = $detail;
                    $detail1['tr_seq'] = -$detail['tr_seq'];
                    $detail1['qty'] = -$detail['qty'];
                    $ivttrDtl1 = new IvttrDtl();
                    $ivttrDtl1->fill($detail1);
                    $ivttrDtl1->save();
                    // Pastikan id terpasang di detail untuk addOnhand
                    $detail1['id'] = $ivttrDtl1->id;
                    $detail['id'] = $ivttrDtl1->id;
                    $ivtId1 = $this->addOnhand($headerData, $detail1);
                    $ivttrDtl1->ivt_id = $ivtId1;
                    $ivttrDtl1->save();

                    // Detail IN (ke gudang tujuan)
                    $detail2 = $detail;
                    $detail2['wh_id'] = $detail['wh_id2'];
                    $detail2['wh_code'] = $detail['wh_code2'];
                    $ivttrDtl2 = new IvttrDtl();
                    $ivttrDtl2->fill($detail2);
                    $ivttrDtl2->save();
                    $detail2['id'] = $ivttrDtl2->id;
                    $detail['id2'] = $ivttrDtl2->id;
                    $ivtId2 = $this->addOnhand($headerData, $detail2);
                    $ivttrDtl2->ivt_id = $ivtId2;
                    $ivttrDtl2->save();
               }
            }
        }
        unset($detail);

        return true;

    }

    public function delInventory(string $trType, int $ivttrId): void
    {
        $this->deleteDetails($trType, $ivttrId);
        $this->deleteHeader($ivttrId);
    }

    private function deleteHeader(int $ivttrId)
    {
        IvttrHdr::where('id', $ivttrId)->forceDelete();
    }

    private function deleteDetails(string $trType, int $ivttrId): void
    {
        $this->delIvtLog($trType, $ivttrId);
        IvttrDtl::where('trhdr_id', $ivttrId)->delete();
    }
    #endregion

    #Program akhir bulan
    public static function processMonthlyInventory($appCode, $period = null)
    {
        // Jika period tidak diberikan, gunakan bulan sebelumnya
        if ($period === null) {
            $lastMonth = Carbon::now()->subMonth();
            $period = $lastMonth->format('Ym');
        }

        // Validasi periode
        $period = preg_replace('/[^0-9]/', '', $period);
        if (strlen($period) !== 6) {
            throw new Exception('Format periode tidak valid: ' . $period);
        }

        $year = (int) substr($period, 0, 4);
        $month = (int) substr($period, 4, 2);

        if ($month < 1 || $month > 12) {
            throw new Exception('Bulan tidak valid: ' . $month);
        }

        // Set execution time limit
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Set database timeout for PostgreSQL
        DB::connection($appCode)->statement('SET statement_timeout = 3600000');
        DB::connection($appCode)->statement('SET idle_in_transaction_session_timeout = 3600000');

        // Build SQL procedure
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
        DB::connection($appCode)->unprepared($sql);

        return true;
    }

    #region Sales Return Delivery Inventory Log
    /**
     * Buat ivt_logs dari DelivPicking untuk Sales Return Delivery
     * Method ini membuat inventory log dari delivery picking untuk Sales Return
     *
     * @param DelivHdr $delivHdr Delivery header
     * @param DelivPicking $picking Delivery picking
     * @param OrderDtl $orderDtl Order detail untuk mendapatkan price_beforetax
     * @return void
     */
    public function createIvtLogFromDeliveryPicking($delivHdr, $picking, $orderDtl)
    {
        // Skip jika material adalah JASA
        $material = Material::find($picking->matl_id);
        if ($material && strtoupper(trim($material->type_code ?? '')) === 'S') {
            return;
        }

        // Hapus log lama jika ada
        $this->delIvtLog('SRD', $delivHdr->id, $picking->id);

        // Untuk Sales Return Delivery, qty adalah positif (menambah stock)
        $qty = $picking->qty;
        $price = $orderDtl->price_beforetax ?? 0;

        // Update atau create IvtBal
        $ivtBal = IvtBal::updateOrCreate([
            'matl_id' => $picking->matl_id,
            'matl_uom' => $picking->matl_uom,
            'wh_id' => $picking->wh_id,
            'batch_code' => $picking->batch_code ?? '',
        ], [
            'matl_code' => $picking->matl_code,
            'wh_code' => $picking->wh_code,
        ]);

        // Tambah qty_oh (return stock)
        $ivtBal->qty_oh += $qty;
        $ivtBal->save();

        // Update ivt_id di picking
        $picking->ivt_id = $ivtBal->id;
        $picking->save();

        // Buat IvtLog
        $logData = [
            'tr_date' => $delivHdr->tr_date ?? date('Y-m-d'),
            'trdtl_id' => $picking->id,
            'trhdr_id' => $delivHdr->id,
            'tr_type' => 'SRD',
            'tr_code' => $delivHdr->tr_code,
            'tr_seq' => $picking->trpacking_seq,
            'tr_seq2' => $picking->tr_seq,
            'ivt_id' => $ivtBal->id,
            'matl_id' => $picking->matl_id,
            'matl_code' => $picking->matl_code,
            'matl_uom' => $picking->matl_uom,
            'wh_id' => $picking->wh_id,
            'wh_code' => $picking->wh_code,
            'batch_code' => $picking->batch_code ?? '',
            'tr_qty' => $qty,
            'qty' => $qty, // Positive untuk return
            'price_beforetax' => $price,
            'price_cogs' => 0,
            'qty_running' => 0,
            'tr_desc' => 'Delivery Return ' . $delivHdr->tr_code,
            'reff_id' => 0,
            'process_flag' => '',
        ];

        IvtLog::create($logData);
    }
    #endregion
}
