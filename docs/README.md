rapp-lib/rlib - Library
========================================

[lib/Core](lib/Core.md) - Webアプリケーションの入出力操作
-------------------------------------
    @TODO: Reportクラスを導入する
    Applecation - App\Applecationの親
    ApplicationEndingException - アプリケーションの実行終了通知
    Report = report() - ロギング機能
    ExtentionManager - 各拡張機能の呼び出し
    UtilProxyManager - Util名前空間の参照
    Util\File - ファイル入出力

[lib/Util](lib/Util.md) - Webアプリケーションの入出力操作
-------------------------------------
    CSVHandker - CSVファイルの入出力
    Migration - Doctrineを使用したMigration
    ClassFinder - Namespaceからクラスを探索する
    Reflection - 各種Reflection

[lib/Table](lib/Table.md) - テーブル単位のSchema管理とSQL発行
-------------------------------------
    @TODO: DBI,Model→DBALに依存関係を移行する
    TableFactory = table() - テーブル定義を取得する
    Table_Base = table("TableName") - 各テーブルのスキーマとSQL組み立てルールの定義
    Result - SQL結果セット
        Table_Base内で$this->resultでアクセスできる
        SELECT以外の結果も同様に格納されている
        table("TableName")->select()などで取得される
    Record - SELECT結果レコード1行
        Table_Base内で$this->result[$n]でアクセスできる
    Query - 組み立て中のSQL
        Table_Base内で$this->queryでアクセスできる
    Pager - Pagenationデータ

[lib/FileStorage](lib/FileStorage.md) - ファイル保存領域の管理
-------------------------------------
    FileStorageManager = file_storage() - 動的作成される保存ファイルの読み書き
    FileStorage = extention("file_storage.xxx_file_storage") - Storage種別のInterface
        Extention/FileStorage/XxxFileStorageがimplementsする
    StoredFile - file_storage("storage_type:/stored/file/code")

[lib/Asset](lib/Asset.md) - JS/CSS、PHPファイルなどの管理
-------------------------------------
    AssetManager - asset()
    Asset - AssetManager内部で使用するオブジェクト

[lib/Auth](lib/Auth.md) - 認証機能
-------------------------------------
    AccountManager - auth()
    Role_Base - auth($role)
        App/Role/XxxRoleがextendsする
    Authenticator - 認証情報を返すInterface
        Controllerがimplementsする

[lib/Webapp](lib/Webapp.md) - Webアプリケーションの入出力操作
-------------------------------------
    Request = request() - GETとPOSTのリクエスト値を扱うArrayObject
    Response = response() - テンプレート変数を扱うArrayObject兼、応答様式の設定
        redirect($url)
        error()
    Session = session() - Session変数のGetter/Setterをもつオブジェクト
    Controller_Base - Controllerの親
        Controllerではフォームの定義、URLに対応する処理をもつ
        フォームの定義はController名で外部からも参照可能
        認証の単位はController単位となっているので、URLに対する処理の呼び出し前に参照される

[lib/Form](lib/Form.md) - 入力値の操作とフォームの生成
-------------------------------------
    FormContainer - 定義に基づいてフォームの入力値を管理
    FormFactory = form() - FormContainerの作成と、FormRepositryProxyの管理
    FormRepositryProxy - FormRepositryの定義を参照してFormContainerを返すArrayObject
    FormRepositry - フォームの定義を持つクラス（Controller）がimplementsするinterface

[lib/Enum](lib/Enum.md) - プルダウンなどで利用する値リストの管理
-------------------------------------
    EnumManager = enum() - Enumを管理
    Enum_Base = enum("SomeEnumClass.values_name") - values（値リスト）の定義の管理
        値リストの実体となるArrayObjectを兼ねる

[lib/Builder](lib/Builder.md) - コード生成
-------------------------------------
    @TODO: テンプレートのElementモデル移行
    @TODO: Controller生成の移行
    WebappBuilder = builder() - 生成エンジン
    Element/Element_Base - 各Elementの親
    Element/SchemaElement - Schemaデータを解析して他の各Elementの組み立てを行う
        (Schema→(Controller→Action),(Table→Col),Role,Enum)というツリー構造
    Element/ControllerElement
    Element/ActionElement
    Element/TableElement
    Element/ColElement
    Element/RoleElement
    Element/EnumElement

lib/Smarty - Smartyテンプレートエンジンの操作
-------------------------------------
    SmartyExtended

lib/DBAL - DB接続データソースの操作
-------------------------------------
    @MEMO: DBI実装で動作可能なので移行の優先度は低い
    Connection_Base @future

lib/DBI - DB接続とSQL発行の旧実装
-------------------------------------
    DBI_Base - Cake2を利用したDBAL旧実装
    Model_Base - DBIの各機能の呼び出しをラッピングする旧実装

functions - 関数定義
-------------------------------------
    functions.php - 各ライブラリ機能のfacadeとなる関数群

assets - プログラム以外のファイル
-------------------------------------
    frontend - asset()経由で参照する共通JS/CSSファイル

include - 旧ライブラリから移行したNamespace未対応のクラス
-------------------------------------
    cake2 - DBIで使用しているCake2ライブラリ
    Rdoc - 旧コード生成とdync実装
