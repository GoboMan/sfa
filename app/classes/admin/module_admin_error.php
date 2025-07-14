<?php

class	module_admin_error extends crow_module
{
	//	下のメソッドは、いじらなくてOK（ 2025/07/14 abe ）

	//	通常エラー
	public function action_index()
	{
		if( crow_request::is_ajax() === true )
			app::exit_ng_with_code(app::CODE_NG);
	}

	//	CSRFエラー（URLをわかりにくくするために "access" としている）
	public function action_access()
	{
		if( crow_request::is_ajax() === true )
			app::exit_ng_with_code(app::CODE_NG_CSRF);
	}

	//	ページが見つからない
	public function action_notfound()
	{
		if( crow_request::is_ajax() === true )
			app::exit_ng_with_code(app::CODE_NG_NOTFOUND);
	}

	//	Scriptが有効ではない
	public function action_noscript()
	{
		if( crow_request::is_ajax() === true )
			app::exit_ng_with_code(app::CODE_NG_NOSCRIPT);
	}
}

?>
