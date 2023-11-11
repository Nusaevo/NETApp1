<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;


class CreateConfigApplsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_appls', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('name', 100)->default('');
            $table->string('version', 15)->default('');
            $table->string('descr', 500)->default('');
            $table->string('status_code', 1)->default('A');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_appls');
    }
}
