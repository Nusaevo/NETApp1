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
        Schema::create('product_attributes', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->decimal('amount', 8, 2);
            $table->foreignId('product_id')->constrained();
            $table->foreignId('attribute_id')->constrained();
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attributes');
    }
};
