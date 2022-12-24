<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->decimal('total_tax', 20, 5)->default(0);
            $table->decimal('total_amount', 20, 5)->default(0);
            $table->decimal('return_amount', 20, 5)->default(0);
            $table->decimal('total_discount', 20, 5)->default(0);
            $table->decimal('tax_percentage', 20, 5)->default(0);
            $table->string('status');
            $table->string('supplier_name');
            $table->foreignId('supplier_id')->constrained();
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
        Schema::dropIfExists('purchase_orders');
    }
};
