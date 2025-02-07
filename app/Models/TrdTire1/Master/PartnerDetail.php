<?php

namespace App\Models\TrdTire1\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\BaseModel;

class PartnerDetail extends BaseModel
{
    protected $table = 'partner_details';

    protected $fillable = [
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
    ];
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
