/*
-------------------------------------
jquery.mi
-------------------------------------

-------------------------------------
■サンプル 通常のmi：

	<script charset="UTF-8" src="{{'/js/rui/jquery.mi/jquery.mi.js'|path_to_url}}"></script>
	
	<style> 
		ul.mi-root, ul.mi-root ul { 
			list-style-type : none; 
		} 
	</style>
		
	<div class="mi" id="tasksArea">
		
		<!-- mi設定 -->
		<input type="hidden" class="mi-config-minItems" value="0"/>
		<input type="hidden" class="mi-config-maxItems" value="9999"/>
		<input type="hidden" class="mi-config-maxRank" value="0"/>
		
		<script type="text/template" class="mi-tmpl">
			<li class="mi-item" id="list_<%=id%>">
				<input type="hidden" name="c[Entry.tasks][<%=id%>][id]" class="mi-item-id" value="<%=id%>"/>
				
				[<span class="mi-item-seq-area"></span>]
				<a href="javascript:void(0);" class="mi-item-up">[↑]</a>
				<a href="javascript:void(0);" class="mi-item-down">[↓]</a>
				<a href="javascript:void(0);" class="mi-item-remove">[削除]</a>
				
				{{input type="text" name="c[Entry.tasks][<%=id%>][label]"}}
				</select>
			</li>
		</script>
		<!-- /mi設定 -->
		
		<a href="javascript:void(0);" class="mi-append">[新規登録]</a>
		
		<ul class="mi-root">
			
			{{foreach $c->input("Entry.tasks")|to_array as $i=>$v}}
			<li class="mi-item" id="list_{{$i}}">
				<input type="hidden" name="c[Entry.tasks][{{$i}}][id]" value="{{$i}}" class="mi-item-id"/>
				
				[<span class="mi-item-seq-area"></span>]
				<a href="javascript:void(0);" class="mi-item-up">[↑]</a>
				<a href="javascript:void(0);" class="mi-item-down">[↓]</a>
				<a href="javascript:void(0);" class="mi-item-remove">[削除]</a>
				
				{{input type="text" name="c[Entry.tasks][`$i`][label]" value=$v.label}}
			{{/foreach}}
			
			<li class="mi-anchor" style="display:none;"></li>
		</ul>
	</div>

-------------------------------------
■サンプル 階層操作可能なmi：

	<script charset="UTF-8" src="{{'/js/rui/jquery.mi/jquery.mi.js'|path_to_url}}"></script>
	
	<style> 
		ul.mi-root, ul.mi-root ul { 
			list-style-type : none; 
		} 
	</style>

	<div class="mi mi-keybind" id="tasksArea">
		
		<input type="hidden" class="mi-config-minItems" value="0"/>
		<input type="hidden" class="mi-config-maxItems" value="9999"/>
		<input type="hidden" class="mi-config-maxRank" value="2"/>
		<script type="text/template" class="mi-tmpl">
			<li class="mi-item" id="list_<%=id%>">
				<input type="hidden" name="c[Entry.tasks][<%=id%>][id]" class="mi-item-id" value="<%=id%>"/>
				<input type="hidden" name="c[Entry.tasks][<%=id%>][pid]" class="mi-item-pid" value=""/>
				<input type="hidden" name="c[Entry.tasks][<%=id%>][rank]" class="mi-item-rank" value=""/>
				<input type="hidden" name="c[Entry.tasks][<%=id%>][seq]" class="mi-item-seq" value=""/>
				
				[ID=<%=id%>]
				[SEQ=<span class="mi-item-seq-area"></span>]
				<a href="javascript:void(0);" class="mi-item-up">[↑]</a>
				<a href="javascript:void(0);" class="mi-item-down">[↓]</a>
				<a href="javascript:void(0);" class="mi-item-indent">[→]</a>
				<a href="javascript:void(0);" class="mi-item-unindent">[←]</a>
				<a href="javascript:void(0);" class="mi-item-remove">[削除]</a>
				<span class="mi-item-grip">[移動]</span>
				
				{{input type="checkbox" name="c[Entry.tasks][<%=id%>][done]" value="1"}}
				{{input type="text" name="c[Entry.tasks][<%=id%>][label]" class="mi-item-keybind"}}
				</select>
			</li>
		</script>
		
		<a href="javascript:void(0);" class="mi-append">[新規登録]</a>
		<ul class="mi-root">
		
			{{foreach $c->input("Entry.tasks")|to_array as $i=>$v}}
			
			{{for $u= $prev_rank to $v.rank-1}} <ul> {{/for}}
			{{for $u= $v.rank to $prev_rank-1}} </ul> {{/for}}
			{{$prev_rank=$v.rank}}
			
			<li class="mi-item" id="list_{{$i}}">
				<input type="hidden" name="c[Entry.tasks][{{$i}}][id]" value="{{$i}}" class="mi-item-id"/>
				<input type="hidden" name="c[Entry.tasks][{{$i}}][pid]" class="mi-item-pid" value="{{$v.pid}}"/>
				<input type="hidden" name="c[Entry.tasks][{{$i}}][rank]" class="mi-item-rank" value="{{$v.rank}}"/>
				<input type="hidden" name="c[Entry.tasks][{{$i}}][seq]" class="mi-item-seq" value="{{$v.seq}}"/>
				
				[ID={{$i}}]
				[SEQ=<span class="mi-item-seq-area"></span>]
				<a href="javascript:void(0);" class="mi-item-up">[↑]</a>
				<a href="javascript:void(0);" class="mi-item-down">[↓]</a>
				<a href="javascript:void(0);" class="mi-item-indent">[→]</a>
				<a href="javascript:void(0);" class="mi-item-unindent">[←]</a>
				<a href="javascript:void(0);" class="mi-item-remove">[削除]</a>
				<span class="mi-item-grip">[移動]</span>
				
				{{input type="checkbox" name="c[Entry.tasks][`$i`][done]" value="1" checked=$v.done}}
				{{input type="text" name="c[Entry.tasks][`$i`][label]" class="mi-item-keybind" value=$v.label}}
			{{/foreach}}
			
			<li class="mi-anchor" style="display:none;"></li>
		</ul>
	</div>
	
-------------------------------------
■サンプル キー操作プラグインの利用：

	<!-- 【mi Keybind】 -->
	<script charset="UTF-8" src="{{'/js/rui/jquery.mi/jquery.mi-keybind.js'|path_to_url}}"></script>
	<style>
		.mi-item-active > .mi-item-keybind {
			background-color: yellow;
		}
	</style>

	<!-- <div class="mi mi-keybind"> ... </div> -->
	<!-- ... {{input type="text" name="c[Entry.tasks][<%=id%>][label]" class="mi-item-keybind"}} ... -->
			
-------------------------------------
■サンプル ドラッグアンドドロップの利用：

	<!-- 【mi Draggable】 -->
	<script charset="UTF-8" src="{{'/js/jquery.ui/jquery.ui.js'|path_to_url}}"></script>
	<script charset="UTF-8" src="{{'/js/jquery.ui/jquery.ui.nestedSortable.js'|path_to_url}}"></script>
	<script>
	MIObserver.extend(function(o, $mi){
		$mi.find(".mi-root").nestedSortable({
			listType: 'ul',
			items: '.mi-item',
			handle: '.mi-item-grip',
			maxLevels: 10,
			
			forcePlaceholderSize: true,
			helper:	'clone',
			opacity: 0.6,
			isTree: true,
			expandOnHover: 700,
			startCollapsed: true
		});
	});
	</script>

-------------------------------------
■サンプル miの拡張、編集検知の例：

	MIObserver.extend(function(o, $mi){
	
		$mi.on("miChange",function(e, action, $item, $targetItem, $target) {
		
			console.log([action,$item.attr("id"),$targetItem&&$targetItem.attr("id")]);
		});
	});
	
*/

