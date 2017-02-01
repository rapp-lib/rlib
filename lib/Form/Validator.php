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
            $field_parts = explode('.',$rule["field_name"]);
            // 対象が配列ではない
            if (count($field_parts)==1) {
                $value = $this->values[$field_parts[0]];
                $rule["name_attr"] = $field_parts[0];
                $this->applyRule($value, $rule);
            // 対象が1次配列
            } elseif (count($field_parts)==2) {
                $value = $this->values[$field_parts[0]][$field_parts[1]];
                $rule["name_attr"] = $field_parts[0]."[".$field_parts[1]."]";
                $this->applyRule($value, $rule);
            // 対象が2次配列
            } elseif (count($field_parts)==3) {
                $values = $this->values[$field_parts[0]];
                foreach ((array)$values as $k => $v) {
                    $value = $v[$field_parts[2]];
                    $rule["name_attr"] = $field_parts[0]."[".$k."]"."[".$field_parts[2]."]";
                    $rule["fieldset_index"] = $k;
                    $this->applyRule($value, $rule);
                }
            }
        }
    }
    /**
     * Rule適用
     */
    private function applyRule ($value, $rule)
    {
        // 条件付きRule
        if (isset($rule["if_target"])) {
            if (is_string($rule["if_target"])) {
                $if_value = $this->getValue($rule["if_target"]);
                if (isset($rule["if_value"])) {
                    if ( ! is_array($rule["if_value"])) {
                        if ($if_value != $rule["if_value"]) {
                            return;
                        }
                    }
                } elseif (isset($rule["if_value_is"])) {
                    if ($rule["if_value_is"]=="blank" && strlen($if_value)) {
                        return;
                    }
                } else {
                    if ( ! strlen($if_value)) {
                        return;
                    }
                }
            }
        }
        // ValidateRuleExtentionを呼び出す
        $error = call_user_func_array(extention("ValidateRule",$rule["type"]), array($this, $value, $rule));
        // エラーがなければ終了
        if ( ! $error) {
            return;
        }
        // エラーの設定
        $error["field_name"] = $rule["field_name"];
        $error["name_attr"] = $rule["name_attr"];
        $error["type"] = $rule["type"];
        if (isset($rule["fieldset_index"])) {
            $error["fieldset_index"] = $rule["fieldset_index"];
        }
        if (isset($rule["message"])) {
            $error["message"] = $rule["message"];
        }
        $this->errors[] =$error;
    }
}
