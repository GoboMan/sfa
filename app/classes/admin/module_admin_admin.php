<?php
class	module_admin_admin extends module_admin
{
	//	下のコメントを参考にメソッドを作成、このコメントは削除OK（ 2025/07/14 abe ）

	//--------------------------------------------------------------------------
	//	管理者一覧
	//--------------------------------------------------------------------------
	public function action_index()
	{
		$rows = model_admin::create_array();
		crow_response::set('rows',$rows);
	}

	//--------------------------------------------------------------------------
	//	ajax : 管理者作成
	//--------------------------------------------------------------------------
	public function action_create()
	{
		$row = model_admin::create_from_request();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	ajax : 管理者編集
	//--------------------------------------------------------------------------
	public function action_update()
	{
		$row = model_admin::create_from_request_with_id();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}


	//--------------------------------------------------------------------------
	//	ajax : 管理者削除
	//--------------------------------------------------------------------------
	public function action_delete()
	{
		$row = model_admin::create_from_request_with_id();
		if($row->trash() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

}

?>
