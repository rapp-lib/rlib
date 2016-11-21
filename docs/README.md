rapp-lib/rlib - Library
========================================

[lib/Table](lib/Table.md) - テーブル単位のSchema管理とSQL発行
-------------------------------------
     @TODO: DBI,Model→DBALに依存関係を移行する
     TableFactory - table()
     Table_Base - table($table_name)
     Result - SELECT結果レコードセット
     Record - SELECT結果レコード1行
     Query - 組み立て中のSQL
     Pager - Pagenationデータ

[FileStorage](lib/FileStorage.md) - ファイル保存領域の管理
-------------------------------------

[Frontend](lib/Frontend.md) - JS/CSSなどのフロントエンドリソース管理
-------------------------------------
     FrontendAssetManager - asset()
     Resource

[Auth](lib/Auth.md) - 認証機能
-------------------------------------
     AccountManager - auth()
     Role_Base - auth($role)
     Authenticator - 認証情報を返すInterface

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


