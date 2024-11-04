<?php

namespace App\Models\TrdRetail1\Config;

use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use App\Models\Base\BaseModel\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
class AppAudit extends TrdRetail1BaseModel
{
    protected $table = 'app_audits';
    public $timestamps = false;

    protected $fillable = [
        'key_code',
        'log_time',
        'action_code',
        'audit_trail',
        'table_name',
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
     * Scope method to get ordered data by log time
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
        $statusUpdate = "[Progress: {$progress}%] {$message}";

        $this->update([
            'audit_trail' => $statusUpdate,
        ]);
    }

    /**
     * Reset audit trail for a re-upload attempt.
     */
    public function resetForReupload()
    {
        $this->update([
            'audit_trail' => "Re-upload attempt started at " . now(),
        ]);
    }
}
