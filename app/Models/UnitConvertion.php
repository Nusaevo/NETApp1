<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitConvertion extends Model
{
    use HasFactory;

    protected $fillable = ['convertion_rate','origin_id','destination_id'];

    public function origin_unit(){
        return $this->belongsTo('App\Models\Unit','origin_id');
    }

    public function destination_unit(){
        return $this->belongsTo('App\Models\Unit','destination_id');
    }
}
