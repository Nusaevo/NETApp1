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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->date('transfer_date');
            $table->unsignedBigInteger('warehouse_origin_id');
            $table->foreign('warehouse_origin_id')->references('id')->on('warehouses');
            $table->unsignedBigInteger('warehouse_destination_id');
            $table->foreign('warehouse_destination_id')->references('id')->on('warehouses');
            $table->foreignId('sales_order_id')->nullable()->constrained();//sebagai gabungan dari sales_warehouse_order
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
        Schema::dropIfExists('transfers');
    }
};
