<?php

namespace App\Traits;
use DB;
trait DefaultColumnsTrait
{
    protected function generateDefaultColumns($table)
    {
        $table->id();
        $table->string('object_name')->nullable();
        $table->string('object_number')->nullable();
        $table->string('object_value')->nullable();
    }

    protected function generateDefaultTimeStamp($table)
    {
        $table->string('created_user_id')->nullable();
        $table->string('updated_user_Id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    }
}
