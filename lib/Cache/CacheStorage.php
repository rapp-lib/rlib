<?php
namespace R\Lib\Cache;
use Zend\Cache\StorageFactory;

class CacheStorage
{
    private $ds;
    public function __construct($cache_config)
    {
        $this->cache_config = $cache_config;
        $this->ds = StorageFactory::factory($cache_config);
    }
    public function __call($func, $args)
    {
        if ( ! is_callable(array($this->ds, $func))) {
            report_error("CacheDatasourceのメソッドが呼び出せません", array(
                "ds" => $this->ds,
                "func" => $func,
            ));
        }
        return call_user_func_array(array($this->ds, $func), $args);
    }
    public function getDs()
    {
        return $this->ds;
    }
}
