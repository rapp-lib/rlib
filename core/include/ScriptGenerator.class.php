<?php

/*
	Code:
		$g =new ScriptGenerator;
		$g->node("root",array("p",array(
			array("v",array("c","register",array(
				array("s","Struc.schema.Member"),
				array("a",array(
					1 =>array("s","Hello"),
					2 =>array("s","World"),
					"A" =>array("a",array(
						array("s","Hello"),
						array("s","World"),
					)),
					array("a",array(
						array("s","Hello"),
						array("s","World"),
					)),
				)),
			))),
			array("v",array("c","register",array(
				array("s","Struc.schema.Product"),
				array("a",array(
				)),
			))),
		)));
		print $g->get_script();
	
	Output:
		<?php
				
			register('Struc.schema.Member',array(
				'1' =>'Hello',
				'2' =>'World',
				'A' =>array(
					'Hello',
					'World',
				),
				'3' =>array(
					'Hello',
					'World',
				),
			));
			register('Struc.schema.Product',array(
			));
*/

//-------------------------------------
//
class ScriptGenerator {
	
	protected $st =array();
	protected $node_types =array(
		"root" =>array(
			"p" =>"node_root_php",
		),
		"statement" =>array(
			"v" =>"node_statement_val",
			"c" =>"node_statement_comment",
		),
		"val" =>array(
			"c" =>"node_val_call",
			"a" =>"node_val_array",
			"s" =>"node_val_str",
			"d" =>"node_val_dec",
		),
	);
	
	//-------------------------------------
	// 
	public function get_script () {
		
		$height =0;
		$pstack =array();
		$src ="";
		
		foreach ($this->st as $st) {
			
			if ($st["feedback"] !== null) {
				
				for ($i=0; $i< $st["feedback"]; $i++) {
					
					$src =preg_replace('!\t([^\t]*)$!',"$1",$src);
				}
				
				$height -=$st["feedback"];
			}
			
			$src .=$st["code"];
			
			if ($st["open"]) {
				
				array_push($pstack,$st["open"]);
			}
			
			if ($st["close"]) {
				
				$closed =array_shift($pstack);
				
				if ($closed != $st["close"]) {
					
					/// autoclose
				}
			}
			
			if ($st["break"] !== null) {
			
				$src .="\n";
				$height +=(int)$st["break"];
				
				if ($height > 0) {
					
					$src .=str_repeat("\t",$height);
				}
			}
			
			if ($st["error"]) {
			
				$src .="/* CompileError: ".$st["error"].' */';
			}
		}
		
		return $src;
	}
	
	//-------------------------------------
	// 
	public function node ($node_group, $node) {
		
		$node_type =array_shift($node);
		$node_args =$node;
		
		$node_method =$this->node_types[$node_group][$node_type]
				? $this->node_types[$node_group][$node_type]
				: $node_type;
		
		if (method_exists($this,$node_method)) {
		
			call_user_func_array(array($this,$node_method),$node_args);
		
		} else {
			
			$this->st[] =array(
				"error" =>"Node ".$node_group." error: "
						.$node_type."(".count($node_args).") is not registered.",
			);
		}
	}
	
	//-------------------------------------
	// 配列データからarray_nodeを生成する
	public function make_array_node ($arr) {
		
		$n =array();
		
		foreach ($arr as $k => $v) {
			
			if (is_array($v)) {
			
				$n[$k] =array("a",$this->get_array_script_node($v));
			
			} elseif (is_numeric($v)) {
			
				$n[$k] =array("d",(int)$v);
				
			} else {
			
				$n[$k] =array("s",(string)$v);
			}
		}
		
		return $n;
	}
	
	//-------------------------------------
	// 
	protected function node_root_php ($statement_nodes) {
		
		$this->st[] =array(
			"code" =>"<?php",
			"open" =>"php",
			"break" =>+1,
		);
		
		$this->st[] =array(
			"code" =>"",
			"break" =>0,
		);
		
		foreach ($statement_nodes as $node) {
		
			$this->node("statement",$node);
		}
	}
	
	//-------------------------------------
	// 
	protected function node_statement_val ($val_node) {	
		
		$this->node("val",$val_node);
		
		$this->st[] =array(
			"code" =>';',
			"break" =>0,
		);
	}
	
	//-------------------------------------
	// 
	protected function node_statement_comment ($comment_text) {	
		
		$this->st[] =array(
			"code" =>'// '.$comment_text,
			"break" =>0,
		);
	}
	
	//-------------------------------------
	// 
	protected function node_val_call ($func_name, $args=array()) {
		
		$this->st[] =array(
			"code" =>$func_name.'(',
		);
		
		for ($i=0; $i< count($args); $i++) {
			
			$this->node("val",$args[$i]);
			
			if ($i < count($args)-1) {
				
				$this->st[] =array(
					"code" =>',',
				);
			}
		}
		
		$this->st[] =array(
			"code" =>')',
		);
	}
	
	//-------------------------------------
	// 
	protected function node_val_array ($value) {
		
		// 改行を含むかどうかの判定
		$array_break =$this->array_break;
		
		if ($array_break === null) {
			
			$array_break =false;
			
			foreach ($value as $v) {
				
				if ($v[0] === "a") {
					
					$array_break =true;
				}
			}
			
			if (count($value) > 4) {
			
				$array_break =true;
			}
		}
		
		$this->st[] =array(
			"code" =>"array(",
			"open" =>"array",
			"break" =>$array_break ? +1 : null,
		);
		
		$loop_counter =0;
		$base_index =0;
		
		foreach ((array)$value as $k => $v) {
			
			if ($k === $base_index) {
			
				$base_index++;
			
			} else {
			
				$this->node("val",array("s",$k));
				
				$this->st[] =array(
					"code" =>' =>',
				);
			}
			
			$this->node("val",$v);
			
			if ($array_break) {
				
				$this->st[] =array(
					"code" =>',',
					"break" =>0,
				);
			
			} else {
				
				if ($loop_counter < count($value)-1) {
				
					$this->st[] =array(
						"code" =>', ',
					);
				}
			}
			
			$loop_counter++;
		}
		
		$this->st[] =array(
			"code" =>")",
			"feedback" =>$array_break ? +1 : null,
			"close" =>"array",
		);
	}
	
	//-------------------------------------
	// 
	protected function _node_val_array ($value) {
		
		$this->st[] =array(
			"code" =>"array(",
			"open" =>"array",
			"break" =>+1,
		);
		
		$base_index =0;
		
		foreach ((array)$value as $k => $v) {
			
			if ($k === $base_index) {
			
				$base_index++;
			
			} else {
			
				$this->node("val",array("s",$k));
				
				$this->st[] =array(
					"code" =>' =>',
				);
			}
			
			$this->node("val",$v);
			
			$this->st[] =array(
				"code" =>',',
				"break" =>0,
			);
			
		}
		
		$this->st[] =array(
			"code" =>")",
			"feedback" =>1,
			"close" =>"array",
		);
	}
	
	//-------------------------------------
	// 
	protected function node_val_dec ($value) {
		
		$this->st[] =array(
			"code" =>(string)((int)$value),
		);
	}
	
	//-------------------------------------
	// 
	protected function node_val_str ($value) {
		
		$this->st[] =array(
			"code" =>"'".str_replace("'","\\'",(string)$value)."'",
		);
	}
}