R\Lib\Frontend
========================================

CONCEPT
--------

- ファイル読み込みをバッファに追加

```php
    {{asset required="app.adm.common-js" buffer="script"}}
    {{asset required="app.adm.base-css" buffer="css"}}

    // HTML上にJSを記述してバッファに追加
    {{code type="js" required=["jquery:>1.*","app.show-errors"]}}
        $(function(){
            showErrors({
                errors: {{$c->get_errors()|json_encode}},
                $form: $("form.form-c")
            });
        });
    {{/code}}
```

- バッファの内容を出力（基本的にはHEAD閉部,BODY開閉で出力）

```php
    {{asset flush=["css","script"] state="head.end"}}
    {{asset flush="html" state="body.start"}}
    {{asset flush="*" state="body.end"}}
```

- /html/assets/app/.assets.phpにアプリ内のモジュールの関係などを記述

```php
    if ( ! $asset) { return; }

    // adm - admテンプレート向けJS/CSS
    $asset->registerJsUrl("app.adm.common-js", $url."/adm/js/common.js")
        ->required("app.adm.base-css")
        ->required("jquery");
    $asset->registerCssUrl("app.adm.base-css", $url."/adm/css/base.css")
        ->required("bs.font-awesome:4.*");

    // show-errors - エラー表示
    $asset->registerJsUrl("app.show-errors", $url."/show-errors/js/show-errors.js")
        ->required("app.show-errors.css")
        ->required("rui.show-errors");
    $asset->registerCssUrl("app.show-errors.css", $url."/show-errors/css/show-errors.css");

    // bs.font-awesome
    $asset->registerCssUrl("bs.font-awesome:4.3.0", "//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css");
```