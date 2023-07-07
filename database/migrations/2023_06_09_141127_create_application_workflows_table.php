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
        Schema::create('application_workflows', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('type');
            $table->string('status');
            $table->string('next_status');
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_workflows');
    }
};
