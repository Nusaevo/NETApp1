<?php

namespace App\Models\SysConfig1\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\Constant;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Session;


class SysConfig1BaseModel extends BaseModel
{
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::configConn();
    }
}
