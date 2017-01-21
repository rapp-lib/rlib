R\Lib\Form
=====================================

Classes
-------------------------------------
    FormFactory form()
        form()->create($def)
            構成に従ってフォームを作成する
        form()->setFormRepositry($repositry_class) => $forms
            FormRepositryからFormRepositryProxyを生成する
    FormContainer $forms[$form_name]
        HTML上のformタグに対応する機能
        Requestとの連携、Table/Recordとの連携、検索、入力チェック、値の保持を行う
    InputField
        HTML上のinputタグに対応する機能
        テンプレートエンジン内にラッピングして使用することを想定
        getHTML()
            inputタグを生成する
    FormRepositry
        フォームの構成（$form_def）が取り出し可能なクラスに付けるinterface
        static getFormDef($form_name=null)
            フォームの構成を返す
    FormRepositryProxy $forms
        FormRepositryからフォームの構成を取り出す機能
        $forms[$form_name]
            FormRepositryから構成を取得してFormConatinerを取得（生成）する
    Validator
        入力チェックを行うクラス
        __construct($values, $rules)
            入力チェックを実施する
        ->getErrors()
            入力エラーを取得する

R\Lib\Form\FormContainer
=====================================

Request受付機能
-------------------------------------
    receive() => $is_received
        {{form form=$forms.entry_form}}と指定された場合、リクエスト値をValuesに受け取る
        このフォームが値を受け取った場合、trueを返す
    setInputValues($input_values)
        フォームから送信された$_REQUESTの値を登録する
        値が空の要素は削除して、def.input_convertの変換処理を逐次適応する
    構成
        form_page
            任意指定、formタグのactionのデフォルトに使用される
        csrf_check
            任意指定、trueを指定するとformタグでCSRF対策のキーを渡して、receivedで突き合わせを行う
        field.input_convert
            任意指定、対応するInputConvertの変換処理を入力値に適用する
        receive_all
            任意指定、trueを指定するとreceiveが常に値を受け付けるようになる
    拡張
        変換処理を拡張する
            R\Extention\InputConvert\XxxInputConvert::callback($value,field_name_parts,$field_def) => $value を定義する
        R\Lib\Extention\InputConvertLoader上で定義されている標準の変換処理
            file_upload, storage 指定されたFileStorageに$_FILESの内容を保存する

入力チェック機能
-------------------------------------
    isValid() => $is_valid
        入力チェックを実行してエラーがなければtrueを返す
        エラーがあれば、エラーの登録を行ってfalseを返す
    getErrors() => $errors
        最後に行った入力チェックで登録されたエラーを返す
        入力チェックは行わない
    構成
        rules.*: $field_name
            必須入力、array($field_name, "required") の指定と同義
        rules.*: array($field_name, $rule_type, "etc"=>$etc_value),
            0:入力項目、1:入力チェック型、以降は入力チェック型別のオプションとして使用
        rules.*: array($field_name, $rule_type, "if_target"=>$if_target_field_name),
            if_targetで指定された項目の入力がある場合のみ有効にする指定
        rules.*: array($field_name, $rule_type, "if_target"=>$if_target_field_name, "if_value"=>$if_value),
            if_targetで指定された項目の入力があり、if_valueの値と等しい場合のみ有効にする指定
    拡張
        入力チェック型を拡張する
            R\Extention\ValidateRule\XxxValidateRule::callback($validator, $value, $rule) を定義する
        R\Lib\Extention\ValidateRuleLoader上で定義されている標準の入力チェック型
            required 必ず入力して下さい
            format, format=regex, regex 正しい形式で入力してください
            format, format=mail 正しいメールアドレスを入力してください
            format, format=tel 半角数字(ハイフンあり可)で入力してください
            format, format=zip 半角数字(ハイフンあり可)で入力してください
            format, format=alphabet 半角英字のみで入力してください
            format, format=number 半角数字のみで入力してください
            format, format=alphanum 半角英数字のみで入力してください
            format, format=kana 全角カナのみで入力してください
            format, format=date 正しい日付を入力してください
            length, min, max x文字以上x文字以内で入力してください
            range, min, max 正しい値を入力して下さい
            confirm, target 入力された値が異なっています
            duplecate, table, col, target_id 既に登録されています ★未完成

入力情報の保持機能
-------------------------------------
    save()
        一時保存領域に設定されている値を保存する
        Values以外の状態は保存されない
    restore()
        一時保存領域に設定されている値を取り出して設定する
    構成
        auto_restore
            Formを使用する際に自動的にrestore()を呼び出して一時保存領域に値があれば取り出す

## Record操作機能
    init($id)
        IDを指定して値を初期化する
        tableが関連づけられていれば$table->selectById($id)で検索して値を設定する
        検索結果がなければIDは自動的にNULLとなり、もともとIDがNULLであれば検索を行わない
    getRecord() => $record
        登録されている値をRecordの型に変換して取り出す
        fieldsに含まれる要素は値の有無にかかわらず含まれる
        col=falseの要素は値の有無にかかわらず含まれない
    setRecord($record)
        Recordにより値を設定する
        getRecord()の場合と同様の規則で値をマッピングする
    構成
        table
            Record操作を行う場合は必須、Table名の指定
        fields.*.col=false
            Recordを扱うときに無視する項目の指定
        fields.*.col
            field_nameがTable上でのcol_nameと異なるときに指定する

検索機能
-------------------------------------
    findBySearchFields() => $table
        入力値を検索条件と照合して検索条件を構築、条件を設定した状態のTableを返す
    $table->select()->getPager() => $pager
        ページャー（R\Lib\Table\Pager）を取得する
        getSearchPageUrl($params)と組み合わせて使用する
    getSearchPageUrl($params) => $url
        検索結果ページのURLを取得する
        登録されている検索条件の値を、$paramsで指定された値で上書きしてクエリを作成する
    構成
        search_page
            検索ページのURLを取得する場合必須、ページの指定
        search_table
            検索対象とするTable名
        fields.*.search=where, target_col 完全一致、比較、IN検索
        fields.*.search=word, target_col キーワード部分一致検索
        fields.*.search=page, volume ページの指定
        fields.*.search=sort, (default) 整列順の指定
    拡張
        検索条件の種類（searchの値）を追加する
            XxxTable->search_typeXxx($form, $field_def, $value) を定義する
            R\Lib\Table\Table_Base->search_typeWhere($form, $field_def, $value) を参考にする

値の直接操作機能
-------------------------------------
    setValues($values)
        登録されている値をそのまま配列で設定する
    getValues() => $values
        登録されている値をそのまま配列で取り出す
    clear()
        全ての状態の消去、設定されていれば一時保存領域の値も消去する
    isEmpty() => $is_empty
        登録されている値が1つでもあればtrueを返す

