<?php
namespace R\Lib\Core;

/**
 * 配列へのドット記法によるアクセス
 */
class ArrayAccessObject
{
    /**
     * 参照の起点
     */
    protected $array_payload;
    protected $array_payload_base_key;

    /**
     *
     */
    public function __construct ( & $ref, $key)
    {
        $key_parts = explode(".",$key);
        // 指定した階層で配列から参照を取得する
        foreach ($key_parts as $key_part) {
            if ( ! is_array($ref)) {
                $ref = array();
            }
            $ref = & $ref[$key_part];
        }
        $this->array_payload = & $ref;
        $this->array_payload_base_key = $key;
    }

    /**
     * 下層の領域を取得
     */
    public function getSubDomain ($key)
    {
        return new self($this->array_payload, $key);
    }

    /**
     * 値を登録する
     */
    public function set ($key, $value=null)
    {
        array_set($this->array_payload, $key, $value);
    }

    /**
     * 登録されている値を取得する
     */
    public function get ($key)
    {
        return array_get($this->array_payload, $key);
    }

    /**
     * 値を削除する
     */
    public function delete ($key=null)
    {
        array_unset($this->array_payload, $key);
    }
}