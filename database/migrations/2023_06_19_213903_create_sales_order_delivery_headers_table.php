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
        Schema::create('sales_order_delivery_headers', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->foreignId('sales_order_id')->constrained('sales_order_headers');
            $table->string('status');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_order_delivery_headers');
    }
};
