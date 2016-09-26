<?php

namespace R\Lib\Form\Input;
use R\Lib\Core\Html;

/**
 * 
 */
class Splittext extends BaseInput
{
	/**
	 * @override
	 */
	public function __construct ($value, $attrs) 
	{
		list($params,$attrs) =$this->filterAttrs($attrs,array(
			"type",
			"name",
			"value",
			"mode",
			"assign",
		));
		// 分割設定
		$settings =array(
			"tel" =>array("delim" =>"-", "length" =>3, "type" =>"text"),
			"zip" =>array("delim" =>"-", "length" =>2, "type" =>"text"),
			"mail" =>array("delim" =>"@", "length" =>2, "type" =>"text"),
		);
		
		if ( ! $params["mode"] && $settings[$params["mode"]]) {
		
			return 'error: mode attribute is-not valid.';
		}
		
		if (isset($value)) {
			
			$attrs["value"] =$value;
		}

		$html["elms"] =array();
			
		$html["setting"] =$settings[$params["mode"]];
		$values_splitted =explode($html["setting"]["delim"],$value,$html["setting"]["length"]);
		
		for ($i=0; $i<$html["setting"]["length"]; $i++) {
			
			$value_splitted =$values_splitted[$i];
		
			$sub_attrs["type"] =$html["setting"]["type"];
			$sub_attrs["name"] =$params["name"]."[".$i."]";
			$sub_attrs["value"] =$value_splitted;
			$sub_attrs["class"] =($attrs["class"] ? $attrs["class"]." " : "")."splittext_".$i;
			
			$html["elms"][$i] =tag("input",$sub_attrs);
		}
		
		// LRA共通ヘッダ／フッタ
		$html["alias"] =sprintf("LRA%09d",mt_rand());
		$html["elm_id"] ='ELM_'.$html["alias"];
		$html["head"] ='<span class="splittextContainer">'
				.'<input type="hidden" class="lraName"'
				.' name="_LRA['.$html["alias"].'][name]"'
				.' value="'.$params['name'].'"/>'
				.'<input type="hidden" class="lraMode"'
				.' name="_LRA['.$html["alias"].'][mode]"'
				.' value="splittext"/>'
				.'<input type="hidden" class="lraGroup"'
				.' name="_LRA['.$html["alias"].'][splitmode]"'
				.' value="'.$params["mode"].'"/>'
				.'<input type="hidden" class="lraVarName"'
				.' name="_LRA['.$html["alias"].'][var_name]"'
				.' value="'.$html["name"].'"/>';
		$html["foot"] ='</span>';
		
		// HTML一式
		$html["full"] =$html["head"]
				.implode(" ".$html["setting"]["delim"]." ",$html["elms"])
				.$html["foot"];
		
		// テンプレート変数へのアサイン
		if ($params["assign"]) {
		
			$this->assign[$params["assign"]] =$html;
			
			return null;

		} else {
			
			$this->html =$html["full"];
		}
	}
}