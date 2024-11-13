<?php

namespace App\Services\Base;

use Illuminate\Support\Facades\DB;
use App\Enums\Constant;

class BaseService
{
    protected $configConnection;
    protected $mainConnection;

    public function __construct($mainConnectionName = null, $configConnectionName = null)
    {
        // Set main connection to 'app' by default, or use the provided connection name
        $this->setMainConnection($mainConnectionName ?? Constant::AppConn());

        // Set config connection to 'config' by default, or use the provided connection name
        $this->setConfigConnection($configConnectionName ?? Constant::ConfigConn());
    }

    public function setMainConnection($connectionName)
    {
        $this->mainConnection = DB::connection($connectionName);
    }

    public function getMainConnection()
    {
        return $this->mainConnection;
    }

    public function setConfigConnection($connectionName)
    {
        $this->configConnection = DB::connection($connectionName);
    }

    public function getConfigConnection()
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
