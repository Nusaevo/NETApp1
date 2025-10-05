<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, PartnerDetail, Material, MatlUom};
use App\Models\TrdTire2\Transaction\{OrderHdr as OrderHdr2, OrderDtl as OrderDtl2};
use App\Models\TrdTire2\Master\{Partner as Partner2, PartnerDetail as PartnerDetail2, Material as Material2, MatlUom as MatlUom2};
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class TransferService
{
    /**
     * Transfer order data dari TrdTire1 ke TrdTire2
     */
    public function transferOrderToTrdTire2(array $orderHdrIds): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'transferred_orders' => []
        ];

        try {
            // Pastikan koneksi TrdTire2 terdaftar
            $this->ensureTrdTire2Connection();

            DB::beginTransaction();


            // Ambil data order headers dengan relasi
            $orderHeaders = OrderHdr::whereIn('id', $orderHdrIds)
                ->with(['Partner', 'Partner.PartnerDetail', 'OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom'])
                ->get();


            // Validasi data yang ditemukan
            if ($orderHeaders->isEmpty()) {
                $errorMsg = "Tidak ada data order header yang ditemukan untuk ID: " . implode(', ', $orderHdrIds);
                Log::error($errorMsg);
                $results['errors'][] = $errorMsg;
                return $results;
            }

            foreach ($orderHeaders as $orderHdr) {
                try {
                    // 1. Transfer/Copy Partner jika belum ada
                    $partner2Id = $this->transferPartner($orderHdr->Partner);

                    // 2. Transfer/Copy Material jika belum ada
                    $materials2Ids = $this->transferMaterials($orderHdr->OrderDtl);

                    // 3. Transfer Order Header
                    $orderHdr2Id = $this->transferOrderHeader($orderHdr, $partner2Id);

                    // 4. Transfer Order Details
                    $this->transferOrderDetails($orderHdr->OrderDtl, $orderHdr2Id, $materials2Ids);

                    $results['transferred_orders'][] = [
                        'original_tr_code' => $orderHdr->tr_code ?? 'N/A',
                        'new_tr_code' => $orderHdr->tr_code ?? 'N/A', // Sama karena menggunakan tr_code yang sama
                        'partner_name' => $orderHdr->Partner->name ?? 'N/A'
                    ];

                    $results['success'][] = "Order " . ($orderHdr->tr_code ?? 'Unknown') . " berhasil ditransfer ke TrdTire2";

                } catch (Exception $e) {
                    $errorMsg = "Gagal transfer order " . ($orderHdr->tr_code ?? 'Unknown') . ": " . $e->getMessage();
                    Log::error("Transfer Order Error", [
                        'order_code' => $orderHdr->tr_code ?? 'Unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $results['errors'][] = $errorMsg;
                }
            }

            DB::commit();
            return $results;

        } catch (Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Gagal transfer data: " . $e->getMessage();
            Log::error("Transfer Service Error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $results;
        }
    }

    /**
     * Transfer/Copy Partner dari TrdTire1 ke TrdTire2
     */
    private function transferPartner(?Partner $partner1): int
    {
        // Validasi partner tidak null
        if (!$partner1) {
            throw new Exception("Partner data tidak ditemukan");
        }

        // Cek apakah partner sudah ada di TrdTire2 berdasarkan code
        $existingPartner2 = Partner2::on('TrdTire2')->where('code', $partner1->code)->first();

        if ($existingPartner2) {
            return $existingPartner2->id;
        }

        // Copy partner dari TrdTire1 ke TrdTire2
        $partner2 = new Partner2();
        $partner2->setConnection('TrdTire2');
        $partner2->fill($partner1->toArray());
        $partner2->save();

        // Copy partner detail jika ada
        if ($partner1->PartnerDetail) {
            $partnerDetail2 = new PartnerDetail2();
            $partnerDetail2->setConnection('TrdTire2');
            $partnerDetail2->fill($partner1->PartnerDetail->toArray());
            $partnerDetail2->partner_id = $partner2->id;
            $partnerDetail2->save();
        }

        return $partner2->id;
    }

    /**
     * Transfer/Copy Materials dari TrdTire1 ke TrdTire2
     */
    private function transferMaterials($orderDetails): array
    {
        $materials2Ids = [];

        foreach ($orderDetails as $detail) {
            $material1 = $detail->Material;

            // Validasi material tidak null
            if (!$material1) {
                continue;
            }

            // Cek apakah material sudah ada di TrdTire2 berdasarkan code
            $existingMaterial2 = Material2::on('TrdTire2')->where('code', $material1->code)->first();

            if ($existingMaterial2) {
                $materials2Ids[$material1->id] = $existingMaterial2->id;
                continue;
            }

            // Copy material dari TrdTire1 ke TrdTire2
            $material2 = new Material2();
            $material2->setConnection('TrdTire2');
            $material2->fill($material1->toArray());
            $material2->save();

            $materials2Ids[$material1->id] = $material2->id;

            // Copy material UOM jika ada
            if ($material1->MatlUom) {
                $matlUom2 = new MatlUom2();
                $matlUom2->setConnection('TrdTire2');
                $matlUom2->fill($material1->MatlUom->toArray());
                $matlUom2->matl_id = $material2->id;
                $matlUom2->save();
            }
        }

        return $materials2Ids;
    }

    /**
     * Transfer Order Header dari TrdTire1 ke TrdTire2
     */
    private function transferOrderHeader(OrderHdr $orderHdr1, int $partner2Id): int
    {
        // Cek apakah order header sudah ada di TrdTire2
        $existingOrderHdr2 = OrderHdr2::on('TrdTire2')->where('tr_code', $orderHdr1->tr_code)->first();

        if ($existingOrderHdr2) {
            return $existingOrderHdr2->id;
        }

        // Copy order header dari TrdTire1 ke TrdTire2
        $orderHdr2 = new OrderHdr2();
        $orderHdr2->setConnection('TrdTire2');
        $orderHdr2->fill($orderHdr1->toArray());
        $orderHdr2->partner_id = $partner2Id;
        $orderHdr2->save();

        return $orderHdr2->id;
    }

    /**
     * Transfer Order Details dari TrdTire1 ke TrdTire2
     */
    private function transferOrderDetails($orderDetails, int $orderHdr2Id, array $materials2Ids): void
    {
        foreach ($orderDetails as $detail1) {
            // Cek apakah order detail sudah ada di TrdTire2
            $existingOrderDtl2 = OrderDtl2::on('TrdTire2')->where('tr_code', $detail1->tr_code)
                ->where('tr_seq', $detail1->tr_seq)
                ->first();

            if ($existingOrderDtl2) {
                continue;
            }

            // Copy order detail dari TrdTire1 ke TrdTire2
            $detail2 = new OrderDtl2();
            $detail2->setConnection('TrdTire2');
            $detail2->fill($detail1->toArray());
            $detail2->trhdr_id = $orderHdr2Id;
            $detail2->matl_id = $materials2Ids[$detail1->matl_id] ?? $detail1->matl_id;
            $detail2->save();
        }
    }

    /**
     * Validasi apakah aplikasi TrdTire2 tersedia
     */
    public function isTrdTire2Available(): bool
    {
        try {
            $config = ConfigAppl::where('code', 'TrdTire2')
                ->where('status_code', 'A')
                ->first();

            if (!$config) {
                Log::warning('TrdTire2 not found in config_appls table');
                return false;
            }

            // Test koneksi ke database TrdTire2
            $testConnection = DB::connection('TrdTire2')->getPdo();
            Log::info('TrdTire2 connection test successful');
            return true;

        } catch (\Exception $e) {
            Log::error('TrdTire2 connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get informasi aplikasi TrdTire2
     */
    public function getTrdTire2Info(): ?ConfigAppl
    {
        return ConfigAppl::where('code', 'TrdTire2')
            ->where('status_code', 'A')
            ->first();
    }

    /**
     * Pastikan koneksi TrdTire2 terdaftar
     */
    private function ensureTrdTire2Connection(): void
    {
        // Cek apakah koneksi TrdTire2 sudah terdaftar
        if (!config("database.connections.TrdTire2")) {
            Log::info('TrdTire2 connection not found, registering dynamic connections...');

            // Panggil fungsi untuk mendaftarkan koneksi dinamis
            if (function_exists('registerDynamicConnections')) {
                registerDynamicConnections();
            } else {
                throw new Exception('Function registerDynamicConnections not found');
            }
        }
    }
}
