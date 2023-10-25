<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigVarsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_vars', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('appl_code', 20)->default('');
            $table->string('var_group', 50)->default('');
            $table->string('descr', 200)->default('');
            $table->smallInteger('seq')->default(1);
            $table->string('type_code', 1)->default('');
            $table->string('default_value', 50)->default('');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_vars');
    }
}
