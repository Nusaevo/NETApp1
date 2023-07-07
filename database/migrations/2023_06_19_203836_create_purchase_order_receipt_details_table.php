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
        Schema::create('purchase_order_receipt_details', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->foreignId('header_id')->constrained('purchase_order_receipt_headers');
            $table->foreignId('product_attribute_id')->constrained('product_attributes');
            $table->integer('quantity');
            $table->boolean('verified')->default(false);
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_receipt_details');
    }
};
