<?php

namespace App\Models\SrvInsur1\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SrvInsur1\Base\Attachment;
use App\Enums\Constant;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Session;


class SrvInsur1BaseModel extends BaseModel
{
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode;
    }


    public function Attachment()
    {
        return $this->hasMany(Attachment::class, 'attached_objectid')
            ->where('attached_objecttype', class_basename($this));
    }
}
