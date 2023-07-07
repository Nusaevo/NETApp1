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
            $this->generateDefaultColumns($table);

            $table->unsignedBigInteger('user_id');
            $table->text('avatar')->nullable();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable();
            $table->string('language')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency')->nullable();
            $table->string('communication')->nullable();
            $table->tinyInteger('marketing')->nullable();
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
        Schema::dropIfExists('User_Infos');
    }
}
