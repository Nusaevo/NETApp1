<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['item_id','transfer_id','qty','qty_defect','unit_id','remark'];

    public function transfer(){
        return $this->belongsTo('App\Models\Transfer');
    }

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function unit(){
        return $this->belongsTo('App\Models\Unit');
    }

    public function transfer_details(){
        return $this->hasMany('App\Models\TransferDetail');
    }
}
