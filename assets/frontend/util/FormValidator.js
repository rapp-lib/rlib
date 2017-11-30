window.FormValidator = function (fo) {
    var self = this;
    self.fo = fo;
    /**
     * 現在評価中のinput
     * Rule.field_nameは*での参照が含まれるのでSibling解決に必要
     */
    self.$current_input = null;

// --

    /**
     * Ruleの適用条件チェック
     */
    self.checkRuleIf = function ($rule, $input)
    {
        self.$current_input = $input;
        // 評価条件設定
        return ! (self.isset($rule["if"]) && ! self.stConds($rule["if"]));
    };
    /**
     * Rule適用
     */
    self.applyRule = function ($rule, $input)
    {
        self.$current_input = $input;
        var field_name = self.fo.getFieldNameByElement($input);
        // 評価条件設定
        if ( ! self.checkRuleIf($rule)) return;
        // ValidateRuleExtentionを呼び出す
        var $callback = FormValidateRuleLoader.getCallback($rule["type"]);
        var $value = self.fo.getInputValue(field_name);
        var $error = $callback(self, $value, $rule);
        // エラーがなければ終了
        if ( ! $error) return;
        // エラーの設定
        $error["field_name"] = field_name;
        $error["type"] = $rule["type"];
        if (self.isset($rule["message"])) $error["message"] = $rule["message"];
        return $error;
    };

// --

    self.getErrors = function ($name)
    {
        return self.errors;
    };
    self.getValue = function ($name)
    {
        // Siblingの値を取得する
        if ($name.match(/^([^\.]+).\*\.([^\.]+)$/)) {
            var parts = $name.split(".");
            var parts_current = self.fo.getFieldNameByElement(self.$current_input).split(".");
            parts[2] = parts_current[2];
            $name = parts.join(".");
        }
        return self.fo.getInputValue($name);
    };

// -- ifの評価処理

    self.stConds = function ($conds)
    {
        for ($key in $conds) if ( ! self.stCond($key,$conds[$key])) return false;
        return true;
    };
    self.stCondsOr = function ($conds)
    {
        for ($key in $conds) if (self.stCond($key,$conds[$key])) return true;
        return false;
    }
    self.stCond = function ($key, $cond)
    {
        if (self.is_numeric($key)) return self.stConds($cond);
        if (self.preg_match(/^or$/i, $key)) return self.stCondsOr($cond);
        if (self.preg_match(/^not$/i, $key)) return ! self.stConds($cond);
        return self.stCondEval($key, $cond);
    }
    self.stCondEval = function ($key, $cond)
    {
        if ($cond === false) return self.stEvalIsBlank($key);
        if ($cond === true) return ! self.stEvalIsBlank($key);
        if (self.is_string($cond)) return self.stEvalEq($key, $cond);
        if ($cond["is_blank"]) return self.stEvalIsBlank($key);
        if ($cond["not_blank"]) return ! self.stEvalIsBlank($key);
        if (self.isset($cond["eq"])) return self.stEvalEq($key, $cond["eq"]);
        if (self.isset($cond["neq"])) return ! self.stEvalEq($key, $cond["neq"]);
        if (self.isset($cond["contains"])) return ! self.stEvalContains($key, $cond["contains"]);
        return false;
    }
    self.stEvalIsBlank = function ($key)
    {
        $value = self.getValue($key);
        if (self.is_array($value) && ! self.count($value)) return true;
        if (self.strlen($value) === 0) return true;
        return false;
    }
    self.stEvalEq = function ($key, $eq_value)
    {
        $value = self.getValue($key);
        return $value == $eq_value;
    }
    self.stEvalContains = function ($key, $contains_value)
    {
        $value = self.getValue($key);
        if ( ! self.is_array($value)) $value = [$value];
        if ( ! self.is_array($contains_value)) $contains_value = [$contains_value];
        for ($k1 in $value) for($k2 in $contains_value) if ($value[$k1] == $contains_value[$k2]) return true;
        return false;
    };

// --

    self.is_string = function (value) {
        return (typeof (value) === "string" || value instanceof String);
    };
    self.isset = function (value) {
        return (typeof (value) !== "undefined");
    };
    self.preg_match = function (pattern, value) {
        return (""+value).match(pattern);
    };
    self.is_numeric = function (value) {
        return self.preg_match(/^\d+$/, value);
    };
    self.is_array = function (value) {
        return (typeof (value) === "object");
    };
    self.count = function (value) {
        return self.is_array(value) ? value.length : 0;
    };
    self.strlen = function (value) {
        return self.isset(value) ? (""+value).length : 0;
    };
};
window.FormValidateRuleLoader = {
    getCallback:function($type)
    {
        return function ($validator, $value, $rule) { return false; };
    }
};
