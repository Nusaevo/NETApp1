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
        Schema::create('sales_order_return_details', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->foreignId('header_id')->constrained('sales_order_return_headers');
            $table->foreignId('product_attribute_id')->constrained('product_attributes');
            $table->integer('quantity');
            $table->string('reason');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_order_return_details');
    }
};
