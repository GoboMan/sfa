<?php
class	module_install_auth extends crow_module
{
	//--------------------------------------------------------------------------
	//	ログイン
	//--------------------------------------------------------------------------
	public function action_index()
	{
	}
	public function action_index_post()
	{
		$uid = crow_config::get('install.login_id', '');
		$upw = crow_config::get('install.login_pw', '');

		if(
			crow_request::get('login_id', '') != $uid ||
			crow_request::get('login_pw', '') != $upw
		){
			crow_response::set( "error", "incorrect id or password" );
			return;
		}

		//	ログイン処理
		if( crow_auth::login_force([]) === false )
		{
			crow_response::set( "error", crow_auth::get_last_error() );
			return;
		}

		//	成功したら標準ページへリダイレクト
		crow::redirect_default();
	}

	//--------------------------------------------------------------------------
	//	ログアウト
	//--------------------------------------------------------------------------
	public function action_logout()
	{
		crow_auth::logout();
		crow::redirect_default();
	}
}
?>
