rapp-lib/rlib - Library
========================================

[Core](lib/Core.md) - Webアプリケーションの入出力操作
-------------------------------------
    @TODO: Reportクラスを導入する
    Applecation - App\Applecationの親
    ApplicationEndingException - アプリケーションの実行終了通知
    Report = report() - ロギング機能
    ExtentionManager - 各拡張機能の呼び出し
    UtilProxyManager - Util名前空間の参照
    Util\File - ファイル入出力

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

[FileStorage](lib/FileStorage.md) - ファイル保存領域の管理
-------------------------------------
    FileStorageManager = file_storage() - 動的作成される保存ファイルの読み書き
    FileStorage = extention("file_storage.xxx_file_storage") - Storage種別のInterface
        Extention/FileStorage/XxxFileStorageがimplementsする
    StoredFile - file_storage("storage_type:/stored/file/code")

[Frontend](lib/Frontend.md) - JS/CSSなどのフロントエンドリソース管理
-------------------------------------
    FrontendAssetManager - asset()
    Resource - FrontendAssetManager内部で使用するオブジェクト

[Auth](lib/Auth.md) - 認証機能
-------------------------------------
    AccountManager - auth()
    Role_Base - auth($role)
        App/Role/XxxRoleがextendsする
    Authenticator - 認証情報を返すInterface
        Controllerがimplementsする

[Webapp](lib/Webapp.md) - Webアプリケーションの入出力操作
-------------------------------------
    Request
    Response
    Session
    Controller_Base

[Form](lib/Form.md) - 入力値の操作とフォームの生成
-------------------------------------
    FormFactory
    FormContainer
    FormRepositry
    FormRepositryProxy

[Enum](lib/Enum.md) - プルダウンなどで利用する値リストの管理
-------------------------------------
    EnumManager
    Enum_Base

[Builder](lib/Builder.md) - コード生成
-------------------------------------
    @TODO: テンプレートのElementモデル移行
    @TODO: Controller生成の移行
    WebappBuilder - builder()
    Element/SchemaElement
    Element/ControllerElement
    Element/ActionElement
    Element/TableElement
    Element/ColElement
    Element/RoleElement
    Element/EnumElement

DBAL - DB接続データソースの操作
-------------------------------------
    @MEMO: DBI実装で動作可能なので移行の優先度は低い
    Connection_Base @future

Smarty - Smartyテンプレートエンジンの操作
-------------------------------------
    SmartyExtended

DBI - DB接続とSQL発行の旧実装
-------------------------------------
    Model_Base - @deprecated
    DBI_Base - @deprecated

Report
-------------------------------------
    @TODO: report(...)をreport()->error(...)に拡張する

Core
-------------------------------------
    Migration @fixed 2.x 2016/10/01
    ClassCollector
         @dep Composer
         > Namespaceに属するClass一覧を得る
    Reflection
         > Reflection機能

- functions
    > 関数定義
    required.php

- assets
    rui
         > 共通JS/CSSアセット

- include
    > Namespace未対応のクラス
    cake2 @deprecated
         > DBIで使用しているCake2ライブラリ


