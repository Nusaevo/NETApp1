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
        Schema::create('partners', function (Blueprint $table) {
            $this->generateDefaultColumns($table);
            $table->string('grp', 5)->default('');
            $table->string('name', 50)->default('');
            $table->string('name_prefix', 5)->default('');
            $table->string('type_code', 5)->default('');
            $table->string('address', 8000)->default('');
            $table->string('city', 20)->default('');
            $table->string('country', 20)->default('');
            $table->string('postal_code', 10)->default('');
            $table->string('contact_person', 8000)->default('');
            $table->string('collect_sched', 100)->default('');
            $table->string('payment_term', 5)->default('');
            $table->string('curr_id', 5)->default('');
            $table->string('bank_acct', 8000)->default('');
            $table->string('tax_npwp', 20)->default('');
            $table->string('tax_nppkp', 20)->default('');
            $table->string('tax_address', 200)->default('');
            $table->integer('pic_id')->default(0);
            $table->string('pic_grp', 5)->default('');
            $table->string('pic_code', 20)->default('');
            $table->string('info', 8000)->default('');
            $table->decimal('amt_limit', 19, 4)->default(0);
            $table->decimal('amt_bal', 19, 4)->default(0);
            $table->string('status_code', 25)->default('');
            $table->unique(['grp', 'code']);
            $table->unique(['grp', 'name']);
            $this->generateDefaultTimeStamp($table);
        });
    }

    public function down()
    {
        Schema::dropIfExists('partners');
    }
};
