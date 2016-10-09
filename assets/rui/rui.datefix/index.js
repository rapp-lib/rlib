	
	//-------------------------------------
	// 日付の誤りを訂正する 
	window.rui.Datefix ={};
	
	//-------------------------------------
	// dateselectの訂正
	window.rui.Datefix.fix_dateselect =function (elm_id) {
		
		var $selects =$("select",elm_id);
		var dates ={};
		
		$selects.each(function(){
		
			if ($(this).attr("name").match(/\[(\w+)\]$/)) {
			
				dates[RegExp.$1] =$(this);
			}
		});
		
		$selects.bind("change",function(){
			
			// 一つでも空白ならば全て空白とする
			if ($(this).val() == "") {
			
				$selects.val("");
			
			} else {
			
				// 空白以外が選択されていたら、空白は初期値で埋める
				$selects.each(function(){
					if ($(this).val() == "") {
						$(this).val($("option",this).eq(1).attr("value"));
					}
				});
				
				// 選択された日付が不正ならば1日に戻す
				if (dates["y"] && dates["m"] && dates["d"]) {
				
					var valid =rui.Datefix.check_date($(dates["y"]).val(),
							$(dates["m"]).val(),$(dates["d"]).val());
							
					if ( ! valid) {
					
						$(dates["d"]).val($("option",this).eq(1).attr("value"));
					}
				}
			}
			
			$selects.trigger("refresh");
		});
	};
	
	//-------------------------------------
	// datetextの訂正
	window.rui.Datefix.fix_datetext =function (elm_id) {
	
		var $elm =$(elm_id);
		
		$elm.bind("change",function(){
		
			if ($(this).val().match(/(\d+)[\/-](\d+)[\/-](\d+)/)) {
				
				var y =RegExp.$1*1;
				var m =RegExp.$2*1;
				var d =RegExp.$3*1;
				
				if (y<50) { y +=2000; } 
				else if (y<100) { y +=1900; }
				if (m<10) { m ="0"+m; } 
				if (d<10) { d ="0"+d; } 
				
				// 正常な日付
				if (rui.Datefix.check_date(y,m,d)) {
				
					$(this).val(y+"/"+m+"/"+d);
					return true;
					
				// 不正な日付だが1日は存在する
				} else if (rui.Datefix.check_date(y,m,1)) {
					
					$(this).val(y+"/"+m+"/01");
					return true;
					
				// 不正な日付だが1月1日は存在する
				} else if (rui.Datefix.check_date(y,1,1)) {
					
					$(this).val(y+"/01/01");
					return true;
				}
			}
			
			$(this).val("");
			return false;
		});
	};
	
	window.rui.Datefix.check_date =function (y,m,d) {
	
		var date =new Date(y,m-1,d);
		var valid =date.getFullYear() == y 
				&& date.getMonth() == m-1
				&& date.getDate() == d;
				
		return valid;
	};