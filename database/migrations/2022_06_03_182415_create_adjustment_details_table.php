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
        Schema::create('adjustment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained();
            $table->foreignId('item_store_id')->constrained();
            $table->decimal('qty_before', 20, 8);
            $table->decimal('qty_defect_before', 20, 8);
            $table->decimal('qty', 20, 8);
            $table->decimal('qty_defect', 20, 8);
            $table->decimal('price', 20, 8);
            $table->string('remark');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adjustment_details');
    }
};
