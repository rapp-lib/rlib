R\Lib\Frontend
========================================

CONCEPT
--------

- Jsのコードをバッファに書き込む:
```php
    $js = 'alert($("#msg").text());';
    frontend()->bufferScriptCode($js)
        ->required("jquery","2.*");
```
    
- モジュールとして外部のCDNを登録する
```php
    frontend()->registerScriptUrl("jquery", "2.2.4", '//code.jquery.com/jquery-2.2.4.min.js');
```

- モジュールとしてライブラリ内のURLを登録する
```php
    frontend()->registerScriptUrl("mi", "1", frontend()->getAssetUrl("lib").'/js_rui/rui.mi/index.js')
        ->required("rui","*");
    frontend()->registerScriptUrl("mi", "2", frontend()->getAssetUrl("lib").'/js_rui/jquery.mi/index.js')
        ->required("jquery","2.*");
```

- 別途モジュールが読み込み済みであることを通知
```php
    print '<script src="//code.jquery.com/jquery-2.2.4.min.js"></script>';
    frontend()->markLoaded("jquery","2.2.4");
```

- バッファの中身を出力
```php
    print frontend()->flush();
```
