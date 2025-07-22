<?php
class	module_admin_industry extends module_admin
{
	//	下のコメントを参考にメソッドを作成、このコメントは削除OK（ 2025/07/14 abe ）

	//--------------------------------------------------------------------------
	//	業界情報
	//--------------------------------------------------------------------------
	public function action_index()
	{
		$rows = model_industry::create_array();
		$url = crow::make_url_self();

		crow_response::sets(
		[
			'rows' => $rows,
			'url' => $url,
		]);
	}

	//--------------------------------------------------------------------------
	//	ajax : 業界情報入力
	//--------------------------------------------------------------------------
	public function action_create()
	{
		$items = json_decode(crow_request::get('admins'));
		foreach($items as $item)
		{
			$row = model_industry::create();
			$row->name = $item['name'];

			if($row->check_and_save() === false)
			{
				app::exit_ng($row->get_last_error());
			}
		}
		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	ajax : 業界情報編集
	//--------------------------------------------------------------------------
	public function action_update()
	{
		$row = model_industry::create_from_request_with_id();
		if($row->check_and_save() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	ajax : 業界情報削除
	//--------------------------------------------------------------------------
	public function action_delete()
	{
		$row = model_industry::create_from_request_with_id();
		if($row->trash() === false)
		{
			app::exit_ng($row->get_last_error());
		}
		app::exit_ok();
	}

}
?>