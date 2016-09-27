<?php
/*
	core/array.php内の全関数の移行完了
 */
namespace R\Lib\Core;


/**
 * 
 */
class Arr {

	/**
	 * [extract description]
	 * @param  [type] $arr [description]
	 * @return [type]      [description]
	 */
	public static function extract ( & $arr) 
	{
		if (is_array($arr)) {
			
			foreach ($arr as $k => $_v) {
				
				if (is_array($arr[$k])) {
					
					Arr::extract($arr[$k]);
				}
				
				$ref =& Arr::ref($arr,$k);
				$ref =$arr[$k];
			}
		
		}
	}

	/**
	 * [archive description]
	 * @param  [type] $root   [description]
	 * @param  [type] $node   [description]
	 * @param  array  $root_k [description]
	 * @return [type]         [description]
	 */
	public static function archive ( & $root, $node=null, $root_k=array()) 
	{
		
		if ($node === null) {
		
			$node =$root;
		}
			
		foreach (array_keys($node) as $k) {
			
			$root_k_copy =$root_k;
			$root_k_copy[] =$k;
			
			if (is_array($node[$k])) {
			
				Arr::archive($root,$node[$k],$root_k_copy);
				
			} else {

				$root[implode($root_k_copy,'.')] =$node[$k];
			}
		}
	}
	
	/**
	 * [ref description]
	 * @param  [type] $arrx [description]
	 * @param  [type] $path [description]
	 * @return [type]       [description]
	 */
	public static function & ref ( & $arrx, $path) 
	{
		if (is_array($path)) {
			
			foreach ($path as $k => $v) {
				
				$path[$k] =str_replace(".","..",$path[$k]);
			}
			
			$path_str =implode('.',$path);
			
		} else {
			
			$path_str =$path;
			
			$path =str_replace("..",'__DELIM__',$path);
			$path =explode('.', $path);
			
			foreach ($path as $k => $v) {
				
				$path[$k] =str_replace("__DELIM__",".",$path[$k]);
			}
		}
		
		$_list =& $arrx;

		foreach ($path as $i => $key) {
			
			if (( ! preg_match('!^0\d+!',$key) && is_numeric($key) 
					&& intval($key) > 0) || $key === '0') {
				
				$key =intval($key);
			
			} else if ($key == "[]") {
				
				$key =$_list
						? max(array_keys($_list))+1
						: 0;
			}
			
			if (count($path)-1 !== $i && ! isset($_list[$key])) {
			
				$_list[$key] =array();
			}
			
			$_list =& $_list[$key];
		}
		
		return $_list;
	}
	
	/**
	 * Registryの中で配列を保持できるようにKeyをエスケープ
	 * @param  [type] $arr [description]
	 * @return [type]      [description]
	 */
	public static function escape ($arr) {
	
		$arr_escaped =array();
		
		foreach ($arr as $k => $v) {
			
			if (is_array($v)) {
				
				$v =array_escape($v);
			}
			
			$k =str_replace('.','..',$k);
			
			$arr_escaped[$k] =$v;
		}
		
		return $arr_escaped;
		
	}
	
