<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigRightsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_rights', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('appl_code', 20)->default('');
            $table->string('group_code', 50)->default('');
            $table->string('menu_code', 100)->default('');
            $table->smallInteger('menu_seq')->default(0);
            $table->string('trustee', 5)->default('');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_rights');
    }
}
