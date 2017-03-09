<?php
namespace R\Lib\Extention\SmartyPlugin;

/**
 * {{input_fieldset name="items" key="i"}}
 */
class SmartyBlockInputFieldset
{
    /**
     * @overload
     */
    public static function callback ($attrs, $content, $smarty, &$repeat)
    {
        // FormContainerによるタグ生成
        $form = $smarty->getCurrentForm();
        $fieldset_name = $attrs["name"];
        $key_assign = $attrs["key"];
        //$stack = $smarty->getBlockTagStack("fieldset.".$fieldset_name);
        if ($repeat===true) {
            $repeat = array(
                "keys" => array_keys((array)$form[$fieldset_name])
            );
        }
        // 開始タグの場合処理を行わない
        if ($repeat) {
            $smarty->assign($key_assign, $key);
        }
    }
}

class Fieldset
{
    private $values;
    private $key_stack;
    public function __construct ($values)
    {
        $this->values = $values;
        $this->key_stack = array_keys($values);
    }
    public function pop ()
    {
        $key = array_pop($this->key_stack);
        $this->values[$key];
    }
}