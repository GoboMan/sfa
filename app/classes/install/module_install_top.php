<?php

class	module_install_top extends module_install
{
	public function action_index()
	{
	}

	//--------------------------------------------------------------------------
	//	テーブル作成および、管理ユーザ作成
	//	前提：テーブル未作成
	//--------------------------------------------------------------------------
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
		$admin->login_id = "root";
		$admin->login_pw = "root";
		$admin->deleted = false;
		if( ! $admin->check_and_save() )
		{
			crow_response::set('error', 'failed to install : '.$admin->get_last_error());
			return;
		}

		crow_response::set('msg', 'install completed');
		return;
	}

	//--------------------------------------------------------------------------
	//	都道府県作成
	//	前提：都道府県テーブルは作成されているものとする
	//--------------------------------------------------------------------------
	public function action_create_prefecture()
	{
		//	実行パスワードチェック
		$exec_pw = crow_request::get('exec_pw', '');
		if( $exec_pw != crow_config::get('install.prefecture_pw') )
		{
			crow_response::set('error', 'incorrect password');
			return;
		}

		//	トランザクション開始
		$hdb = crow::get_hdb_writer();
		$hdb->begin();
		$table_design = $hdb->get_design('prefecture');

		//	テーブルが存在するか確認
		if( $hdb->exists_table('prefecture') == null )
		{
			$hdb->exec_create_table_with_design( $table_design );
		}
		//	テーブルが存在する場合は、テーブルを一度削除して再作成する
		else
		{
			$hdb->exec_drop_table_with_design( $table_design );
			$hdb->exec_create_table_with_design( $table_design );

			$exists_prefecture = model_prefecture::create_array();
			if( count($exists_prefecture) > 0 )
			{
				foreach( $exists_prefecture as $pref )
				{
					if( $pref->trash() === false )
					{
						$hdb->rollback();
						crow_response::set('error', 'failed to delete prefecture : '.$pref->get_last_error());
					}
				}
			}
		}

		//	都道府県作成
		$prefecture_arr =
		[
			['prefecture_id' => 1, 'name' => '北海道', 'area' => 1],
			['prefecture_id' => 2, 'name' => '青森県', 'area' => 2],
			['prefecture_id' => 3, 'name' => '岩手県', 'area' => 2],
			['prefecture_id' => 4, 'name' => '宮城県', 'area' => 2],
			['prefecture_id' => 5, 'name' => '秋田県', 'area' => 2],
			['prefecture_id' => 6, 'name' => '山形県', 'area' => 2],
			['prefecture_id' => 7, 'name' => '福島県', 'area' => 2],
			['prefecture_id' => 8, 'name' => '茨城県', 'area' => 3],
			['prefecture_id' => 9, 'name' => '栃木県', 'area' => 3],
			['prefecture_id' => 10, 'name' => '群馬県', 'area' => 3],
			['prefecture_id' => 11, 'name' => '埼玉県', 'area' => 3],
			['prefecture_id' => 12, 'name' => '千葉県', 'area' => 3],
			['prefecture_id' => 13, 'name' => '東京都', 'area' => 3],
			['prefecture_id' => 14, 'name' => '神奈川県', 'area' => 3],
			['prefecture_id' => 15, 'name' => '新潟県', 'area' => 4],
			['prefecture_id' => 16, 'name' => '富山県', 'area' => 4],
			['prefecture_id' => 17, 'name' => '石川県', 'area' => 4],
			['prefecture_id' => 18, 'name' => '福井県', 'area' => 4],
			['prefecture_id' => 19, 'name' => '山梨県', 'area' => 4],
			['prefecture_id' => 20, 'name' => '長野県', 'area' => 4],
			['prefecture_id' => 21, 'name' => '岐阜県', 'area' => 4],
			['prefecture_id' => 22, 'name' => '静岡県', 'area' => 4],
			['prefecture_id' => 23, 'name' => '愛知県', 'area' => 4],
			['prefecture_id' => 24, 'name' => '三重県', 'area' => 5],
			['prefecture_id' => 25, 'name' => '滋賀県', 'area' => 5],
			['prefecture_id' => 26, 'name' => '京都府', 'area' => 5],
			['prefecture_id' => 27, 'name' => '大阪府', 'area' => 5],
			['prefecture_id' => 28, 'name' => '兵庫県', 'area' => 5],
			['prefecture_id' => 29, 'name' => '奈良県', 'area' => 5],
			['prefecture_id' => 30, 'name' => '和歌山県', 'area' => 5],
			['prefecture_id' => 31, 'name' => '鳥取県', 'area' => 6],
			['prefecture_id' => 32, 'name' => '島根県', 'area' => 6],
			['prefecture_id' => 33, 'name' => '岡山県', 'area' => 6],
			['prefecture_id' => 34, 'name' => '広島県', 'area' => 6],
			['prefecture_id' => 35, 'name' => '山口県', 'area' => 6],
			['prefecture_id' => 36, 'name' => '徳島県', 'area' => 7],
			['prefecture_id' => 37, 'name' => '香川県', 'area' => 7],
			['prefecture_id' => 38, 'name' => '愛媛県', 'area' => 7],
			['prefecture_id' => 39, 'name' => '高知県', 'area' => 7],
			['prefecture_id' => 40, 'name' => '福岡県', 'area' => 8],
			['prefecture_id' => 41, 'name' => '佐賀県', 'area' => 8],
			['prefecture_id' => 42, 'name' => '長崎県', 'area' => 8],
			['prefecture_id' => 43, 'name' => '熊本県', 'area' => 8],
			['prefecture_id' => 44, 'name' => '大分県', 'area' => 8],
			['prefecture_id' => 45, 'name' => '宮崎県', 'area' => 8],
			['prefecture_id' => 46, 'name' => '鹿児島県', 'area' => 8],
			['prefecture_id' => 47, 'name' => '沖縄県', 'area' => 9],
		];

		foreach( $prefecture_arr as $pref )
		{
			$row = model_prefecture::create();
			$row->name = $pref['name'];
			$row->area = $pref['area'];

			if( $row->check_and_save() === false )
			{

				$hdb->rollback();
				crow_response::set('error', 'failed to create prefecture : '.$row->get_last_error());
				return;
			}
		}

		//	コミットして終了
		$hdb->commit();
		crow_response::set('msg', 'prefecture created');
		return;
	}
}


?>
