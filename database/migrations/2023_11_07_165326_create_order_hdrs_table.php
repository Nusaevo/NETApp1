<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateOrderHdrsTable extends Migration
{
    use DefaultColumnsTrait;
    public function up()
    {
        Schema::create('order_hdrs', function (Blueprint $table) {
            $table->id();
            $table->string('tr_type', 5)->default('');
            $table->bigInteger('tr_id')->default(0);
            $table->date('tr_date')->default('1900-01-01');
            $table->string('reff_code', 100)->nullable(false);
            $table->unsignedInteger('partner_id')->default(0);
            $table->string('partner_code', 20)->default('');
            $table->unsignedInteger('sales_id')->default(0);
            $table->string('sales_code', 20)->default('');
            $table->string('deliv_by', 100)->default('');
            $table->unsignedInteger('payment_term_id')->default(0);
            $table->string('payment_term', 20)->default('');
            $table->unsignedInteger('curr_id')->default(0);
            $table->string('curr_code', 20)->default('');
            $table->decimal('curr_rate', 12, 2)->default(1);
            $table->string('status_code', 1)->default('');
            $this->generateDefaultTimeStamp($table);
            $table->primary('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_hdrs');
    }
}
