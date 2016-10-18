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
     * 一時保存領域
     */
    private $tmp_storage = null;

    /**
     * @override
     */
    public function __construct ($def=array())
    {
        $this->def = self::completeDef($def);
        // auto_restoreの指定があれば読み込み時に自動的に一時保存領域から値を復元する
        if ($this->def["auto_restore"]) {
            $this->restore();
        }
    }

// -- 必須構成情報の取得

    /**
     * @getter $def["form_name"]
     */
    public function getFormName ()
    {
        if ( ! isset($this->def["form_name"])) {
            report_error("Formの構成にform_nameがありません",array(
                "def" => $this->def,
            ));
        }
        return $this->def["form_name"];
    }

    /**
     * @getter $def["tmp_storage_name"]
     */
    public function getTmpStorageName ()
    {
        if ( ! isset($this->def["tmp_storage_name"])) {
            report_warning("Formの構成にtmp_storage_nameがありません",array(
                "def" => $this->def,
            ));
        }
        return $this->def["tmp_storage_name"];
    }

// -- Valuesの直接操作

    /**
     * 値を配列で直接設定
     */
    public function setValues ($values)
    {
        $this->clear();
        $this->array_payload = (array)$values;
    }

    /**
     * 値を配列として返す
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
     * 値の設定状態の消去
     * 一時保存領域が有効である場合、あわせて消去する
     */
    public function clear ()
    {
        $this->received = null;
        $this->is_valid = null;
        $this->errors = null;
        $this->array_payload = array();
        // 一時保存領域が有効である場合、消去する
        if (isset($this->tmp_storage)) {
            $this->getTmpStorage()->delete();
        }
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
        $values = $this->getValues();
        $this->getTmpStorage()->set("values", $values);
    }

    /**
     * saveした値の復帰
     */
    public function restore ()
    {
        $values = $this->getTmpStorage()->get("values");
        $this->setValues($values);
    }

    /**
     * 保存領域の確保
     */
    private function getTmpStorage ()
    {
        if ( ! isset($this->tmp_storage)) {
            $this->tmp_storage = request()->session(__CLASS__)
                ->getSubDomain("tmp_storage")
                ->getSubDomain($this->getTmpStorageName());
        }
        return $this->tmp_storage;
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
            $form_name = $this->getFormName();
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
        $form_name = $this->getFormName();
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
            $this->is_valid = count($this->errors) ? false : true;
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
     * 検索条件を指定したTableを取得
     * ※関係するdef : search_table
     */
    public function search ()
    {
        if ( ! $this->def["search_table"]) {
            report_error("Formにsearch_tableが関連づけられていません",array(
                "form_def" => $this->def,
            ));
        }
        return table($this->def["search_table"])->findBySearchFields($this, $this->def["fields"]);
    }

    /**
     * @deprecated
     * 旧仕様のlist_settingでfindBySearchFormを呼び出す
     * ※関係するdef : search_table
     */
    public function findBySearchForm ()
    {
        return table($this->def["search_table"])->findBySearchForm($this->def["list_setting"], $this->getValues());
    }

    /**
     * Formの値をもとに、関連づけられたTableのRecordインスタンスを作成
     */
    public function getRecord ()
    {
        $record = $this->getTable()->createRecord();
        $this->converRecord($record, false);
        return $record;
    }

    /**
     * Recordインスタンスの値からFormの値を設定する
     * ※関係するdef : fields.*.col
     */
    public function setRecord ($record)
    {
        $this->converRecord($record, true);
    }

    /**
     * Recordとフォームの値の相互変換
     * @param bool $is_record_to_values ? Recordから値を取り込む : Recordに値を登録する
     */
    private function converRecord ($record, $is_record_to_values)
    {
        foreach ($this->def["fields"] as $field_name => $field_def) {
            // colがfalseであれば削除
            if ($field_def["col"]===false) {
                continue;
            }
            // 下層の値は親で処理するのでスキップ
            if ($field_def["level"]==2 || $field_def["level"]==3) {
                continue;
            }
            $col_name = $field_def["col"];
            $table_name = $field_def["table"];
            //TODO: テーブル定義の確認
            // $col_def = table()->getDef($table_name,$col_name);
            // fields型の場合下層の要素を処理
            if ($field_def["type"]=="fields") {
                // 要素別の処理
                foreach ((array)$field_def["child_field_names"] as $child_field_name) {
                    $child_field_def = $this->def["fields"][$child_field_name];
                    // colがfalseであれば削除
                    if ($child_field_def["col"]===false) {
                        continue;
                    }
                    $item_name = $field_def["item_name"];
                    $child_col_name = $child_field_def["col"];
                    $child_table_name = $child_field_def["table"];
                    //TODO: テーブル定義の確認
                    // 値を登録
                    if ($is_record_to_values) {
                        $this[$field_name][$item_name] = $record[$col_name][$child_col_name];
                    } else {
                        $record[$col_name][$child_col_name] = $this[$field_name][$item_name];
                    }
                }
            // fieldset型の場合2階層下の要素を処理
            } elseif ($field_def["type"]=="fieldset") {
                // fieldsetの添え字を取得
                if ($is_record_to_values) {
                    $fieldset_indexes = array_keys((array)$record[$col_name]);
                } else {
                    $fieldset_indexes = array_keys((array)$this[$field_name]);
                }
                foreach ($fieldset_indexes as $fieldset_index) {
                    // 要素別の処理
                    foreach ((array)$field_def["child_field_names"] as $child_field_name) {
                        $child_field_def = $this->def["fields"][$child_field_name];
                        // colがfalseであれば削除
                        if ($child_field_def["col"]===false) {
                            continue;
                        }
                        $item_name = $field_def["item_name"];
                        $child_col_name = $child_field_def["col"];
                        $child_table_name = $child_field_def["table"];
                        //TODO: テーブル定義の確認
                        // 値を登録
                        if ($is_record_to_values) {
                            $this[$field_name][$fieldset_index][$item_name]
                                = $record[$col_name][$fieldset_index][$child_col_name];
                        } else {
                            $record[$col_name][$fieldset_index][$child_col_name]
                                = $this[$field_name][$fieldset_index][$item_name];
                        }
                    }
                }
            // 下層を処理しない型の処理
            } else {
                // 値を登録
                if ($is_record_to_values) {
                    $this[$field_name] = $record[$col_name];
                } else {
                    $record[$col_name] = $this[$field_name];
                }
            }
        }
        return $record;
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
                $field_def["item_name"] = $field_col_name;
                $field_def["parent_field_name"] = $field_name_parts[0];
            // 対象が2次配列
            } elseif (count($field_name_parts)==3) {
                $field_def["level"] = 3;
                $field_col_name = $field_name_parts[2];
                $field_def["item_name"] = $field_col_name;
                $field_def["parent_field_name"] = $field_name_parts[0];
            }
            // Level2,3のFieldであれば親Fieldの定義を取得/補完
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
            // tableに関連付いている場合のtable/colの補完
            if (isset($def["table"])) {
                // tableの補完
                if ( ! isset($field_def["table"])) {
                    $field_def["table"] = $def["table"];
                }
                // colの補完
                if ( ! isset($field_def["col"])) {
                    $field_def["col"] = $field_col_name;
                }
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

// -- magic

    /**
     * @deprecated
     * @override
     * reportの呼び出し時の処理
     */
    public function __report ()
    {
        return array(
            "form_name" => $this->def["form_name"],
            "tmp_storage_name" => $this->def["tmp_storage_name"],
            "values" => $this->array_payload,
        );
    }
}