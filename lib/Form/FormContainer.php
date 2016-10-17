<?php
namespace R\Lib\Form;

use R\Lib\Core\ArrayObject;

class FormContainer extends ArrayObject
{
    /**
     * Formの構成情報
     * 構成後に変更/初期化されることはない
     */
    private $def = array();

    /**
     * $def["fields"]に対応するInputFieldインスタンス
     */
    private $input_fields = null;

    /**
     * Request受付状態
     */
    private $received = null;

    /**
     * Validate結果
     */
    private $is_valid = null;
    private $errors = null;

    /**
     * @override
     */
    public function __construct ($def=array())
    {
        $this->def = self::completeDef($def);
        // auto_restoreの指定があれば読み込み時に自動的に値を復元する
        if ($this->def["auto_restore"]) {
            $this->restore();
        }

    }

// -- Valuesの直接操作

    /**
     * @setter $this
     * 配列による値の設定
     */
    public function setValues ($values)
    {
        $this->clear();
        $this->array_payload = (array)$values;
    }

    /**
     * @getter $this
     */
    public function getValues ()
    {
        return $this->array_payload;
    }

    /**
     * 値が空かどうかを返す
     */
    public function isEmpty ()
    {
        return count($this->array_payload)==0;
    }

// -- 初期化関連処理

    /**
     * 値と値に関係する状態のリセット
     */
    public function clear ()
    {
        $this->received = null;
        $this->is_valid = null;
        $this->errors = null;
        $this->array_payload = array();
    }

    /**
     * 初期化処理
     * IDを指定して、tableが関係している場合検索してDBから値を設定する
     * ※関係するdef: table
     */
    public function init ($id=null)
    {
        $this->clear();
        // IDで検索して値を初期値として設定
        if (isset($id) && $this->def["table"]) {
            $record = table($this->def["table"])->selectById($id);
            if ($record) {
                $this->setRecord($record);
            }
        }
    }

// -- save/restore関連処理

    /**
     * 値の保存
     */
    public function save ()
    {
        $session = session(array(__CLASS__, "saved", $this->def["form_full_name"]));
        $session["values"] = $this->getValues();
    }

    /**
     * saveした値の復帰
     */
    public function restore ()
    {
        $session = session(array(__CLASS__, "saved", $this->def["form_full_name"]));
        $this->setValues($session["values"]);
    }

// -- Request関連処理

    /**
     * Requestを確認して値の受け取り状態を確認する
     * 受け取り状態であればRequestから値を取り込む
     */
    public function receive ()
    {
        if ( ! isset($this->received)) {
            $request = request();
            $form_param_name = "_f";
            $form_name = $this->def["form_name"];
            // form_param_nameに自分のform_nameが設定されていれば受け取り状態
            if ($form_name && $request[$form_param_name]==$form_name) {
                foreach ($request as $k => $v) {
                    if ($k==$form_param_name) {
                        continue;
                    }
                    $values[$k] = $v;
                }
                $this->setInputValues($values);
                $this->received = true;
            } else {
                $this->received = false;
            }
        }
        return $this->received;
    }

    /**
     * receiveで受付確認ができるHiddenタグ
     */
    public function getReceiveParamHidden ()
    {
        $form_param_name = "_f";
        $form_name = $this->def["form_name"];
        return tag("input",array(
            "type" => "hidden",
            "name" => $form_param_name,
            "value" => $form_name,
        ));
    }

    /**
     * InputValuesからドメイン変換して値を設定
     */
    public function setInputValues ($input_values)
    {
        $this->setValues($input_values);
    }

// -- Validate/ValidValues関連

    /**
     * 正常値が設定されているかどうか判断する
     * Rulesに従って値の検証を行う
     */
    public function isValid ()
    {
        // rulesから入力チェックを行う
        if ( ! isset($this->is_valid)) {
            $validator = new Validator($this->def["rules"], $this);
            $this->errors = $validator->getErrors();
            $this->is_valid = count($this->errors) ? true : false;
        }
        return $this->is_valid;
    }

    /**
     * 入力エラーを取得
     */
    public function getErrors ()
    {
        return $this->errors;
    }

    /**
     * @getter $def["rules"]
     */
    public function getRules ()
    {
        return $this->def["rules"];
    }

// -- Table/Record関連

    /**
     * 関連づけられたTableインスタンスを作成
     * ※関係するdef : table
     */
    public function getTable ()
    {
        if ( ! $this->def["table"]) {
            report_error("Formにtableが関連づけられていません",array(
                "form_def" => $this->def,
            ));
        }
        return table($this->def["table"]);
    }

    /**
     * findBySearchForm
     * ※関係するdef : table
     */
    public function findBySearchForm ()
    {
        return $this->getTable()->findBySearchForm($this->def["list_setting"], $this->getValues());
    }

    /**
     * Formの値をもとに、関連づけられたTableのRecordインスタンスを作成
     * ※関係するdef : table,fields.*.col
     */
    public function getRecord ()
    {
        $record_values = array();
        foreach ($this as $k => $v) {
            // Fieldsに含まれない値は削除
            if ( ! isset($this->def["fields"][$k])) {
                continue;
            }
            $field_def = $this->def["fields"][$k];
            // colがfalseのFieldは削除
            if ($field_def["col"]===false) {
                continue;
            }
            // colが指定されている場合は優先
            $col_name = isset($field_def["col"]) ? $field_def["col"] : $k;
            $record_values[$col_name] = $v;
        }
        return $this->getTable()->createRecord($record_values);
    }

