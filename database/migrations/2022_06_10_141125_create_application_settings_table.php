<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

return new class extends Migration
{
    use DefaultColumnsTrait;
    public function up()
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('key');
            $table->string('value');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_settings');
    }
};
