<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;

/**
 * $_SESSIONへのアクセス
 */
class Session implements InvokableProvider
{
    /**
     * @override InvokableProvider
     */
    public function invoke ($key="")
    {
        return new self($key);
    }
    private $is_started = false;
    /**
     * 参照の起点
     */
    private $base_key;
    /**
     *
     */
    public function __construct ($key="")
    {
        if ( ! $this->is_started) {
            $this->start();
            $this->is_started = true;
        }
        $this->base_key = $key;
    }
    /**
     * 下層の領域を取得
     */
    public function session ($key)
    {
        if (strlen($this->base_key)) {
            $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        }
        return new Session($key);
    }
    /**
     * 値を追加する
     */
    public function add ($key, $value=null)
    {
        if (strlen($this->base_key)) {
            $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        }
        array_add($_SESSION, $key, $value);
    }
    /**
     * 値を登録する
     */
    public function set ($key, $value=null)
    {
        if (strlen($this->base_key)) {
            $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        }
        array_set($_SESSION, $key, $value);
    }
    /**
     * 登録されている値を取得する
     */
    public function get ($key)
    {
        if (strlen($this->base_key)) {
            $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        }
        return array_get($_SESSION, $key);
    }
    /**
     * 値を削除する
     */
    public function delete ($key=null)
    {
        if (strlen($this->base_key)) {
            $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        }
        array_unset($_SESSION, $key);
    }
    /**
     * session_start
     */
    public function start ()
    {
        // セッションの開始
        ini_set("session.cookie_httponly",true);
        ini_set("session.cookie_secure",$_SERVER['HTTPS']);
        session_cache_limiter('');
        header("Pragma: public");
        header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("P3P: CP='UNI CUR OUR'");
        session_start();
    }
}
