<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

return new class extends Migration
{
    use DefaultColumnsTrait;
    public function up()
    {
        Schema::create('product_attribute_location', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->integer('stock')->default(0);
            $table->string('status')->nullable();
            $this->generateDefaultTimeStamp($table);

            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->constrained();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attribute_location');
    }
};
