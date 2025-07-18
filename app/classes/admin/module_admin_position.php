<?php
class	module_admin_position extends module_admin
{
	//	下のコメントを参考にメソッドを作成、このコメントは削除OK（ 2025/07/14 abe ）

	//--------------------------------------------------------------------------
	//	ポジション情報
	//--------------------------------------------------------------------------
	public function action_index()
	{
		$rows = model_position::create_array();
		$url = crow::make_url_self();

		crow_response::sets(
		[
			'rows' => $rows,
			'url' => $url,
		]);
	}

	//--------------------------------------------------------------------------
	//	ajax : ポジション情報入力
	//--------------------------------------------------------------------------
	public function action_create()
	{
		$row = model_position::create_from_request();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}
	//--------------------------------------------------------------------------
	//	ajax : ポジション情報編集
	//--------------------------------------------------------------------------
	public function action_update()
	{
		$row = model_position::create_from_request_with_id();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	ajax : ポジション情報削除
	//--------------------------------------------------------------------------
	public function action_delete()
	{
		$row = model_position::create_from_request_with_id();
		if($row->trash() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}
}
?>