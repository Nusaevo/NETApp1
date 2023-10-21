<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigMenusTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_menus', function (Blueprint $table) {
            $table->id();
            $table->string('appl_code', 20)->default('');
            $table->string('menu_code', 100)->default('');
            $table->string('menu_caption', 100)->default('');
            $table->string('status_code', 1)->default('A');
            $table->string('is_active', 1)->default('1');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_menus');
    }
}
