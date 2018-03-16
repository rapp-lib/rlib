<?php
namespace R\Lib\Form;

class Validator
{
    protected $values;
    protected $errors;
    /**
     * 現在applyRuleで評価中のrule
     */
    private $current_rule = null;

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
        $parts = explode('.',$name);
        if (count($parts)==1) return $this->values[$parts[0]];
        elseif (count($parts)==2) return $this->values[$parts[0]][$parts[1]];
        elseif (count($parts)==3) {
            if($parts=="*") {
                // Siblingの値を取得する
                return $this->values[$parts[0]][$this->current_rule["fieldset_index"]][$parts[1]];
            } else return $this->values[$parts[0]][$parts[1]][$parts[2]];
        }
    }
    public function getExternalFormValue ($form_name, $name)
    {
        $parts = explode(".", $form_name);
        $values = app()->form[$parts[0]][$parts[1]]->getSavedvalues();
        return array_get($values, $name);
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
        $this->current_rule = $rule;
        // 評価条件設定
        if (isset($rule["if"]) && ! $this->stConds($rule["if"])) return;
        // ValidateRuleExtentionを呼び出す
        $callback = ValidateRuleLoader::getCallback($rule["type"]);
        $error = call_user_func_array($callback, array($this, $value, $rule));
        // エラーがなければ終了
        if ( ! $error) return;
        // エラーの設定
        $error["field_name"] = $rule["field_name"];
        $error["name_attr"] = $rule["name_attr"];
        $error["type"] = $rule["type"];
        if (isset($rule["fieldset_index"])) $error["fieldset_index"] = $rule["fieldset_index"];
        if (isset($rule["message"])) $error["message"] = $rule["message"];
        $this->errors[] = $error;
    }

// -- ifの評価処理

    private function stConds($conds)
    {
        foreach ($conds as $key=>$cond) if ( ! $this->stCond($key,$cond)) return false;
        return true;
    }
    private function stCondsOr($conds)
    {
        foreach ($conds as $key=>$cond) if ($this->stCond($key,$cond)) return true;
        return false;
    }
    private function stCond($key, $cond)
    {
        if (is_numeric($key)) return $this->stConds($cond);
        if (preg_match('!^or$!i',$key)) return $this->stCondsOr($cond);
        if (preg_match('!^not$!i',$key)) return ! $this->stConds($cond);
        return $this->stCondEval($key, $cond);
    }
    private function stCondEval($key, $cond)
    {
        if ($cond === false) return $this->stEvalIsBlank($key);
        if ($cond === true) return ! $this->stEvalIsBlank($key);
        if (is_string($cond)) return $this->stEvalEq($key, $cond);
        if ($cond["is_blank"]) return $this->stEvalIsBlank($key);
        if ($cond["not_blank"]) return ! $this->stEvalIsBlank($key);
        if (isset($cond["eq"])) return $this->stEvalEq($key, $cond["eq"]);
        if (isset($cond["neq"])) return ! $this->stEvalEq($key, $cond["neq"]);
        if (isset($cond["contains"])) return ! $this->stEvalContains($key, $cond["contains"]);
        return false;
    }
    private function stEvalIsBlank($key)
    {
        $value = $this->getValue($key);
        if (is_array($value) && ! count($value)) return true;
        if (strlen($value) === 0) return true;
        return false;
    }
    private function stEvalEq($key, $eq_value)
    {
        $value = $this->getValue($key);
        return $value == $eq_value;
    }
    private function stEvalContains($key, $contains_value)
    {
        $value = $this->getValue($key);
        if ( ! is_array($value)) $value = array($value);
        if ( ! is_array($contains_value)) $contains_value = array($contains_value);
        foreach ($value as $v1) foreach ($contains_value as $v2) if ($v1 == $v2) return true;
        return false;
    }
}
