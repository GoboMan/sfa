<?php
/*

	admin 基底

*/
class	module_admin extends crow_module
{
	//	override
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
