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
        Schema::create('item_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_variant_id')->constrained();
            $table->foreignId('store_id')->constrained();
            $table->decimal('qty', 20, 8)->default(0);
            $table->decimal('qty_defect', 20, 8)->default(0);
            $table->decimal('price', 20, 8)->default(0);
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
        Schema::dropIfExists('item_warehouses');
    }
};
