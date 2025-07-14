<?php
class	module_admin_auth extends crow_module
{
	//	下のメソッドは、いじらなくてOK（ 2025/07/14 abe ）

	//--------------------------------------------------------------------------
	//	ログイン
	//--------------------------------------------------------------------------
	public function action_index()
	{
	}
	public function action_index_post()
	{
		//	ログイン処理
		if( crow_auth::login() === false )
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
