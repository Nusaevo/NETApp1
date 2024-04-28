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
        Schema::create('materials', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('name', 100)->default('');
            $table->string('descr', 200)->default('');
            $table->string('type_code', 5)->default('');
            $table->string('class_code', 5)->default('');
            $table->string('jwl_carat', 20)->default('');
            $table->string('jwl_base_matl', 20)->default('');
            $table->string('jwl_category1', 20)->default('');
            $table->decimal('jwl_wgt_gold', 15, 5)->default(0);
            // $table->integer('jwl_supplier_id')->default(0);
            // $table->string('jwl_supplier_code', 20)->default('');
            // $table->string('jwl_supplier_id1', 20)->default('');
            // $table->string('jwl_supplier_id2', 20)->default('');
            // $table->string('jwl_supplier_id3', 20)->default('');
            $table->decimal('jwl_sides_carat', 15, 5)->default(0);
            // $table->smallInteger('jwl_sides_cnt')->default(0);
            // $table->string('jwl_sides_matl', 20)->default('');
            // $table->decimal('jwl_selling_price_usd', 15, 2)->default(0);
            // $table->decimal('jwl_selling_price', 15, 2)->default(0);
            // $table->string('jwl_sides_calc_method', 5)->default('');
            // $table->decimal('jwl_matl_price', 15, 2)->default(0);
            $table->string('jwl_sellprc_calc_method', 5)->default('');
            $table->integer('jwl_price_markup_id')->default(0);
            $table->string('jwl_price_markup_code', 20)->default('');
            $table->decimal('jwl_selling_price', 15, 2)->default(0);
            $table->string('uom', 5)->default('');
            $table->string('brand', 20)->default('');
            $table->string('dimension', 200)->default('');
            $table->decimal('wgt', 15, 2)->default(0);
            $table->decimal('qty_min', 15, 2)->default(0);
            $table->string('taxable', 1)->default('');
            $table->string('info', 8000)->default('');
            $table->string('status_code', 25)->default('');
            $table->index('name');
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
        Schema::dropIfExists('materials');
    }
};
