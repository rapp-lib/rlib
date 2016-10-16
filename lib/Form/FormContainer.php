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
        $this->def = $def;
    }

// -- Valuesの直接操作

    /**
     * @setter $this
     * 配列による値の設定
     */
    public function setValues ($values)
    {
        $this->clear();
        $this->array_payload = $values;
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

// -- save/recovery関連処理

    /**
     * 値の保存
     */
    private function save ()
    {
        $session = session(array(__CLASS__, "saved", $this->def["form_full_name"]));
        $session["values"] = $this->getValues();
    }

    /**
     * saveした値の復帰
     */
    private function recovery ()
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
            $form_name = $this->getFormName();
            $form_param_name = $this->getFormParamName();
            // form_param_nameに自分のform_nameが設定されていれば受け取り状態
            if ($form_name && $request[$form_param_name]==$form_name) {
                $values = (array)$request;
                unset($values[$form_param_name]);
                $this->setInputValues($values);
                $this->received = true;
            } else {
                $this->received = false;
            }
        }
        return $this->received;
    }

    /**
     * InputValuesからドメイン変換して値を設定
     */
    public function setInputValues ($input_values)
    {
        $this->setValues($input_values);
    }

    /**
     * @getter $def["form_name"]
     */
    public function getFormName ()
    {
        return $this->def["form_name"];
    }

    /**
     * @getter $def["form_param_name"]
     */
    public function getFormParamName ()
    {
        $form_param_name = $this->def["form_param_name"];
        if ( ! $form_param_name && $this->getFormName()) {
            $form_param_name = "_f";
        }
        return $form_param_name;
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
            $validator = new Validator((array)$this->def["rules"], (array)$this);
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
        return $this->isValid() ? array() : $this->errors;
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
                "form" => $this,
            ));
        }
        return table($this->def["table"]);
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

    /**
     * @override
     */
    public function __call ($method_name, $args)
    {
        report_error("メソッドがありません",array(
            "method_name" => $method_name,
            "form_def" => $this->def,
        ));
    }
}