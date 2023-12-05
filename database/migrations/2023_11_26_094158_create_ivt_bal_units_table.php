<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ivt_bal_units', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ivt_id')->default(0);
            $table->integer('matl_id')->default(0);
            $table->string('matl_uom', 5)->default('');
            $table->string('wh_id', 20)->default('');
            $table->string('batch_code', 50)->default('');
            $table->string('unit_code', 100)->default('');
            $table->decimal('qty_oh', 18, 2)->default(0);
            $table->string('status_code', 25)->default('');
            $table->timestamps();

            $table->foreign('ivt_id')->references('id')->on('ivt_bals');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ivt_bal_units');
    }
};
