<?php
namespace R\Lib\Form;

use ArrayObject;

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

    public function __construct ($def=array())
    {
        $this->def = self::completeDef($def);
        // auto_restoreの指定があれば読み込み時に自動的に一時保存領域から値を復元する
        if ($this->def["auto_restore"]) {
            $this->restore();
        }
    }

    /**
     * フロント利用向けにFormの状態情報を取得
     */
    public function exportState ()
    {
        return array(
            "field_names" => array_keys($this->def["fields"]),
            "errors" => $this->errors,
            "rules" => $this->def["rules"],
        );
    }

// -- 多階層構造

    /**
     * 多階層構造用のFormCollection
     */
    private $sub_forms = null;
    /**
     * 多階層Formの取得
     */
    public function __get ($key)
    {
        if ($key=="forms") {
            if ( ! $this->sub_forms) $this->sub_forms = new FormCollection($this->def);
            return $this->sub_forms;
        }
        return null;
    }
    /**
     * 多階層Formへの値の保存
     */
    public function saveTo ($key)
    {
        $values = $this->getValues();
        $this->forms[$key]->setValues($values);
        $this->forms[$key]->save();
    }
    /**
     * 多階層Formにsaveした値の復帰
     */
    public function restoreFrom ($key)
    {
        $this->forms[$key]->restore();
        $values = $this->forms[$key]->getValues();
        $this->setValues($values);
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
            report_warning("Formの構成にtmp_storage_nameがありません",array("def"=>$this->def));
        }
        return $this->def["tmp_storage_name"];
    }

// -- Valuesの直接操作

    /**
     * 値を配列で直接設定
     */
    public function setValues ($values)
    {
        $this->clearValues();
        foreach ((array)$values as $k=>$v) {
            $this[$k] = $v;
        }
    }

    /**
     * 値を配列として返す
     */
    public function getValues ()
    {
        return $this->getArrayCopy();
    }

    /**
     * 値の設定状態の消去
     */
    public function clearValues ()
    {
        $this->received = null;
        $this->is_valid = null;
        $this->errors = null;
        foreach ($this as $k=>$v) {
            unset($this[$k]);
        }
    }

    /**
     * 値が空かどうかを返す
     */
    public function isEmpty ()
    {
        return count($this)==0;
    }

// -- 初期化関連処理

    /**
     * 値の設定状態の消去
     * 一時保存領域が有効である場合、あわせて消去する
     */
    public function clear ()
    {
        $this->clearValues();
        // 一時保存領域が有効である場合、消去する
        if (isset($this->tmp_storage)) {
            $this->getTmpStorage()->delete("values");
        }
    }

    /**
     * @deprecated
     *
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
        $this->getTmpStorage()->set("values" ,$values);
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
            $container_name = "Form_FormContainer_".$this->getTmpStorageName();
            $this->tmp_storage = app()->session($container_name);
        }
        return $this->tmp_storage;
    }

    /**
     * 保存領域の値の取得
     */
    public function getSavedValues ()
    {
        return (array)$this->getTmpStorage()->get("values");
    }

