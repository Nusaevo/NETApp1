<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Traits\ModelTrait;
use App\Models\Master\Partner;

class BillingHdr extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;

    protected $table = 'billing_hdrs';

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    protected $fillable = [
        'tr_type',
        'tr_id',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'payment_term_id',
        'payment_term',
        'payment_due_days',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
        'created_by',
        'updated_by',
        'version_number',
    ];

    // Define the relationships
    public function partners()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function orderDtls()
    {
        return $this->hasMany(OrderDtl::class, 'trhdr_id', 'id');
    }
}
