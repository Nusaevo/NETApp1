<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\DefaultColumnsTrait;
return new class extends Migration
{
    use DefaultColumnsTrait;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_price_logs', function (Blueprint $table) {
            $table->id();
            $table->decimal('old_price', 20, 5)->default(0);
            $table->decimal('new_price', 20, 5)->default(0);
            $table->foreignId('item_price_id')->constrained();
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
        Schema::dropIfExists('item_price_logs');
    }
};
