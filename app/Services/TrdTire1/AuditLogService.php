<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\AuditLogs;
use App\Models\TrdTire1\Transaction\BillingHdr;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuditLogService
{
    /**
     * Create audit log for billing print date changes
     *
     * @param array $billingIds Array of billing IDs
     * @param string $newPrintDate New print date
     * @param string|null $oldPrintDate Old print date (optional)
     * @return array Array of created audit logs
     */
    public static function createPrintDateAuditLogs(array $billingIds, string $newPrintDate, ?string $oldPrintDate = null): array
    {
        $auditLogs = [];

        foreach ($billingIds as $billingId) {
            $billing = BillingHdr::find($billingId);

            if (!$billing) {
                continue; // Skip if billing not found
            }

            // Prepare audit trail data sesuai dengan format di gambar
            $auditTrail = [
                'print_date before' => $oldPrintDate ?? $billing->print_date,
                'print_date after' => $newPrintDate,
                'user_id' => Auth::id() ?? 'system',
                'billing_id' => $billingId,
                'tr_code' => $billing->tr_code,
                'partner_id' => $billing->partner_id,
                'event_time' => now()->toISOString(),
            ];

            // Create audit log
            $auditLog = AuditLogs::create([
                'group_code' => 'BILLING',
                'event_code' => 'TAGIHAN',
                'event_time' => now(),
                'key_value' => $billingId,
                'audit_trail' => json_encode($auditTrail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]);

            $auditLogs[] = $auditLog;
        }

        return $auditLogs;
    }

    /**
     * Create audit log for billing print action
     *
     * @param array $billingIds Array of billing IDs
     * @return array Array of created audit logs
     */
    public static function createPrintAuditLogs(array $billingIds): array
    {
        $auditLogs = [];

        foreach ($billingIds as $billingId) {
            $billing = BillingHdr::find($billingId);

            if (!$billing) {
                continue; // Skip if billing not found
            }

            // Prepare audit trail data
            $auditTrail = [
                'status before' => $billing->status_code,
                'status after' => 'P', // PRINT status
                'user_id' => Auth::id() ?? 'system',
                'billing_id' => $billingId,
                'tr_code' => $billing->tr_code,
                'partner_id' => $billing->partner_id,
                'event_time' => now()->toISOString(),
                'action' => 'CETAK NOTA',
            ];

            // Create audit log
            $auditLog = AuditLogs::create([
                'group_code' => 'BILLING',
                'event_code' => 'CETAK',
                'event_time' => now(),
                'key_value' => $billingId,
                'audit_trail' => json_encode($auditTrail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
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

    /**
     * Test method to verify audit log creation
     *
     * @param int $billingId
     * @return bool
     */
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
