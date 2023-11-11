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
        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_warehouse_id')->constrained();
            $table->foreignId('sales_order_id')->constrained();
            $table->decimal('qty', 20, 5)->default(0);
            $table->decimal('qty_wo', 20, 5)->default(0);
            $table->decimal('price', 20, 5)->default(0);
            $table->decimal('discount', 20, 5)->default(0);
            $table->string('item_name');
            $table->string('unit_name');
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
        Schema::dropIfExists('sales_order_details');
    }
};
