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
            $this->generateDefaultColumns($table);
            $table->foreignId('user_id')->constrained('config_users');
            $table->string('user_code', 50)->default('');
            $table->foreignId('appl_id')->constrained('config_appls');
            $table->string('appl_code', 20)->default('');
            $table->string('name', 200)->default('');
            $table->string('status_code', 1)->default('A');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_groups');
    }
}
