<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, DelivHdr, DelivPacking, DelivPicking, BillingHdr, BillingOrder, BillingDeliv};
use App\Models\TrdTire1\Master\{Partner, PartnerDetail, Material, MatlUom};
use App\Models\TrdTire2\Transaction\{OrderHdr as OrderHdr2, OrderDtl as OrderDtl2, DelivHdr as DelivHdr2, DelivPacking as DelivPacking2, DelivPicking as DelivPicking2, BillingHdr as BillingHdr2, BillingOrder as BillingOrder2, BillingDeliv as BillingDeliv2};
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

            $orderHeaders = OrderHdr::whereIn('id', $orderHdrIds)
                ->with([
                    'Partner',
                    'Partner.PartnerDetail',
                    'OrderDtl',
                    'OrderDtl.Material',
                    'OrderDtl.Material.MatlUom',
                    'DelivHdr',
                    'DelivHdr.DelivPacking',
                    'DelivHdr.DelivPacking.DelivPickings',
                    'BillingHdr',
                    'BillingHdr.BillingOrder',
                    'BillingHdr.BillingDeliv'
                ])
                ->get();


            if ($orderHeaders->isEmpty()) {
                $errorMsg = "Tidak ada data order header yang ditemukan untuk ID: " . implode(', ', $orderHdrIds);
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

                    // 5. Transfer Delivery Data jika ada
                    $deliveryTransferred = $this->transferDeliveryData($orderHdr, $orderHdr2Id, $partner2Id, $materials2Ids);

                    // 6. Transfer Billing Data jika ada
                    $billingTransferred = $this->transferBillingData($orderHdr, $orderHdr2Id, $partner2Id, $materials2Ids);

                    $results['transferred_orders'][] = [
                        'original_tr_code' => $orderHdr->tr_code,
                        'new_tr_code' => $orderHdr->tr_code,
                        'partner_name' => $orderHdr->Partner->name,
                        'delivery_transferred' => $deliveryTransferred,
                        'billing_transferred' => $billingTransferred
                    ];

                    $successMsg = "Order " . ($orderHdr->tr_code ?? 'Unknown') . " berhasil ditransfer ke TrdTire2";
                    if ($deliveryTransferred) {
                        $successMsg .= " (termasuk delivery)";
                    }
                    if ($billingTransferred) {
                        $successMsg .= " (termasuk billing)";
                    }
                    $results['success'][] = $successMsg;

                } catch (Exception $e) {
                    $errorMsg = "Gagal transfer order " . ($orderHdr->tr_code ?? 'Unknown') . ": " . $e->getMessage();
                    $results['errors'][] = $errorMsg;
                }
            }

            DB::commit();
            return $results;

        } catch (Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Gagal transfer data: " . $e->getMessage();
            return $results;
        }
    }

    /**
     * Transfer/Copy Partner dari TrdTire1 ke TrdTire2
     */
    private function transferPartner(?Partner $partner1): int
    {
        if (!$partner1) {
            throw new Exception("Partner data tidak ditemukan");
        }

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

            if (!$material1) {
                continue;
            }

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
     * Transfer Delivery Data dari TrdTire1 ke TrdTire2
     */
    private function transferDeliveryData(OrderHdr $orderHdr, int $orderHdr2Id, int $partner2Id, array $materials2Ids): bool
    {
        try {
            $delivHdr = $orderHdr->DelivHdr;

            if (!$delivHdr) {
                return false;
            }

            // Cek apakah delivery header sudah ada di TrdTire2
            $existingDelivHdr2 = DelivHdr2::on('TrdTire2')->where('tr_code', $delivHdr->tr_code)->first();

            if ($existingDelivHdr2) {
                return true; // Sudah ada, skip transfer
            }

            // Transfer Delivery Header
            $delivHdr2 = new DelivHdr2();
            $delivHdr2->setConnection('TrdTire2');
            $delivHdr2->fill($delivHdr->toArray());
            $delivHdr2->partner_id = $partner2Id; // Update dengan partner ID TrdTire2
            $delivHdr2->save();

            // Transfer Delivery Packing - Gunakan query langsung untuk menghindari masalah relasi
            $delivPackings = DelivPacking::where('trhdr_id', $delivHdr->id)->get();

            foreach ($delivPackings as $delivPacking) {
                $existingDelivPacking2 = DelivPacking2::on('TrdTire2')
                    ->where('tr_code', $delivPacking->tr_code)
                    ->where('tr_seq', $delivPacking->tr_seq)
                    ->first();

                if (!$existingDelivPacking2) {
                    $delivPacking2 = new DelivPacking2();
                    $delivPacking2->setConnection('TrdTire2');
                    $delivPacking2->fill($delivPacking->toArray());
                    $delivPacking2->trhdr_id = $delivHdr2->id; // Update dengan delivery header ID TrdTire2
                    $delivPacking2->save();

                    // Transfer Delivery Picking - Gunakan query langsung
                    $delivPickings = DelivPicking::where('trpacking_id', $delivPacking->id)->get();

                    foreach ($delivPickings as $delivPicking) {
                        $existingDelivPicking2 = DelivPicking2::on('TrdTire2')
                            ->where('trpacking_id', $delivPacking2->id)
                            ->where('tr_seq', $delivPicking->tr_seq)
                            ->first();

                        if (!$existingDelivPicking2) {
                            $delivPicking2 = new DelivPicking2();
                            $delivPicking2->setConnection('TrdTire2');
                            $delivPicking2->fill($delivPicking->toArray());
                            $delivPicking2->trpacking_id = $delivPacking2->id; // Update dengan packing ID TrdTire2
                            $delivPicking2->matl_id = $materials2Ids[$delivPicking->matl_id] ?? $delivPicking->matl_id; // Update dengan material ID TrdTire2
                            $delivPicking2->save();
                        }
                    }
                }
            }

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Transfer Billing Data dari TrdTire1 ke TrdTire2
     */
    private function transferBillingData(OrderHdr $orderHdr, int $orderHdr2Id, int $partner2Id, array $materials2Ids): bool
    {
        try {
            // Cari BillingOrder yang memiliki reffhdrtr_code = order_code
            $billingOrder = BillingOrder::where('reffhdrtr_code', $orderHdr->tr_code)->first();

            if (!$billingOrder) {
                return false; // Tidak ada billing data
            }

            // Dari BillingOrder, ambil trhdr_id untuk mencari BillingHdr
            $billingHdr = BillingHdr::where('id', $billingOrder->trhdr_id)->first();

            if (!$billingHdr) {
                return false; // Tidak ada billing header
            }

            // Cek apakah billing header sudah ada di TrdTire2
            $existingBillingHdr2 = BillingHdr2::on('TrdTire2')->where('tr_code', $billingHdr->tr_code)->first();

            if ($existingBillingHdr2) {
                return true; // Sudah ada, skip transfer
            }

            // Transfer Billing Header
            $billingHdr2 = new BillingHdr2();
            $billingHdr2->setConnection('TrdTire2');
            $billingHdr2->fill($billingHdr->toArray());
            $billingHdr2->partner_id = $partner2Id; // Update dengan partner ID TrdTire2
            $billingHdr2->tax_process_date = now(); // Set tax_process_date dengan tanggal sekarang (tanggal transfer berhasil)
            $billingHdr2->save();

            // Update tax_process_date di TrdTire1 juga
            $billingHdr->tax_process_date = now();
            $billingHdr->save();

            // Transfer Billing Order - Gunakan query langsung untuk menghindari masalah relasi
            $billingOrders = BillingOrder::where('trhdr_id', $billingHdr->id)
                ->where('tr_type', $billingHdr->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($billingOrders as $billingOrder) {
                $existingBillingOrder2 = BillingOrder2::on('TrdTire2')
                    ->where('tr_code', $billingOrder->tr_code)
                    ->where('tr_seq', $billingOrder->tr_seq)
                    ->first();

                if (!$existingBillingOrder2) {
                    $billingOrder2 = new BillingOrder2();
                    $billingOrder2->setConnection('TrdTire2');
                    $billingOrder2->fill($billingOrder->toArray());
                    $billingOrder2->trhdr_id = $billingHdr2->id; // Update dengan billing header ID TrdTire2
                    $billingOrder2->save();
                }
            }

            // Transfer Billing Delivery - Gunakan query langsung untuk menghindari masalah relasi
            $billingDelivs = BillingDeliv::where('trhdr_id', $billingHdr->id)->get();

            foreach ($billingDelivs as $billingDeliv) {
                $existingBillingDeliv2 = BillingDeliv2::on('TrdTire2')
                    ->where('trhdr_id', $billingHdr2->id)
                    ->where('deliv_code', $billingDeliv->deliv_code)
                    ->first();

                if (!$existingBillingDeliv2) {
                    $billingDeliv2 = new BillingDeliv2();
                    $billingDeliv2->setConnection('TrdTire2');
                    $billingDeliv2->fill($billingDeliv->toArray());
                    $billingDeliv2->trhdr_id = $billingHdr2->id; // Update dengan billing header ID TrdTire2
                    $billingDeliv2->save();
                }
            }

            return true;

        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * Transfer Delivery data langsung dari TrdTire1 ke TrdTire2
     */
    public function transferDeliveryToTrdTire2(array $delivHdrIds): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'transferred_deliveries' => []
        ];

        try {
            // Pastikan koneksi TrdTire2 terdaftar
            $this->ensureTrdTire2Connection();

            DB::beginTransaction();

            // Ambil data delivery headers dengan relasi lengkap termasuk Order
            $delivHdrs = DelivHdr::whereIn('id', $delivHdrIds)
                ->with([
                    'Partner',
                    'Partner.PartnerDetail',
                    'DelivPacking',
                    'DelivPacking.DelivPickings',
                    'DelivPacking.DelivPickings.Material',
                    'DelivPacking.DelivPickings.Material.MatlUom',
                    'OrderHdr',
                    'OrderHdr.OrderDtl',
                    'OrderHdr.OrderDtl.Material',
                    'OrderHdr.OrderDtl.Material.MatlUom'
                ])
                ->get();

            // Validasi data yang ditemukan
            if ($delivHdrs->isEmpty()) {
                $errorMsg = "Tidak ada data delivery header yang ditemukan untuk ID: " . implode(', ', $delivHdrIds);
                $results['errors'][] = $errorMsg;
                return $results;
            }

            foreach ($delivHdrs as $delivHdr) {
                try {
                    // 1. Transfer/Copy Partner jika belum ada
                    $partner2Id = $this->transferPartner($delivHdr->Partner);

                    // 2. Transfer Order jika ada (dari reffhdr_id atau reffhdrtr_code di DelivPacking)
                    $orderHdr2Id = null;
                    $orderTransferred = false;
                    $billingTransferred = false;
                    $materials2Ids = [];
                    $processedOrderIds = [];
                    $transferredOrders = []; // Simpan order yang sudah ditransfer untuk transfer billing

                    // Ambil OrderHdr dari DelivPacking menggunakan reffhdr_id atau reffhdrtr_code
                    // Query DelivPacking langsung dari database menggunakan trhdr_id
                    $delivPackings = DelivPacking::where('trhdr_id', $delivHdr->id)
                        ->where('tr_type', $delivHdr->tr_type)
                        ->get();

                    foreach ($delivPackings as $packing) {
                        $orderHdr = null;

                        try {
                            // Prioritas 1: Gunakan reffhdr_id jika ada
                            if ($packing->reffhdr_id) {
                                // Coba dengan filter tr_type sesuai reffhdrtr_type jika ada
                                if ($packing->reffhdrtr_type) {
                                    $orderHdr = OrderHdr::where('id', $packing->reffhdr_id)
                                        ->where('tr_type', $packing->reffhdrtr_type)
                                        ->with(['OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom', 'BillingHdr'])
                                        ->first();
                                }

                                // Jika tidak ditemukan, coba dengan tr_type PO
                                if (!$orderHdr) {
                                    $orderHdr = OrderHdr::where('id', $packing->reffhdr_id)
                                        ->where('tr_type', 'PO')
                                        ->with(['OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom', 'BillingHdr'])
                                        ->first();
                                }

                                // Jika masih tidak ditemukan, coba tanpa filter tr_type
                                if (!$orderHdr) {
                                    $orderHdr = OrderHdr::where('id', $packing->reffhdr_id)
                                        ->with(['OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom', 'BillingHdr'])
                                        ->first();
                                }

                            }

                            // Prioritas 2: Jika tidak ada dari reffhdr_id, gunakan reffhdrtr_code
                            if (!$orderHdr && $packing->reffhdrtr_code) {
                                // Coba dengan filter tr_type sesuai reffhdrtr_type jika ada
                                if ($packing->reffhdrtr_type) {
                                    $orderHdr = OrderHdr::where('tr_code', $packing->reffhdrtr_code)
                                        ->where('tr_type', $packing->reffhdrtr_type)
                                        ->with(['OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom', 'BillingHdr'])
                                        ->first();
                                }

                                // Jika tidak ditemukan, coba dengan tr_type PO
                                if (!$orderHdr) {
                                    $orderHdr = OrderHdr::where('tr_code', $packing->reffhdrtr_code)
                                        ->where('tr_type', 'PO')
                                        ->with(['OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom', 'BillingHdr'])
                                        ->first();
                                }

                                // Jika masih tidak ditemukan, coba tanpa filter tr_type
                                if (!$orderHdr) {
                                    $orderHdr = OrderHdr::where('tr_code', $packing->reffhdrtr_code)
                                        ->with(['OrderDtl', 'OrderDtl.Material', 'OrderDtl.Material.MatlUom', 'BillingHdr'])
                                        ->first();
                                }

                            }

                            // Transfer Order jika ditemukan dan belum pernah diproses
                            if ($orderHdr && !in_array($orderHdr->id, $processedOrderIds)) {
                                $processedOrderIds[] = $orderHdr->id;

                                // Transfer Material dari OrderDtl
                                $orderMaterials2Ids = $this->transferMaterials($orderHdr->OrderDtl);

                                // Merge materials, hindari duplikasi
                                foreach ($orderMaterials2Ids as $matlId1 => $matlId2) {
                                    if (!isset($materials2Ids[$matlId1])) {
                                        $materials2Ids[$matlId1] = $matlId2;
                                    }
                                }

                                // Transfer Order Header
                                $orderHdr2Id = $this->transferOrderHeader($orderHdr, $partner2Id);

                                // Transfer Order Details
                                $this->transferOrderDetails($orderHdr->OrderDtl, $orderHdr2Id, $orderMaterials2Ids);

                                // Simpan order yang sudah ditransfer untuk transfer billing nanti
                                $transferredOrders[] = [
                                    'orderHdr' => $orderHdr,
                                    'orderHdr2Id' => $orderHdr2Id,
                                    'materials2Ids' => $orderMaterials2Ids
                                ];

                                $orderTransferred = true;
                            }
                        } catch (Exception $e) {
                            // Continue ke packing berikutnya
                        }
                    }

                    // 3. Transfer/Copy Material dari DelivPicking (hanya yang belum ada dari Order)
                    $delivMaterials2Ids = $this->transferMaterialsFromDelivPicking($delivHdr);
                    // Merge dengan prioritas: jika sudah ada di materials2Ids, gunakan yang sudah ada
                    foreach ($delivMaterials2Ids as $matlId1 => $matlId2) {
                        if (!isset($materials2Ids[$matlId1])) {
                            $materials2Ids[$matlId1] = $matlId2;
                        }
                    }

                    // 4. Transfer Delivery Header
                    $delivHdr2Id = $this->transferDeliveryHeaderDirect($delivHdr, $partner2Id);

                    // 5. Transfer Delivery Packing dan Picking
                    $this->transferDeliveryPackingAndPicking($delivHdr, $delivHdr2Id, $materials2Ids);

                    // 6. Transfer Billing Data untuk setiap order yang sudah ditransfer
                    foreach ($transferredOrders as $transferredOrder) {
                        $billingResult = $this->transferBillingData(
                            $transferredOrder['orderHdr'],
                            $transferredOrder['orderHdr2Id'],
                            $partner2Id,
                            $transferredOrder['materials2Ids']
                        );
                        if ($billingResult) {
                            $billingTransferred = true;
                        }
                    }

                    $results['transferred_deliveries'][] = [
                        'original_tr_code' => $delivHdr->tr_code,
                        'new_tr_code' => $delivHdr->tr_code,
                        'partner_name' => $delivHdr->Partner->name ?? 'Unknown',
                        'order_transferred' => $orderTransferred,
                        'billing_transferred' => $billingTransferred
                    ];

                    $successMsg = "Delivery " . ($delivHdr->tr_code ?? 'Unknown') . " berhasil ditransfer ke TrdTire2";
                    if ($orderTransferred) {
                        $successMsg .= " (termasuk Order)";
                    }
                    if ($billingTransferred) {
                        $successMsg .= " (termasuk Billing)";
                    }
                    $results['success'][] = $successMsg;

                } catch (Exception $e) {
                    $errorMsg = "Gagal transfer delivery " . ($delivHdr->tr_code ?? 'Unknown') . ": " . $e->getMessage();
                    $results['errors'][] = $errorMsg;
                    Log::error('Transfer Delivery Error', [
                        'deliv_code' => $delivHdr->tr_code ?? 'Unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            DB::commit();
            return $results;

        } catch (Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Gagal transfer data: " . $e->getMessage();
            Log::error('Transfer Delivery Batch Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $results;
        }
    }

    /**
     * Transfer/Copy Materials dari DelivPicking
     */
    private function transferMaterialsFromDelivPicking(DelivHdr $delivHdr): array
    {
        $materials2Ids = [];

        // Query DelivPacking langsung dari database menggunakan trhdr_id
        $delivPackings = DelivPacking::where('trhdr_id', $delivHdr->id)
            ->where('tr_type', $delivHdr->tr_type)
            ->get();

        foreach ($delivPackings as $packing) {
            // Query DelivPicking langsung dari database menggunakan trpacking_id
            $delivPickings = DelivPicking::where('trpacking_id', $packing->id)
                ->with('Material', 'Material.MatlUom')
                ->get();

            foreach ($delivPickings as $picking) {
                $material1 = $picking->Material;

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
        }

        return $materials2Ids;
    }

    /**
     * Transfer Delivery Header langsung (tanpa OrderHdr)
     */
    private function transferDeliveryHeaderDirect(DelivHdr $delivHdr1, int $partner2Id): int
    {
        // Cek apakah delivery header sudah ada di TrdTire2
        $existingDelivHdr2 = DelivHdr2::on('TrdTire2')->where('tr_code', $delivHdr1->tr_code)->first();

        if ($existingDelivHdr2) {
            return $existingDelivHdr2->id;
        }

        // Copy delivery header dari TrdTire1 ke TrdTire2
        $delivHdr2 = new DelivHdr2();
        $delivHdr2->setConnection('TrdTire2');
        $delivHdr2->fill($delivHdr1->toArray());
        $delivHdr2->partner_id = $partner2Id;
        $delivHdr2->save();

        return $delivHdr2->id;
    }

    /**
     * Transfer Delivery Packing dan Picking
     */
    private function transferDeliveryPackingAndPicking(DelivHdr $delivHdr, int $delivHdr2Id, array $materials2Ids): void
    {
        // Query DelivPacking langsung dari database menggunakan trhdr_id
        $delivPackings = DelivPacking::where('trhdr_id', $delivHdr->id)
            ->where('tr_type', $delivHdr->tr_type)
            ->get();

        foreach ($delivPackings as $delivPacking) {
            // Cek apakah delivery packing sudah ada di TrdTire2
            $existingDelivPacking2 = DelivPacking2::on('TrdTire2')
                ->where('tr_code', $delivPacking->tr_code)
                ->where('tr_seq', $delivPacking->tr_seq)
                ->first();

            if ($existingDelivPacking2) {
                $delivPacking2Id = $existingDelivPacking2->id;
            } else {
                // Copy delivery packing dari TrdTire1 ke TrdTire2
                $delivPacking2 = new DelivPacking2();
                $delivPacking2->setConnection('TrdTire2');
                $delivPacking2->fill($delivPacking->toArray());
                $delivPacking2->trhdr_id = $delivHdr2Id;
                $delivPacking2->save();
                $delivPacking2Id = $delivPacking2->id;
            }

            // Query DelivPicking langsung dari database menggunakan trpacking_id
            $delivPickings = DelivPicking::where('trpacking_id', $delivPacking->id)->get();

            // Transfer Delivery Picking
            foreach ($delivPickings as $delivPicking) {
                $existingDelivPicking2 = DelivPicking2::on('TrdTire2')
                    ->where('trpacking_id', $delivPacking2Id)
                    ->where('tr_seq', $delivPicking->tr_seq)
                    ->first();

                if (!$existingDelivPicking2) {
                    $delivPicking2 = new DelivPicking2();
                    $delivPicking2->setConnection('TrdTire2');
                    $delivPicking2->fill($delivPicking->toArray());
                    $delivPicking2->trpacking_id = $delivPacking2Id;
                    $delivPicking2->matl_id = $materials2Ids[$delivPicking->matl_id] ?? $delivPicking->matl_id;
                    $delivPicking2->save();
                }
            }
        }
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
