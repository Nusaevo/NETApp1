<?php

namespace App\Traits;
use DB;
trait DefaultColumnsTrait
{
    protected function generateDefaultColumns($table)
    {
        $table->id();
        $table->string('code', 50)->unique();
    }

    protected function generateDefaultTimeStamp($table)
    {
        $table->integer('version_number')->default(1);
        $table->string('created_by', 50)->default(DB::raw('CURRENT_USER'));
        $table->string('updated_by', 50)->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
}
