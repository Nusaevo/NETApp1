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
        Schema::create('purchase_order_return_headers', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->foreignId('purchase_order_id')->constrained('purchase_order_headers');
            $table->string('status');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_return_headers');
    }
};
