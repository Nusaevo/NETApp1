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
        Schema::create('purchase_receives_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receive_id')->constrained();
            $table->foreignId('purchase_order_details_id')->constrained();
            $table->foreignId('item_warehouse_id')->constrained();
            $table->string('item_name');
            $table->string('unit_name');
            $table->decimal('qty',20,8)->default(0);
            $table->string('remark'); //rusak, cacat, normal
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
        Schema::dropIfExists('receive_details');
    }
};
