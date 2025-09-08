<?php

namespace App\Models\TrdTire1\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\BaseModel;

class PartnerDetail extends BaseModel
{
    protected $table = 'partner_details';

    protected $fillable = [
        'partner_id',
        'partner_grp',
        'partner_code',
        'wp_details',
        'contacts',
        'banks',
        'shipping_address',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'wp_details' => 'array',
        'contacts' => 'array',
        'banks' => 'array',
        'partner_id' => 'integer',
    ];

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
