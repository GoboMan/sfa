<?php

class	module_front_workforce extends module_front
{
	public function action_index()
	{
	}

	//------------------------------------------------------------------------------
	//	ajax : 一覧取得
	//------------------------------------------------------------------------------
	public function action_ajax_get_rows()
	{
		$hdb = crow::get_hdb();

		//	プロジェクトテーブルにを結合して一覧取得
		$sql = $hdb->raw('get_project_rows');
		$pager = crow_db_pager::create_with_query($sql)
			->set_row_per_page(25)
			->set_page_no(1)
			->build()
			;

		$rows = $pager->get_rows();
		$rows_with_id = crow_utility::array_replace_key($rows, 'project_id');

		app::exit_ok(json_encode([
			'rows' => $rows_with_id,
			'total' => $pager->get_total(),
			'start_index' => $pager->get_start_index(),
			'row_per_page' => $pager->get_row_per_page(),
			'prev_page_no' => $pager->get_prev_page(),
			'next_page_no' => $pager->get_next_page(),
		]));
	}

	//------------------------------------------------------------------------------
	//	ajax : 登録
	//------------------------------------------------------------------------------
	public function action_ajax_create()
	{
		$row = model_project::create_from_request();
		$row->start_date = strtotime(crow_request::get('start_date'));
		$row->created_at = time();

		if( $row->check_and_save() === false )
		{
			app::exit_ng( $row->get_last_error() );
		}

		$row->created_at = date('Y-m-d H:i:s', $row->created_at);

		app::exit_ok(json_encode($row));
	}

}


?>
