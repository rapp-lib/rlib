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

// -- ObjectStorage機能

    /**
     * Credentialの発行
     */
    public function createCred ($cred_data, $options=array())
    {
        $data = array("cred_data" => $cred_data, "options" => $options);
        $cred = app()->security->hash(serialize($data));
        $this->setItem($cred, $data);
        report_info("[CacheStorage] Credentialを作成しました", array(
            "cred" => $cred,
            "data" => $data,
            "cache_storage" => $this,
        ));
        return $cred;
    }
    /**
     * Credentialの解決
     */
    public function resolveCred ($cred)
    {
        $data = $cred ? $this->getItem($cred) : null;
        if ( ! $data) {
            report_warning("[CacheStorage] Credentialの登録がありません", array(
                "cred" => $cred,
                "cache_storage" => $this,
            ));
            return null;
        }
        if ($data["options"]["expire"] && $data["options"]["expire"] < time()) {
            report_warning("[CacheStorage] Credentialの有効期限切れが切れています", array(
                "cred" => $cred,
                "expire" => date("Y/m/d/ H:i", $data["options"]["expire"]),
                "data" => $data,
                "cache_storage" => $this,
            ));
            return null;
        }
        report_info("[CacheStorage] Credentialを解決しました", array(
            "cred" => $cred,
            "cred_data" => $cred_data,
            "cache_storage" => $this,
        ));
        return $data["cred_data"];
    }
    /**
     * Credentialの削除
     */
    public function dropCred ($cred)
    {
        $this->removeItem($cred);
    }

    public function __report()
    {
        return array("config"=>$this->cache_config);
    }
}
