<!?php

//-------------------------------------
// Context: <?=$c["account"]?>_auth
class <?=str_camelize($c["account"])?>AuthContext extends Context_App {
	
	//-------------------------------------
	// 認証チェック
	public function check_auth () {
		
		$access_only =registry("Auth.access_only.<?=$c["account"]?>");
		$controller_name =registry("Request.controller_name");
		
		// ログインが必要な場合の処理
		if (in_array($controller_name,(array)$access_only) &&  ! $this->id()) {
			
			redirect("page:<?=$c["name"]?>.entry_form",array(
				"redirect_to" =>registry("Request.request_uri"),
			));
		}
	}
	
	//-------------------------------------
	// ログイン処理
	public function login ($login_id, $login_pass) {
		
		$accounts =array(
			"test" =>array("id" =>1, "pass" =>"pass"),
		);
		
		// 既にログインされていれば情報を削除
		if ($this->id()) {
			
			$this->id(false);
		}
		
		// ログインID/パスワードチェック
		if ($login_id && $login_pass && $accounts[$login_id]
				&& $login_pass == $accounts[$login_id]["pass"]) {
				
			$this->id($accounts[$login_id]["id"]);
		}
	}
	
	//-------------------------------------
	// ログアウト処理
	public function logout () {
	
		$this->id(false);
	}
}
