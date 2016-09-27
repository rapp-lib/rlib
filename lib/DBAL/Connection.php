<?php
namespace R\Lib\DBAL;

/**
 *
 */
class Connection
{
    /**
     * 接続情報の作成
     */
    public static function get ($ds_name=null)
    {
        if ($ds_name === null) {
            $ds_name = "default";
        }

        if ( ! self::$connected[$ds_name]) {
            $info = registry("DBI.connection.".$ds_name);

            if ( ! $info["dbal"]) {
                $info["dbal"] = "cake2";
            }

            $connection_class = "R\\Lib\\DBAL\\Connection_".str_camelize($info["dbal"]);
            self::$connected[$ds_name] = new $connection_class($ds_name, $info);
        }

        return self::$connected[$ds_name];
    }

    /**
     * 接続情報の作成
     */
    public function __construct ($info)
    {
    }

    /**
     * 接続リソースの取得
     */
    public function ds ($st)
    {
    }

    /**
     * SQLの実行
     */
    public function exec ($st)
    {
    }

    /**
     * SQL実行結果の取得
     */
    public function fetch ($st)
    {
    }
}