(function($){
	
	//-------------------------------------
	// 可変数フォームを構築するクラス
	window.MIObserver =function ($mi) {
		
		var o =this;
		
		//-------------------------------------
		// 設定
		o.config ={};
		
		//-------------------------------------
		// 初期化処理
		o.init =function(){
			
			// 拡張機能の読み込み
			$.each(MIObserver.extensions,function(){
			
				this(o,$mi);
			});
		
			// テンプレートの取り込み
			o.config.tmpl =_.template($mi.find(".mi-tmpl").eq(0).html());
			
			// パラメータのhidden値の取得
			o.config.minItems =$mi.find(".mi-config-minItems",$mi).val() || 0;
			o.config.maxItems =$mi.find(".mi-config-maxItems",$mi).val() || 9999;
			o.config.maxRank =$mi.find(".mi-config-maxRank",$mi).val() || 9999;
			o.config.subNodeSelector =$mi.find(".mi-config-subNodeSelector",$mi).val() || "ul";
			o.config.subNodeHtml =$mi.find(".mi-config-subNodeHtml",$mi).val() || "<ul></ul>";
			
			//-------------------------------------
			// 追加ボタンの操作
			$mi.find(".mi-append").on("click",function(){
				
				o.appendItem();
			});
				
			//-------------------------------------
			// 既存の各要素について初期化処理
			$mi.find(".mi-item").each(function(){
				
				o.initItem($(this));
			});
			
			$mi.trigger("miInit");
			
			o.update();
				
			//-------------------------------------
			// 不足している要素の追加
			while ($mi.find(".mi-item").length < o.config.minItems) { 
				
				o.appendItem();
			}
		};
		
		//-------------------------------------
		// コントロールの表示更新
		o.update =function(){
		
			// 要素数が上限であれば追加ボタンを隠す
			if ($mi.find(".mi-item").length >= o.config.maxItems) { 
				
				$mi.find(".mi-append").hide();
				
			} else {
			
				$mi.find(".mi-append").show();
			}
			
			// 要素数が下限であれば削除ボタンを隠す
			if ($mi.find(".mi-item").length <= o.config.minItems) {
				
				$mi.find(".mi-remove").hide();
				
			} else {
			
				$mi.find(".mi-remove").show();
			}
			
			// 要素の連番表示の更新
			var seq =1;
			
			$mi.find(".mi-item").each(function(){
				
				$(this).find(".mi-item-seq").val(seq);
				$(this).find(".mi-item-seq-area").html(seq);
				seq++;
			});
			
			// 親子構造の更新
			var stack =[];
			
			$mi.find(".mi-item").each(function(){
				
				var id =$(this).find(".mi-item-id").val();
				var rank =o.getRank($(this));
				var pid =rank>0 && stack[rank-1] ? stack[rank-1] : 0;
				
				stack[rank] =id;
				
				$(this).find(".mi-item-pid").val(pid);
				$(this).find(".mi-item-rank").val(rank);
			});
			
			$mi.trigger("miUpdate");
		};
		
		//-------------------------------------
		// 要素の追加
		o.appendItem =function($anchorItem){
		
			// 要素数が上限であれば追加拒否
			if ($mi.find(".mi-item").length >= o.config.maxItems) { 
				
				return; 
			}
			
			// テンプレートHTMLにIDを設定して新しい要素を生成
			var $item =$(o.config.tmpl({ id: o.getNextId() }));
			
			o.initItem($item);
			
			$mi.trigger("miChange",["append",$item,$anchorItem]);
			
			$anchorItem
					? $anchorItem.after($item)
					: $(".mi-anchor",$mi).before($item);
			
			o.update();
			
			return $item;
		};
		
		//-------------------------------------
		// 要素の初期化
		o.initItem =function($item){
			
			//-------------------------------------
			// フォームの編集
			$item.find("input,select,textarea").on("change",function(e){
				
				$mi.trigger("miChange",["edit",$(this).closest(".mi-item"), undefined, $(this)]);
			
				e.stopPropagation();
			});
			
			//-------------------------------------
			// 削除ボタンの操作
			$item.find(".mi-item-remove").on("click",function(){
				
				o.removeItem($item);
			});
		
			//-------------------------------------
			// 上と入れ替えるボタンの操作
			$item.find(".mi-item-up").on("click",function(){
				
				o.swapUpItem($item);
			});
		
			//-------------------------------------
			// 下と入れ替えるボタンの操作
			$item.find(".mi-item-down").on("click",function(){
				
				o.swapDownItem($item);
			});
		
			//-------------------------------------
			// 右にインデントボタンの操作
			$item.find(".mi-item-indent").on("click",function(){
				
				o.indentItem($item);
			});
		
			//-------------------------------------
			// 左にインデント解除ボタンの操作
			$item.find(".mi-item-unindent").on("click",function(){
				
				o.unindentItem($item);
			});
			
			$mi.trigger("miInitItem",[$item]);
		};
		
		//-------------------------------------
		// 要素の削除
		o.removeItem =function($item){
			
			// 要素数が下限であれば削除拒否
			if ($mi.find(".mi-item").length <= o.config.minItems) { 
				
				return;
			}
			
			// 子要素を下げる
			$item.children(o.config.subNodeSelector)
					.children(".mi-item").each(function(){
				
				o.unindentItem($(this));
			});
			
			$mi.trigger("miChange",["remove",$item]);
			
			$item.remove();
			
			o.update();
		};
		
		//-------------------------------------
		// 要素を一つ上に移動
		o.swapUpItem =function($item){
				
			// 入れ替える前の要素がなければ拒否
			if ( ! $item.prev().hasClass("mi-item")) {
				
				// インデントされている場合は解除して再検証
				o.unindentItem($item);
				
				if ( ! $item.prev().hasClass("mi-item")) {
					
					return;
				}
			}
			
			$mi.trigger("miChange",["swapUp",$item,$item.prev()]);
			
			$item.after($item.prev());
				
			o.update();
		};
		
		//-------------------------------------
		// 要素を一つ下に移動
		o.swapDownItem =function($item){
				
			// 入れ替える次の要素がなければ拒否
			if ( ! $item.next().hasClass("mi-item")) {
				
				// インデントされている場合は解除して再検証
				o.unindentItem($item);
				
				if ( ! $item.next().hasClass("mi-item")) {
				
					return;
				}
			}
			
			$mi.trigger("miChange",["swapDown",$item,$item.next()]);
			
			$item.before($item.next());
			
			o.update();
		};
		
		//-------------------------------------
		// 要素のインデント
		o.indentItem =function($item){
			
			// 前に親になる要素がなければ拒否
			if ( ! $item.prev().hasClass("mi-item")) {
				
				return;
			}
			
			// 最大ランクチェック
			var rank =o.getRank($item);
			
			$item.find(".mi-item").each(function(){
				
				var childRank =o.getRank($(this));
				
				if (rank < childRank) {
					
					rank =childRank;
				}
			});
			
			// ランク制限に該当する場合は拒否
			if (rank >= o.config.maxRank) {
			
				return;
			}
						
			var $container =$item.prev().children(o.config.subNodeSelector);
			
			// コンテナがなければ作成
			if ( ! $container.length) {
				
				$item.prev().append(o.config.subNodeHtml);
				
				$container =$item.prev().children(o.config.subNodeSelector);
			}
			
			$mi.trigger("miChange",["indent",$item,$item.prev()]);
			
			$item.appendTo($container);
			
			o.update();
		};
		
		//-------------------------------------
		// 要素のインデント解除
		o.unindentItem =function($item){
			
			var $container =$item.parent(o.config.subNodeSelector);
			
			// 親がなければ拒否
			if ( ! $container.parent().hasClass("mi-item")) {
				
				return;
			}
			
			$mi.trigger("miChange",["unindent",$item,$container.parent()]);
			
			$item.insertAfter($container.parent());
			
			// 子要素が全て抜けたコンテナは削除
			if ( ! $container.children(".mi-item").length) {
				
				$container.remove();
			}
			
			o.update();
		};
		
		//-------------------------------------
		// 次に生成する要素のIDの取得
		o.getNextId =function(){
			
			var nextId =0;
			
			$mi.find(".mi-item-id").each(function(){
				
				if (nextId <= $(this).val()) {
					
					nextId =$(this).val();
				}
			});
			
			return 1*nextId+1;
		};
		
		//-------------------------------------
		// 親要素を取得
		o.getParentItem =function ($item) {
			
			if ($item.parent().parent().hasClass("mi-item")) {
				
				return $item.parent().parent();
			}
			
			return null;
		};
		
		//-------------------------------------
		// ランクを取得
		o.getRank =function ($item) {
			
			return $item.parents(".mi-item")
					.filter("#"+$mi.attr("id")+" .mi-item").length;
		};
		
		//-------------------------------------
		// 順序的に前に位置する要素を取得
		o.getPrevItem =function ($item) {
			
			var $resultItem =null;
			var $prevItem =null;
			
			$mi.find(".mi-item").each(function(){
				
				var $currnetItem =$(this);
				
				if ($currnetItem.attr("id") == $item.attr("id") && $resultItem == null) {
				
					$resultItem =$prevItem;
				}
				
				$prevItem =$currnetItem;
			});
			
			return $resultItem;
		};
		
		//-------------------------------------
		// 順序的に次に位置する要素を取得
		o.getNextItem =function ($item) {
			
			var $resultItem =null;
			var isFound =false;
			
			$mi.find(".mi-item").each(function(){
				
				var $currnetItem =$(this);
				
				if (isFound && $resultItem == null) {
					
					$resultItem =$currnetItem;
				}
				
				isFound =$currnetItem.attr("id") == $item.attr("id");
			});
			
			return $resultItem;
		};
	};
	
	MIObserver.extensions =[];
	
	//-------------------------------------
	// 拡張機能の登録
	MIObserver.extend =function (extension) {
		
		MIObserver.extensions.push(extension);
	};
	
	// /MIObserver定義
	//-------------------------------------
	
	var miList ={};
	
	//-------------------------------------
	// jQueryを拡張して$(...).mi()を有効にする
	$.fn.extend({
	
		mi: function() {
			
			// 初期化済みの場合
			if ($(this).attr("id") && miList[$(this).attr("id")]) {
				
				return miList[$(this).attr("id")];
			}
			
			// 初期化処理
			if ( ! $(this).attr("id")) {
				
				var newId ="mi"+parseInt(Math.random()*10000000);
				$(this).attr("id",newId);
			}
			
			var mi =new MIObserver(this);
			
			mi.init();
			
			miList[$(this).attr("id")] =mi;
			
			return mi;
		}
	});

	//-------------------------------------
	// .miについて自動的に初期化
	$(function(){
		
		$(window).trigger("miBeforeApply");
		
		$(".mi").each(function(){
		
			$(this).mi();
		});
	});
	
})(jQuery);