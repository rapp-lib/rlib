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
        - > frontend()経由で利用
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

    - Smarty @deprecated
        - @TODO SmartyExtendedの機能を下層に送って最小化する
        - > Smartyテンプレートエンジンを利用するための機能
        - > MVCのView兼Controller機能の旧実装
        - SmartyExtended
        - SmartyController_Base
    - DBI @deprecated
        - > DB接続とSQL発行の旧実装
        - Model_Base
        - DBI_Base
    - Context @deprecated
        - > 認証/Session解決の旧実装
        
    - DBAL @future
        - > DBとの接続/SQL実発行
    - Report @future
        - @TODO report(...)をreport()->error(...)に拡張する
    - Controller @future
        - @TODO ControllerをSmartyと切り離す
    - Form @future
        - @TODO ControllerをSmartyと切り離す

- util
    - Migration @fixed 2.x 2016/10/01
        - @dep Doctrine/DBAL
        - @dep util/ClassCollector
    - ClassCollector
        - @dep Composer
        - > Namespaceに属するClass一覧を得る
    - Reflection
        - > Reflection機能

- assets
    - rui
        - > 共通JS/CSSアセット

- ext
    - cake2 @deprecated
        - > DBIで使用しているCake2ライブラリ
    - include @deprecated
        - > Namespace未対応のクラス


