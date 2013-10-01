<?php

	//-------------------------------------
	// URLの組み立て
	function url ($base_url=null, $params=null, $anchor=null) {
		
		$url =null;
		
		if (is_string($base_url)) {
			
			$url .=$base_url;
		}
		
		// URL内パラメータの解決
		$ptn_url_param ='!^([^\[\?]*\[[^\[\?]+\][^\?]*)(\?.*)?$!';
		
		if (preg_match($ptn_url_param,$url,$match)) {
			
			list(,$url,$qs) =$match;
			
			$tmp_url_params =& ref_globals("tmp_url_params");
			$tmp_url_params =$params;
			
			$url =preg_replace_callback('!\[([^\]]+)\]!',"url_param_replace",$url);
			
			$params =$tmp_url_params;
			
			$url .=$qs;
			
			if (preg_match($ptn_url_param,$url)) {
				
				report_warning("URL params wasnot resolved",array(
					"url" =>$url,
					"base_url" =>$base_url,
					"params" =>$params,
				));
			}
		}
		
		if ($params) {
			
			if ($url !== null) {
				
				$url .=strpos($url,'?')===false ? '?' : '&';
			}
			
			if (is_string($params)) {
			
				$url .=$params;
			
			} elseif (is_array($params)) {
				
				$url .=http_build_query($params,null,'&');
			}
		}
		
		if (strlen($anchor)) {
			
			$url .='#'.$anchor;
		}
		
		return $url;
	}
	
	//-------------------------------------
	// URL内パラメータの置換処理
	function url_param_replace ($match) {
		
		$replaced =$match[0];
		
		$tmp_url_params =& ref_globals("tmp_url_params");
		
		if (isset($tmp_url_params[$match[1]])) {
			
			$replaced =$tmp_url_params[$match[1]];
			unset($tmp_url_params[$match[1]]);
		}
		
		return $replaced;
	}
	
	
	//-------------------------------------
	// HTMLタグの組み立て
	function tag ($name, $attrs=null, $content=null) {
		
		$html ='';
		
		if ( ! is_string($name)) {
			
			return htmlspecialchars($name);
		}
		
		$html .='<'.$name.' ';
		
		if ($attrs === null) {
		
		} elseif (is_string($attrs)) {
			
			$html .=$attrs.' ';
			report_warning("HTMLタグのattrsは配列で指定してください");
			
		} elseif (is_array($attrs)) {
			
			foreach ($attrs as $k => $v) {
				
				if ($v !== null) {
				
					if (is_numeric($k)) {
						
						$html .=$v.' ';
					
					} else {
						
						if (($name == "input" || $name == "textarea" 
								|| $name == "select") && $k == "name") {
							
							$v =param_name($v);
						
						} elseif (is_array($v)) {
							
							if ($k == "style") {
								
								$style =array();
								
								foreach ($v as $style_name => $style_attr) {
									
									if (is_numeric($style_name)) {
										
										$style .=$style_attr;
									
									} else {
									
										$style .=$style_name.':'.$style_attr.';';
									}
								}
								
								$v =$style;
								
							} elseif ($k == "class") {
								
								$v =implode(' ',$v);
								
							} else {
								
								$v =implode(',',$v);
							}
						}
						
						$v =str_replace(array("\r\n","\n",'"'),array(" "," ",'&quot;'),$v);
						$html .=param_name($k).'="'.$v.'" ';
					}
				}
			}
		}
		
		if ($content === null) {
			
			$html .='/>';
			
		} elseif ($content === true) {
			
			$html .='>';
			
		} elseif ($content === false) {
			
			$html ='</'.$name.'>';
			
		} elseif (is_array($content)) {
			
			$html .='>';
			
			foreach ($content as $k => $v) {
				
				$html .=call_user_func_array("tag",(array)$v);
			}
			
			$html .='</'.$name.'>';
			
		} elseif (is_string($content)) {
			
			$html .='>';
			$html .=$content;
			$html .='</'.$name.'>';
		}
		
		return $html;
	}
	
	//-------------------------------------
	// URL上でのパラメータ名の配列表現の正規化
	function param_name ($param_name) {
		
		if (preg_match('!^([^\[]+\.[^\[]+)([\[].*?)?!',$param_name,$match)) {
			
			$stack =explode(".",$match[1]);
			$param_name =array_shift($stack)."[".implode("][",$stack)."]".$match[2];
		}
		
		return $param_name;
	}
	
	//-------------------------------------
	// 入力値の正規化
	function sanitize ($value) {
	
		if (is_array($value)) {
		
			foreach ($value as $k => $v) {
			
				$value[$k] =sanitize($v);
			}
			
		} else {
			
			$value =str_replace(
					array('&','<','>'),
					array('&amp;','&lt;','&gt;'),
					$value);
		}
		
		return $value;
	}
	
	//-------------------------------------
	// 入力値の逆正規化
	function sanitize_decode ($value) {
	
		if (is_array($value)) {
		
			foreach ($value as $k => $v) {
			
				$value[$k] =sanitize_decode($v);
			}
			
		} else {
			
			$value =str_replace(
					array('&amp;','&lt;','&gt;'),
					array('&','<','>'),
					$value);
		}
		
		return $value;
	}