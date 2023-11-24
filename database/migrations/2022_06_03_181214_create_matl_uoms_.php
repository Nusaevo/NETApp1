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
        Schema::create('matl_uoms', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->integer('matl_id')->default(0);
            $table->string('matl_code', 20)->default('');
            $table->string('matl_uom', 5)->default('');
            $table->string('reff_uom', 5)->default('');
            $table->decimal('reff_factor', 15, 5)->default(0);
            $table->decimal('base_factor', 15, 5)->default(0);
            $table->string('price_grp', 5)->default('');
            $table->string('barcode', 50)->default('');
            $table->decimal('qty_oh', 15, 2)->default(0);
            $table->decimal('qty_fgr', 15, 2)->default(0);
            $table->decimal('qty_fgi', 15, 2)->default(0);
            $table->unique(['matl_id', 'matl_uom']);
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
        Schema::dropIfExists('matl_uoms');
    }
};
