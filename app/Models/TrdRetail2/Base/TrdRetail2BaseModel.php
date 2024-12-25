<?php

namespace App\Models\TrdRetail2\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Attachment;
use App\Enums\Constant;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Session;


class TrdRetail2BaseModel extends BaseModel
{
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode;
    }
}
