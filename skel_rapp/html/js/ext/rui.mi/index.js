/*
	<script language="javascript"> rui.require("rui.mi"); </script>

	<div class="mi">
		<input type="hidden" class="mi_min" value="0"/>
		<input type="hidden" class="mi_max" value="3"/>
		{{foreach $c->input("Entry.art_arts")|unserialize as $i=>$v}}
		<div class="mi_set">
			{{input type="file" name="c[Entry.art_arts][`$i`][file]" value=$v.file}}
			{{input type="text" name="c[Entry.art_arts][`$i`][caption]" value=$v.caption}}
			<span class="mi_remove">[Ｘ]</span>
			<span class="mi_up">[↑]</span>
			<span class="mi_down">[↓]</span>
		</div>
		{{/foreach}}
		<div class="mi_anchor"></div>
		<div class="mi_tmpl">
		<div class="mi_set">
			{{input name="c[Entry.art_arts][%{INDEX}][file]" type="file"}}
			{{input name="c[Entry.art_arts][%{INDEX}][caption]" type="text"}}
			<span class="mi_remove">[Ｘ]</span>
			<span class="mi_up">[↑]</span>
			<span class="mi_down">[↓]</span>
		</div>
		</div>
		<span class="mi_append">[要素の追加]</span>
	</div>
*/

	//-------------------------------------
	// 可変数フォームを作成
	var init_multiple_input =function($mi){
		
		//-------------------------------------
		// パラメータのhidden値の取得
		var mi_min =$(".mi_min",$mi).length ? $(".mi_min",$mi).val() : 0;
		var mi_max =$(".mi_max",$mi).length ? $(".mi_max",$mi).val() : 1000;
		
		var mi_tmpl =$(".mi_tmpl",$mi).eq(0).html();
		$(".mi_tmpl",$mi).remove();
		
		//-------------------------------------
		// 要素の初期化
		var init_mi_set =function($mi_set){
			
			//-------------------------------------
			// 削除ボタンの操作
			$(".mi_remove",$mi_set).on("click",function(){
			
				// 削除数の下限チェック
				if ($(".mi_set").length <= mi_min) { return; }
				
				// 選択要素の削除
				$mi_set.remove();
			});
		
			//-------------------------------------
			// 上と入れ替えるボタンの操作
			$(".mi_up",$mi_set).on("click",function(){
				
				if ($mi_set.prev().hasClass("mi_set")) {
				
					$mi_set.after($mi_set.prev());
				}
			});
		
			//-------------------------------------
			// 下と入れ替えるボタンの操作
			$(".mi_down",$mi_set).on("click",function(){
				
				if ($mi_set.next().hasClass("mi_set")) {
				
					$mi_set.before($mi_set.next());
				}
			});
		};
			
		//-------------------------------------
		// 既存の各要素について処理
		$(".mi_set",$mi).each(function(){
			
			init_mi_set($(this));
		});
		
		//-------------------------------------
		// 追加ボタンの操作
		$(".mi_append",$mi).on("click",function(){
			
			// 追加数の上限チェック
			if ($(".mi_set").length >= mi_max) { return; }
			
			// .mi_tmplをコピーして追加
			var index =parseInt(Math.random()*10000000);
			var $mi_set =$(mi_tmpl.replace(/%\{INDEX\}/g,index));
			init_mi_set($mi_set);
			
			$(".mi_anchor",$mi).before($mi_set);
		});
			
		//-------------------------------------
		// 不足している要素の追加
		while ($(".mi_set").length < mi_min) { 
			
			$(".mi_append",$mi).trigger("click");
		}
	};
	
	
$(function(){
	
	// .miについて処理
	$(".mi").each(function(){
	
		init_multiple_input($(this));
	});
});