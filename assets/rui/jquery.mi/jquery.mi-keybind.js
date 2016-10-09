
(function($){
	
	MIObserver.extend(function(o, $mi){
		
		if ( ! $mi.hasClass("mi-keybind")) {
			
			return;
		}
		
		// キーコードの定義
		var KEY ={ UP:38, DOWN:40, TAB:9, BS:8, DEL:46, ENTER:13 };
	
		//-------------------------------------
		// キーコードの解決
		var getKeyCode =function (e) {
		
			return document.all ? e.keyCode :
					document.getElementById ? (e.keyCode ? e.keyCode: e.charCode) :
					document.layers ? e.which : 0;
		};
		
		//-------------------------------------
		// 選択中の要素への操作
		var miContorolActiveItem =function (action) {
			
			var $item =$mi.find(".mi-item-active");
			var $focusTarget =$mi.find(".mi-item-keybind-active");
			
			// インデントダウン
			if (action == "unindent") {
				
				o.unindentItem($item);
				
				$focusTarget.focus();
				
			// インデントアップ
			} else if (action == "indent") {
				
				o.indentItem($item);
				
				$focusTarget.focus();
			
			// 要素を後ろに追加
			} else if (action == "append") {
				
				var $newItem =o.appendItem($item);
				
				$newItem.find(".mi-item-keybind").focus();
				
			// 要素を1つ上に移動
			} else if (action == "swapUp") {
				
				o.swapUpItem($item);
				
				$focusTarget.focus();
				
			// 要素を1つ下に移動
			} else if (action == "swapDown") {
				
				o.swapDownItem($item);
				
				$focusTarget.focus();
				
			// フォーカスを1つ上に移動
			} else if (action == "focusUp") {
				
				var $targetItem =o.getPrevItem($item);
				
				if ($targetItem) {
				
					$targetItem.find(".mi-item-keybind").eq(0).focus();
				}
				
			// フォーカスを1つ下に移動
			} else if (action == "focusDown") {
				
				var $targetItem =o.getNextItem($item);
				
				if ($targetItem) {
				
					$targetItem.find(".mi-item-keybind").eq(0).focus();
				}
				
			// 削除してフォーカスを1つ上に移動
			} else if (action == "deleteUp") {
				
				var $targetItem =o.getPrevItem($item);
				
				if ($targetItem) {
				
					o.removeItem($item);
				
					$targetItem.find(".mi-item-keybind").eq(0).focus();
				}
				
			// 削除してフォーカスを1つ下に移動
			} else if (action == "deleteDown") {
				
				var $targetItem =o.getNextItem($item);
				
				if ($targetItem) {
				
					o.removeItem($item);
				
					$targetItem.find(".mi-item-keybind").eq(0).focus();
				}
			}
		};
		
		//-------------------------------------
		// 要素の選択
		var miActivateItem =function ($item) {
			
			$mi.find(".mi-item.mi-item-active").removeClass("mi-item-active");
			$item.addClass("mi-item-active");
			
			$mi.find(".mi-item-keybind").removeClass("mi-item-keybind-active");
			$item.find(".mi-item-keybind").eq(0).addClass("mi-item-keybind-active");
		};
	
		//-------------------------------------
		// Enter/BSによる遷移停止
		$mi.on("keypress",function(e){
			
			if (getKeyCode(e) == KEY.ENTER || getKeyCode(e) == KEY.BS) {
				
				e.preventDefault();
				e.stopPropagation();
			}
		});
		
		// ※複数要素にまたがるキー操作があるので一貫した間引きが必要
		var onKeydown =_.debounce(function(e){
			
			var action =null;
			
			// Shift+TAB → インデントダウン
			if (getKeyCode(e) == KEY.TAB && e.shiftKey) { action ="unindent"; }
			
			// TAB → インデントアップ
			if (getKeyCode(e) == KEY.TAB && ! e.shiftKey) { action ="indent"; }
			
			// Enter → 要素を後ろに追加
			if (getKeyCode(e) == KEY.ENTER) { action ="append"; }
				
			// Ctrl+Up → 要素を1つ上に移動
			if (getKeyCode(e) == KEY.UP && e.ctrlKey) { action ="swapUp"; }
				
			// Ctrl+Down → 要素を1つ下に移動
			if (getKeyCode(e) == KEY.DOWN && e.ctrlKey) { action ="swapDown"; }
				
			// Up → フォーカスを1つ上に移動
			if (getKeyCode(e) == KEY.UP && ! e.ctrlKey) { action ="focusUp"; }
				
			// Down → フォーカスを1つ下に移動
			if (getKeyCode(e) == KEY.DOWN && ! e.ctrlKey) { action ="focusDown"; }
				
			// Ctrl(or blank)+Backspace → 削除
			if (getKeyCode(e) == KEY.BS && ($(this).val() == "" || e.ctrlKey)) { action ="deleteUp"; }
				
			// Ctrl(or blank)+Delete → 削除
			if (getKeyCode(e) == KEY.DEL && ($(this).val() == "" || e.ctrlKey)) { action ="deleteDown"; }
			
			if (action) {
				
				miContorolActiveItem(action);
				
				e.stopPropagation();
				e.preventDefault();
			}
		},1);
			
		//-------------------------------------
		// 要素の初期化時の処理
		$mi.on("miInitItem",function(e, $item){
			
			// オートコンプリート停止
			$item.find(".mi-item-keybind").attr("autocomplete","off");
			
			//-------------------------------------
			// 主要素へのFocusによる選択
			$item.find(".mi-item-keybind, input, select").on("focus",function(){
				
				miActivateItem($(this).closest(".mi-item"));
			});
	
			//-------------------------------------
			// 要素のキー操作
			$item.find(".mi-item-keybind").on("keydown",onKeydown);
		});
	});
	
})(jQuery);