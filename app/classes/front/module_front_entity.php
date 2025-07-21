<?php

class	module_front_entity extends module_front
{
	public function action_index()
	{
	}

	public function action_ajax_get_rows()
	{
		$hdb = crow::get_hdb();

		//	取引先テーブルに営業の名前を結合して一覧取得
		$sql = $hdb->raw('get_entity_rows_with_user_name');
		$pager = crow_db_pager::create_with_query($sql)
			->set_row_per_page(2)
			->set_page_no(1)
			->build()
			;

		$rows = $pager->get_rows();
		$rows_with_id = crow_utility::array_replace_key($rows, 'entity_id');

		//	更新日時をunix timestampに変換
		foreach($rows_with_id as &$row) $row['updated_at'] = strtotime($row['updated_at']);

		//	取引先一覧とページャ情報を返す
		app::exit_ok(json_encode([
			'rows' => $rows_with_id,
			'total' => $pager->get_total(),
			'start_index' => $pager->get_start_index(),
			'row_per_page' => $pager->get_row_per_page(),
			'prev_page_no' => $pager->get_prev_page(),
			'next_page_no' => $pager->get_next_page(),
		]));
	}

	//	取引先詳細取得
	public function action_ajax_detail()
	{
		$entity_id = crow_request::get('entity_id');

		$hdb = crow::get_hdb();
		$row = $hdb->raw_select_one('get_entity_row_with_user_name', $entity_id);

		//	更新日時をunix timestampに変換
		$row['updated_at'] = strtotime($row['updated_at']);

		app::exit_ok(json_encode($row));
	}

	//	取引先登録
	public function action_ajax_create()
	{
		//	登録
		$row = model_entity::create_from_request();
		$row->updated_at = time();
		if( $row->check_and_save() === false )
			app::exit_ng($row->get_last_error());

		//	取引先テーブルに営業の名前を結合して一覧取得
		$hdb = crow::get_hdb();
		$row = $hdb->raw_select_one('get_entity_row_with_user_name', $row->entity_id);

		//	更新日時をunix timestampに変換
		$row['updated_at'] = strtotime($row['updated_at']);

		app::exit_ok(json_encode($row));
	}

	//	取引先更新
	public function action_ajax_update()
	{
		$row = model_entity::create_from_request_with_id();
		$row->updated_at = time();
		if( $row->check_and_save() === false )
			app::exit_ng($row->get_last_error());

		//	取引先テーブルに営業の名前を結合して一覧取得
		$hdb = crow::get_hdb();
		$new_row = $hdb->raw_select_one('get_entity_row_with_user_name', $row->entity_id);

		//	更新日時をunix timestampに変換
		$new_row['updated_at'] = strtotime($new_row['updated_at']);

		app::exit_ok(json_encode($new_row));
	}

	//	取引先削除
	public function action_ajax_delete()
	{
		$entity_id = crow_request::get('entity_id');
		$row = model_entity::create_from_id($entity_id);
		if( $row->trash() === false )
			app::exit_ng($row->get_last_error());

		app::exit_ok(json_encode(true));
	}

	//	前ページ取得
	public function action_ajax_get_page()
	{
		$page_no = crow_request::get('page_no');
		$hdb = crow::get_hdb();

		crow_log::notice('page_no:'.$page_no);

		//	取引先テーブルに営業の名前を結合して一覧取得
		$sql = $hdb->raw('get_entity_rows_with_user_name');
		$pager = crow_db_pager::create_with_query($sql)
			->set_row_per_page(2)
			->set_page_no($page_no)
			->build()
			;

		$rows = $pager->get_rows();
		$rows_with_id = crow_utility::array_replace_key($rows, 'entity_id');

		//	更新日時をunix timestampに変換
		foreach($rows_with_id as &$row) $row['updated_at'] = strtotime($row['updated_at']);

		app::exit_ok(json_encode(
		[
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
