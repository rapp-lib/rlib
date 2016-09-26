<?php


//-------------------------------------
//  定義済みクラス、関数を一覧取得
class ViewReflection {

	/**
	 *  実行関数
	 */
	public static function check ($check_class_dir ="/src/Core/") {

		self::loadFile(array(
			"/src/Core/",
			"/src/Core/Report/",
		));

		// 定義済みクラス一覧取得
		$defind_classes =get_declared_classes();
		// Reflectionクラスで定義済みクラスの情報取得
		$info_classes =array();
		// いったん全部のリフレくかけたら、クラスの絞り込み（指定したディレクトリにファイル単位で絞り込み）
		 foreach ($defind_classes as $class) {

			$info_classes[]=self::getInfoClass(new ReflectionClass($class));
		}

		$pattern ="!" . $check_class_dir . "!";
		$filter_info_classes =array();
		foreach ($info_classes as $class) {

			if (preg_match($pattern, $class["file"])) {

				$filter_info_classes[] =$class;
			}
		}

		// CSV形式に変換
		$csv_output =array();
		foreach($filter_info_classes as $value) {

			if (is_array($value["methods"])) {

				foreach ($value["methods"] as $i => $v) {

					 if ($i === 0) {

						$csv_output[] =array(
							"file" =>$value["file"],
							"line" =>$value["line"],
							"ns" =>$value["ns"],
							"class" =>$value["class"],
							"method" =>$v,
							"comment" =>$value["comment"],
						);

					} else {

						$csv_output[] =array(
							"file" =>"",
							"line" =>"",
							"ns" =>"",
							"class" =>"",
							"method" =>$v,
							"comment" =>"",
						);
					}
				}
			} else {

				$csv_output[] =array(
					"file" =>$value["file"],
					"line" =>$value["line"],
					"ns" =>$value["ns"],
					"class" =>$value["class"],
					"method" =>"なし",
					"comment" =>$value["comment"],
				);
			}
		}

		// CSV出力（クラス）
		$csv_filename =registry("Path.tmp_dir")
			."/csv_output/ReflectionClass-".date("Ymd-His")."-"
			.sprintf("%04d",rand(0,9999)).".csv";
		$csv_setting =array (
			"file_charset" =>"SJIS-WIN",
			"data_charset" =>"UTF-8",
			"rows" =>array(
				"file" =>"ファイル",
				"line" =>"開始行",
				"ns" =>"ネームスペース",
				"class" =>"クラス",
				"method" =>"メソッド",
				"comment" =>"DocComment",
			),
		);

		$csv =new CSVHandler($csv_filename,"w",$csv_setting);

		foreach ($csv_output as $v) {

			$csv->write_line($v);
		}

		clean_output_shutdown(array(
			"download" =>basename($csv_filename),
			"file" =>$csv_filename,
		));
	}

	/**
	 *  クラス情報の取得
	 * @param ReflectionClass $ref_class
	 * @return array $info
	 */
	public static function getInfoClass($ref_class) {

		$info =array();
		$info["file"] =$ref_class->getFileName();
		$info["line"] =$ref_class->getStartLine();
		$info["ns"] =$ref_class->getNamespaceName();
		$info["class"] =self::createClassName($ref_class);
		$info["comment"] =$ref_class->getDocComment();

		if ($ref_class->getMethods()) {

			foreach ($ref_class->getMethods() as $ref_method) {
				
				$info["methods"][] =self::createMethodName($ref_class , $ref_method);
			}

		} else {

			$info["methods"] =false;
		}
		return $info;
	}

	/**
	 *  クラスファイル読み込み
	 * @param  string $dir
	 */
	public static function loadFile ($dir) {

		// 複数ディレクトリをまたぐ場合
		if (is_array($dir)){
			foreach($dir as $v) {
				self::loadFile($v);
			}
		}

		ob_start();
		foreach (glob(RLIB_ROOT_DIR.$dir."*.php") as $filename) {

			require_once($filename);
		}
		ob_end_clean();
	}

	/**
	 *  クラスの表記様式生成
	 * @param ReflectionClass $ref_class
	 * @return string 
	 */
	public static function createClassName ($ref_class) {

		// 基本形式
		$class_format =array();
		$class_format["class"] ="class";
		$class_format["class_name"] =$ref_class->getShortName();

		//extends考慮
		if ($ref_class->getParentClass()) {

			$class_format["extends"] ="extends";
			$class_format["parent_class_name"] =$ref_class->getParentClass()->getShortName();
		}

		//interface考慮
		if ($ref_class->getInterfaceNames()) {

			$class_format["implements"] ="implements";
			$class_format["parent_class_name"] =implode(" , ",$ref_class->getInterfaceNames());
		}

		return implode(" ",$class_format);
	}

	/**
	 *  メソッドの表記様式生成
	 * @param  ReflectionClass $ref_class
	 * @param  ReflectionMethod $ref_method
	 * @return string 
	 */
	public static function createMethodName ($ref_class,$ref_method) {

		$method_format =array();

		// メソッド
		$method_name =$ref_method->getName();
		// アクセス修飾子の判別
		if ($ref_class->getMethod($method_name)->isPublic()) {

			$method_format["access"] ="public";

		} elseif ($ref_class->getMethod($method_name)->isProtected()) {

			$method_format["access"] ="protected";
		
		} elseif ($ref_class->getMethod($method_name)->isPrivate()) {

			$method_format["access"] ="private";
		}
		// static判別
		if ($ref_class->getMethod($method_name)->isStatic()) {

			$method_format["static"] ="static";
		}

		// 引数の作成
		$ref_params =$ref_class->getMethod($method_name)->getParameters();
		if (! $ref_params) {

			$method_format["method_name"] =$method_name ." () ";

		} else {

			$parameter =self::createParameter($ref_params);

			$method_format["method_params"] =" (" . implode(", ",$parameter) . ")";
		}

		return implode(" ",$method_format);
	}

	/**
	 *  パラメータの表記様式生成
	 * @param  ReflectionParameter $ref_params
	 * @return array 
	 */
	public static function createParameter ($ref_params) {

		$result =array();
		foreach ($ref_params as $ref_param) {

			// 引数名
			if ($ref_param->isPassedByReference()) {

				$param_name ="& $".$ref_param->getName() ;

			} else {

				$param_name  ="$".$ref_param->getName() ;
			}

			// パラメータが省略可能でない場合はデフォルト値取得時にエラー発生
			if ($ref_param->isDefaultValueAvailable ()) {

				if (is_null($ref_param->getDefaultValue())) {

					$default_value = "null";

				} elseif (is_array($ref_param->getDefaultValue())) {

					$default_value = "array()";

				}  elseif (is_bool($ref_param->getDefaultValue())) {

					$default_value = $ref_param->getDefaultValue() === true ? "true" : "false";
				}else {

					$default_value =$ref_param->getDefaultValue();
				}

				$result[] =$param_name." =" . $default_value;

			} else {
				
				$result[] =$param_name;
			}
		}

		return $result;
	}
}