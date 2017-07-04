<?php
namespace R\Lib\Cache;

class CacheDriver
{
    private $storages = array();
    public function __invoke($ds_name="default")
    {
        return $this->getStorage($ds_name);
    }
    public function __call($func, $args)
    {
        return call_user_func_array(array($this->getStorage("default"), $func), $args);
    }
    public function getStorage($ds_name="default")
    {
        if ( ! $this->storages[$ds_name]) {
            $cache_config = app()->config("cache.connection.".$ds_name);
            $this->storages[$ds_name] = new CacheStorage($cache_config);
        }
        return $this->storages[$ds_name];
    }
}
