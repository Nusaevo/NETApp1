<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateConfigUsersTable extends Migration
{
    use DefaultColumnsTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('config_users', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('email')->unique();
            $table->string('name', 100)->default('');
            $table->string('remember_token', 100)->nullable();
            $table->string('password', 80)->default('');
            $table->string('dept', 50)->default('');
            $table->string('phone', 100)->default('');
            $table->string('status_code', 1)->default('');
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
        Schema::dropIfExists('config_users');
    }
}
