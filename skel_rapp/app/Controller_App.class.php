<?php

//-------------------------------------
// Controller基本クラス
class Controller_App extends Controller_Base {

	//-------------------------------------
	// act_*前処理
	public function before_act () {
	
		parent::before_act();
		
		// リクエスト変換処理
		obj("LayoutRequestArray")->fetch_request_array();
		
		// 管理者認証
		// $this->context("c_admin_auth","c_admin_auth",false,"AdminAuthContext");
		// $this->c_admin_auth->check_auth();
	}
	
	//-------------------------------------
	// act_*後処理
	public function after_act () {
	
		parent::after_act();
	}
}