<?php
	
	//-------------------------------------
	// 
	function readme_changelog ($options, $webapp_build_readme) {

		ob_start();
?>
README changelog
	v1.0 111116 Y.Toyosawa

--------------------------------------------------------------------------
[目次]

	[ChangeLog]
		[WFCLibrary] 05/08/11
		[Elusion] 05/12/13
		[SharedStyle] 06/08/05
		[Resq] 06/12/31
		[Resq2] 07/03/05
		[FS-RLib] 11/02/01 Farmstone版
		[RLib-lv110325] 11/03/25 Farmstone派生版
		[Rapp1-lv111116] 11/11/16 Bukumo版

-------------------------------------
[WFCLibrary] 05/08/11

2004年夏ごろから2005年まで使用していた個人用ライブラリ。
先輩エンジニアから継承したコードを習作として書き直したものが基礎。
この時点ではまだMVCFWではなく、WFCを含んだライブラリ群。
WFCはWorldCompilerに籍があった時期に使用していたライブラリ。
MojaviではじめてMVCに触れ、Switch文でMVCを切り分けていた。
DBアクセスやフォームの生成、Validationはこの時期からの機能。


-------------------------------------
[Elusion] 05/12/13

この時点でMapleやEthnaに影響を受けてMVCFWをライブラリに組み込んだ。
Ballisticという名称ではじめて自動生成エンジンを実装した。
この時期のコードはActionが1ファイル1関数で実現されている。
テンプレートエンジンにはじめてSmartyを採用している。
ClassのStatic機能を多用しているため、コードパターンが特徴的。


-------------------------------------
[SharedStyle] 06/08/05

創業時に多かったモバイルの開発案件に併せてElusionを書き直したもの。
ElusionでMVCを取り入れたものが荒削りだったので、機能追加よりは整理が主。
このフレームワークで他社への技術提供をはじめて行った。


-------------------------------------
[Resq] 07/01/02

MVCFWが一般的となり、他社でもSymphonyで自動生成エンジンが公開されるのを確認した時期。
PHP5に移行し、この時期OOSで話題となっていたSymphonyに強く影響を受けた。
世間一般的なPHPのMVCFWの基本形が見え始め、PHP4までは避けていたClassの多用が目立つ。


-------------------------------------
[Resq2] 07/03/05

当時では大規模であった開発規模300万程度の案件の実装に併せて開発したもの。
大規模開発を効率的に行うために機能を整理した。
内部の構造はZFやSymphony等にならって一般的な名称や構造に改めた。
長期にわたって継続したため、多機能で複雑化が進んだフレームワークであった。
必要な機能の追加が容易で、安易な拡張が進んだせいで見通しが損なわれていった。
成長期に入り、改修が追いつかず場当たり的拡張で寿命を延ばしたが、貢献の大きかったFW。


-------------------------------------
[FS-RLib] 11/02/01 Farmstone版

ライブラリの改訂が長くなされない間のCakePHPなどOOSの成長の早さを感じた時期。
根幹に新機能を多く取り入れたが、失敗に終わり、1ヶ月で廃棄されたFW。
ContextやCakeDSを利用したDBアクセスはこの時導入した新機能の1つ。


-------------------------------------
[RLib-lv110325] 11/03/25 Farmstone派生版

Farmstone版（Resq3）をたたき台に、旧Resqに一部差し戻して新規開発。
FS版のViewport機能を廃し、Smartyを再導入。
rlibの基礎形を11年4月にリリース、同11月までの半年間でほぼ完成。
この時点での呼称は新フレームワーク（仮）。次期Rapp1.0の基礎となる。

主な導入事例：
	・11/05/01頃 受診ナビ
	・11/06/01頃 Unchain LPOツール
	・11/06/01頃 四季 BRH
	・11/09/01頃 Unchain 楽天DB
	・11/09/01頃 地球の放課後
	・11/10/01頃 TossRace
	・11/10/01頃 音響特機
	・11/11/01頃 やせとも
	
	
-------------------------------------
[Rapp1-lv111116] 11/11/16 Bukumo版

11年11月Bukumoの着工に際してRappとしてのメジャーナンバー1を派生。
Cakeとの共存のための機能修正の影響が大きい。

主な変更点：
	・Cakeプロジェクト取り込み機能（load_cake）の導入
		・[追加]load_cake
		・[追加]CakeHandler
	・ModelとDBIの呼び出し方をStaticからFunctionに変更
		・[追加]model
		・[追加]dbi
		・[廃予]Model::load
		・[廃予]DBI::load
	・生成されるModelの関数名をシンプルに変更
		・get_productがgetに変わる等

主な導入事例：
	・11/11/15～ Bukumo
	
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