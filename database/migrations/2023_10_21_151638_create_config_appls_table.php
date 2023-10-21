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
            $table->id();
            $table->string('appl_code', 20)->default('');
            $table->string('appl_ver', 15)->default('');
            $table->string('appl_name', 100)->default('');
            $table->string('appl_desc', 500)->default('');
            $table->string('status_code', 1)->default('A');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_appls');
    }
}
