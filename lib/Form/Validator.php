<?php
namespace R\Lib\Form;

class Validator
{
    protected $values;
    protected $errors;

    public function __construct ($rules, $values)
    {
        $this->values = $values;
        $this->errors = array();
        $this->applyRules($rules);
    }
    public function getErrors ()
    {
        return $this->errors;
    }
    public function getValue ($name)
    {
        return $this->values[$name];
    }
    private function applyRules ($rules)
    {
        foreach ($rules as $rule) {
            // requiredの省略記法の補完
            if (is_string($rule)) {
                $rule = array($rule, "required");
            }
            // fieldの補完
            if ($rule[0] && ! isset($rule["field"])) {
                $rule["field"] = $rule[0];
            }
            // typeの補完
            if ($rule[1] && ! isset($rule["type"])) {
                $rule["type"] = $rule[1];
            }
            $field_parts = explode('.',$field_name);
            // 対象が配列ではない
            if (count($field_parts)==1) {
                $value = $this->values[$field_parts[0]];
                $this->applyRule($value, $rule);
            // 対象が1次配列
            } elseif (count($field_parts)==2) {
                $value = $this->values[$field_parts[0]][$field_parts[1]];
                $rule["field_attr"] = $field_parts[0]."[".$field_parts[1]."]";
                $this->applyRule($value, $rule);
            // 対象が2次配列
            } elseif (count($field_parts)==3 && $field_parts[2]=="*") {
                $values = $this->values[$field_parts[0]];
                foreach ($values as $k => $v) {
                    $value = $v[$field_parts[2]];
                    $rule["field"] = $field_parts[0].".".$k.".".$field_parts[2];
                    $rule["field_attr"] = $field_parts[0]."[".$k."]"."[".$field_parts[2]."]";
                    $rule["field_set_index"] = $k;
                    $this->applyRule($value, $rule);
                }
            }
        }
    }
    private function applyRule ($value, $rule)
    {
        $error = call_user_func_array(plugin("ValidateRule",$rule["type"]), array($this, $value, $rule));
        // エラーがなければ終了
        if ( ! $error) {
            return;
        }
        // エラーの設定
        $error["name"] = $rule["name"];
        $error["name_attr"] = $rule["name_attr"];
        $error["type"] = $rule["type"];
        if (isset($rule["message"])) {
            $error["message"] = $rule["message"];
        }
        $this->errors[] =$error;
    }
}
