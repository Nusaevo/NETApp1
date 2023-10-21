<?php

namespace App\Traits;
use DB;
trait DefaultColumnsTrait
{
    protected function generateDefaultColumns($table)
    {
        $table->id();
    }

    protected function generateDefaultTimeStamp($table)
    {
        $table->integer('version_number')->default(1);
        $table->string('created_by')->default(DB::raw('CURRENT_USER'));
        $table->string('updated_by')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
}
