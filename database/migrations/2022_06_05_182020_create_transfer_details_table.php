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
        Schema::create('transfer_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_warehouse_origin_id');
            $table->foreign('item_warehouse_origin_id')->references('id')->on('item_warehouses');
            $table->unsignedBigInteger('item_warehouse_destination_id');
            $table->foreign('item_warehouse_destination_id')->references('id')->on('item_warehouses');
            $table->foreignId('transfer_id')->constrained();
            $table->decimal('qty', 20, 8);
            $table->decimal('qty_defect', 20, 8);
            $table->string('remark');
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
        Schema::dropIfExists('transfer_details');
    }
};
