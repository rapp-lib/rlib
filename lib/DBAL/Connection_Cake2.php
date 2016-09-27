<?php
namespace R\Lib\DBAL;
use R\Lib\DBAL\Connection;
/**
 *
 */
class Connection_Cake2 extends Connection
{
    /**
     * @override
     */
    public function __construct ($ds_name, $info)
    {
        require_once(dirname(__FILE__)."/../cake2/rlib_cake2.php");
        require_once(constant("CAKE_DIR").'/Model/ConnectionManager.php');

        // [Deprecated] 旧cakeとの互換処理
        if ($info["driver"] && ! $info["datasource"]) {
            $info["datasource"] = 'Database/'.str_camelize($info["driver"]);
        }

        ConnectionManager::create($ds_name, $info);
    }

    /**
     * @override
     */
    public function ds ($st)
    {
        return ConnectionManager::getDataSource($ds_name);
    }

    /**
     * @override
     */
    public function exec ($st)
    {
    }

    /**
     * @override
     */
    public function fetch ($st)
    {
    }
}