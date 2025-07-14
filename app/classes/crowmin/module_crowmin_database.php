<?php

class	module_crowmin_database extends module_crowmin
{
	//	改行
	const RET = "\r\n";


	//	クォート取得
	private static function quat()
	{
		return crow_config::get("db.type","mysqli")=="mysqli" ? "`" : '"';
	}
	private static function quat_wrap( $content_ )
	{
		$q = self::quat();
		return $q.$content_.$q;
	}

	//	アクション：テーブルデザイン
	public function action_index()
	{
		//	アクティブリンクの指定
		crow_response::set('active', 'index');

		//	デザイン読み込み
		if( $this->reload_design_file() === false )
			return $this->error('failed read design file');

		//	DBの差分チェック
		$diff = $this->check_diff();
		crow_response::set('diff', $diff);
	}

	//	アクション：テーブル内容
	public function action_list()
	{
		//	アクティブリンクの指定
		crow_response::set('active', 'list');

		//	デザイン読み込み
		if( $this->reload_design_file() === false )
			return $this->error('failed read design file');

		//	DBの差分チェック
		$diff = $this->check_diff();
		crow_response::set('diff', $diff);

		//	テーブル仕様取得
		$table = crow_request::get('table', '');
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "illegal request"]
			);
		}
		crow_response::set('design', $table_design);

		//	ページャーで一覧取得
		$model_name = "model_".$table;
		$sql = $model_name::sql_select_all();

		$pager = crow_db_pager::create_with_obj($sql)
			->set_page_no( crow_request::get_int('page',1) )
			->set_row_per_page(50)
			->set_page_range(10)
			->set_wide_link(true, 50)
			->build()
			;
		crow_response::set("pager", $pager);
		crow_response::set("rows", $pager->get_rows());
	}

	//	アクション：レコード追加 : ajax
	public function action_add()
	{
		//	テーブル仕様取得
		$table = crow_request::get('crow_db_table_name', '');
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return $this->exit_ng(self::CODE_NG, 'not found table');
		}

		//	更新
		$model_name = "model_".$table;
		$row = $model_name::create_from_request();
		if( $row === false ) return $this->exit_ng(self::CODE_NG, 'not found record');
		if( $row->check_and_save() === false )
		{
			return $this->exit_ng(self::CODE_NG, $row->get_last_error());
		}

		$this->exit_ok();
	}

	//	アクション：レコード編集 : ajax
	public function action_edit()
	{
		//	テーブル仕様取得
		$table = crow_request::get('crow_db_table_name', '');
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return $this->exit_ng(self::CODE_NG, 'not found table');
		}

		//	パスワードカラムは入力がなければ変更なしとする
		foreach( $table_design->fields as $key => $col )
		{
			if( $col->type == "password" )
			{
				if( strlen(crow_request::get($key, '')) <= 0 )
					crow_request::unset($key);
			}
		}

		//	更新
		$model_name = "model_".$table;
		$row = $model_name::create_from_request_with_id();
		if( $row === false ) return $this->exit_ng(self::CODE_NG, 'not found record');
		if( $row->check_and_save() === false )
		{
			return $this->exit_ng(self::CODE_NG, $row->get_last_error());
		}

		$this->exit_ok();
	}

	//	アクション：レコード削除 : ajax
	public function action_delete()
	{
		$table = crow_request::get('table', '');
		$id = crow_request::get('id', '');
		$model_name = "model_".$table;
		$row = $model_name::create_from_id($id);
		if( $row === false ) return $this->exit_ng(self::CODE_NG, 'not found record');
		if( $row->trash() === false )
		{
			return $this->exit_ng(self::CODE_NG, $row->get_last_error());
		}

		$this->exit_ok();
	}

	//	アクション：クエリ発行
	public function action_query()
	{
		//	アクティブリンクの指定
		crow_response::set('active', 'query');

		//	デザイン読み込み
		if( $this->reload_design_file() === false )
		{
			crow_response::set("result", false);
			crow_response::set("err_msg", "failed to read design file");
			return;
		}

		//	DBの差分チェック
		$diff = $this->check_diff();
		crow_response::set('diff', $diff);

		//	クエリ整形
		$hdb = crow::get_hdb();
		$query = trim(crow_request::get("query", ""));
		$query = mb_str_replace("\r", "", $query);
		$query = mb_str_replace("\n", " ", $query);
		$query = trim($query);
		if( substr($query,-1)==";" ) $query = substr($query,0,strlen($query)-1);
		crow_response::set("query", $query);
		crow_response::set('query_type', '');

		if( $query !== "" )
		{
			//	select/showクエリ
			if( substr(strtolower($query),0,6) == "select" ||
				substr(strtolower($query),0,7) == "explain" ||
				substr(strtolower($query),0,4) == "show"
			){
				crow_response::set('query_type', 'select');

				//	selectなら、付与されていない場合に限りlimitを付ける
				if( substr(strtolower($query),0,6) == "select" )
				{
					$pos = strpos($query,"limit");
					if( $pos===false )
					{
						$query .= " limit 0, 100";
					}
					crow_response::set("query", $query);
				}

				//	実行
				$rset = $hdb->query($query);
				if( $rset === false )
				{
					crow_response::set("result", false);
					crow_response::set(
						"err_msg",
						"(".$hdb->get_last_error_code().") ".$hdb->get_last_error_msg()
					);
					return;
				}

				//	結果整理
				if( $rset->num_rows() > 0 )
				{
					$cols = [];
					$first_row = $rset->get_row();
					crow_response::set('columns', array_keys($first_row));
					crow_response::set('rows', $rset->get_rows());
				}
			}
			//	select以外
			else
			{
				crow_response::set('query_type', 'not select');

				//	実行
				$rset = $hdb->query($query);
				if( $rset === false )
				{
					crow_response::set("result", false);
					crow_response::set(
						"err_msg",
						"(".$hdb->get_last_error_code().") ".$hdb->get_last_error_msg()
					);
					return;
				}
				else
				{
					crow_response::set("result", true);
				}
			}
		}
	}

	//	アクション：ローカライズ
	public function action_lang()
	{
		//	アクティブリンクの指定
		crow_response::set('active', 'lang');

		//	デザイン読み込み
		if( $this->reload_design_file() === false )
			return $this->error('failed read design file');

		//	DBの差分チェック
		$diff = $this->check_diff();
		crow_response::set('diff', $diff);
	}

	//	アクション：ローカライズ
	public function action_lang_post()
	{
		//	アクティブリンクの指定
		crow_response::set('active', 'lang');

		//	デザイン読み込み
		if( $this->reload_design_file() == false )
			return $this->error('failed read design file');

		//	DBの差分チェック
		$diff = $this->check_diff();
		crow_response::set('diff', $diff);

		//	DB定義の文言一式から、最大のタブ幅を算出しておく
		$hdb = crow::get_hdb();
		$def = $hdb->get_design_all();
		$max_key = 0;
		foreach( $def as $name => $table_row )
		{
			$table_name = (strlen($table_row->logical_name) > 0 ? $table_row->logical_name : $table_row->name);
			foreach( $table_row->fields as $col_name => $col_row )
			{
				$key = "db.".$name.".".$col_name;
				if( strlen($key) > $max_key ) $max_key = strlen($key);

				if( count($col_row->const_logical_names) > 0 )
				{
					foreach( $col_row->const_logical_names as $const_key => $logical_name )
					{
						$key = "db.".$name.".const.".$const_key;
						if( strlen($key) > $max_key ) $max_key = strlen($key);
					}
				}
			}
		}

		//	日本語ファイル作成
		$lines = [];
		foreach( $def as $name => $table_row )
		{
			$table_name = (strlen($table_row->logical_name) > 0 ? $table_row->logical_name : $table_row->name);
			$lines[] = "//------------------------------------------------------------------------------";
			$lines[] = "//	".$table_name;
			$lines[] = "//------------------------------------------------------------------------------";

			$key = "db.".$name;
			$tab = "\t";
			$tabnum = intval(($max_key - strlen($key)) / 4);
			for( $i=0; $i<$tabnum; $i++ ) $tab .= "\t";
			$lines[] = $key.$tab."= ".$table_name;
			$const_lines = [];
			foreach( $table_row->fields as $col_name => $col_row )
			{
				$desc = strlen($col_row->logical_name) > 0 ? $col_row->logical_name : $col_name;
				$key = "db.".$name.".".$col_name;

				$tab = "\t";
				$tabnum = intval(($max_key - strlen($key)) / 4);
				for( $i=0; $i<$tabnum; $i++ ) $tab .= "\t";

				$lines[] = $key.$tab."= ".$desc;

				//	const値
				foreach( $col_row->const_logical_names as $const_key => $logical_name )
				{
					$key = "db.".$name.".const.".$const_key;
					$tab = "\t";
					$tabnum = intval(($max_key - strlen($key)) / 4);
					for( $i=0; $i<$tabnum; $i++ ) $tab .= "\t";
					$const_lines[] = $key.$tab."= ".$logical_name;
				}
			}
			$lines[] = "";

			if( count($const_lines) > 0 )
			{
				foreach( $const_lines as $line )
					$lines[] = $line;
				$lines[] = "";
			}
		}
		file_put_contents(CROW_PATH."app/assets/lang/ja/_common_/db.txt", implode(self::RET,$lines).self::RET);

		//	英語ファイル作成
		$lines = [];
		foreach( $def as $name => $table_row )
		{
			$table_name =  $table_row->name;
			$lines[] = "//------------------------------------------------------------------------------";
			$lines[] = "//	".$table_name;
			$lines[] = "//------------------------------------------------------------------------------";

			$key = "db.".$name;
			$tab = "\t";
			$tabnum = intval(($max_key - strlen($key)) / 4);
			for( $i=0; $i<$tabnum; $i++ ) $tab .= "\t";
			$lines[] = $key.$tab."= ".$table_name;
			$const_lines = [];
			foreach( $table_row->fields as $col_name => $col_row )
			{
				$desc = $col_name;
				$key = "db.".$name.".".$col_name;

				$tab = "\t";
				$tabnum = intval(($max_key - strlen($key)) / 4);
				for( $i=0; $i<$tabnum; $i++ ) $tab .= "\t";

				$lines[] = $key.$tab."= ".$desc;

				//	const値
				foreach( $col_row->const_logical_names as $const_key => $logical_name )
				{
					$key = "db.".$name.".const.".$const_key;
					$tab = "\t";
					$tabnum = intval(($max_key - strlen($key)) / 4);
					for( $i=0; $i<$tabnum; $i++ ) $tab .= "\t";
					$const_lines[] = $key.$tab."= ".$logical_name;
				}
			}
			$lines[] = "";

			if( count($const_lines) > 0 )
			{
				foreach( $const_lines as $line )
					$lines[] = $line;
				$lines[] = "";
			}
		}
		file_put_contents(CROW_PATH."app/assets/lang/en/_common_/db.txt", implode(self::RET,$lines).self::RET);

		crow_response::set("msg", "言語ファイルを作成しました");
	}

	//	アクション：バックアップ
	public function action_backup()
	{
		//	アクティブリンクの指定
		crow_response::set('active', 'backup');

		//	デザイン読み込み
		if( $this->reload_design_file() === false )
			return $this->error('failed read design file');

		//	DBの差分チェック
		$diff = $this->check_diff();
		crow_response::set('diff', $diff);

		//	バックアップの一覧取得
		$path = CROW_PATH."output/backup/";
		$fnames = crow_storage::disk()->get_files($path, false, ["sql"]);
		$rows = [];
		foreach( $fnames as $fname )
		{
			$rows[] =
			[
				'name'	=> crow_storage::extract_filename($fname),
				'date'	=> date('Y/m/d H:i:s', filemtime($fname)),
				'size'	=> filesize($fname),
			];
		}
		crow_response::set('rows', array_reverse($rows));
	}

	//	アクション：バックアップ実行
	public function action_backup_exec()
	{
		$fname = CROW_PATH."output/backup";
		if( is_dir($fname) === false )
		{
			if( mkdir($fname,0777) === false )
			{
				return crow::redirect_action_with_vars( "backup",
					["error" => 'failed to create backup dir : '.$fname]
				);
			}
		}
		$fname .= "/db_".date('YmdHis').".sql";
		if( file_put_contents( $fname, crow::get_hdb()->export() ) === false )
		{
			return crow::redirect_action_with_vars( "backup",
				["error" => 'failed to output sql file : '.$fname]
			);
		}
		return crow::redirect_action_with_vars( "backup",
			["msg" => "database backup succeeded : ".$fname]
		);
	}

	//	アクション：インポート実行
	public function action_import_exec()
	{
		$fname = CROW_PATH."output/backup/".crow_request::get('data','');
		if( is_file($fname) === false )
		{
			return crow::redirect_action_with_vars( "backup",
				["error" => 'failed to import, not found backup file : '.$fname]
			);
		}

		$hdb = crow::get_hdb();
		if( $hdb->exec_sql_file($fname) === false )
		{
			return crow::redirect_action_with_vars( "backup",
				["error" => 'failed to import : '.$fname]
			);
		}

		return crow::redirect_action_with_vars( "backup",
			["msg" => "database import succeeded : ".$fname]
		);
	}

	//	アクション：バックアップ削除
	public function action_backup_del()
	{
		$fname = CROW_PATH."output/backup/".crow_request::get('data','');
		if( is_file($fname) === false )
		{
			return crow::redirect_action_with_vars( "backup",
				["error" => 'failed to import, not found backup file : '.$fname]
			);
		}

		unlink($fname);

		return crow::redirect_action_with_vars( "backup",
			["msg" => "deleted backup file : ".$fname]
		);
	}

	//	アクション：テーブル作成
	public function action_create_table()
	{
		//	リクエストされたテーブルの仕様取得
		$table = crow_request::get('table', '');
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design === false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "illegal request"]
			);
		}

		//	作成
		if( $hdb->exec_create_table_with_design($table_design) === false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "failed to create table:".$table]
			);
		}

		//	完了
		return crow::redirect_action_with_vars( "index",
			["msg" => "create table succeeded : ".$table]
		);
	}

	//	アクション：テーブル削除
	public function action_drop_table()
	{
		$table = crow_request::get('table', '');
		$sql = "drop table ".$table;
		$hdb = crow::get_hdb();
		if( $hdb->query($sql) === false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "failed to drop table:".$table]
			);
		}

		//	完了
		return crow::redirect_action_with_vars( "index",
			["msg" => "drop table succeeded : ".$table]
		);
	}

	//	アクション：フィールド作成
	public function action_create_field()
	{
		//	仕様取得
		$hdb = crow::get_hdb();
		$table = crow_request::get('table', '');
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "illegal request"]
			);
		}

		//	フィールド定義を探す
		$field = crow_request::get('field', '');
		foreach( $table_design->fields as $field_design )
		{
			if( $field_design->name == $field )
			{
				//	フィールド作成
				$field_syntax = $hdb->sql_field_syntax_with_design($table_design, $field_design);
				$sql = "alter table ".self::quat_wrap($table)." add ".$field_syntax;
				if( $hdb->query($sql) === false )
				{
					return crow::redirect_action_with_vars( "index",
						["error" => "failed to create field : ".$table."->".$field]
					);
				}

				//	完了
				return crow::redirect_action_with_vars( "index",
					["msg" => "create field succeeded : ".$table."->".$field]
				);
			}
		}

		//	フィールド定義が見つからないのでエラー
		return crow::redirect_action_with_vars( "index",
			["error" => "illegal request"]
		);
	}

	//	アクション：フィールド修復
	public function action_restore_field()
	{
		$table = crow_request::get('table', '');
		$field = crow_request::get('field', '');

		//	フィールド定義を探す
		$hdb = crow::get_hdb();

		$table_design = $hdb->get_design( $table );
		foreach( $table_design->fields as $field_design )
		{
			if( $field_design->name == $field )
			{
				//	トランザクション
				$hdb->begin();

				$field_syntax = $hdb->sql_field_syntax_with_design($table_design, $field_design);

				//	フィールドの存在チェック。存在しなければ修復はできない
				$cur_fields = $hdb->get_fields($table);
				if( isset($cur_fields[$field_design->name]) === false )
				{
					$hdb->rollback();
					return crow::redirect_action_with_vars( "index",
						["error" => "not found exists field : ".$table."->".$field]
					);
				}
				$cur_field = $cur_fields[$field_design->name];

				//	現行でPKがある場合は変更するために解除する
				if( $cur_field['primary_key'] )
				{
					//	AIがあるなら、一旦解除する
					if( $cur_field['auto_increment'] )
					{
						$field_syntax_without_key = str_replace("primary key", "", $field_syntax);
						$field_syntax_without_key = str_replace("auto_increment", "", $field_syntax_without_key);
						$sql = "alter table ".self::quat_wrap($table)." modify ".$field_syntax_without_key;
						if( $hdb->query($sql) === false )
						{
							$hdb->rollback();
							return crow::redirect_action_with_vars( "index",
								["error" => "failed to restore field : ".$table."->".$field]
							);
						}
					}

					//	primary keyの解除
					$sql = $hdb->sql_delete_primary_key($table_design);
					if( $hdb->query($sql) === false )
					{
						$hdb->rollback();
						return crow::redirect_action_with_vars( "index",
							["error" => "failed to restore field : ".$table."->".$field]
						);
					}
				}

				//	フィールド定義変更（PK/AIはここで付与される）

//	todo : 複合キーの場合は、ここで単体ではなく複合でセットしなければならない

				$sql = "alter table ".self::quat_wrap($table)." modify ".$field_syntax;
				if( $hdb->query($sql) === false )
				{
					$hdb->rollback();
					return crow::redirect_action_with_vars( "index",
						["error" => "failed to restore field : ".$table."->".$field]
					);
				}

				//	コミットして完了
				$hdb->commit();
				return crow::redirect_action_with_vars( "index",
					["msg" => "restore field succeeded : ".$table."->".$field]
				);
			}
		}

		//	フィールド定義が見つからないのでエラー
		return crow::redirect_action_with_vars( "index",
			["error" => "illegal request"]
		);
	}

	//	アクション：フィールド削除
	public function action_delete_field()
	{
		$table = crow_request::get('table', '');
		$field = crow_request::get('field', '');

		$sql = "alter table ".self::quat_wrap($table)." drop column ".self::quat_wrap($field);
		$hdb = crow::get_hdb();
		if( $hdb->query($sql) == false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "failed to delete field:".$table."->".$field]
			);
		}

		//	結果表示
		return crow::redirect_action_with_vars( "index",
			["msg" => "delete field succeeded : ".$table."->".$field]
		);
	}

	//	アクション：インデックス作成
	public function action_create_index()
	{
		$table = crow_request::get('table', '');
		$name  = crow_request::get('name', '');
		if( strlen($table)<=0 || strlen($name)<=0 ) return;

		//	仕様取得
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "illegal request"]
			);
		}

		$sql = $hdb->sql_create_index_syntax_with_design($table_design, $name);
		if( $sql===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "not found a define of index : ".$table."->".$name]
			);
		}
		if( $hdb->query($sql) === false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "failed to create index : ".$table."->".$name]
			);
		}

		//	結果表示
		return crow::redirect_action_with_vars( "index",
			["msg" => "create index succeeded : ".$table."->".$name]
		);
	}

	//	アクション：インデックス修復
	public function action_restore_index()
	{
		$table = crow_request::get('table', '');
		$name  = crow_request::get('name', '');
		if( strlen($table)<=0 || strlen($name)<=0 ) return;

		//	仕様取得
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "illegal request"]
			);
		}

		$sql_del = $hdb->sql_drop_index_syntax_with_design($table_design, $name);
		$sql_create = $hdb->sql_create_index_syntax_with_design($table_design, $name);

		if( $sql_del===false || $sql_create===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "not found a define of index : ".$table."->".$name]
			);
		}
		if( $hdb->query($sql_del) === false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "failed to restore index : ".$table."->".$name]
			);
		}
		if( $hdb->query($sql_create) === false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "failed to restore index : ".$table."->".$name]
			);
		}

		//	結果表示
		return crow::redirect_action_with_vars( "index",
			["msg" => "restore index succeeded : ".$table."->".$name]
		);
	}

	//	アクション：インデックス削除
	public function action_delete_index()
	{
		$table = crow_request::get('table', '');
		$name  = crow_request::get('name', '');
		if( strlen($table)<=0 || strlen($name)<=0 ) return;

		//	仕様取得
		$hdb = crow::get_hdb();
		$table_design = $hdb->get_design( $table );
		if( $table_design===false )
		{
			return crow::redirect_action_with_vars( "index",
				["error" => "illegal request"]
			);
		}

		//	新仕様の名前のインデックス削除時はエラーで止まらないようにする。
		//	エラーだった場合は旧仕様の名前のインデックス削除を試みる
		$remove_result = false;
		crow_log::set_abort_on_error(false);
		{
			$sql = "alter table ".self::quat_wrap($table)." drop index ".$hdb->encode($table_design->name."_".$name);
			$remove_result = $hdb->query($sql);
		}
		crow_log::set_abort_on_error(true);

		if( $remove_result === false )
		{

			$sql = "alter table ".self::quat_wrap($table)." drop index ".$hdb->encode($name);
			if( $hdb->query($sql) === false )
			{
				return crow::redirect_action_with_vars( "index",
					["error" => "failed to drop index : ".$table."->".$name]
				);
			}
		}

		//	結果表示
		return crow::redirect_action_with_vars( "index",
			["msg" => "delete index succeeded : ".$table."->".$name]
		);
	}

	//	DBの差分チェック
	private function check_diff()
	{
		//	デザインと現行DBから、それぞれテーブル一覧取得
		$hdb = crow::get_hdb();
		$table_designs = $hdb->get_design_all();
		$table_names = $hdb->get_tables();

		//	インデックス取得
		$indexes = $hdb->get_indexes();

		//	現行DBに対して、DB仕様をチェック
		$diff = [];
		foreach( $table_names as $table_name )
		{
			$found = false;
			foreach( $table_designs as $design )
			{
				if( $design->name == $table_name )
				{
					$found = true;
					break;
				}
			}

			if( $found === false )
			{
				//	現行テーブルにあって、DB仕様にないテーブル
				$diff[] =
				[
					"name"		=> $table_name,
					"exists"	=> "current",
				];
			}
		}

		//	DB仕様に対して、現行DBをチェック
		foreach( $table_designs as $table_design )
		{
			//	テーブルが現行のDBにある？
			if( $hdb->exists_table($table_design->name) )
			{
				$current_fields = $hdb->get_fields($table_design->name);
				$diff_item =
				[
					"name"			=> $table_design->name,
					"exists"		=> "both",
					"field_comp"	=> true,
				];

				//	フィールドが現行DBにないものは要修復、定義が違うものも要修復
				foreach( $table_design->fields as $field )
				{
					$field_diff = $this->check_field(
						$table_design->name, $field, $current_fields );

					$field_same = ($field_diff['exists']==true && $field_diff['differ']==false);

					$diff_item['field'][] =
					[
						"name"		=> $field->name,
						"design"	=> $field,
						"same"		=> $field_same,
						"diff"		=> $field_diff
					];
					if( $field_same === false )
						$diff_item['field_comp'] = false;
				}

				//	フィールドが現行DBにあって定義にないものは要修復
				foreach( $current_fields as $current_field )
				{
					$found = false;
					foreach( $table_design->fields as $field )
					{
						if( $field->name == $current_field['name'] ){
							$found = true;
							break;
						}
					}
					if( $found === false )
					{
						//	フィールドが現行DBにあって定義にない
						$diff_item['field'][] =
						[
							"name"		=> $current_field['name'],
							"design"	=> false,
							"same"		=> false,
							"cur"		=> $current_field
						];
						$diff_item['field_comp'] = false;
					}
				}

				//	インデックスのチェック
				$tmp_current_indexes = isset($indexes["indexes"][$table_design->name]) ? $indexes["indexes"][$table_design->name] : [];
				$tmp_current_uniques = isset($indexes["uniques"][$table_design->name]) ? $indexes["uniques"][$table_design->name] : [];

				$current_indexes = [];
				$current_uniques = [];
				foreach($tmp_current_indexes as $k => $v)
				{
					$current_indexes[$k] = $v;
				}
				foreach($tmp_current_uniques as $k => $v)
				{
					$current_uniques[$k] = $v;
				}

				$idx_diff_item = [];
				$index_types = ["indexes", "uniques"];
				foreach( $index_types as $index_type )
				{
					$target = $index_type=="indexes" ? $table_design->indexes : $table_design->indexes_with_unique;
					foreach( $target as $index_name => $cols )
					{
						if(
							isset($current_indexes[$table_design->name."_".$index_name])===false &&
							isset($current_uniques[$table_design->name."_".$index_name])===false
						){
							//	定義にあって、現行にないインデックス
							$idx_diff_item[] =
							[
								"name"		=> $index_name,
								"compare"	=> "def_only",
								"cols"		=> $cols,
								"unique"	=> $index_type=="uniques"
							];
							continue;
						}
						else if
						(
							(isset($current_uniques[$table_design->name."_".$index_name])===false && $index_type=="uniques") ||
							(isset($current_indexes[$table_design->name."_".$index_name])===false && $index_type=="indexes")
						){
							//	ユニーク制約の状態が異なる
							$idx_diff_item[] =
							[
								"name"		=> $index_name,
								"compare"	=> "differ_unq",
								"cols"		=> $cols,
								"unique"	=> $index_type=="uniques"
							];
							continue;
						}

						$current_cols = isset($current_indexes[$table_design->name."_".$index_name]) ?
							$current_indexes[$table_design->name."_".$index_name] : $current_uniques[$table_design->name."_".$index_name];
						if( count($current_cols) != count($cols) )
						{
							//	定義と現行のインデックス定義が異なる
							$idx_diff_item[] =
							[
								"name"		=> $index_name,
								"compare"	=> "differ_def",
								"cols"		=> $cols,
								"unique"	=> $index_type=="uniques"
							];
							continue;
						}

						$same = true;
						foreach( $current_cols as $col_index => $col_name )
						{
							if( $cols[$col_index] != $col_name )
							{
								//	定義と現行のインデックス定義が異なる
								$idx_diff_item[] =
								[
									"name"		=> $index_name,
									"compare"	=> "differ_cols",
									"cols"		=> $cols,
									"unique"	=> $index_type=="uniques"
								];
								$same = false;
								break;
							}
						}
						if( $same === false ) continue;

						//	一致
						$idx_diff_item[] =
						[
							"name"		=> $index_name,
							"compare"	=> "same",
							"cols"		=> $cols,
							"unique"	=> $index_type=="uniques"
						];
					}
				}

				foreach( $current_indexes as $index_name => $cols )
				{
					if(
						isset($table_design->indexes[substr($index_name, strlen($table_design->name."_"))])===false &&
						isset($table_design->indexes_with_unique[substr($index_name, strlen($table_design->name."_"))])===false
					){
						//	現行にあって、定義にないインデックス
						$idx_diff_item[] =
						[
							"name"		=> $index_name,
							"compare"	=> "current_only",
							"cols"		=> $cols,
							"unique"	=> false
						];
					}
				}
				foreach( $current_uniques as $index_name => $cols )
				{
					if(
						isset($table_design->indexes[substr($index_name, strlen($table_design->name."_"))])===false &&
						isset($table_design->indexes_with_unique[substr($index_name, strlen($table_design->name."_"))])===false
					){
						//	現行にあって、定義にないインデックス
						$idx_diff_item[] =
						[
							"name"		=> $index_name,
							"compare"	=> "current_only",
							"cols"		=> $cols,
							"unique"	=> true
						];
					}
				}

				$diff_item['indexes'] = $idx_diff_item;
				$diff[] = $diff_item;
			}
			//	テーブルが現行のDBにない
			else
			{
				$diff[] =
				[
					"name"		=> $table_design->name,
					"exists"	=> "design",
				];
			}
		}

		return $diff;
	}

	//	フィールドチェック
	private function check_field( $table_name_, $field_, $current_fields_ )
	{
		$diff = [];

		//	現在のDBにある？
		if( isset($current_fields_[$field_->name]) === false )
		{
			$diff['exists'] = false;
			return $diff;
		}
		$diff['exists'] = true;
		$diff['differ'] = false;

		$cur = $current_fields_[$field_->name];

		if(
			($field_->primary_key !== $cur['primary_key']) ||
			($field_->auto_increment !== $cur['auto_increment']) ||
			($field_->type=='tinyint' &&
				(
					($cur['type'] != 'tinyint' && $cur['type'] != 'smallint') ||
					($cur['length'] > 0 && $cur['length'] != $field_->size) ||
					$cur['unsigned'] == true
				)
			) ||
			($field_->type=='utinyint' &&
				(
					($cur['type'] != 'tinyint unsigned' && $cur['type'] != 'smallint') ||
					($cur['length'] > 0 && $cur['length'] != $field_->size) ||
					$cur['unsigned'] == false
				)
			) ||
			($field_->type=='int' &&
				(
					($cur['type'] != 'int' && $cur['type'] != 'integer') ||
					($cur['length'] > 0 && $cur['length'] != $field_->size) ||
					$cur['unsigned'] == true
				)
			) ||
			($field_->type=='uint' &&
				(
					($cur['type'] != 'int unsigned' && $cur['type'] != 'integer') ||
					($cur['length'] > 0 && $cur['length'] != $field_->size) ||
					$cur['unsigned'] == false
				)
			) ||
			($field_->type=='bigint' &&
				(
					($cur['type'] != 'bigint' && $cur['type'] != 'integer') || // postgresでAI(serial)の場合、integerになる
					($cur['length'] > 0 && $cur['length'] != $field_->size) ||
					$cur['unsigned'] == true
				)
			) ||
			($field_->type=='ubigint' &&
				(
					$cur['type'] != 'bigint unsigned' ||
					($cur['length'] > 0 && $cur['length'] != $field_->size) ||
					$cur['unsigned'] == false
				)
			) ||
			($field_->type=='varchar' &&
				(
					($cur['type'] != 'varchar' && $cur['type'] != 'character varying') ||
					$cur['length'] != $field_->size
				)
			) ||
			($field_->type=='text' && $cur['type']!='text') ||
			($field_->type=='bigtext' && $cur['type']!='longtext') ||
			($field_->type=='varcrypt' && $cur['type']!='tinyblob' && $cur['type']!='bytea') ||
			($field_->type=='crypt' && $cur['type']!='blob' && $cur['type']!='bytea') ||
			($field_->type=='bigcrypt' && $cur['type']!='longblob' && $cur['type']!='bytea') ||
			($field_->type=='unixtime' && $cur['type']!='bigint') ||
			($field_->type=='datetime' && $cur['type']!='datetime' && $cur['type']!='timestamp without time zone') ||
			($field_->type=='boolean' && $cur['type']!='tinyint' && $cur['type']!='smallint') ||
			($field_->type=='url' && $cur['type']!='text') ||
			($field_->type=='mail' &&
				(
					($cur['type'] != 'varchar' && $cur['type'] != 'character varying') ||
					$cur['length'] != 255
				)
			) ||
			($field_->type=='telno' &&
				(
					($cur['type'] != 'varchar' && $cur['type'] != 'character varying') ||
					$cur['length'] != 20
				)
			) ||
			($field_->type=='geometry' && $cur['type']!='geometry') ||
			($field_->type=='double' && $cur['type']!='double') ||
			($field_->type=='password' &&
				(
					($cur['type'] != 'varchar' && $cur['type'] != 'character varying') ||
					$cur['length'] != 255
				)
			) ||
			($field_->type=='bit' &&
				(
					$cur['type'] != 'bit' ||
					$cur['length'] != $field_->size
				)
			)
		){
			$diff['differ'] = true;
		}

		//	[]で括られた型はそのままチェック
		if( substr($field_->type,0,1)=="[" &&
			substr($field_->type,-1)==']'
		){
			$custom_type = substr($field_->type,1,strlen($field_->type)-2);

			//	データ長付きの場合
			if( preg_match("/(.*)\\(([0-9]+)\\)/isuA", $custom_type,$m) )
			{
				if( $m[1]!=$cur['type'] || $m[2]!=$cur['length'] ) $diff['differ'] = true;
			}
			//	データ長なしの場合
			else
			{
				if( $custom_type != $cur['type'] ) $diff['differ'] = true;
			}
		}

		return $diff;
	}

	//	エラー処理
	private function error( $msg_ )
	{
		echo $msg_;
		exit;
	}

	//	DB仕様のリロード
	private function reload_design_file()
	{
		$hdb = crow::get_hdb();
		if( $hdb->init() === false ) return false;
		return true;
	}
}


?>
