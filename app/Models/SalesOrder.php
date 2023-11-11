<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ModelTrait;

class SalesOrder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    protected $fillable = ['transaction_date', 'wo_date', 'total_tax', 'payment_id', 'is_finished', 'total_amount', 'total_discount', 'payment', 'tax_percentage', 'customer_name', 'customer_id'];

    public function scopeIsFinished($query, $value = 1)
    {
        return $query->where('sales_orders.is_finished', $value);
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\Customer')->withTrashed();
    }

    public function payment()
    {
        return $this->belongsTo('App\Models\Payment')->withTrashed();
    }

    public function sales_order_details()
    {
        return $this->hasMany('App\Models\SalesOrderDetail');
    }

    public function invoice_details()
    {
        return $this->hasMany('App\Models\InvoiceDetail');
    }
}
