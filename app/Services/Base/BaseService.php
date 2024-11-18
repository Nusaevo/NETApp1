<?php
namespace App\Services\Base;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Enums\Constant;

class BaseService
{
    protected $configConnection;
    protected $mainConnection;

    public function __construct($mainConnectionName = null, $configConnectionName = null)
    {
        $appCodeConnection = Session::get('databasee', 'pgsql');
        $this->setMainConnection($mainConnectionName ?? $appCodeConnection);
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
