<!?php

//-------------------------------------
// Context: <?=$c["account"]?>_auth
class <?=str_camelize($c["account"])?>AuthContext extends Context_App {
	
	//-------------------------------------
	// 認証チェック
	public function check_auth () {
		
		// ログインしていない場合
		if ( ! $this->id()) {
		
			$access_only =registry("Auth.access_only.<?=$c["account"]?>");
			$controller_name =registry("Request.controller_name");
			
			// ログインが必要な場合の処理
			if (in_array($controller_name,(array)$access_only)) {
				
				redirect("page:<?=$c["name"]?>.entry_form",array(
					"redirect_to" =>registry("Request.request_uri")
						."?".http_build_query($_GET),
				));
			}
		
		// 既にログインしている場合
		} else {
			
			$this->refresh();
		}
	}
	
	//-------------------------------------
	// ログイン済みユーザに対する処理
	public function refresh () {
	
		// ログイン時にアカウント情報はSessionに登録しないこと
		// ここでIDに対応する情報を都度参照するべき
		
		// AssertSegmentの関連付け
		// model()->bind_segment("<?=str_camelize($c["account"])?>",$this->id());
	}
	
	//-------------------------------------
	// ログイン処理
	public function login ($login_id, $login_pass) {
		
		// ログインチャレンジ時は事前にログアウト処理
		$this->logout();
		
		// ログインID/パスワードチェック
		$id =($login_id == "admin" && $login_pass == "cftyuhbvg")
				? 1
				: null;
				
		if ($id) {
				
			$this->id($id);
			
			$this->refresh();
			
			// Session Fixation対策
			session_regenerate_id(true);
		}
	}
	
	//-------------------------------------
	// ログアウト処理
	public function logout () {
	
		$this->session(false,false);
	}
}
