<?php
/*
	2016/07/06 
		core/html.php内の全関数の移行完了
		sanitize/sanitize\decodeはStringに移行
 */

namespace R\Lib\Core;

/**
 * 
 */
class Html {

	/**
	 * 正規URLの組み立て処理
	 * @param  [type] $base_url [description]
	 * @param  array  $params   [description]
	 * @param  [type] $anchor   [description]
	 * @return [type]           [description]
	 */
	public static function url ($base_url=null, $params=array(), $anchor=null) {
		
		$url =$base_url;
		
        // 文字列形式のURLパラメータを配列に変換
        if (is_string($params)) {
            
			parse_str($params,$tmp_params);
            $params =$tmp_params;
        }
        
		// アンカーの退避
		if (preg_match('!^(.*?)#(.*)$!',$url,$match)) {
			
			list(,$url,$old_anchor) =$match;
			
			if ($anchor === null) {
				
				$anchor =$old_anchor;
			}
		}
		
		// QSの退避
		if (preg_match('!^([^\?]+)\?(.*)!',$url,$match)) {
			
			list(,$url,$qs) =$match;
			parse_str($qs,$qs_params);
            $params =array_replace_recursive($qs_params, $params);
		}
		
		// URLパス内パラメータの置換
		$ptn_url_param ='!\[([^\]]+)\]!';
		
		if (preg_match($ptn_url_param,$url)) {
			
			$tmp_url_params =& ref_globals("tmp_url_params");
			$tmp_url_params =$params;
			$url =preg_replace_callback($ptn_url_param,"url_param_replace",$url);
			$params =$tmp_url_params;
			
            // 置換漏れの確認
			if (preg_match($ptn_url_param,$url)) {
				
				report_warning("URL params was-not resolved, remain",array(
					"url" =>$url,
					"base_url" =>$base_url,
					"params" =>$params,
				));
			}
		}
		
        // QSの設定
		if ($params) {
			
            self::urlParamKsortRecursive($params);
            $url .=strpos($url,'?')===false ? '?' : '&';
            $url .=http_build_query($params,null,'&');
		}
		
        // アンカーの設定
		if (strlen($anchor)) {
			
			$url .='#'.$anchor;
		}
		
		return $url;
	}
	
	/**
	 * URL内パラメータの置換処理
	 * @param  [type] $match [description]
	 * @return [type]        [description]
	 */
	public static function urlParamReplace ($match) {
		
		$replaced =$match[0];
		
		$tmp_url_params =& ref_globals("tmp_url_params");
		
		if (isset($tmp_url_params[$match[1]])) {
			
			$replaced =$tmp_url_params[$match[1]];
			unset($tmp_url_params[$match[1]]);
		}
		
		return $replaced;
	}
	
	/**
	 * URL内パラメータの整列処理
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	private static function urlParamKsortRecursive ( & $params) {
        
        if ( ! is_array($params)) {
            
            return;
        }
        
        ksort($params);
            
        foreach ($params as & $v) {
            
            self::urlParamKsortRecursive($v);
        }
	}
	
	/**
	 * HTMLタグの組み立て
	 * @param  [type] $name    [description]
	 * @param  [type] $attrs   [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public static function tag ($name, $attrs=null, $content=null) {
		
		$html ='';
		
		if ( ! is_string($name)) {
			
			return htmlspecialchars($name);
		}
		
		$html .='<'.$name.' ';
		
		if ($attrs === null) {
		
		} else if (is_string($attrs)) {
			
			$html .=$attrs.' ';
			report_warning("HTMLタグのattrsは配列で指定してください");
			
		} else if (is_array($attrs)) {
			
			foreach ($attrs as $k => $v) {
				
				if ($v !== null) {
				
					if (is_numeric($k)) {
						
						$html .=$v.' ';
					
					} else {
						
						if (($name == "input" || $name == "textarea" 
								|| $name == "select") && $k == "name") {
							
							$v =self::urlParamName($v);
						
						} else if (is_array($v)) {
							
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
								
							} else if ($k == "class") {
								
								$v =implode(' ',$v);
								
							} else {
								
								$v =implode(',',$v);
							}
						}
						
						$v =str_replace(array("\r\n","\n",'"'),array(" "," ",'&quot;'),$v);
						$html .=self::urlParamName($k).'="'.$v.'" ';
					}
				}
			}
		}
		
		if ($content === null) {
			
			$html .='/>';
			
		} else if ($content === true) {
			
			$html .='>';
			
		} else if ($content === false) {
			
			$html ='</'.$name.'>';
			
		} else if (is_array($content)) {
			
			$html .='>';
			
			foreach ($content as $k => $v) {
				
				$html .=call_user_func_array("tag",(array)$v);
			}
			
			$html .='</'.$name.'>';
			
		} else if (is_string($content)) {
			
			$html .='>';
			$html .=$content;
			$html .='</'.$name.'>';
		}
		
		return $html;
	}
	
	/**
	 * URL上でのパラメータ名の配列表現の正規化
	 * @param  [type] $param_name [description]
	 * @return [type]             [description]
	 */
	public static function urlParamName ($param_name) {
		
		if (preg_match('!^([^\[]+\.[^\[]+)([\[].*?)?!',$param_name,$match)) {
			
			$stack =explode(".",$match[1]);
			$param_name =array_shift($stack)."[".implode("][",$stack)."]".$match[2];
		}
		
		return $param_name;
	}
}