<!?php

/**
 * @controller
 */
class <?=str_camelize($c["name"])?>Controller extends Controller_App 
{
	/**
	 * @page
	 * @title <?=$c["label"]?> TOP
	 */
	public function act_index () 
	{
		redirect("page:.login");
	}

	/**
	 * @page
	 * @title <?=$c["label"]?> ログイン
	 */
	public function act_login () 
	{
		$this->context("c",1,true);
		
		// 転送先指定の保存
		if ($_REQUEST["redirect_to"]) {
			$redirect_to =sanitize_decode($_REQUEST["redirect_to"]);
			$this->c->session("redirect_to",$redirect_to);
		}

		// 入力値のチェック
		if ($_REQUEST["_i"]=="c") {
			$this->c->validate_input($_REQUEST,array());
			$this->c_<?=$c["account"]?>_auth->login(
					$this->c->input("login_id"),
					$this->c->input("login_pass"));
			
			if ($this->c_<?=$c["account"]?>_auth->id()) {

				// 転送先の指定があればそちらを優先
				if ($this->c->session("redirect_to")) {
					redirect($this->c->session("redirect_to"));
				}
				
				redirect("page:index.index");
			
			} else {

				$this->vars["login_error"] =true;
			}
		}
	}

	/**
	 * @page
	 * @title <?=$c["label"]?> ログアウト
	 */
	public function act_logout () 
	{
		$this->context("c");
		
		// ログアウト処理
		$this->c_<?=$c["account"]?>_auth->logout();
		
		redirect("page:index.index");
	}
}
