<?php
namespace R\Lib\Cache;
use Zend\Cache\StorageFactory;

class CacheStorage
{
    private $ds;
    private $cache_config;
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
    /**
     * 有効期限の取得
     */
    public function getTTL ()
    {
        return $this->cache_config["adapter"]["options"]["ttl"];
    }

// -- CredStorage

    /**
     * Credの発行
     */
    public function createCred ($data)
    {
        $cred = app()->security->getRandHash();
        $this->setItem($cred, $data);
        report_info("Cred Created", array(
            "cred" => $cred,
            "data" => $data,
            "cache_storage" => $this,
        ));
        return $cred;
    }
    /**
     * Credentialの読み込み
     */
    public function readCred ($cred)
    {
        $data = $cred ? $this->getItem($cred) : null;
        if ( ! $data) {
            report_warning("Cred Read Failur, NotFound", array(
                "cred" => $cred,
                "cache_storage" => $this,
            ));
            return null;
        }
        report_info("Cred Read", array(
            "cred" => $cred,
            "data" => $data,
            "cache_storage" => $this,
        ));
        return $data;
    }
    /**
     * Credentialの削除
     */
    public function dropCred ($cred)
    {
        report_info("Cred Dropped", array(
            "cred" => $cred,
            "cache_storage" => $this,
        ));
        $this->removeItem($cred);
    }

    public function __report ()
    {
        return array("ds_name"=>$this->cache_config["ds_name"]);
    }
}
