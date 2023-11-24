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
        Schema::create('matl_boms', function (Blueprint $table) {
            $table->id();
            $table->integer('matl_id')->default(0);
            $table->string('matl_code', 20)->default('');
            $table->integer('base_matl_id')->default(0);
            $table->string('base_matl_code', 20)->default('');
            $table->smallInteger('seq')->default(1);
            $table->decimal('jwl_sides_carat', 15, 5)->default(0);
            $table->smallInteger('jwl_sides_cnt')->default(0);
            $table->string('jwl_sides_matl', 20)->default('');
            $table->string('jwl_sides_parcel', 100)->default('');
            $table->decimal('jwl_sides_price', 15, 2)->default(0);
            $table->decimal('jwl_sides_amt', 15, 2)->default(0);
            $this->generateDefaultTimeStamp($table);
            $table->unique(['matl_id', 'seq']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matl_boms');
    }
};
