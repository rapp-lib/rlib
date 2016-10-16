rapp-lib/rlib - Simple Webapp Library
========================================

INDEX
--------

- lib
    - [Table](lib/Table.md)  @fixed 2.x 2016/10/05
        - @TODO DBI,Model→DBALに依存関係を移行する
        - > table()経由で利用
        - > SQL組み立て＋結果操作機能
        - TableFactory
        - Table_Base
        - Result
        - Record
        - Query
    - [Frontend](lib/Frontend.md)  @fixed 2.x 2016/10/10
        - @TODO アップロード/user_file操作機能を集約
        - > asset()経由で利用
        - > JS/CSSなどのフロントエンドリソース管理機能
        - FrontendAssetManager
        - Resource
    - [Auth](lib/Auth.md)  @fixed 2.x 2016/10/01
        - > auth()経由で利用
        - > 認証機能
        - AccountManager
        - Role_Base
    - [Builder](lib/Builder.md) @新実装に以降中
        - > TODO: テンプレートのElementモデル移行
        - > builder()経由で利用
        - > 自動生成機能
        - SchemaElement
        - ControllerElement
        - ActionElement
        - TableElement
        - ColElement

    - Smarty
        - @TODO form/inputタグをFormライブラリに関連づける
        - > Smartyテンプレートエンジンを利用するための機能
        - > MVCのView兼Controller機能の旧実装
        - SmartyExtended
        - SmartyController_Base @deprecated
    - Controller
        - @TODO ControllerをSmartyと切り離す
        - Controller_Base @future
    - Form
        - @MEMO Controllerの後に実装予定
    - Context
        - > 認証/Session解決の旧実装
        - Context_Base @deprecated
    - Enum
        - @MEMO Formの後に実装予定
        - List_Base @deprecated
    - DBAL
        - > DBとの接続/SQL実発行
        - @MEMO DBI実装で動作可能なので移行の優先度は低い
        - Connection_Base @future
    - DBI
        - > DB接続とSQL発行の旧実装
        - Model_Base @deprecated
        - DBI_Base @deprecated
    - Report
        - @TODO report(...)をreport()->error(...)に拡張する

- util
    - Migration @fixed 2.x 2016/10/01
        - @dep Doctrine/DBAL
        - @dep util/ClassCollector
    - ClassCollector
        - @dep Composer
        - > Namespaceに属するClass一覧を得る
    - Reflection
        - > Reflection機能

- functions
    - @TODO Laravel array_関数を参考に参照解決を整理
    - @TODO 不必要な関数を削除、頻度の低いものからUtil以下に移行
    - > 関数定義
    - required.php
    
- assets
    - rui
        - > 共通JS/CSSアセット

- ext
    - cake2 @deprecated
        - > DBIで使用しているCake2ライブラリ
    - include @deprecated
        - > Namespace未対応のクラス