	/**
	 * 配列をregisry同様のストアとして利用する
	 * @param  [type] $arr     [description]
	 * @param  [type] $name    [description]
	 * @param  [type] $value   [description]
	 * @param  array  $options [description]
	 * @return [type]          [description]
	 */
	public static function & registry ( & $arr, $name=null ,$value=null, $options=array()) 
	{
        // [Deprecated] $escapeとして指定を行っていた場合の処理
        if (is_bool($options)) {
            
            $escape =$options;
            $options =array("escape"=>$escape);
        }
        
		// 全取得
		if ($name === null) {
			
			return $arr;
			
		// 配列指定（name）
		} else if (is_array($name)) {
			
			foreach ($name as $a_name => $a_value) {
			
				array_registry($arr,$a_name,$a_value,$options);
			}
			
			return $arr;
		}
		
		// 必須定義（!...）
		if ($must_def =preg_match('/^!(.+)$/',$name,$match)) {
			
			$name =$match[1];
		}
		
		// 値の取得
		if ($value === null) {
			
			// 参照を解決（値で参照）
			if ($name === false) {
			
				$ref =$arr;
				
			} else if (isset($options["escape"])) {
				
				$ref =$arr[$name];
				
			} else {
				
				$ref =Arr::ref($arr, $name);
			}
			
			// 必須エラー
			if ($must_def && $ref === null) {
				
				report_error("Registry must-be defined.",array(
					"name" =>$name
				));
			}
			
			return $ref;
		}
		
		// 参照解決
		if ($name === false) {
		
			$ref =& $arr;
			
		} else if ($options["escape"]) {
			
			$ref =& $arr[$name];
			
		} else {
			
			$ref =& Arr::ref($arr, $name);
		}
		
		// 消去（value=false）
		if ($value === false) {
		
			if ($name === false) {
			
				$arr =array();
				
			} else if ($options["escape"]) {
				
				unset($arr[$name]);
				
			} else {
				
				if (preg_match('!^(.*?[^\.])\.([^\.]+)$!',$name,$match)) {
				
					$ref =& Arr::ref($arr, $match[1]);
					unset($ref[$match[2]]);
					
				} else {
				
					unset($arr[$name]);
				}
			}
			
		// 配列指定（value）
		} else if (is_array($value)) {
			
			if ( ! is_array($ref)) {
				
				$ref =array();
			}
			
            // 配列指定の値をマージしない指定がある場合、上書き
            if ($options["no_array_merge"]) {
                
                $ref =$value;
                
            } else {    
                
    			foreach ($value as $a_name => $a_value) {
    			
    				array_registry($ref,$a_name,$a_value,$options);
    			}
    		}
            
		// 値の設定
		} else {
		
			$ref =$value;	
		}
		
		return $ref;
	}

	/**
	 * [Deprecated] 配列をJSON文字列に変換する
	 * @param  [type] $entry [description]
	 * @return [type]        [description]
	 */
	public static function array_to_json_DEPRECATED ($entry) 
	{
		return json_encode($entry);
	}

	/**
	 * [Deprecated] JSON文字列を配列に変換する
	 * @param  [type] $json [description]
	 * @return [type]       [description]
	 */
	public static function json_to_array_DEPRECATED ($json) 
	{
		return json_decode($json,true);
	}

	/**
	 * [Deprecated] 最初の要素を取得（Smarty Modifierに移行すべき）
	 * @param  [type] $arr [description]
	 * @return [type]      [description]
	 */
	public static function getFirst ($arr) {
		
		if (is_array($arr) && $arr) {
		
		 	$keys =array_keys($arr);
		 	return $arr[array_shift($keys)];
			
		} else {
			
			return null;
		}
	}

	/**
	 * [Deprecated] 最後の要素を取得（Smarty Modifierに移行すべき）
	 * @param  [type] $arr [description]
	 * @return [type]      [description]
	 */
	public static function getLast ($arr) {
		
		if (is_array($arr) && $arr) {
		
		 	$keys =array_keys($arr);
		 	return $arr[array_pop($keys)];
			
		} else {
			
			return null;
		}
	}

	/**
	 * [Deprecated] 値を強制的に配列に変換する（Smarty Modifierに移行すべき）
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public static function to_array ($value) {
		
		// 配列はそのまま返す
		if (is_array($value)) {
		
		 	return $value;
		
		// オブジェクトは展開
		} else if (is_object($value)) {
			
			return get_object_vars($value);
		
		// null/falseは空の配列に変換
		} else if ($value === null || $value === false) {
			
			return array();
		
		// unserialize可能であれば展開
		} else if (is_string($value) && ($unserialized =@unserialize($value)) !== false) {
			
			if ($value === "b:0;") {
			
				return false;
			}
			
			return $unserialized;
		}
		
		// その他はすべて第一要素に入れて配列で返す
		return array($value);
	}
}