<?php

namespace App\Models\TrdTire1\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\BaseModel;

class PartnerDetail extends BaseModel
{
    protected $table = 'partners_details';


    protected $fillable = [
        'wp_details',
        'positions',
        'banks',
    ];

    // Relasi balik ke tabel partners
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