    /**
     * Recordインスタンスの値からFormの値を設定する
     * ※関係するdef : fields.*.col
     */
    public function setRecord ($record)
    {
        // fieldsに指定された値のみを対象とする
        foreach ($this->def["fields"] as $field_name => $field_def) {
            // colがfalseのFieldは無視
            if ($field_def["col"]===false) {
                continue;
            }
            // colが指定されている場合は優先
            $col_name = isset($field_def["col"]) ? $field_def["col"] : $field_name;
            if (isset($record[$col_name])) {
                $this[$field_name] = $record[$col_name];
            }
        }
    }

// -- InputField関連

    /**
     * InputFieldを取得
     */
    public function getInputField ($field_name)
    {
        if ( ! isset($this->def["fields"][$field_name])) {
            report_error("指定されたFieldが定義されていません",array(
                "field_name" => $field_name,
                "def" => $this->def,
            ));
        }
        // InputFieldを生成する
        if ( ! $this->input_fields[$field_name]) {
            $this->input_fields[$field_name] = new InputField($this, $this->def["fields"][$field_name]);
        }
        return $this->input_fields[$field_name];
    }

    /**
     * HTML上のName属性からInputFieldを取得
     */
    public function getInputFieldByNameAttr ($name_attr)
    {
        $field_name = str_replace(array("[","]"),array(".",""),$name_attr);
        $field_name_parts = explode('.',$field_name);
        // 対象が配列ではない
        if (count($field_name_parts)==1) {
        // 対象が1次配列
        } elseif (count($field_name_parts)==2) {
        // 対象が2次配列
        } elseif (count($field_name_parts)==3) {
            $field_name = $field_name_parts[0].".*.".$field_name_parts[2];
        }
        return $this->getInputField($field_name);
    }

    /**
     * HTML上のName属性から値を取得
     */
    public function getValueByNameAttr ($name_attr)
    {
        $field_name = str_replace(array("[","]"),array(".",""),$name_attr);
        $field_name_parts = explode('.',$field_name);
        // 対象が配列ではない
        if (count($field_name_parts)==1) {
            return $this->array_payload[$field_name_parts[0]];
        // 対象が1次配列
        } elseif (count($field_parts)==2) {
            return $this->array_payload[$field_name_parts[0]][$field_name_parts[1]];
        // 対象が2次配列
        } elseif (count($field_name_parts)==3) {
            return $this->array_payload[$field_name_parts[0]][$field_name_parts[1]][$field_name_parts[2]];
        }
        return null;
    }

    /**
     * 構成を補完
     */
    private static function completeDef ($def)
    {
        // fieldをfield_name=>field_def形式に補完
        $fields = array();
        foreach ((array)$def["fields"] as $k => $v) {
            if (is_numeric($k) && is_string($v)) {
                $fields[$v] = array();
            } else {
                $fields[$k] = $v;
            }
        }
        $def["fields"] = $fields;
        // fieldの補完処理
        foreach ($def["fields"] as $field_name => & $field_def) {
            $field_name_parts = explode('.',$field_name);
            $field_col_name = null;
            // 対象が配列ではない
            if (count($field_name_parts)==1) {
                $field_def["level"] = 1;
                $field_col_name = $field_name_parts[0];
            // 対象が1次配列
            } elseif (count($field_name_parts)==2) {
                $field_def["level"] = 2;
                $field_col_name = $field_name_parts[1];
                $field_def["parent_field_name"] = $field_name_parts[0];
            // 対象が2次配列
            } elseif (count($field_name_parts)==3) {
                $field_def["level"] = 3;
                $field_col_name = $field_name_parts[2];
                $field_def["parent_field_name"] = $field_name_parts[0];
            }
            // Level2,3のFieldであれば親Fieldの定義を取得/補完
            $parent_field_def = null;
            if ($field_def["parent_field_name"]) {
                $parent_field_def = & $def["fields"][$field_def["parent_field_name"]];
                // 親Fieldの補完
                if ( ! $parent_field_def) {
                    $parent_field_def = array("col"=>$field_def["parent_field_name"]);
                }
                // 親Fieldのtypeを補完
                if ($field_def["level"]==2) {
                    $parent_field_def["type"] = "fields";
                } elseif ($field_def["level"]==3) {
                    $parent_field_def["type"] = "fieldset";
                }
                // 親Fieldのchild_field_namesを補完
                $parent_field_def["child_field_names"][] = $field_name;
            }
            // tableの補完
            if ( ! $field_def["table"]) {
                if ($parent_field_def && $parent_field_def["table"]) {
                    $field_def["table"] = $parent_field_def["table"];
                } else {
                    $field_def["table"] = $def["table"];
                }
            }
            // colの補完
            if ( ! $field_def["col"]) {
                $field_def["col"] = $field_col_name;
            }
            $field_def["field_name"] = $field_name;
        }
        // rulesを補完してfieldsに統合
        $def["rules"] = (array)$def["rules"];
        foreach ($def["rules"] as $i => & $rule) {
            // requiredの省略記法の補完
            if (is_string($rule)) {
                $rule = array("field_name"=>$rule, "type"=>"required");
            }
            // field_nameの補完
            if ($rule[0] && ! isset($rule["field_name"])) {
                $rule["field_name"] = $rule[0];
                unset($rule[0]);
            }
            // typeの補完
            if ($rule[1] && ! isset($rule["type"])) {
                $rule["type"] = $rule[1];
                unset($rule[1]);
            }
        }
        // 補完済みのdefを返す
        return $def;
    }
}