// -- Request/HTML関連処理

    /**
     * 入力値配列を確認して値の受け取り状態を確認する
     * 受け取り状態であれば値を取り込んでtrueを返す
     */
    public function receive ($input)
    {
        if ( ! isset($this->received)) {
            // csrf_checkの指定があればCSRF対策キーを確認する
            if ($this->def["csrf_check"]) {
                if ($input[app()->security->getCsrfTokenName()] != app()->security->getCsrfToken()) {
                    return $this->received = false;
                }
            }
            $form_param_name = "_f";
            $form_name = $this->getFormName();
            // form_param_nameに自分のform_nameが設定されていれば受け取り状態
            if ($this->def["receive_all"] || ($form_name && $input[$form_param_name]==$form_name)) {
                foreach ($input as $k => $v) {
                    if ($k==$form_param_name || $k=="__token") continue;
                    $values[$k] = $v;
                }
                $this->setInputValues($values);
                return $this->received = true;
            }
            return $this->received = false;
        }
        return $this->received;
    }

    /**
     * InputValuesから変換処理を行って値を設定
     * 値が空の要素は削除して、field.input_convertの変換処理を逐次適用する
     */
    public function setInputValues ($input_values)
    {
        // formタグの仕様により混入する非正規な空データを削除してからデータを登録
        \R\Lib\Util\Arr::array_clean($input_values);
        $this->setValues($input_values);
        // 入力変換処理
        $this->mapFields(function($value, $field_name, $field_def){
            // ファイルアップロード
            if ($value instanceof \Psr\Http\Message\UploadedFileInterface) {
                $storage_name = $field_def["storage"];
                if ($value->getError() !== UPLOAD_ERR_OK) {
                    // ファイルをアップロードしていない、またはエラー
                    $value = new UploadedFile(null, $value);
                } elseif ( ! $storage_name) {
                    $value = new UploadedFile(null, $value);
                    report_warning("File Upload Failure", array(
                        "field_name" => $field_name,
                        "field_def" => $field_def,
                    ), "FileUpload");
                } elseif ($file = app()->file->getStorage($storage_name)->upload($value)) {
                    $value = new UploadedFile($file->getUri(), $value);
                    report_info("File Uploaded",array(
                        "field_name" => $field_name,
                        "uri" => $value,
                        "field_def" => $field_def,
                    ), "FileUpload");
                } else {
                    $value = new UploadedFile(null, $value);
                    report_warning("File Upload Failure",array(
                        "field_name" => $field_name,
                        "uploaded_file" => $value,
                        "field_def" => $field_def,
                    ), "FileUpload");
                }
            }
            return $value;
        });
    }

    /**
     * Formタグを作成
     *      def.form_pageでactionのURLを補完
     *      def.csrf_checkの指定があればCSRF対策キーを埋め込む
     */
    public function getFormHtml ($attrs, $content)
    {
        // receiveで受付確認ができるHiddenタグを追加
        if ( ! $this->def["receive_all"]) {
            $content .= tag("input",array(
                "type" => "hidden",
                "name" => "_f",
                "value" => $this->getFormName(),
            ));
        }
        // csrf_checkの指定があればCSRF対策キーを埋め込む
        if ($this->def["csrf_check"]) {
            $content .= tag("input",array(
                "type" => "hidden",
                "name" => app()->security->getCsrfTokenName(),
                "value" => app()->security->getCsrfToken(),
            ));
        }
        // form_page/search_pageでactionのURLを補完
        if ( ! isset($attrs["action"])) {
            if (isset($this->def["form_page"])) {
                $attrs["action"] = "id://".$this->def["form_page"];
            } elseif (isset($this->def["search_page"])) {
                $attrs["action"] = "id://".$this->def["search_page"];
            }
            $attrs["action"] = "".app()->http->getServedRequest()->getUri()
                ->getRelativeUri($attrs["action"])
                ->withoutAuthorityInWebroot();
        }
        return tag("form",$attrs,$content);
    }

    /**
     * InputFieldを取得
     */
    public function getInputField ($attrs)
    {
        $field_value = null;
        $name_attr = $attrs["name"];
        $field_name = str_replace(array("[","]"),array(".",""),$name_attr);
        $field_name_parts = explode('.',$field_name);
        // 対象が配列ではない
        if (count($field_name_parts)==1) {
            $field_value = $this[$field_name_parts[0]];
        // 対象が1次配列
        } elseif (count($field_name_parts)==2) {
            $field_value = $this[$field_name_parts[0]][$field_name_parts[1]];
        // 対象が2次配列
        } elseif (count($field_name_parts)==3) {
            $field_value = $this[$field_name_parts[0]][$field_name_parts[1]][$field_name_parts[2]];
            $field_name = $field_name_parts[0].".*.".$field_name_parts[2];
        }
        if ( ! isset($this->def["fields"][$field_name])) {
            report_error("指定されたFieldが定義されていません",array(
                "name_attr" => $name_attr,
                "field_name" => $field_name,
                "def" => $this->def,
            ));
        }
        // InputFieldを生成する
        return new InputField($this, $this->def["fields"][$field_name], $field_value, $attrs);
    }

    /**
     * Fieldの論理名を取得
     */
    public function getFieldLabel ($field_name)
    {
        return (string)$this->def["fields"][$field_name]["label"];
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
     * @setter
     */
    public function setIsValid ($is_valid)
    {
        $this->is_valid = (bool)$is_valid;
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
     * ※関係するdef : search_table, fields.search
     */
    public function search ()
    {
        $search_fields = array();
        foreach ((array)$this->def["fields"] as $field_name => $field_def) {
            $search_type = $field_def["search"];
            $search_yield = $field_def["search_yield"];
            if ( ! isset($search_type)) {
                continue;
            }
            // 2階層の親要素
            if ($field_def["type"]=="fields") {
                $child_search_fields = array();
                // 関係する別のTableに対して下層の検索条件を付与
                foreach ((array)$field_def["child_field_names"] as $child_field_name) {
                    $child_field_def = $this->def["fields"][$child_field_name];
                    $child_search_type = $child_field_def["search"];
                    if ( ! isset($child_search_type)) {
                        continue;
                    }
                    $child_search_fields[] = array(
                        "type" => $child_search_type,
                        "field_def" => $child_field_def,
                        "value" => $this[$field_name][$child_field_def["item_name"]],
                    );
                }
                $field_def["search_fields"] = $child_search_fields;
                $search_fields[] = array(
                    "type" => $search_type,
                    "yield" => $search_yield,
                    "field_def" => $field_def,
                    "value" => $this[$field_name],
                );
            // 1階層の値
            } elseif ($field_def["level"]==1) {
                $search_fields[] = array(
                    "type" => $search_type,
                    "yield" => $search_yield,
                    "field_def" => $field_def,
                    "value" => $this[$field_name],
                );
            }
        }
        if ( ! $this->def["search_table"]) {
            report_error("Formにsearch_tableが関連づけられていません",array(
                "form_def" => $this->def,
            ));
        }
        // 関係するTableに対してJoinと検索条件を付与
        $q = table($this->def["search_table"]);
        foreach ((array)$this->def["search_joins"] as $join) {
            if (is_array($join)) $q->join($join[0], $join[1] ?: array(), $join[2] ?: "LEFT");
            elseif (is_string($join)) $q->joinBelongsTo($join);
        }
        $q->findBySearchFields($this, $search_fields);
        return $q;
    }

    /**
     * 検索ページのURLを取得する
     * ※関係するdef : search_page, fields.search
     */
    public function getSearchPageUrl ($add_params=false)
    {
        if ( ! isset($this->def["search_page"])) {
            report_error("検索ページのURLを取得するにはsearch_pageの指定が必須です",array(
                "form_def" => $this->def,
            ));
        }
        $params = array();
        if ( ! $this->def["receive_all"]) {
            $params["_f"] = $this->getFormName();
        }
        $values = $this->getValues();
        foreach ($this->def["fields"] as $field_name => $field_def) {
            if ($add_params!==false && isset($field_def["search"])) {
                $value = $values[$field_name];
                if (isset($add_params[$field_name])) {
                    $value = $add_params[$field_name];
                }
                // 1ページ目はページ番号の指定は不要
                if ($field_def["search"]=="page" && $value==1) {
                    continue;
                // デフォルト設定通りであれば不要
                } elseif (isset($field_def["default"]) && $field_def["default"]==$value) {
                    continue;
                } elseif ($value) {
                    $params[$field_name] = $value;
                }
            }
        }
        $uri = app()->http->getServedRequest()->getUri()
            ->getRelativeUri("id://".$this->def["search_page"], $params)
            ->withoutAuthority();
        return $uri;
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
     * ※関係するdef : fields.*.read_col(or col)
     */
    public function getRecord ()
    {
        $record = $this->getTable()->createRecord();
        $this->convertRecord($record, "record_values");
        return $record;
    }
    /**
     * Formの値をもとに、Values句を持ったTableインスタンスを作成
     * ※関係するdef : fields.*.read_col(or col)
     */
    public function getTableWithValues ()
    {
        $record = $this->getTable()->createRecord();
        $this->convertRecord($record, "values_clause");
        return $this->getTable()->values((array)$record);
    }
    /**
     * Recordインスタンスの値からFormの値を設定する
     * ※関係するdef : fields.*.write_col(or col)
     */
    public function setRecord ($record)
    {
        if ( ! $record) {
            report_error("setRecordのパラメータがRecordオブジェクトではない");
        }
        $this->convertRecord($record, "form_values");
    }
    /**
     * convertRecord内でfield_def.colをtypeで読み替える為の処理
     * record_values: Form→Recordに変換する際はcol_record_valuesを優先して参照する
     * form_values: Record→Formに変換する際はcol_form_valuesを優先して参照する
     * values_clause: getTableWithValues時のValues句への対応づけ、col_value_clauseを優先
     */
    private static function getDefColName ($field_def, $type)
    {
        $key = "col";
        if ($type=="record_values" || $type===false) {
            if (isset($field_def["col_record_values"])) $key = "col_record_values";
        } elseif ($type=="form_values" || $type===false) {
            if (isset($field_def["col_form_values"])) $key = "col_form_values";
        } elseif ($type=="values_clause") {
            if (isset($field_def["col_record_values"])) $key = "col_record_values";
            if (isset($field_def["col_values_clause"])) $key = "col_values_clause";
        }
        return $field_def[$key];
    }
    private static function isRecordToValues ($type)
    {
        return $type=="form_values";
    }
    /**
     * Recordとフォームの値の相互変換
     * @param bool $is_record_to_values ? Recordから値を取り込む : Recordに値を登録する
     */
    private function convertRecord ($record, $convert_type)
    {
        foreach ($this->def["fields"] as $field_name => $field_def) {
            $col_name = self::getDefColName($field_def, $convert_type);
            // colがfalseであれば削除
            if ($col_name===false) {
                continue;
            }
            // 下層の値は親で処理するのでスキップ
            if ($field_def["level"]==2 || $field_def["level"]==3) {
                continue;
            }
            $table_name = $field_def["table"];
            // fields型の場合下層の要素を処理
            if ($field_def["type"]=="fields") {
                // 要素別の処理
                foreach ((array)$field_def["child_field_names"] as $child_field_name) {
                    $child_field_def = $this->def["fields"][$child_field_name];
                    $child_col_name = self::getDefColName($child_field_def, $convert_type);
                    // colがfalseであれば削除
                    if ($child_col_name===false) {
                        continue;
                    }
                    $item_name = $child_field_def["item_name"];
                    $child_table_name = $child_field_def["table"];
                    //TODO: テーブル定義の確認
                    // 値を登録
                    if (self::isRecordToValues($convert_type)) {
                        $this[$field_name][$item_name] = $record[$col_name][$child_col_name];
                    } else {
                        $record[$col_name][$child_col_name] = $this[$field_name][$item_name];
                    }
                }
            // fieldset型の場合2階層下の要素を処理
            } elseif ($field_def["type"]=="fieldset") {
                $values = array();
                // fieldsetの添え字を取得
                if (self::isRecordToValues($convert_type)) {
                    $fieldset_indexes = array_keys((array)$record[$col_name]);
                } else {
                    $fieldset_indexes = array_keys((array)$this[$field_name]);
                }
                foreach ($fieldset_indexes as $fieldset_index) {
                    // 要素別の処理
                    foreach ((array)$field_def["child_field_names"] as $child_field_name) {
                        $child_field_def = $this->def["fields"][$child_field_name];
                        $child_col_name = self::getDefColName($child_field_def, $is_record_to_values);
                        // colがfalseであれば削除
                        if ($child_col_name===false) continue;
                        $item_name = $child_field_def["item_name"];
                        $child_table_name = $child_field_def["table"];
                        //TODO: テーブル定義の確認
                        // 値を登録
                        if (self::isRecordToValues($convert_type)) {
                            $values[$fieldset_index][$item_name]
                                = $record[$col_name][$fieldset_index][$child_col_name];
                        } else {
                            $values[$fieldset_index][$child_col_name]
                                = $this[$field_name][$fieldset_index][$item_name];
                        }
                    }
                }
                if (self::isRecordToValues($convert_type)) {
                    $this[$field_name] = $values;
                } else {
                    // assoc参照関係があれば下層をRecordに対応づける
                    if ($assoc_col_name = $col_name) {
                        $table_def = app()->table->getTableDef($this->def["table"]);
                        $assoc_table_name = $table_def["cols"][$assoc_col_name]["assoc"]["table"];
                        if ($assoc_table_name) {
                            $assoc_table = table($assoc_table_name);
                            foreach ($values as $k=>$v) {
                                $values[$k] = $assoc_table->createRecord($v);
                            }
                        }
                    }
                    $record[$col_name] = $values;
                }
            // 下層を処理しない型の処理
            } else {
                //TODO: テーブル定義の確認
                // $col_def = table()->getDef($table_name,$col_name);
                // 値を登録
                if (self::isRecordToValues($convert_type)) {
                    $this[$field_name] = $record->getColValue($col_name);
                } else {
                    $record[$col_name] = $this[$field_name];
                }
            }
        }
        return $record;
    }

    /**
     * Fieldの定義に従ってcallbackを逐次呼び出して値を書き換える
     * @param $callback function($value, $field_name, $field_def) => $value
     */
    public function mapFields ($callback)
    {
        $values = $this;
        // 入力値の変換処理
        foreach ($this->def["fields"] as $field_name => $field_def) {
            $parts = explode('.',$field_name);
            // 対象が配列ではない
            if (count($parts)==1) {
                $values[$parts[0]] = call_user_func($callback, $values[$parts[0]], $field_name, $field_def);
            // 対象が1次配列
            } elseif (count($parts)==2) {
                $values[$parts[0]][$parts[1]] = call_user_func($callback, $values[$parts[0]][$parts[1]], $field_name, $field_def);
            // 対象が2次配列
            } elseif (count($parts)==3) {
                if ( ! \R\Lib\Util\Arr::is_arraylike($values[$parts[0]])) continue;
                if (count($values[$parts[0]])==0) continue;
                if ($parts[1]!="*") continue;
                foreach ($values[$parts[0]] as & $fieldset) {
                    $fieldset[$parts[2]] = call_user_func($callback, $fieldset[$parts[2]], $field_name, $field_def);
                }
            }
        }
    }

// -- Csv関連

    /**
     * CsvHandlerを作成する
     */
    public function openCsvFile ($csv_file, $mode="r")
    {
        return new FormCsvHandler($csv_file, $mode, $this->def);
    }

// -- 内部処理

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
            // valid_file ruleの補完
            if (isset($field_def["storage"])) {
                $def["rules"][] = array($field_name, "valid_file");
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
            "values" => $this->getValues(),
            "errors" => $this->getErrors(),
            "forms" => $this->sub_forms,
        );
    }
}