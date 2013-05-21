
	rui.require_js("jquery.datepick/jquery.datepick.min.js");
	rui.require_js("jquery.datepick/jquery.datepick-ja.js");
	rui.require_css("jquery.datepick/jquery.datepick.css");
	
	//-------------------------------------
	// 日付選択UI
	window.rui.Datepick ={};
	
	//-------------------------------------
	// テキストボックスに日付選択UIを付加
	window.rui.Datepick.impl_dateselect =function (elm_id, options) {
		
		var dates ={};
		
		$("select",elm_id).each(function(){
		
			if ($(this).attr("name").match(/\[(\w+)\]$/)) {
			
				dates[RegExp.$1] =$(this);
			}
		});
				
		var $input =$("<input/>").attr({type:"hidden"});
		$(elm_id).after($input);
		options.yearRange
		$input.datepick({
			showTrigger: '<button type="button">...</button>',
			onSelect: function (date_str) {
				var date =new Date(""+date_str);
				var  changed =false;
				if (dates["y"]) { $(dates["y"]).val(date.getFullYear()); changed ="y"; }
				if (dates["m"]) { $(dates["m"]).val(date.getMonth()+1); changed ="m"; }
				if (dates["d"]) { $(dates["d"]).val(date.getDate()); changed ="d"; }
				if (changed) { dates[changed].trigger("change"); }
			},
			yearRange: options.yearRange,
			shortYearCutoff: 99999
		});	
	};