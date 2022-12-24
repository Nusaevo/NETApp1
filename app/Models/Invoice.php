<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_date','reference_number','invoice_type','total_amount','total_tax','total_discount'];
   
    public function invoice_details(){
        return $this->hasMany('App\Models\InvoiceDetail');
    }
}
