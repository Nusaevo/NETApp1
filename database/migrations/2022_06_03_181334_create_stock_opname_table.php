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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_warehouse_id')->constrained();
            $table->decimal('old_qty', 20, 8)->default(0);
            $table->decimal('new_qty', 20, 8)->default(0);
            $table->decimal('old_qty_defect', 20, 8)->default(0);
            $table->decimal('new_qty_defect', 20, 8)->default(0);
            $this->generateDefaultTimeStamp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_opnames');
    }
};
