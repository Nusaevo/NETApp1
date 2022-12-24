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
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->decimal('qty', 20, 5)->default(0);
            $table->decimal('price', 20, 5)->default(0);
            $table->decimal('discount', 20, 5)->default(0);
            $table->string('item_name');
            $table->string('unit_name');
            $table->foreignId('item_store_id')->constrained();
            $table->foreignId('purchase_order_id')->constrained();
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
        Schema::dropIfExists('purchase_order_details');
    }
};
