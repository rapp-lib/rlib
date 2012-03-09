<?php
	
	//-------------------------------------
	// Securityインスタンスのファクトリ
	function sec ($name=null) {
		
		$instance =& ref_globals("loaded_sec");
		
		$name =$name
				? $name."Security"
				: "Security_App";
		
		if ( ! $instance[$name]) {
			
			$instance[$name] =new $name;
			$instance[$name]->init();
		}
		
		return $instance[$name];
	}

//-------------------------------------
// Security実装
class Security_Base {

	protected $segments =array();
	protected $bound_segments =array();
	
	//-------------------------------------
	// 初期化処理
	public function init () {
	}
	
	//-------------------------------------
	// Segment登録
	public function bind_segment ($segment_name, $segment_id) {
		
		$this->bound_segments[$segment_name] =$segment_id;
	}
	
	//-------------------------------------
	// Securityエラーチェック
	public function check_segments ($assert_name, $assert_id) {
		
		foreach ($this->bound_segments as $segment_name => $segment_id) {
			
			$assert_config =$this->segments[$segment_name][$assert_name];
			
			if ( ! $assert_config) {
				
				continue;
			}
			
			// SQLを発行してデータの検出を確認
			if ($assert_config["query"]) {
			
				dbi()->bind(array(
					"segment_id" =>$segment_id,
					"assert_id" =>$assert_id,
				));
				$exists =dbi()->select_count($assert_config["query"]);
				
				if ( ! $exists) {
					
					$this->error("SEGMENT_ERROR",array(
						"assert_config" =>$assert_config,
						"segment_name" =>$segment_name,
						"segment_id" =>$segment_id,
						"assert_name" =>$assert_name,
						"assert_id" =>$assert_id,
					));
				}
			}
		}
	}
	
	//-------------------------------------
	// Securityエラー発生時の処理
	public function error ($errcode, $params=array()) {
	}
}
class Security_App extends Security_Base {
	
	protected $asserts =array(
		
		"auth_Admin" =>array(),
		
		"auth_Student" =>array(
		
			"access_Student" =>array(
				"query" =>array(
					"table" =>"Student",
					"conditions" =>array(
						array("Student.id = :assert_id"),
						array("Student.id = :segment_id"),
					),
				),
			),
			
			"access_Favorite" =>array(
				"query" =>array(
					"table" =>"Favorite",
					"conditions" =>array(
						"Favorite.id = :assert_id",
						"Favorite.student_id = :segment_id",
					),
				),
			),
			
			"access_Attend" =>array(
				"query" =>array(
					"table" =>"Attend",
					"conditions" =>array(
						"Attend.id = :assert_id",
						"Attend.student_id = :segment_id",
					),
				),
			),
			
			"access_Event" =>array(
				"query" =>array(
					"table" =>"Event",
					"joins" =>array(
						array("EventSchedule","EventSchedule.event_id = Event.id"),
						array("Attend","Attend.event_schedule_id = Event.id"),
					),
					"conditions" =>array( 
						"Event.id = :assert_id",
						"or" =>array(
							"Attend.student_id = :segment_id",
						),
							"Event.category" =>"open",
					),
				),
			),
			
			"access_EventSchedule" =>array(
				"query" =>array(
					"table" =>"EventSchedule",
					"joins" =>array(
						array("Event","Event.id = EventSchedule.event_id"),
						array("Attend","Attend.event_schedule_id = Event.id"),
					),
					"conditions" =>array(
						"EventSchedule.id = :assert_id",
						"or" =>array(
							"Event.type" =>"open",
							"Attend.student_id = :segment_id",
						),
					),
				),
			),
		),
	);
	
	//-------------------------------------
	// 初期化処理
	public function init () {
	}
	
	//-------------------------------------
	// Securityエラー発生時の処理
	public function error ($errcode, $params=array()) {
	}
}