<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigAuditsTable extends Migration
{
    use DefaultColumnsTrait;

    public function up()
    {
        Schema::create('config_audits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('appl_code', 20)->default('');
            $table->string('key_code', 30)->default('');
            $table->timestamp('log_time')->default(now());
            $table->string('action_code', 20)->default('');
            $table->text('audit_trail')->default('');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('config_audits');
    }
}
