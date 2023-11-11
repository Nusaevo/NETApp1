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
            $this->generateDefaultColumns($table);
            $table->foreignId('appl_id')->constrained('config_appls');
            $table->string('appl_code', 20)->default('');
            $table->string('menu_header', 100)->default('');
            $table->string('sub_menu', 100);
            $table->string('menu_caption', 100)->default('');
            $table->string('link', 100)->default('');
            $table->string('status_code', 1)->default('A');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_menus');
    }
}
