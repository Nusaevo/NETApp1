<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

return new class extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('sales_order_headers', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->foreignId('customer_id')->constrained();
            $table->string('status');
            $table->decimal('total_amount', 8, 2);
            $table->decimal('total_amount_paid', 8, 2);
            $table->decimal('payment_method', 8, 2);
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_order_headers');
    }
};
