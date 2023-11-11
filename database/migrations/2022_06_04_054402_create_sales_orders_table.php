<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Traits\DefaultColumnsTrait;
return new class extends Migration
{
    use DefaultColumnsTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_purchase_order_id')
            ->nullable()
            ->constrained('purchase_orders', 'id');
            $table->string('status_code', 5)->default('');
            $table->date('transaction_date');
            $table->date('wo_date');
            $table->decimal('total_tax', 20, 5)->default(0);
            $table->decimal('total_amount', 20, 5)->default(0);
            $table->decimal('total_discount', 20, 5)->default(0);
            $table->decimal('tax_percentage', 20, 5)->default(0);
            $table->string('customer_name');

            $table->foreignId('payment_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->boolean('is_finished')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
};
