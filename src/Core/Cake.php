<?php
/*
    2016/07/17
        core/cake.php内の全関数の移行完了

 */
namespace R\Lib\Core;

use R\Lib\Core\Vars;
/**
 * 
 */
class Cake {

	/**
	 * [cake_lib description] 
	 * @return [type]      [description]
	 */
	// Cakeモジュールの読み込み
	function cakeLib () {

		static $cake_lib;

		if ( ! $cake_lib) {

			if (Vars::registry("Config.cake_lib") == "rlib_cake2") {

				$cake_lib =new Cake2Loader;

			} else {

				$cake_lib =new CakeLoader;
			}
		}

		return $cake_lib;
	}
}