R\Lib\Route
=====================================

route()機能の呼び出しパターン
-------------------------------------
    route()->addBase("main", "/path/to/htdocs", "html_dir/in/htdocs", "example.com");
    route()->changeBase("main");
    route()->add("/admin/products/show-[id].html", "admin_products.show");
    route()->add(array("/admin/products/edit.html"=>"admin_products.edit"));
    $route = route(".edit"); // ".XXX"の形式→相対pageと解釈
    $route = route("admin_products.edit"); // "XXX.XXX"の形式→相対pageと解釈
    $route = route("/admin/products/edit.html"); // "/"から始まる→pathと解釈
    $route = $route->addParam("id",$id); // URL変換時のパラメータを追加
    $route = $route->addParam(array("id"=>$id)); // 複数パラメータ追加
    $url = $route->url(array("from"=>$from),"top"); // パラメータを追加してURLを作成
    $url = $route->urlHttp(); // フルURLを取得
    $url = $route->urlHttps(); // HTTPSのURLを取得
    $route = route("url:/system/admin/products?back=1"); // "url:"から始まる→urlと解釈
    $route = route("url:/system/admin/products/show-123.html"); // パラメータを含む
    $params = $route->getParams(); // URL内に含まれていたパラメータを抽出
    $page = $route->page(); // pageに変換する
    $path = $route->path(); // pathに変換する
    $file = $route->file(); // fileに変換する

