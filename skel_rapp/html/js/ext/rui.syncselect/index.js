/*

■SAMPLE1 静的関連付け：
	<select name="c[Comment.parent]" id="_parent_elm_id">
		<option value="">--</option>
		<option value="1">1</option>
		<option value="2">2</option>
	</select>
	<select name="c[Comment.someitem]" id="_elm_id">
		<option value="">--</option>
		<option value="C121">C121</option>
		<option value="C122">C122</option>
		<option value="C111">C111</option>
		<option value="C112">C112</option>
	</select>
	<script>
		rui.require_pkg("rui.syncselect",function(){ 
			rui.syncselect('_elm_id', '_parent_elm_id',
					{"C121":"1", "C122":"1","C111":"2", "C112":"2"},"select");
		});
	</script>
	
■SAMPLE2 AJAXによる動的関連付け：
	<select name="c[Comment.parent]" id="_parent_elm_id">
		<option value="">--</option>
		<option value="1">1</option>
		<option value="2">2</option>
	</select>
	<select name="c[Comment.someitem]" id="_elm_id">
		<option value="">--</option>
	</select>
	<script>
		rui.require_pkg("rui.syncselect",function(){ 
			rui.syncselect('_elm_id', '_parent_elm_id',
					"/get_list_json.html?","select");
		});
	</script>


*/
	
	//-------------------------------------
	// 親のセレクト要素に連動させる
	window.rui.syncselect =function (elm_id, parent_elm_id, pair, mode) {

		$(function(){
			
			// 子の要素をValueでまとめて記録しておく
			ssp_values[elm_id] ={};
			
			// Selectの場合、子Optionを記録
			if ($("#"+elm_id).get(0).tagName.match(/select/i)) {	
			
				$("#"+elm_id+" option").each(function(){
					
					var value =$(this).attr("value");
					ssp_values[elm_id][value] =$(this);
				});
				
			// RadioまたはCheckboxならば子Input要素を記録
			} else {
			
				$("#"+elm_id+" ._listitem").each(function(){
					
					var value =$("input[type!='hidden']",this).attr("value");
					ssp_values[elm_id][value] =$(this);
				});
			}
			
			// pairが配列である場合静的関連付け
			var static_pair_ref =function(){
			
				var elm =$("#"+elm_id);
				var parent_item_id =$("#"+parent_elm_id).val();
				var current_item_id =$(elm).val();
				
				// 子要素を一旦全て削除
				$(elm).children().remove();
				
				// あらかじめ記録していた子要素の一覧を処理
				for (var item_id in ssp_values[elm_id]) {
					
					// 複数の親に関連づけるよう配列を正規化
					var pair_items =typeof pair[item_id] == "object"
							? pair[item_id]
							: {0:pair[item_id]}
					
					// 子要素に対応する全ての親要素について処理
					for (var pair_id in pair_items) {
						
						// Valueが空白の子要素は必ず先頭に追加
						if (item_id == "") {
							
							$(elm).prepend(ssp_values[elm_id][item_id]);
							
						// 選択中の親に関連付く子要素を下に追加していく
						} else if (parent_item_id == pair_items[pair_id]) {
							
							$(elm).append(ssp_values[elm_id][item_id]);
						}
					}
				}
				
				// 子の選択状態をもとに戻す
				$(elm).val(current_item_id);
				
				// 子の選択要素の変化時の処理を実行
				$(elm).trigger("change");
			};
			
			// pairがURLである場合の動関連付け
			var ajax_pair_ref =function(){
				
				var elm =$("#"+elm_id);
				var current_item_id =$(elm).val();
				var parent_item_id =$("#"+parent_elm_id).val();
				
				// GET通信でPAIRのJSONデータ取得
				$.ajax({
					url : pair,
					data : {"parent" : parent_item_id},
					type : "GET",
					async : true,
					success : function(data){
					
						// JSONデータを解析
						var json =$.parseJSON(data);
						var pair_elms ={};
						
						for (var i in json) {
						
							pair_elms[i] ='<option value="'+i+'">'+json[i]+'</option>';
						}
						
						// 空白以外の子要素を一旦全て削除
						$(elm).children('[value!=""]').remove();
						
						for (var i in pair_elms) {
							
							// 選択中の親に関連付く子要素を下に追加していく
							$(elm).append(pair_elms[i]);
						}
						
						// 子の選択状態をもとに戻す
						$(elm).val(current_item_id);
						
						// 子の選択要素の変化時の処理を実行
						$(elm).trigger("change");
					}
				});
			};
			
			var onchange_parent =typeof pair=="object" 
					? static_pair_ref
					: ajax_pair_ref;
					
			// 親の選択要素の変化時の処理を登録
			$("#"+parent_elm_id).bind("change",onchange_parent);
			
			// 親の選択要素の変化時の処理を実行
			$("#"+parent_elm_id).trigger("change");
		});
	};
	window.ssp_values ={};