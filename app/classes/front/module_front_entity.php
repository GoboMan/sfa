<?php

class	module_front_entity extends module_front
{
	public function action_index()
	{
	}

	//	取引先一覧取得
	public function action_ajax_get_rows()
	{
		$hdb = crow::get_hdb();

		//	取引先テーブルに営業の名前を結合して一覧取得
		$rows = $hdb->raw_select('get_entity_rows_with_user_name');
		$rows_with_id = crow_utility::array_replace_key($rows, 'entity_id');

		//	更新日時をunix timestampに変換
		foreach($rows_with_id as &$row) $row['updated_at'] = strtotime($row['updated_at']);

		app::exit_ok(json_encode($rows_with_id));
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
		$row = $hdb->raw_select_one('get_entity_row_with_user_name_by_id', $row->entity_id, $row->user_id);

		//	更新日時をunix timestampに変換
		$row['updated_at'] = strtotime($row['updated_at']);

		app::exit_ok(json_encode($row));
	}
}


?>
