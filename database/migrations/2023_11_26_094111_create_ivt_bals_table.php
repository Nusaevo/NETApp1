<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up()
    {
        Schema::create('ivt_bals', function (Blueprint $table) {
            $table->id();
            $table->integer('matl_id')->default(0);
            $table->string('matl_uom', 5)->default('');
            $table->integer('wh_id')->default(0);
            $table->string('wh_code', 20)->default('');
            $table->string('batch_code', 50)->default('');
            $table->decimal('qty_oh', 18, 2)->default(0);
            $table->string('wh_bin', 10)->default('');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ivt_bals');
    }
};
