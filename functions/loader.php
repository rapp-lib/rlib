<?php
	//$loader = require_once __DIR__."/../bootstrap.php";
	//R\Lib\AppLoader::load("R\\App\\Webapp",array(
	//	"Path.html_dir" => __FILE__,
	//	"Request.request_url" => __FILE__,
	//),true);

	/**
	 * 
	 */
	function app ($name=null)
	{
		if ( ! $name) {
			$name = "application";
		}
		
		$class = "R\\App\\".R\Lib\String::to_class($name);
		
		$obj = storage("app")->get("singleton.".$class);
		if ( ! $obj) {
			$obj = new $class;
			storage("app","singleton")->set($class, $obj);
		}
		return $obj;
	}


	/**
	 * 
	 */
	function util ($name=null)
	{
		R\Util\HttpRequestHandler::request();
	}

	/**
	 * 
	 */
	function plugin ($name=null)
	{
	}

	/**
	 * 
	 */
	function storage ($name=null)
	{
	}
