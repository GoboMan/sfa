<?php
class	module_admin_user extends module_admin
{
	//	下のコメントを参考にメソッドを作成、このコメントは削除OK（ 2025/07/14 abe ）

	//--------------------------------------------------------------------------
	//	ユーザー一覧
	//--------------------------------------------------------------------------
	public function action_index()
	{
		$rows = model_user::create_array();
		crow_response::set('rows',$rows);
	}

	//--------------------------------------------------------------------------
	//	ajax : ユーザー作成
	//--------------------------------------------------------------------------
	public function action_create()
	{
		$row = model_user::create_from_request();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	ajax : ユーザー編集
	//--------------------------------------------------------------------------
	public function action_update()
	{
		$row = model_user::create_from_request_with_id();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}


	//--------------------------------------------------------------------------
	//	ajax : ユーザー削除
	//--------------------------------------------------------------------------
	public function action_delete()
	{
		$row = model_user::create_from_request_with_id();
		if($row->trash() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

}
