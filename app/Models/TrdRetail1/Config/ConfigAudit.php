<?php

namespace App\Models\TrdRetail1\Config;

use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Auth;
class ConfigAudit extends BaseModel
{
    protected $table = 'config_audits';
    public $timestamps = false;

    protected $fillable = ['key_code', 'log_time', 'action_code', 'progress', 'audit_trail', 'table_name', 'status_code', 'created_at', 'created_by'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appAudit) {
            $appAudit->created_by = Auth::user()->code ?? 'system';
            $appAudit->created_at = now();
        });
    }

    /**
     * Scope method to get ordered data by log time.
     */
    public function scopeGetOrderedData($query)
    {
        return $query->orderBy('log_time', 'desc')->get();
    }

    /**
     * Update audit trail with progress, status code, and message.
     *
     * @param int $progress The progress percentage.
     * @param string $message The status or error message to append to the audit trail.
     * @param string|null $statusCode Optional status code ('S' = Success, 'E' = Error, 'P' = Processing).
     *                                 Defaults to null and infers from progress.
     */
    public function updateAuditTrail($progress, $message, $statusCode = null)
    {
        $this->update([
            'audit_trail' => $message, // Update the message
            'status_code' => $statusCode, // Explicit status code
            'progress' => $progress, // Update progress
        ]);
    }

    /**
     * Reset audit trail for a re-upload attempt.
     */
    public function resetForReupload()
    {
        $this->update([
            'audit_trail' => 'Re-upload attempt started at ' . now(),
            'status_code' => 'P',
            'progress' => 0,
        ]);
    }
}
