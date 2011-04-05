<?php
	
	//-------------------------------------
	// 
	function readme_about_lib ($options, $webapp_build_readme) {

		ob_start();
?>
README about_lib
	v1.0 110329 Y.Toyosawa

--------------------------------------------------------------------------
[目次]

	[基本構成]
	
		[基本構成.1]アプリケーション固有のファイル構成
			webapp_dir以下のファイル構成の説明
			
		[基本構成.2]ライブラリ内部のファイル構成
			ライブラリのファイル構成の説明
		
		[基本構成.3]主要な関数の紹介
		
		[基本構成.4]主要なクラスの紹介

	[自動生成]
	
		[自動生成.1]CSV/A5ERファイルからSchemaを生成する
			WebappBuilder-CreateSchema機能の使用方法の説明
			
		[自動生成.2]Schemaからソースコードを生成する
			WebappBuilder-DeployFiles機能の使用方法の説明
			
		[自動生成.3]ロールバックの実行
			WebappBuilder-RollbackFiles機能の使用方法の説明
			
		[自動生成.4]カタログページ生成
			WebappBuilder-ScriptScanner機能の使用方法の説明
					
		[自動生成.5]Readme表示
			WebappBuilder-Readme機能の使用方法の説明
	
	[機能概要]
	
		[機能概要.1]フレームワークの動作の基本的な流れ
			URLアクセスからHTML出力までの流れの説明
		
		[機能概要.2]フレームワークでの開発の基本的な流れ
			フレームワークを使用した開発の基本的な作業の流れの説明
			
	[機能詳細]
	
		[機能詳細.1]Dync機能
			レポート出力や自動生成等
		
		[機能詳細.2]DBアクセス機能
			DBIクラスを中心とするlib_dbの機能説明
		
		[機能詳細.3]Smartyテンプレート拡張機能
			Controllerクラスを中心とすlib_smartyの機能説明
		
		[機能詳細.4]入力/セッション管理機能
			Contextクラスを中心とすlib_contextの機能説明
		
		[機能詳細.5]各種拡張モジュール
			modulesでの拡張機能の一覧説明
		
		[機能詳細.6]基本設定項目
			設定項目の説明
			
--------------------------------------------------------------------------
[基本構成]

-------------------------------------
[基本構成.1]アプリケーション固有のファイル構成

	/webapp/
		html/ ... DocumentRootとなるディレクトリ
			save/ ... アップロードされた画像ファイルなどを配置するDir（0777推奨）
			.htacces ... Rewrite設定を記述する必要のある箇所
			.index.php ... Rewrite設定により全てのアクセスの起点となるPHP
			xxx.html ... （名称自由）各種テンプレートHTML
			xxx/ ... （名称自由）各種URL構造上のDir
				xxx.yyy.html ... （名称自由）各種テンプレートHTML
		app/ ... include_path配下で、主にController等を定義する
			controller/
				XxxController.class.php
			context/
				XxxContext.class.php
			provider/
				XxxProvider.class.php
			list/
				XxxList.class.php
			Controller_App.class.php
			Context_App.class.php
			Provider_App.class.php
		config/ ... 主にregisty設定を記述するファイルを格納するDir
			config.php ... 各種設定ファイルの起点となるPHP
			main.config.php ... （名称自由）アプリケーションの動作に関する設定ファイル
			install.config.php ... （名称自由）初回配置時の初期設定を行う設定ファイル
			schema.config.php ... （名称自由）Schema設定ファイル
			schema.config.csv ... （名称自由）Schema生成用CSVファイル
		tmp/ ... Smartyキャッシュ等一時ファイル保存用Dir（0777推奨）
		
-------------------------------------
[基本構成.2]ライブラリ内部のファイル構成

	/rlib.git/
		core.php ... ライブラリ読み込みの起点となるPHP
		core/ ... 共用関数群の配置Dir
			include/ ... 共用クラス群の配置Dir
			pear/ ... Pearライブラリ
			smarty/ ... Smarty[ライブラリ
		lib_smarty/
			modules/ ... 共用モジュール定義Dir
				input_type/ ... inputタグのHTML生成
				html_element/ ... 共用のHTML部分テンプレート
				smarty_plugin/ ... Smartyのプラグイン
			default/ ... クラス未定義時に使用される代替クラス定義Dir
				Controller_App.class.php
			lib_smarty.php ... lib_smartyライブラリ読み込みの起点となるPHP
			Controller_Base.class.php ... Controllerクラスの基本クラス
			SmartyExtended.class.php ... Smartyを拡張したクラス
		lib_db/
			cakeds/ ... CakePHPのDatasourceエンジンを構成するファイル群
			default/ ... クラス未定義時に使用される代替クラス定義Dir
				DBI_App.class.php
			lib_db.php ... lib_dbライブラリ読み込みの起点となるPHP
			DBI.class.php ... DBアクセスクラスのファクトリクラス
			DBI_Base.class.php ...  ... DBアクセスクラスの基本クラス
		lib_context/
			modules/ ... 共用モジュール定義Dir
				rule/ ... 入力チェックルール関数の定義Dir
				search_type/ ... 検索ルール関数の定義Dir
			default/ ... クラス未定義時に使用される代替クラス定義Dir
				Context_App.class.php
			lib_context.php ... lib_contextライブラリ読み込みの起点となるPHP
			Context_Base.class.php ... Contextクラスの基本クラス

-------------------------------------
[基本構成.3]主要な関数の紹介

	後述の[自動生成.4]カタログページ生成で参照してください。
	
-------------------------------------
[基本構成.4]主要なクラスの紹介
	
	後述の[自動生成.4]カタログページ生成で参照してください。

--------------------------------------------------------------------------
[自動生成]

-------------------------------------
[自動生成.1]CSV/A5ERファイルからSchemaを生成する

手順：
	(1) "/config/schema.config.csv"ファイルを読み込む
	(2) ファイルがなければ"/config/schema.config.a5er"ファイルを読み込む
	(3) URLへアクセス
	(4) /config/schema.config.phpを生成する

URL：
	?exec=1
	&_[report]=1
	&_[webapp_build][schema]=1
	&_[webapp_build][force]=1 ... 既存のファイルの上書きを許可

A5ERの場合：
	・論理名、物理名、型定義から推測されるTYPE、pkey情報のみ取り出します。
	・テーブル定義は大まかにしか再現しないのでA5M2で生成してください。
	
CSVのサンプル：
	#tables	table	col	label	def	type	other
		Comment		コメント			
			content	内容	text	text	
			category	カテゴリ	text	select	list=comment_category
	#	このように#で始まる行はコメントです
	#controller	controller	label	type	table	account	other
		comment_master	コメント管理	master	Comment		
		admin_login	管理者ログイン	login		admin	
		
-------------------------------------
[自動生成.2]Schemaからソースコードを生成する

手順：
	(1) registyのSchemaを記述
	(2) URLへアクセス
	(3) 定義された各ファイルを自動生成

URL：
	?exec=1
	&_[report]=1
	&_[webapp_build][deploy]=1
	&_[webapp_build][force]=1 ... 既存のファイルの上書きを許可

Schemaの指定方法：

	Schema.tables.<テーブル名>
		label: ラベル
		pkey: IDに使用する列名
		del_flg: 削除フラグに使用する列名
		reg_date: 行頭録日付に使用する列名
		[virtual: DB上のテーブルとModelを配置しない設定]
		
	Schema.cols.<テーブル名>.<列名>
		label: ラベル
		type: IDに使用する短縮列名
		[list: 選択肢名]
		[def.type: SQL定義に使用するデータ型名]
		
	Schema.controller.<コントローラ名>
		label: ラベル
		type: コントローラの種類（master,login,formなど）
		[table: 扱うテーブル名（master,formで必須）]
		[account: 扱うアカウント（loginで必須）]
		[wrapper: 共通ヘッダフッタのファイルの接頭辞]
		[header: 共通ヘッダHTML]
		[footer: 共通フッタHTML]
		
Schemaのサンプル：	
	registry(array(
		"Schema.tables" =>array(
			"Product" =>array("label"=>"製品", "pkey"=>"id", "del_flg"=>"del_flg"),
			"Member" =>array("label"=>"会員", "pkey"=>"id", "del_flg"=>"del_flg"),
		),
		"Schema.cols" =>array(
			"Product.name" =>array("label"=>"製品名", "type"=>"text"),
			"Product.price" =>array("label"=>"値段", "type"=>"select"
					, "list"=>"product_price", "def"=>"int"),
			"Member.login_id" =>array("label"=>"ログインID", "type"=>"text"),
			"Member.login_pass" =>array("label"=>"パスワード", "type"=>"text"),
		),
		"Schema.controller" =>array(
			"member_master" =>array("label"=>"会員管理", "type"=>"master", "table"=>"Member"),
			"product_master" =>array("label"=>"製品管理", "type"=>"master", "table"=>"Product"),
			"admin_login" =>array("label"=>"管理者ログイン"
					, "type"=>"login", "table"=>"Admin", "account"=>"admin"),
		),
	));

-------------------------------------
[自動生成.3]ロールバックの実行

手順：
	(1) 自動生成時のHistoryKeyを記録
	(2) HistoryKeyを指定してURLへアクセス
	(3) 自動操作を打ち消す操作が実行される

URL：
	?exec=1
	&_[report]=1
	&_[webapp_build][rollback]=1
	&_[webapp_build][history]=(U+9桁の数字)
	&_[webapp_build][action][create]=1 ... 作成したファイルの削除
	&_[webapp_build][action][backup]=1 ... バックアップの復元
	&_[webapp_build][force]=1 ... 既存のファイルの上書きを許可

-------------------------------------
[自動生成.4]カタログページ生成

手順：
	(1) URLにアクセス
	(2) classまたはfunction定義のカタログページが生成される

URL：
	?exec=1
	&_[report]=1
	&_[webapp_build][profile]=1
	&_[webapp_build][target]=lib ... ディレクトリ名（Default: webapp_dir）
	&_[webapp_build][catalog]=class ... 一覧参照先(classまたはfunction)の指定

-------------------------------------
[自動生成.5]Readmeの表示

手順：
	(1) URLにアクセス
	(2) modules/readme/readme_xxx.phpで定義されたReadmeファイルが表示される
	
URL：
	?exec=1
	&_[report]=1
	&_[webapp_build][readme]=1
	&_[webapp_build][page]=about_lib ... ページ名（Default: index）

--------------------------------------------------------------------------
[機能概要]

-------------------------------------
[機能概要.1]フレームワークの動作の基本的な流れ
	
	（１）URLRewrite処理
	
		・"/test/test.index.html?aaa=bbb"へアクセス
		・"/html/.htaccess"によりURLの書き換え
		・"/html/.index.php?__REQUEST_URI__=/test/test.index.html&aaa=bbb"へのアクセスとなる
		・"/html/.index.php"を基準にライブラリ、設定の読み込み、ルーティングが実行される
	
	（２）アプリケーションの初期化
	
		・"/rlib.git/core.php"が読み込まれ、各種ライブラリ機能が有効化
		・"/config/config.php"が読み込まれ、設定が適応される
		・".index.php"内で定義されている"__start"が呼び出される
		・"__start"の先頭で"/core/webapp.php"で定義される"start_webapp"が呼び出される
		※"start_webapp"はWebアプリケーションの初期化全般の重要な処理を設定を元に実施する
		・".index.php"内で定義されている"__end"関数を終了時呼び出しに予約する
	
	（３）Controller/Actionの実行
	
		・"__REQUEST_URI__"パラメータの解釈が行われる
		・"Routing.page_to_path"設定を基準にURLとController/Actionの対応付けが決定される
		・Controllerクラス（"XxxController"）が初期化される
		・Controllerクラスの"before_act"メソッドが呼び出される
		・ControllerクラスのActionメソッド（"act_xxx"）が呼び出される
		・Controllerクラスの"after_act"メソッドが呼び出される
	
	（４）テンプレートの読み込みと出力
	
		・アクセスされたURLから"/html/"以下のテンプレートファイルを特定する
		・Controllerクラスの"fetch"関数でSmartyテンプレートファイルを読み込む
		※Controllerクラスは"Smarty"および"SmartyExtended"クラスを継承している
		・テンプレートを解釈して得られたHTMLを表示して終了

--------------------------------------------------------------------------
[機能概要.2]フレームワークでの開発の基本的な流れ
	
	（１）開発環境の作成
	
		・"rlib.git/"と"webapp.git/"を開発環境へ設置
		・"html/.htaccess"内の".../.index.php"を実際のURLに書き換える
		・".../webapp.git/html/index.html"にWebからアクセスできることを確認する
	
	（２）自動生成
	
		・SchemaCSVを書き換えてSchema生成（[自動生成.1]参照）
			・SchemaCSV ... "config/schema.config.csv"のCSVファイル
			・Schema ... "config/schema.config.php"のregistry設定
		・Schemaを書き換えてプログラム生成（[自動生成.2]参照）
			・ControllerクラスやテンプレートHTMLが生成される。
	 
	（３）基本設定の変更
	
		・基本設定ファイルを書き換えて、DB接続やエンコーディングを設定
			・"config/config.php" ... 基本設定ファイル
				registry設定と他の設定ファイルの読み込みを行う
				DB接続やファイルアップロードなどの動作設定を記述する
			・"config/schema.config.php" ... 自動生成用のSchema設定
			・"config/routing.config.php" ... ルート設定
			・"config/generated.config.php" ... 自動生成された画面の設定（自動生成）
			・"config/install.config.php" ... Table作成用のSQL等の初期設定（自動生成）
	
	（４）ルートの変更
	
		・ルート設定を変更してページの追加や、ページのURLの変更を行う
			・ルート設定 ... registryの"Routing.page_to_path"の設定
			
	（５）Controller調整
	
		・ControllerクラスのActionメソッドを編集してSQL等を調整する
			・Controllerクラス ... "app/controller/XxxController.class.php"に定義されているクラス
			・Actionメソッド ... "XxxController::act_yyy()"メソッド
		・Controller_AppのAction前後の処理に認証などを加える
			全画面共通のセッションや、認証処理を加える
			・"Controller_App::before_action()"はActionメソッドの前に呼び出される
			・"Controller_App::after_action()"はActionメソッドの後に呼び出される
		
	（６）テンプレート調整
	
		・ヘッダやフッタの共通HTMLを編集して全体の画面構成を変更
			共通HTMLは主に"html/element/xxx.html"に設置されている
		・テンプレートHTMLを追加、編集して個別の画面構成を変更

--------------------------------------------------------------------------
[機能詳細]

-------------------------------------
[機能詳細.1]Dync機能
	
	※パラメータ名「_」は"Config.dync_key"で設定されている（null設定で機能無効化）
	※通常認証が必要な設定となっている（初期設定は社内標準のIDPW）
	
	・レポート出力機能
		・URLに"?_[report]=1"を付加することで有効化される
		・SQLの実行結果や動作のログを画面上で確認可能
		
	・自動生成機能
		・URLに"?exec=1&_[webapp_build][xxx]=1"を付加することで有効化される
		・詳しくは[自動生成]参照
	
-------------------------------------
[機能詳細.2]DBアクセス機能

主な機能：
	
	・DB接続の管理
	・SQLクエリの発行
	・配列形態のSQLクエリの解釈

API：

	・DBI::load($name)
		
		接続DBIインスタンスを得る
		初回呼び出し時に"DBI.connection.Name"の接続情報で接続
		
	・DBI::exec($sql_statement, $command="execute")
	
		生のSQLクエリを実行する
		commandの指定で動作が変わる
			・execute ... 実行のみ（Default）
			・fetchRow ... 結果から1行の取得
			・fetchAll ... 結果から全行の取得
			
	・DBI::insert($query)
	
		Insertクエリの実行
		
		・呼び出しサンプル：
		
			// INSERT
			$r =DBI::load()->insert(array(
				'table' => "member",
				'alias' => "Member",
				'fields' => array(
					"Member.name" =>"Toyosawa_NEW",
				),
			));
			
	・DBI::update($query)
	
		Updateクエリの実行
		
		・呼び出しサンプル：
		
			// UPDATE
			$r =DBI::load()->update(array(
				'table' => "member",
				'alias' => "Member",
				'fields' => array(
					"Member.name" =>"NEW_RECORD",
				),
				'conditions' => array(
					"Member.name LIKE" =>"%_NEW",
				),
			));
			
	・DBI::delete($query)
	
		Deleteクエリの実行
		
		・呼び出しサンプル：
		
			// DELETE
			$r =DBI::load()->delete(array(
				'table' => "member",
				'alias' => "Member",
				'conditions' => array(
					"Member.name LIKE" =>"%_NEW",
				),
			));
			
	・DBI::save($query)
	
		UpdateOrInsertクエリの実行
		ConditionsがあればUpdate、なければInsertクエリを発行します。
		※呼び出し方はUpdate/Insertと同様
			
	・DBI::select($query)
	
		Select文で取得できる全行を取得
		配列構造は"$ts[index][TableAlias.col_name]"となる
		
		・呼び出しサンプル：
		
			// SELECT
			$ts =DBI::load()->select(array(
				'table' => "Member",
				'alias' => "Member",
				'fields' => array(
					"Member.*",
				),
				'conditions' => array(
					"Member.id >" =>"2", 
				),
				'limit' => null,
				'offset' => null,
				'joins' => array(),
				'order' => null,
				'group' => null,
			));
		
	・DBI::select_one($query)
	
		Select文で1行だけ取得
		配列構造は"$t[TableAlias.col_name]"となる
		※呼び出し方法はselect同様
		
	・DBI::select_count($query)
		
		Select文で取得できる行数を取得
		int型で結果を返す
		※呼び出し方法はselect同様
		
	・DBI::select_pager($query)
		
		Pager構造体を取得
		※呼び出し方法はselect同様
		
	・DBI::get_datasource()
		
		接続に関するCakeDatasourceを取得
		
	・DBI::desc($table_name)
		
		テーブル構造の解析結果を得る
		
	・DBI::last_insert_id($table_name, $pkey)
		
		LAST_INSERT_IDの取得
	
-------------------------------------
[機能詳細.3]Smartyテンプレート拡張機能

主な機能：
	
	・Smartyテンプレートを解釈する機能
	・ControllerとSmartyをつなぐ機能
	・Contextを生成する機能

主なSmarty拡張：

	・{{include file="path:/element/xxx.html"}}{{/a}}
		
		部分HTMLの読み込みにPath（HTMLDirからの相対パス）の記述が可能
		
	・{{input name="c[Member.name]" value="" type="text"}}
		
		input_typeモジュールで定義された入力用HTMLを生成する
		
	・{{a href="../member_top_page.html"}}{{/a}}
	・{{a _path="/member/member_top_page.html"}}{{/a}}
	・{{a _page="member_page.top"}}{{/a}}
	
		aタグのURL指定でPage名やPath（HTMLDirからの相対パス）の記述が可能
		
	・{{form _page="member_page.top"}}{{/form}}
	
		formタグのURL指定でPage名やPath（HTMLDirからの相対パス）の記述が可能
		
	・{{$t.Comment.timestamp|date:"Y/m/d"}}
		
		Timestampを整形する機能
		
	・{{$t.Member.category|select:"member_category"}}
		
		XxxList::select()関数を呼び出して要素を選択する機能
		
	・{{$t.Files.file_code|userfile}}
		
		UserFileManagerでアップロードしたファイルのURLをコードから計算する
		パラメータにGroup名を渡すことも可能
		
-------------------------------------
[機能詳細.4]入力/セッション管理機能

主な機能：
	・セッション変数の管理
	・ユーザ入力の管理
		・ユーザ入力の入力チェック
		・ユーザ入力に併せた検索/ソート/ページ分け処理
	・認証制御

API：
	・Context::session($values)
	・Context::session($key, [$value])
		
		セッション変数のRead/Writeを行う
		
		
	・Context::input($values)
	・Context::input($key, [$value])
		
		input内の一連のデータを一意に特定する情報を保存しておく
		query系機能ではこの設定に従ってSQLの組み立てを行う
		※値はセッション変数（"__id"）に保存される
		
	・Context::errors($values)
	・Context::errors($key, [$value])
	
	・Context::id([$id])
		
		input内の一連のデータを一意に特定する情報を保存しておく
		query系機能ではこの設定に従ってSQLの組み立てを行う
		※値はセッション変数（"__id"）に保存される
		
	・Context::validate($required, $rules)
	・Context::query_select_one($query)
	・Context::query_list($query)
	・Context::query_save($query)
	
-------------------------------------
[機能詳細.5]各種拡張モジュール
	
	・search_type（検索条件/lib_context）
	
		Context::query_list()で"search.xxx.type"から検索条件を生成
		
	・rule（入力チェック条件/lib_context）
	
		Context::validate()の第2引数から入力チェック条件を生成
		条件はregistryの"Validate.rules.n.type"設定でも記述可能
		
	・smarty_plugin（Smartyプラグイン/lib_smarty）
		
		Smarty::fetch()でテンプレート解釈を行う際に使用
		
	・input_type（inputタグでの入力要素HTML/lib_smarty）
	
		Smartyテンプレート内のinputタグを解釈する際に使用
		
	・html_element（共通のHTML部品/lib_smarty）
	
		Smartyテンプレート内のincludeタグでの読み込み対象の一つとして使用
	
-------------------------------------
[機能詳細.6]基本設定項目

	// Smartyキャッシュ等一時ファイル用ディレクトリ
		"Path.tmp_dir" =>realpath(dirname(__FILE__)."/../tmp"),
		
	// Dync機能の設定
		"Config.dync_key" =>"_",
		"Config.dync_auth_id" =>"e77989ed21758e78331b20e477fc5582",
		"Config.dync_auth_pw" =>"547d913f6ee96d283eb4d50aea20acc1",
		
	// Dtrack機能の設定
		"Config.dtrack_key" =>null,
		
	// 入出力の文字コード（設定に従って自動変換が行われる）
		"Config.external_charset" =>"SJIS-WIN",
		"Config.dtrack_bind_session" =>false,
		
	// レポート出力設定
		"Report.error_reporting" =>E_ALL&~E_NOTICE,
		
	// WebappDirからの相対パスでinclude_pathを設定
		"Config.webapp_include_path" =>array(
			"app",
			"app/controller",
			"app/context",
			"app/list",
			"app/provider",
		),
		
	// 基本ライブラリの読み込み設定
		"Config.load_lib" =>array(
			"lib_smarty",
			"lib_context",
			"lib_db",
		),
	
	// DB接続設定（初回DBアクセス時に接続される）
		"DBI.preconnect" =>array(
			
		// 名称省略での接続先設定
			"default" =>array(
				'driver' => 'mysql',
				'persistent' => false,
				'host' => 'localhost',
				'login' => 'dev',
				'password' => 'pass',
				'database' => 'r3_test',
				'prefix' => '',
			),
		),
		
	// ファイルアップロード先の設定
		"UserFileManager.upload_dir" =>array(
			"default" =>realpath(dirname(__FILE__)."/../html/save/user_uploaded")."/",
			"group" =>array(
			),
		),
		
	// ファイルアップロード時に使用する拡張子の設定
		"UserFileManager.allow_ext" =>array(
			"default" =>null,
			"group" =>array(
			),
		),
		
	// ルート設定
		"Routing.page_to_path" =>array(
			"index.index" =>"/index.html",
		),
		
	// 入力チェック
		"Validate.rules" =>array(
			array("target"=>"Product.tel", "type"=>"tel"),
			array("target"=>"Product.mail", "type"=>"mail"),
		),
		
	// 認証（認証用Context内で、アクセス制限判定に使用）
		"Auth.access_only.member" =>array(
			"product_master",
		),
		
	// アプリケーション内で使用される各種ラベル
		"Label.schema.col.Product.name" =>"製品名",
		"Label.errmsg.input.required.Product.name" =>"製品名が空白です",
		"Label.errmsg.user.member_login_failed" =>"IDまたはPassが誤っています",
		
	
<?
		$text =ob_get_clean();
		$text =str_replace("\t","&nbsp;&nbsp;&nbsp;&nbsp;",$text);
		$text =str_replace("\n","<br/>",$text);
		
		if (preg_match_all('!<br/>(?:-|\s)+<br/>(\[[^\]]+\])!',$text,$matches,PREG_SET_ORDER)) {
			
			foreach ($matches as $match) {
				
				$key =md5($match[1]);
				
				$text =preg_replace(
						'!'.preg_quote($match[0]).'!',
						'<br/><a href="#'.md5("[目次]").'">▲</a>'
							.'<a name="'.$key.'"></a>'."$0",
						$text);
				
				$text =preg_replace(
						'!'.preg_quote($match[1]).'!',
						'<a href="#'.$key.'">'."$0".'</a>',
						$text);
			}
		}
		
		return $text;
	}