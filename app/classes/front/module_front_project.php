<?php

class	module_front_project extends module_front
{
	public function action_index()
	{
	}

	public function action_ajax_get_rows()
	{
		$hdb = crow::get_hdb();

		//	プロジェクトテーブルにを結合して一覧取得
		$sql = $hdb->raw('get_project_rows');
		$pager = crow_db_pager::create_with_query($sql)
			->set_row_per_page(2)
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
}


?>
