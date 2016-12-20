<?php
namespace R\Lib\Webapp;

/**
 * $_SESSIONへのアクセス
 */
class Session
{
    /**
     * 参照の起点
     */
    private $base_key;
    /**
     *
     */
    public function __construct ($key)
    {
        $this->base_key = $key;
    }
    /**
     * 下層の領域を取得
     */
    public function session ($key)
    {
        $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        return new Session($key);
    }
    /**
     * 値を登録する
     */
    public function set ($key, $value=null)
    {
        $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        array_set($_SESSION, $key, $value);
    }
    /**
     * 登録されている値を取得する
     */
    public function get ($key)
    {
        $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        return array_get($_SESSION, $key);
    }
    /**
     * 値を削除する
     */
    public function delete ($key=null)
    {
        $key = strlen($key) ? $this->base_key.".".$key : $this->base_key;
        array_unset($_SESSION, $key);
    }
}