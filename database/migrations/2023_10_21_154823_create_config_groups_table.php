<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigGroupsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_groups', function (Blueprint $table) {
            $table->id();
            $table->string('appl_code', 20)->default('');
            $table->string('group_code', 50)->default('');
            $table->string('user_code', 50)->default('');
            $table->string('note1', 200)->default('');
            $table->string('status_code', 1)->default('A');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_groups');
    }
}
