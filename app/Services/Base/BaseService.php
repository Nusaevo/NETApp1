<?php

namespace App\Services\Base;

use Illuminate\Support\Facades\DB;
use App\Enums\Constant;

class BaseService
{
    protected $configConnection;

    public function __construct($connectionName = null)
    {
        $this->setConnection($connectionName ?? Constant::ConfigConn());
    }

    public function setConnection($connectionName)
    {
        $this->configConnection = DB::connection($connectionName);
    }

    public function getConnection()
    {
        return $this->configConnection;
    }
    protected function mapData($data)
    {
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->str1,
            ];
        })->toArray();
    }
}
