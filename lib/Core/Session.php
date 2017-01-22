<?php
namespace R\Lib\Core;

/**
 * $_SESSIONへのアクセス
 */
class Session
{
    /**
     * インスタンスを取得
     */
    public static function getInstance ($key="")
    {
        return new self($key);
    }
    /**
     * 参照の起点
     */
    private $base_key;
    /**
     *
     */
    public function __construct ($key="")
    {
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
}
