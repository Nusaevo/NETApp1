<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateUserInfosTable extends Migration
{
    use DefaultColumnsTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_infos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->text('avatar')->nullable();
            $table->string('company', 255);
            $table->string('phone', 255);
            $table->string('website', 255);
            $table->string('country', 255);
            $table->string('language', 255);
            $table->string('timezone', 255);
            $table->string('currency', 255);
            $table->string('communication', 255);
            $this->generateDefaultTimeStamp($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_infos');
    }
}

