<?php

class	module_install extends crow_module
{
	//--------------------------------------------------------------------------
	//	preload
	//--------------------------------------------------------------------------
	public function preload()
	{
		//	ログイン済みでなければログイン画面へ飛ばす
		if( crow_auth::is_logined() === false )
		{
			crow::redirect("auth");
			return false;
		}

		//	ログイン済みなら通過
		return true;
	}
}


?>
