<?php
namespace R\Lib\Core;

use Illuminate\Config\Repository;

class Config extends Repository
{
    public function __invoke ($key)
    {
        return $this[$key];
    }
    public function __construct ()
    {
        $values = array();
        // 設定ファイルの読み込み
        $config_dir = constant("R_APP_ROOT_DIR")."/config";
        foreach (glob($config_dir."/*.config.php") as $config_file) {
            foreach ((array)include($config_file) as $k=>$v) \R\Lib\Util\Arr::array_add($values, $k, $v);
        }
        // 環境別設定ファイルの読み込み
        if ($app_env = \R\Lib\Util\Arr::array_get($values, "app.env")) {
            foreach (glob($config_dir."/env/".$app_env."/*.config.php") as $config_file) {
                foreach ((array)include($config_file) as $k=>$v) \R\Lib\Util\Arr::array_add($values, $k, $v);
            }
        }
        foreach (\R\Lib\Util\Arr::array_dot($values) as $k=>$v) $this[$k] = $v;
    }
    public function prepend($key, $value)
    {
        $array = $this->get($key);
        array_unshift($array, $value);
        $this->set($key, $array);
    }
    public function push($key, $value)
    {
        $array = $this->get($key);
        $array[] = $value;
        $this->set($key, $array);
    }
	protected function load($group, $namespace, $collection)
	{
        //
    }
}
