<?php

namespace App\Models\TrdRetail1\Config;

use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use Illuminate\Support\Facades\Auth;
class ConfigAudit extends TrdRetail1BaseModel
{
    protected $table = 'config_audits';
    public $timestamps = false;

    protected $fillable = [
        'key_code',
        'log_time',
        'action_code',
        'progress',
        'audit_trail',
        'table_name',
        'status_code',
        'created_at',
        'created_by'
    ];

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
     * Update audit trail with progress and status message.
     *
     * @param int $progress The progress percentage.
     * @param string $message The status or error message to append to the audit trail.
     */
    public function updateAuditTrail($progress, $message)
    {
        // Determine status code based on progress
        $statusCode = $progress === 100 ? 'S' : ($progress === 0 ? 'E' : 'P'); // 'S' = Success, 'E' = Error, 'P' = Processing

        $this->update([
            'audit_trail' => $message,     // Only the message is stored here
            'status_code' => $statusCode,
            'progress' => $progress,       // Update the progress column directly
        ]);
    }

    /**
     * Reset audit trail for a re-upload attempt.
     */
    public function resetForReupload()
    {
        $this->update([
            'audit_trail' => "Re-upload attempt started at " . now(),
            'status_code' => 'P',
            'progress' => 0,
        ]);
    }
}
