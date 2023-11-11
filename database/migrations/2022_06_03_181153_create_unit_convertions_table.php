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
        Schema::create('unit_convertions', function (Blueprint $table) {
            $table->id();
            //origin -> destination ( x 1000 ) atau / 1000, dst...
            $table->decimal('convertion_rate',20,5)->default(1);
            $table->unsignedBigInteger('origin_id');
            $table->foreign('origin_id')->references('id')->on('units');
            
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->foreign('destination_id')->references('id')->on('units');
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
        Schema::dropIfExists('unit_convertions');
    }
};
