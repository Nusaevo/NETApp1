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
        $table->string('created_user_id')->nullable();
        $table->string('updated_user_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
}
