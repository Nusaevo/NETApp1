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
        Schema::create('customers', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();

            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
