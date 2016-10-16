

## 入出力値の様式変換

    _REQUEST ← GETPOSTで受け付けたままの形式
    ↓ request()の初期化時
    request ← 文字コード/XMLENTITY変換済みの形式
    ↓ autoload→bindRequest()適用時にform_nameを条件に自動設定
    input_values ← request同様の形式
    ↓ setInputValues()による設定時に変換
    values = this ← typeによる入力様式変換済みの形式（旧来はLRA等で_REQUESTの前に変換済み）
    ↓ getInputField[s]時による取得時に変換
    input_fields ← valuesをInputFieldでラッピングしたもの
    ↓ getValidValues()による取得時にvaluesから変換
    valid_values ← 形式はvaluesのまま、validate通過済みの値のみに絞ったもの
    ↓ getTableValues()による取得時に変換
    table_values ← valid_valuesを、table::setValuesに渡すため、colにより絞り込む

## 内部の値の名前

    def ← FormContainerの構成
    form_name ← dec_class内でdefが一意に宣言されている名前
    dec_class ← "protected static $form_xxx"の置かれているクラス名

## defの構成

~~~
    "autoload" => array(
        "pages" => array(".entry_csv_form", ".entry_csv_confirm", ".entry_csv_exec"),
        "session" => true,
        "session_on_request" => true
        ),
    "table" => "Product",
    "fields" => array(
        "name",
        "tel" => array("type"=>"split_text", "delim"=>"-"),
        "mail_confirm" => array("col"=>null),
        "sub_img_files" => array("type"=>"multiple", "fields" => array(
            "title",
            "img_file" => array("type"=>"file")
        )),
        "category_ids",
    ),
    "rules" => array(
        "name"
    ),
~~~