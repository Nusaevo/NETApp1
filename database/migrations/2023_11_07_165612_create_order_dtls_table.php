<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;
class CreateOrderDtlsTable extends Migration
{
    use DefaultColumnsTrait;
    public function up()
    {
        Schema::create('order_dtls', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('trhdr_id')->default(0);
            $table->string('tr_type', 5)->default('');
            $table->bigInteger('tr_id')->default(0);
            $table->smallInteger('tr_seq')->default(0);
            $table->unsignedInteger('item_unit_id')->default(0);
            $table->string('item_name', 200)->default('');
            $table->string('unit_name', 5)->default('');
            $table->decimal('qty', 12, 2)->default(0);
            $table->decimal('qty_reff', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('amt', 12, 2)->default(0);
            $table->string('status_code', 1)->default('');
            $this->generateDefaultTimeStamp($table);
            $table->primary('id');
        });
    }
    public function down()
    {
        Schema::dropIfExists('order_dtls');
    }
}
