<?php

class	module_install_top extends module_install
{
	public function action_index()
	{
	}

	public function action_index_post()
	{
		//	実行パスワードチェック
		$exec_pw = crow_request::get('exec_pw', '');
		if( $exec_pw != crow_config::get('install.pw') )
		{
			crow_response::set('error', 'incorrect password');
			return;
		}

		//	DB構築
		$hdb = crow::get_hdb_writer();
		if( $hdb->exec_install() === false )
		{
			crow_response::set('error', 'failed installation');
			return;
		}

		//	管理ユーザ作成
		$admin = model_admin::create();
		$admin->login_id = "admin";
		$admin->login_pw = "work";
		$admin->deleted = false;
		if( ! $admin->check_and_save() )
		{
			crow_response::set('error', 'failed to install : '.$admin->get_last_error());
			return;
		}

		crow_response::set('msg', 'install completed');
		return;
	}
}


?>
