<?php

namespace App\Models\TrdRetail1\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TrdRetail1\Base\Attachment;
use App\Enums\Constant;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Session;
use DB;


class TrdRetail1BaseModel extends BaseModel
{
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = 'main';
    }

    public function Attachment()
    {
        return $this->hasMany(Attachment::class, 'attached_objectid')
            ->where('attached_objecttype', class_basename($this));
    }
}
