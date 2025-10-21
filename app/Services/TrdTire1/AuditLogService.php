<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\AuditLogs;
use App\Models\TrdTire1\Transaction\BillingHdr;
use App\Models\TrdTire1\Transaction\DelivHdr;
use App\Models\TrdTire1\Transaction\DelivPacking;
use App\Models\TrdTire1\Transaction\DelivPicking;
use App\Models\TrdTire1\Master\Partner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuditLogService
{
    public static function createPrintDateAuditLogs(array $billingIds, string $newPrintDate, ?string $oldPrintDate = null): array
    {
        $auditLogs = [];

        foreach ($billingIds as $billingId) {
            $billing = BillingHdr::with('Partner')->find($billingId);

            if (!$billing) {
                continue; // Skip if billing not found
            }

            // Get partner name
            $partnerName = $billing->Partner ? $billing->Partner->name : 'Unknown Partner';

            // Prepare audit trail data sesuai dengan format di gambar
            $auditTrail = [
                'print_date before' => $oldPrintDate ?? $billing->print_date,
                'print_date after' => $newPrintDate,
                'user_name' => Auth::user()?->name ?? 'system',
                'billing_id' => $billingId,
                'tr_code' => $billing->tr_code,
                'partner_name' => $partnerName,
                'event_time' => Carbon::now(),
            ];

            // Create audit log
            $auditLog = AuditLogs::create([
                'group_code' => 'BILLING',
                'event_code' => 'TAGIHAN',
                'event_time' => Carbon::now(),
                'key_value' => $billingId,
                'audit_trail' => $auditTrail,
            ]);

            $auditLogs[] = $auditLog;
        }

        return $auditLogs;
    }


    public static function createPrintAuditLogs(array $billingIds): array
    {
        $auditLogs = [];

        foreach ($billingIds as $billingId) {
            $billing = BillingHdr::with('Partner')->find($billingId);

            if (!$billing) {
                continue; // Skip if billing not found
            }

            // Get partner name
            $partnerName = $billing->Partner ? $billing->Partner->name : 'Unknown Partner';

            // Prepare audit trail data
            $auditTrail = [
                'status before' => $billing->status_code,
                'status after' => 'P', // PRINT status
                'user_name' => Auth::user()?->name ?? 'system',
                'billing_id' => $billingId,
                'tr_code' => $billing->tr_code,
                'partner_name' => $partnerName,
                'event_time' => Carbon::now(),
                'action' => 'CETAK NOTA',
            ];

            // Create audit log
            $auditLog = AuditLogs::create([
                'group_code' => 'BILLING',
                'event_code' => 'CETAK',
                'event_time' => Carbon::now(),
                'key_value' => $billingId,
                'audit_trail' => $auditTrail,
            ]);

            $auditLogs[] = $auditLog;
        }

        return $auditLogs;
    }

    /**
     * Get audit logs for specific billing
     *
     * @param int $billingId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBillingAuditLogs(int $billingId)
    {
        return AuditLogs::where('group_code', 'BILLING')
            ->where('key_value', $billingId)
            ->orderBy('event_time', 'desc')
            ->get();
    }

    /**
     * Get audit logs by event code
     *
     * @param string $eventCode
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAuditLogsByEvent(string $eventCode, int $limit = 100)
    {
        return AuditLogs::where('group_code', 'BILLING')
            ->where('event_code', $eventCode)
            ->orderBy('event_time', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all billing audit logs
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllBillingAuditLogs(int $limit = 100)
    {
        return AuditLogs::where('group_code', 'BILLING')
            ->orderBy('event_time', 'desc')
            ->limit($limit)
            ->get();
    }

    // public static function createDeliveryKirim(int $delivHdrId)
    // {
    //     $deliv = DelivHdr::find($delivHdrId);
    //     if (!$deliv) {
    //         return null;
    //     }

    //     $reffhdrId = DelivPacking::where('trhdr_id', $deliv->id)->value('reffhdr_id');
    //     $firstPackingId = DelivPacking::where('trhdr_id', $deliv->id)->value('id');
    //     $whCode = null;
    //     if ($firstPackingId) {
    //         $whCode = DelivPicking::where('trpacking_id', $firstPackingId)->value('wh_code');
    //     }

    //     $auditTrail = [
    //         'nota' => $deliv->tr_code,
    //         'gudang' => $whCode,
    //         'tanggal kirim' => $deliv->tr_date ? Carbon::parse($deliv->tr_date)->format('Y-m-d') : null,
    //         'user_id' => Auth::id() ?? 'system',
    //         'event_time' => Carbon::now(),
    //     ];

    //     return AuditLogs::create([
    //         'group_code' => 'DELIVERY',
    //         'event_code' => 'KIRIM',
    //         'event_time' => Carbon::now(),
    //         'key_value' => $reffhdrId ?? $deliv->id,
    //         'audit_trail' => $auditTrail,
    //     ]);
    // }

    public static function createDeliveryKirim(array $delivHdrIds): array
    {
        $auditLogs = [];

        foreach ($delivHdrIds as $delivHdrId) {
            $deliv = DelivHdr::find($delivHdrId);

            if (!$deliv) {
                continue; // Skip if delivery not found
            }

            $reffhdrId = DelivPacking::where('trhdr_id', $deliv->id)->value('reffhdr_id');
            $firstPackingId = DelivPacking::where('trhdr_id', $deliv->id)->value('id');
            $whCode = null;
            if ($firstPackingId) {
                $whCode = DelivPicking::where('trpacking_id', $firstPackingId)->value('wh_code');
            }

            $auditTrail = [
                'nota' => $deliv->tr_code,
                'gudang' => $whCode,
                'tanggal kirim' => $deliv->tr_date ? Carbon::parse($deliv->tr_date)->format('Y-m-d') : null,
                'user_name' => Auth::user()?->name ?? 'system',
                'event_time' => Carbon::now(),
            ];

            // Create audit log
            $auditLog = AuditLogs::create([
                'group_code' => 'DELIVERY',
                'event_code' => 'KIRIM',
                'event_time' => Carbon::now(),
                'key_value' => $reffhdrId ?? $deliv->id,
                'audit_trail' => $auditTrail,
            ]);

            $auditLogs[] = $auditLog;
        }

        return $auditLogs;
    }

    // public static function createDeliveryBatalKirim(int $delivHdrId)
    // {
    //     $deliv = DelivHdr::find($delivHdrId);
    //     if (!$deliv) {
    //         return null;
    //     }

    //     $reffhdrId = DelivPacking::where('trhdr_id', $deliv->id)->value('reffhdr_id');
    //     $firstPackingId = DelivPacking::where('trhdr_id', $deliv->id)->value('id');
    //     $whCode = null;
    //     if ($firstPackingId) {
    //         $whCode = DelivPicking::where('trpacking_id', $firstPackingId)->value('wh_code');
    //     }

    //     $auditTrail = [
    //         'nota' => $deliv->tr_code,
    //         'gudang' => $whCode,
    //         'tanggal kirim' => $deliv->tr_date ? Carbon::parse($deliv->tr_date)->format('Y-m-d') : null,
    //         'user_id' => Auth::id() ?? 'system',
    //         'event_time' => Carbon::now(),
    //     ];

    //     return AuditLogs::create([
    //         'group_code' => 'DELIVERY',
    //         'event_code' => 'BATAL KIRIM',
    //         'event_time' => Carbon::now(),
    //         'key_value' => $reffhdrId ?? $deliv->id,
    //         'audit_trail' => $auditTrail,
    //     ]);
    // }


    public static function createDeliveryBatalKirim(array $delivHdrIds): array
    {
        $auditLogs = [];

        foreach ($delivHdrIds as $delivHdrId) {
            $deliv = DelivHdr::find($delivHdrId);

            if (!$deliv) {
                continue; // Skip if delivery not found
            }

            $reffhdrId = DelivPacking::where('trhdr_id', $deliv->id)->value('reffhdr_id');
            $firstPackingId = DelivPacking::where('trhdr_id', $deliv->id)->value('id');
            $whCode = null;
            if ($firstPackingId) {
                $whCode = DelivPicking::where('trpacking_id', $firstPackingId)->value('wh_code');
            }

            $auditTrail = [
                'nota' => $deliv->tr_code,
                'gudang' => $whCode,
                'tanggal kirim' => $deliv->tr_date ? Carbon::parse($deliv->tr_date)->format('Y-m-d') : null,
                'user_name' => Auth::user()?->name ?? 'system',
                'event_time' => Carbon::now(),
            ];

            // Create audit log
            $auditLog = AuditLogs::create([
                'group_code' => 'DELIVERY',
                'event_code' => 'BATAL KIRIM',
                'event_time' => Carbon::now(),
                'key_value' => $reffhdrId ?? $deliv->id,
                'audit_trail' => $auditTrail,
            ]);

            $auditLogs[] = $auditLog;
        }

        return $auditLogs;
    }


    public static function testAuditLogCreation(int $billingId): bool
    {
        try {
            $auditLog = self::createPrintDateAuditLogs(
                [$billingId],
                now()->format('Y-m-d'),
                null
            );

            return !empty($auditLog);
        } catch (\Exception $e) {
            Log::error('Test audit log creation failed: ' . $e->getMessage());
            return false;
        }
    }
}
