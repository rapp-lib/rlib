	
	//-------------------------------------
	// 和暦に対応する
	window.rui.Japcal ={};
	
	window.rui.Japcal.master ={
		"M" : [1867, 44, "明治"],
		"T" : [1911, 14, "大正"],
		"S" : [1925, 63, "昭和"],
		"H" : [1988, 100, "平成"]
	};
	
	//-------------------------------------
	// 年の選択肢を和暦に変換する
	window.rui.Japcal.year_input_exchange =function (inputSet) {
		
		// 年の入力を取得
		var $year =$(inputSet).find("select").filter(function(){
			
			return $(this).attr("name").match(/\[y\]$/);
		});
		
		// 年号の選択肢組み立て
		var $era =$('<select></select>')
				.addClass("japcal-era");
		
		$.each(rui.Japcal.master,function(k,v){
			$era.append($('<option>').html(v[2]).val(k));
		});
		
		// 和暦年の選択肢組み立て
		var $jpyear =$('<select></select>')
				.addClass("japcal-jpyear")
				.append($('<option>').html('').val(''));
		
		// 年号に対応する和暦の選択肢を初期化する処理
		var refreshJpyear =function(){
			
			var selectedEra =$era.val();
			var offset =rui.Japcal.master[selectedEra][0];
			var expire =rui.Japcal.master[selectedEra][1];
			
			$jpyear.children().remove();
			$jpyear.append($('<option>').html("").val(""));
			
			for (var i=1; i<=expire; i++) {
			
				if ($year.find("option[value='"+(offset+i)+"']").length > 0) {
				
					$jpyear.append($('<option>').html(i+"年").val(i));
				}
			}
		};
		
		// 和暦の入力を西暦の入力項目に同期する処理
		var syncToYear =function(){
			
			var selectedEra =$era.val();
			var selectedJpyear =$jpyear.val();
			
			var offset =rui.Japcal.master[selectedEra][0];
			
			$year.val(offset+parseInt(selectedJpyear));
			$year.trigger("change");
		}
		
		// 西暦の入力項目から和暦の入力に同期する処理
		var syncFromYear =function(){
			
			var selectedYear =$year.val();
			
			if (selectedYear == "") {
				
				$jpyear.val("");
				return;
			}
			
			for (var i in rui.Japcal.master) {
				
				var offset =rui.Japcal.master[i][0];
				var expire =rui.Japcal.master[i][1];
				
				if (offset <= selectedYear &&
						offset+expire > selectedYear) {
					
					$era.val(i);
					refreshJpyear();
					
					$jpyear.val(selectedYear-offset);
					return;
				}
			}
		}
		
		$era.on("change",refreshJpyear);
		$era.on("change",syncToYear);
		$jpyear.on("change",syncToYear);
		$year.on("refresh",syncFromYear);
		
		// 入力項目の差し替え
		$year.after($jpyear);
		$year.after($era);
		$year.hide();
		
		refreshJpyear();
		syncFromYear();
	};