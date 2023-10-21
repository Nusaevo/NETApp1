<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigSnumsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_snums', function (Blueprint $table) {
            $table->id();
            $table->string('appl_code', 20)->default('');
            $table->string('snum_group', 50)->default('');
            $table->bigInteger('last_cnt')->default(0);
            $table->bigInteger('wrap_low')->default(0);
            $table->bigInteger('wrap_high')->default(0);
            $table->smallInteger('step_cnt')->default(0);
            $table->string('remark', 500)->default('');
            $table->string('status_code', 1)->default('A');
            $table->string('is_active', 1)->default('1');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_snums');
    }
}
