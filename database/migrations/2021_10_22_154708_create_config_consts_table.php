<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigConstsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_consts', function (Blueprint $table) {
            $this->generateDefaultColumns($table);

            $table->string('group_code', 20)->default('');
            $table->foreignId('group_id')->constrained('config_groups');
            $table->string('appl_code', 20)->default('');
            $table->foreignId('appl_id')->constrained('config_appls');
            $table->string('user_code', 50)->default('');
            $table->foreignId('user_id')->constrained('config_users');

            $table->smallInteger('seq')->default(1);
            $table->string('str1', 10)->default('');
            $table->string('str2', 20)->default('');
            $table->decimal('num1', 10, 2)->default(0);
            $table->decimal('num2', 10, 2)->default(0);
            $table->string('note1', 100)->default('');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_consts');
    }
}
