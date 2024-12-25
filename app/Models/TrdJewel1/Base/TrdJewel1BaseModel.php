<?php

namespace App\Models\TrdJewel1\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\Constant;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Session;


class TrdJewel1BaseModel extends BaseModel
{
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode;
    }
}
