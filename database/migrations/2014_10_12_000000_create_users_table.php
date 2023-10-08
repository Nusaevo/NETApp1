<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;

class CreateUsersTable extends Migration
{
    use DefaultColumnsTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->string('code', 20)->default('');
            $table->string('descr', 200)->default('');
            $table->string('parent_id', 20)->default('');
            $table->string('grp', 1)->default('');
            $table->string('type_code', 1)->default('');
            $table->string('level_code', 1)->default('');
            $table->string('status_code', 1)->default('A');
            $table->string('is_active', 1)->default('1');
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
        Schema::dropIfExists('Users');
    }
}
