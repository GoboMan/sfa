<?php
/*

	テーブルモデルの基底


	モデル拡張で下記のメソッドを実装すると処理を変更できる

	・保存の拡張
		save_crow_ext()
		※ 保存失敗時は push_validation_error() でエラーを積みつつ、false を返却すること

	・バリデーションチェックの拡張
		validation_crow_ext()
		※ チェック失敗時は push_validation_error() でエラーを積むこと

	・論理削除の拡張
		trash_crow_ext()
		※ 削除失敗時は push_validation_error() でエラーを積みつつ、false を返却すること

	・物理削除の拡張
		delete_crow_ext()
		※ 削除失敗時は push_validation_error() でエラーを積みつつ、false を返却すること


	下記メソッドはcrow内部のテーブル拡張において使用するため、ユーザ側での定義は行わないこと。
	・save_ext()
	・validation_ext()
	・trash_ext()

*/
class crow_db_table_model extends stdClass
{
	//--------------------------------------------------------------------------
	//	インスタンスの作成
	//--------------------------------------------------------------------------
	public static function create()
	{
		$class_name = get_called_class();
		return new $class_name();
	}

	//--------------------------------------------------------------------------
	//	プライマリキーを指定してインスタンスを作成する
	//
	//	存在しなかった場合はfalseを返却する
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public static function create_from_id( $id_, $html_escape_ = false )
	{
		if( $id_===false ) return false;
		$class_name = get_called_class();
		return $class_name::create_from_sql
		(
			$class_name::sql_select_one($id_),
			$html_escape_
		);
	}

	//--------------------------------------------------------------------------
	//	ハッシュキーを指定してインスタンスを作成する
	//	ただし、テーブル設計で "hash" という名前のユニークカラムがある前提とする
	//--------------------------------------------------------------------------
	public static function create_from_hash( $hash_, $html_escape_ = false )
	{
		if( $hash_ === false ) return false;
		$class_name = get_called_class();
		return $class_name::create_from_sql
		(
			$class_name::sql_select_all()->and_where('hash', $hash_),
			$html_escape_
		);
	}

	//--------------------------------------------------------------------------
	//	全件取得のクエリを投げてインスタンスを作成する
	//
	//	配列が返却される
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public static function create_array( $html_escape_ = false )
	{
		$class_name = get_called_class();
		return $class_name::create_array_from_sql($class_name::sql_select_all(), $html_escape_);
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：リクエストパラメータからの入力を行う
	//--------------------------------------------------------------------------
	public static function create_from_request( $trim_ = true )
	{
		$class_name = get_called_class();
		$instance = new $class_name();
		return $instance->input_from_request( $trim_ );
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：リクエストパラメータからの入力を行う
	//
	//	create_from_request()と異なり、一旦指定されたprimary_keyでDBから取得した後、
	//	リクエストパラメータによって各メンバを上書きする。
	//	（編集アクションなどで利用することを想定している）
	//
	//	primary_key がリクエストに含まれない場合は false を返す。
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//
	//	db_design上でprimary_key が複合指定されている場合、
	//	リクエスト情報にどれか一つでも欠けているとエラーとなる。
	//--------------------------------------------------------------------------
	public static function create_from_request_with_id( $trim_ = true, $html_escape_ = false )
	{
		$class_name = get_called_class();
		$instance = new $class_name();

		//	primary_key の指定があるかチェックする
		$hdb = crow::get_hdb_reader();
		$table_design = $hdb->get_design( $instance->m_table_name );
		if( $table_design === false )
		{
			crow_log::error("not found table design : ".$instance->m_table_name);
			return false;
		}
		if( $table_design->primary_key === false )
		{
			crow_log::error("not found primary key field : ".$instance->m_table_name);
			return false;
		}

		$primay_value = false;
		if( is_array($table_design->primary_key) )
		{
			$primay_value = [];
			foreach( $table_design->primary_key as $pkey )
			{
				$v = crow_request::get($pkey, false);
				if( $v === false )
				{
					crow_log::error("not specified primary field : ".$pkey);
					return false;
				}
				$primary_value[] = $v;
			}
		}
		else
		{
			$primary_value = crow_request::get($table_design->primary_key, false);
			if( $primary_value === false )
			{
				crow_log::error("not specified primary field : ".$table_design->primary_key);
				return false;
			}
		}

		//	DBから取得
		$rset = $hdb->query($class_name::sql_select_one($primary_value)->build());
		if( $rset->num_rows() <= 0 ) return false;
		$instance->input_from_record( $rset->get_row($html_escape_) );

		//	リクエストから入力
		return $instance->input_from_request( $trim_ );
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：create_from_request_id()のハッシュ版
	//
	//	idではなくhashの値を使用してインスタンスを作成する。
	//	テーブル設計で "hash" という名前のユニークカラムが存在することが前提となる。
	//--------------------------------------------------------------------------
	public static function create_from_request_with_hash( $trim_ = true, $html_escape_ = false )
	{
		$class_name = get_called_class();
		$instance = new $class_name();

		//	ハッシュが指定されていなければ失敗
		$hash = crow_request::get('hash', false);
		if( $hash === false ) return false;

		//	DBから取得
		$hdb = crow::get_hdb_reader();
		$rset = $hdb->query($class_name::sql_select_all()->and_where('hash', $hash)->build());
		if( $rset->num_rows() <= 0 ) return false;
		$instance->input_from_record( $rset->get_row($html_escape_) );

		//	リクエストから入力
		return $instance->input_from_request( $trim_ );
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：単一のDB結果レコードから作成する
	//
	//	$record_で指定するレコード情報は、
	//	キーがフィールド名で値がフィールド値の、１レコードを表す連想配列とする。
	//--------------------------------------------------------------------------
	public static function create_from_record( $record_ )
	{
		$class_name = get_called_class();
		$instance = new $class_name();
		return $instance->input_from_record( $record_ );
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：単一のDB結果レコードを返す SQL（crow_db_sql）から作成する
	//
	//	レコードが存在しなければ、falseを返却する。
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public static function create_from_sql( $sql_, $html_escape_ = false )
	{
		$sql = $sql_->limit(0,1)->build();
		$hdb = strpos($sql, "for update") === false ?
			crow::get_hdb_reader() : crow::get_hdb_writer();
		$class_name = get_called_class();
		$rset = $hdb->query($sql);
		if( $rset === false )
		{
			crow_log::warning('fatal query:'.$sql);
			return false;
		}
		if( $rset->num_rows() <= 0 ) return false;
		return $class_name::create_from_record(
			$rset->get_row($html_escape_)
		);
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：複数のDB結果レコードから作成する（インスタンスの配列を返却）
	//
	//	返却される配列のキーは primary_key の値となる。
	//	primary_keyが複数あるテーブルの場合、キーは0からの数字の連番となる。
	//
	//	$records_ にはレコードの配列を指定する。
	//	ここでいうレコードとは、create_from_record()に指定するものと同様の仕様とする
	//--------------------------------------------------------------------------
	public static function create_array_from_record( $records_ )
	{
		$result = [];
		$class_name = get_called_class();

		$primary_checked = false;
		$primary_key = false;
		foreach( $records_ as $record )
		{
			$instance = $class_name::create_from_record($record);
			if( $primary_checked !== true )
			{
				$hdb = crow::get_hdb_reader();
				$table_design = $hdb->get_design( $instance->m_table_name );
				if( $table_design )
				{
					if( is_array($table_design->primary_key) === false )
						$primary_key = $table_design->primary_key;
				}
				$primary_checked = true;
			}
			if( $primary_key !== false ) $result[$instance->{$primary_key}] = $instance;
			else $result[] = $instance;
		}
		return $result;
	}

	//--------------------------------------------------------------------------
	//	インスタンスの作成：複数のDB結果レコードを返す SQL（crow_db_sql）から作成する
	//
	//	返却される配列のキーは primary_key になる。
	//	※primary_keyが複数ある場合は、0からの連番がキーとなる。
	//
	//	レコードが存在しなければ、空の配列を返却する。
	//	html_escape_ を true にすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public static function create_array_from_sql( $sql_, $html_escape_ = false )
	{
		$sql = $sql_->build();
		$hdb = strpos($sql, "for update") === false ?
			crow::get_hdb_reader() : crow::get_hdb_writer();

		$rset = $hdb->query($sql);
		if( $rset === false )
		{
			crow_log::notice("failed to create_array_from_sql");
			return [];
		}

		$class_name = get_called_class();
		return $class_name::create_array_from_record(
			$rset->get_rows($html_escape_)
		);
	}

	//--------------------------------------------------------------------------
	//	テーブル名取得
	//--------------------------------------------------------------------------
	public function get_table_name()
	{
		return $this->m_table_name;
	}

	//--------------------------------------------------------------------------
	//	最初のエラー取得
	//
	//	ない場合はfalse返却
	//--------------------------------------------------------------------------
	public function get_first_error()
	{
		return count($this->m_error_list) > 0 ?
			reset($this->m_error_list) : false;
	}

	//--------------------------------------------------------------------------
	//	最終エラー取得
	//
	//	ない場合はfalse返却
	//--------------------------------------------------------------------------
	public function get_last_error()
	{
		return count($this->m_error_list) > 0 ?
			end($this->m_error_list) : false;
	}

	//--------------------------------------------------------------------------
	//	全エラー取得
	//--------------------------------------------------------------------------
	public function get_errors()
	{
		return $this->m_error_list;
	}

	//--------------------------------------------------------------------------
	//	連想配列へ変換する
	//--------------------------------------------------------------------------
	public function to_named_array()
	{
		$arr = [];
		$table_design = crow::get_hdb_reader()->get_design( $this->m_table_name );
		foreach( $table_design->fields as $field )
			$arr[$field->name] = $this->{$field->name};

		return $arr;
	}

	//--------------------------------------------------------------------------
	//	標準のセレクトSQL作成
	//
	//	作成された crow_db_sql のインスタンスを返却する
	//	細かい制御はそのインスタンスに対して行えばよい
	//--------------------------------------------------------------------------
	public static function sql_select()
	{
		return get_called_class()::sql_select_all();
	}
	public static function sql_select_all()
	{
		//	各テーブルごとのモデルで継承必須
		crow_log::error( 'not implemented sql_select_all()' );
	}

	//	primary key の値を指定して select sql を作成
	public static function sql_select_one( $primary_value_ )
	{
		//	各テーブルごとのモデルで継承必須
		crow_log::error( 'not implemented sql_select_one()' );
	}

	//--------------------------------------------------------------------------
	//	行をカウントする
	//--------------------------------------------------------------------------
	public static function count( $sql_ = false )
	{
		$class_name = get_called_class();
		$sql = $sql_ !== false ? $sql_->target('count(1) as cnt') : $class_name::sql_select_all()->target('count(1) as cnt');
		$hdb = crow::get_hdb_reader();
		$row = $hdb->select_one($sql->build());
		return intval($row['cnt']);
	}

	//--------------------------------------------------------------------------
	//	セーブ
	//
	//	primary_key に値があれば update を試みて、なければ（0なら）insertを試みる。
	//
	//	下記二つのパターン時は更新/挿入の判断ができないため、本メソッドではなく、
	//	insert()、update()を使い、更新/挿入を明示すること。
	//	・primary key が存在しないテーブル
	//	・auto_incrementが指定されていないテーブル（つまり挿入時もprimarykeyの指定が必須になる）
	//--------------------------------------------------------------------------
	public function save()
	{
		$hdb = crow::get_hdb_writer();
		return $this->is_set_primary_value() ?
			$this->insert() : $this->update();
	}

	//--------------------------------------------------------------------------
	//	挿入
	//
	//	pk/aiのあるテーブルの場合、save()やcheck_and_save()を使うほうが安全
	//--------------------------------------------------------------------------
	public function insert()
	{
		$hdb = crow::get_hdb_writer();
		if( $hdb->exec_insert_with_model($this) === false )
		{
			$this->m_error_list[] = crow_msg::get('db.err.insert');
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	更新
	//
	//	pk/aiのあるテーブルの場合、save()やcheck_and_save()を使うほうが安全
	//--------------------------------------------------------------------------
	public function update()
	{
		$hdb = crow::get_hdb_writer();
		if( $hdb->exec_update_with_model($this) === false )
		{
			$this->m_error_list[] = crow_msg::get('db.err.update');
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	チェック付きセーブ
	//
	//	重複チェック、IDチェック、バリデーションチェック、セーブを同時に行う。
	//	primary key に値が格納されていれば更新、格納されていなければ挿入を行う。
	//
	//	下記二つのパターン時は更新/挿入の判断ができないため、本メソッドではなく、
	//	check_and_insert()、check_and_update()を使い、更新/挿入を明示すること。
	//	・primary key が存在しないテーブル
	//	・auto_incrementが指定されていないテーブル（つまり挿入時もprimarykeyの指定が必須になる）
	//
	//	クエリ発行後の処理を拡張したい場合には、継承したクラスでsave_crow_ext()を実装すればよい
	//	その場合、$type_にはupdate時に"update"、insert時に"insert"が渡される。
	//	呼ばれた側は、true/falseを返却すること。falseの場合には、バリデエラーと同様 m_error_list へ追加すること
	//
	//	primary key が 0 でない場合に引数にカラム名の配列を指定することで、そのカラムのみの更新となる。
	//	ただしその際のバリデーションチェックについては全カラムに対して行われる。
	//	primary key が 0 の場合の引数は無視される。
	//
	//	失敗時は false を返却。
	//--------------------------------------------------------------------------
	public function check_and_save( $update_columns_ = false )
	{
		$hdb = crow::get_hdb_writer();
		$table_design = $hdb->get_design( $this->m_table_name );

		//	プライマリ値が格納されてるなら存在チェック
		if( $this->is_set_primary_value() === true )
		{
			$row = false;
			$class_name = get_called_class();
			if( is_array($table_design->primary_key) === true )
			{
				$args = [];
				foreach( $table_design->primary_key as $pkey )
					$args[] = $this->{$pkey};
				$row = $class_name::create_from_id( $args );
			}
			else
			{
				$row = $class_name::create_from_id( $this->{$table_design->primary_key} );
			}

			if( $row === false )
			{
				$err = crow_msg::get('db.err.notfound');
				$err = str_replace(":table_name", crow_msg::get('db.'.$this->m_table_name, "(data)"), $err);
				$this->m_error_list[] = $err;
				return false;
			}
		}
		if( $this->check_unique() === false ) return false;
		if( $this->validation() === false ) return false;

		//	処理実行
		if( $this->is_set_primary_value() === true )
		{
			//	update
			if( $hdb->exec_update_with_model($this, $update_columns_) === false )
			{
				$this->m_error_list[] = crow_msg::get('db.err.update');
				return false;
			}
		}
		else
		{
			//	insert
			if( $hdb->exec_insert_with_model($this) === false )
			{
				$this->m_error_list[] = crow_msg::get('db.err.insert');
				return false;
			}
		}

		//	拡張された後処理の実行
		if( method_exists($this, 'save_crow_ext') )
		{
			if( $this->save_crow_ext() === false )
				return false;
		}
		if( method_exists($this, 'save_ext') )
		{
			if( $this->save_ext() === false )
				return false;
		}

		//	認証のauto_updateが有効の場合、set_logined_rowを自動実行する
		if( crow_config::get('auth.db.auto_update', '') == 'true' )
		{
			if( crow_config::get('auth.db.table', '') == $this->m_table_name )
			{
				$privilege = crow_config::get('auth.privilege');
				if( strlen($privilege) <= 0 ) $privilege = "auth";

				$exists_id = crow_auth::get_logined_id($privilege);
				if( $exists_id == $this->{$table_design->primary_key} )
				{
					crow_auth::set_logined_row($this, $privilege);
				}
			}
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	チェック付き挿入
	//
	//	pk/aiのあるテーブルの場合、check_and_save()を使うほうが安全
	//--------------------------------------------------------------------------
	public function check_and_insert()
	{
		//	チェック
		if( $this->check_unique() === false ) return false;
		if( $this->validation() === false ) return false;

		//	処理実行
		return $this->insert();
	}

	//--------------------------------------------------------------------------
	//	チェック付き更新
	//
	//	pk/aiのあるテーブルの場合、check_and_save()を使うほうが安全
	//--------------------------------------------------------------------------
	public function check_and_update()
	{
		$hdb = crow::get_hdb_writer();

		//	チェック
		if( $this->check_unique() === false ) return false;
		if( $this->validation() === false ) return false;

		//	処理実行
		return $this->update();
	}

	//--------------------------------------------------------------------------
	//	チェック付き更新をした後に削除を行う
	//
	//	ダミー値で更新した後削除フラグを立てたい場合などに使う
	//--------------------------------------------------------------------------
	public function check_and_save_and_trash( $update_columns_ = false )
	{
		return $this->check_and_save($update_columns_) === false || $this->trash() === false
			? false : true;
	}

	//--------------------------------------------------------------------------
	//	ゴミ箱に移動（削除フラグがなければ完全削除となる）
	//--------------------------------------------------------------------------
	public function trash()
	{
		//	拡張された処理の実行
		if( method_exists($this, 'trash_crow_ext') )
		{
			if( $this->trash_crow_ext() === false )
				return false;
		}
		if( method_exists($this, 'trash_ext') )
		{
			if( $this->trash_ext() === false )
				return false;
		}

		if( crow::get_hdb_writer()->exec_trash_with_model($this) === false )
		{
			$this->m_error_list[] = crow_msg::get('db.err.trash');
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	完全削除
	//--------------------------------------------------------------------------
	public function delete()
	{
		//	拡張された処理の実行
		if( method_exists($this, 'delete_crow_ext') )
		{
			if( $this->delete_crow_ext() === false )
				return false;
		}
		if( method_exists($this, 'delete_ext') )
		{
			if( $this->delete_ext() === false )
				return false;
		}

		if( crow::get_hdb_writer()->exec_delete_with_model($this) === false )
		{
			$this->m_error_list[] = crow_msg::get('db.err.delete');
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	リクエスト情報からメンバフィールドへマップする
	//	自インスタンスを返却する
	//
	//	例）
	//		db仕様で、titleというフィールドが定義されているテーブルの場合、
	//		GETやPOSTのリクエストパラメータで"title="が渡されているなら
	//		$this->title に格納される。
	//
	//		値をtrimして値を入力する必要がない場合には、引数にfalseを指定する。
	//--------------------------------------------------------------------------
	public function input_from_request( $trim_ = true )
	{
		$hdb = crow::get_hdb_reader();
		$table_design = $hdb->get_design( $this->m_table_name );

		//	リクエストの内容をマッピング
		foreach( $table_design->fields as $field )
		{
			//	日付パラメータの場合
			if( $field->type == "unixtime" || $field->type == "datetime" )
			{
				$d_y = crow_request::get($this->m_table_name."_".$field->name."_y",false);
				if( $d_y === false ) $d_y = crow_request::get($field->name."_y",0);
				$d_m = crow_request::get($this->m_table_name."_".$field->name."_m",false);
				if( $d_m === false ) $d_m = crow_request::get($field->name."_m",0);
				$d_d = crow_request::get($this->m_table_name."_".$field->name."_d",false);
				if( $d_d === false ) $d_d = crow_request::get($field->name."_d",0);
				$d_h = crow_request::get($this->m_table_name."_".$field->name."_h",false);
				if( $d_h === false ) $d_h = crow_request::get($field->name."_h",0);
				$d_i = crow_request::get($this->m_table_name."_".$field->name."_i",false);
				if( $d_i === false ) $d_i = crow_request::get($field->name."_i",0);
				$d_s = crow_request::get($this->m_table_name."_".$field->name."_s",false);
				if( $d_s === false ) $d_s = crow_request::get($field->name."_s",0);

				$d_y = intval($d_y);
				$d_m = intval($d_m);
				$d_d = intval($d_d);
				$d_h = intval($d_h);
				$d_i = intval($d_i);
				$d_s = intval($d_s);
				if( $d_y != 0 || $d_m != 0 || $d_d != 0 || $d_h != 0 || $d_i != 0 || $d_s != 0 )
				{
					$this->{$field->name} = mktime($d_h, $d_i, $d_s, $d_m, $d_d, $d_y);
					$this->{$field->name."_y"} = intval($d_y);
					$this->{$field->name."_m"} = intval($d_m);
					$this->{$field->name."_d"} = intval($d_d);
					$this->{$field->name."_h"} = intval($d_h);
					$this->{$field->name."_i"} = intval($d_i);
					$this->{$field->name."_s"} = intval($d_s);
					continue;
				}
			}

			//	座標パラメータが、緯度・経度で指定された場合
			if( $field->type == "geometry" )
			{
				$lat = crow_request::get($this->m_table_name."_".$field->name."_lat",false);
				if( $lat === false ) $lat = crow_request::get($field->name."_lat",false);
				$lng = crow_request::get($this->m_table_name."_".$field->name."_lng",false);
				if( $lng === false ) $lng = crow_request::get($field->name."_lng",false);
				if( $lat !== false && $lng !== false )
				{
					$this->{$field->name} = [$lat, $lng];
					$this->{$field->name."_lat"} = $lat;
					$this->{$field->name."_lng"} = $lng;
					continue;
				}
			}

			//	bitの場合
			if( $field->type == "bit" )
			{
				$bits = crow_request::get($this->m_table_name."_".$field->name, false);
				if( $bits === false ) $bits = crow_request::get($field->name, false);

				if( $bits === false ) ;

				//	リクエスト値が配列だった場合は、全てOR結合する
				else if( is_array($bits) === true )
				{
					$bit_keys = get_called_class()::{'get_'.$field->name.'_keys'}();
					$this->{$field->name} = 0;
					foreach( $bits as $bit )
					{
						$ibit = intval($bit);
						if( in_array($ibit, $bit_keys) === true )
							$this->{$field->name} |= $ibit;
					}
				}

				//	リクエスト値が配列でない場合は、そのまま数値として取り込む
				else
				{
					$this->{$field->name} = intval($bits);
				}
				continue;
			}

			//	テーブル名の指定がある場合は、優先する
			$val = crow_request::get($this->m_table_name."_".$field->name, false);
			if( $val === false ) $val = crow_request::get($field->name, false);
			if( $val !== false )
			{
				//	ここでは型変換を行わない。
				//	数値かどうかなどのバリデーションチェックがあるため。
				switch( $field->type )
				{
					case "geometry":
						if( is_array($val) )
						{
							//	座標型を配列で渡す場合には、要素が2個以上でないといけない
							if( count($val) < 2 )
							{
								crow_log::error("not enough parameter of geometry");
								break;
							}
							$this->{$field->name} = [$val[0], $val[1]];
							$this->{$field->name."_lat"} = $val[0];
							$this->{$field->name."_lng"} = $val[1];
						}
						break;

					case "boolean":
						if( is_array($val) ) $this->{$field->name} = count($val) > 0 ? true : false;
						else if( $val === "true" || $val == 1 ) $this->{$field->name} = true;
						else if( $val === "false" || $val == 0 ) $this->{$field->name} = false;
						break;

					default:
						$this->{$field->name} = $trim_ ? trim($val) : $val;
						break;
				}

				//	電話番号をマップするときは、全角を半角へ変換する
				if( $field->type=="telno" )
				{
					$this->{$field->name} = mb_str_replace("０","0", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("１","1", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("２","2", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("３","3", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("４","4", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("５","5", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("６","6", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("７","7", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("８","8", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("９","9", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("ー","-", $this->{$field->name});
					$this->{$field->name} = mb_str_replace("－","-", $this->{$field->name});
				}

				//	unixtime/datetimeをマップするときは分解する
				if( $field->type=="unixtime" || $field->type=="datetime" )
				{
					//	全て数字でないなら、文字列で指定された可能性があるのでパースする
					$d = $this->{$field->name};
					if( ! preg_match("/^[0-9]+$/",$d) )
					{
						$d = intval(strtotime($this->{$field->name}));
						$this->{$field->name} = $d;
					}
					$d = intval($d);

					$this->{$field->name."_y"} = intval(date("Y",$d));
					$this->{$field->name."_m"} = intval(date("m",$d));
					$this->{$field->name."_d"} = intval(date("d",$d));
					$this->{$field->name."_h"} = intval(date("H",$d));
					$this->{$field->name."_i"} = intval(date("i",$d));
					$this->{$field->name."_s"} = intval(date("s",$d));
				}
			}
		}
		return $this;
	}


	//--------------------------------------------------------------------------
	//	DBの結果レコードからメンバフィールドへマップする
	//	自インスタンスを返却する
	//--------------------------------------------------------------------------
	public function input_from_record( $record_ )
	{
		if( $record_ )
		{
			$hdb = crow::get_hdb_reader();
			$table_design = $hdb->get_design( $this->m_table_name );
			foreach( $table_design->fields as $field )
			{
				//	null許容の場合
				if( $field->nullable === true && $record_[$field->name] === null )
				{
					$this->{$field->name} = null;
					continue;
				}

				//	geometryの場合
				if( $field->type == "geometry" )
				{
					if( isset($record_[$field->name."_lat"]) &&
						isset($record_[$field->name."_lng"])
					){
						$this->{$field->name} =
						[
							doubleval($record_[$field->name."_lat"]),
							doubleval($record_[$field->name."_lng"])
						];
						$this->{$field->name."_lat"} = $this->{$field->name}[0];
						$this->{$field->name."_lng"} = $this->{$field->name}[1];
					}
					continue;
				}

				//	passwordの場合、格納しない
				if( $field->type == "password" )
				{
					$this->{$field->name} = '';
					continue;
				}

				//	それ以外の場合
				if( isset($record_[$field->name]) )
				{
					//	型変換しながら格納
					switch( $field->type )
					{
						case "tinyint":
						case "int":
						case "bigint":
							$this->{$field->name} = intval($record_[$field->name]);
							break;
						case "unixtime":
							$t = intval($record_[$field->name]);
							$this->{$field->name} = $t;
							$this->{$field->name."_y"} = intval(date("Y",$t));
							$this->{$field->name."_m"} = intval(date("m",$t));
							$this->{$field->name."_d"} = intval(date("d",$t));
							$this->{$field->name."_h"} = intval(date("H",$t));
							$this->{$field->name."_i"} = intval(date("i",$t));
							$this->{$field->name."_s"} = intval(date("s",$t));
							break;
						case "datetime":
							$t = strtotime($record_[$field->name]);
							$this->{$field->name} = $t;
							$this->{$field->name."_y"} = intval(date("Y",$t));
							$this->{$field->name."_m"} = intval(date("m",$t));
							$this->{$field->name."_d"} = intval(date("d",$t));
							$this->{$field->name."_h"} = intval(date("H",$t));
							$this->{$field->name."_i"} = intval(date("i",$t));
							$this->{$field->name."_s"} = intval(date("s",$t));
							break;
						case "float":
							$this->{$field->name} = floatval($record_[$field->name]);
							break;
						case "double":
							$this->{$field->name} = doubleval($record_[$field->name]);
							break;
						case "boolean":
							$this->{$field->name} = $record_[$field->name]==1 ? true : false;
							break;
						case "varchar":
						case "text":
						case "bigtext":
						case "url":
						case "mail":
						case "telno":
						case "varcrypt":
						case "crypt":
						case "bigcrypt":
						case "mailcrypt":
						default:
							$this->{$field->name} = $record_[$field->name];
							break;
					}
				}
			}
		}
		return $this;
	}


	//--------------------------------------------------------------------------
	//	DB仕様でunq指定されたフィールドの重複チェックを行う。
	//
	//	重複するレコードがないなら、trueを返却。
	//	primary_key が格納されている場合は、そのレコードは除外してチェックする
	//--------------------------------------------------------------------------
	public function check_unique()
	{
		$hdb = crow::get_hdb_reader();
		$table_design = $hdb->get_design( $this->m_table_name );
		if( $table_design === false ) return false;

		//	(select プライマリキー) or (select *)
		$sql = false;
		if( $table_design->primary_key )
		{
			$sql = crow_db_sql::create_select($table_design->primary_key);
		}
		else
		{
			$sql = crow_db_sql::create_select();
		}

		//	from テーブル
		$sql->from($table_design->name);

		//	削除フラグがあるなら条件に。
		if( $table_design->deleted )
			$sql->and_where($table_design->deleted, 0);

		//	where組立（primary）
		foreach( $table_design->fields as $field )
		{
			if( $field->primary_key )
			{
				if( $this->{$field->name} != $field->default_value )
					$sql->and_where($field->name, "<>", $this->{$field->name});
				break;
			}
		}

		//	where組立（unique）
		$is_first_col = true;
		$field_names = [];
		$sql->and_where_open();
		foreach( $table_design->fields as $field )
		{
			if( $field->primary_key ) continue;
			if( $field->unique )
			{
				//	値が格納されている場合のみ、uniqueチェックの対象とする
				$target = false;
				switch( $field->type )
				{
					case "tinyint":
					case "int":
					case "bigint":
					case "unixtime":
					case "datetime":
					case "double":
						if( $this->{$field->name} != 0 ) $target = true;
						break;

					case "geometry":
						if( $this->{$field->name."_lat"} != 0 &&
							$this->{$field->name."_lng"} != 0
						) $target = true;
						break;

					case "boolean":
						$target = true;
						break;

					case "varchar":
					case "text":
					case "bigtext":
					case "url":
					case "mail":
					case "telno":
					case "varcrypt":
					case "crypt":
					case "bigcrypt":
					case "mailcrypt":
						if( strlen($this->{$field->name}) > 0 ) $target = true;
						break;
				}
				if( $target === false ) continue;

				if( $is_first_col === true )
				{
					$sql->where($field->name, $this->{$field->name});
					$is_first_col = false;
				}
				else
				{
					$sql->or_where($field->name, $this->{$field->name});
				}
				$field_names[] = crow_msg::get( "db.".$this->m_table_name.".".$field->name, "(item)" );
			}
		}
		$sql->where_close();

		//	チェック不要なら正常とする
		if( count($field_names) <= 0 ) return true;

		//	チェック
		$rset = $hdb->query($sql->build());
		if( $rset->num_rows() > 0 )
		{
			$this->m_error_list[] = str_replace(":field_names", implode(", ", $field_names), crow_msg::get('db.err.duplicate'));
			return false;
		}
		return true;
	}


	//--------------------------------------------------------------------------
	//	バリデーションチェックにおけるエラーを、エラーリストに追加する
	//--------------------------------------------------------------------------
	public function push_validation_error( $field_name_, $error_ )
	{
		$this->m_validation_errors[$field_name_][] = $error_;
		$this->m_error_list[] = $error_;
	}


	//--------------------------------------------------------------------------
	//	バリデーションチェックにおけるエラーを、全て取得
	//--------------------------------------------------------------------------
	public function get_validation_errors()
	{
		return $this->m_validation_errors;
	}

	//--------------------------------------------------------------------------
	//	バリデーションチェックにおけるエラーを文字列として取得する。
	//	エラーが複数あった場合、$span_ で指定した文字列を間に挟んだ文字列を作成する
	//--------------------------------------------------------------------------
	public function get_validation_errors_with_span( $span_ )
	{
		$output = "";
		foreach( $this->m_validation_errors as $error_list )
		{
			foreach( $error_list as $error )
			{
				if( $output != "" ) $output .= $span_;
				$output .= $error;
			}
		}
		return $output;
	}

	//--------------------------------------------------------------------------
	//	バリデーションチェックにおけるエラーを文字列として取得する。
	//	エラーが複数あった場合、エラー一つにつき $open_と$close_ で括った文字列を作成する
	//--------------------------------------------------------------------------
	public function get_validation_errors_with_tag( $open_, $close_ )
	{
		$output = "";
		foreach( $this->m_validation_errors as $error_list )
		{
			foreach( $error_list as $error )
				$output .= $open_.$error.$close_;
		}
		return $output;
	}

	//--------------------------------------------------------------------------
	//	DB仕様に従って値チェックを行う
	//
	//	正常だった場合はtrue、不正だった場合はfalseが返却される。
	//	エラー時のメッセージは、get_validation_errors() や類似のメソッドで取得できる。
	//	この時のエラーメッセージは crow_validation の仕様に従ったものとなる。
	//
	//	チェックを拡張したい場合には、継承したクラスでvalidation_crow_ext()を実装すればよい
	//--------------------------------------------------------------------------
	public function validation()
	{
		$this->m_validation_errors = [];

		$hdb = crow::get_hdb_reader();
		$table_design = $hdb->get_design( $this->m_table_name );
		foreach( $table_design->fields as $field )
			$this->validation_field( $field->name );

		//	拡張されたバリデーションの実行
		if( method_exists($this, 'validation_crow_ext') )
			$this->validation_crow_ext();

		//	テーブル拡張用のチェック
		if( method_exists($this, 'validation_ext') )
			$this->validation_ext();

		return count($this->m_validation_errors) > 0 ? false : true;
	}

	//--------------------------------------------------------------------------
	//	フィールド名を指定して、値チェックを行う
	//--------------------------------------------------------------------------
	public function validation_field( $field_name_ )
	{
		$hdb = crow::get_hdb_reader();
		$table_design = $hdb->get_design( $this->m_table_name );
		$is_error = true;
		foreach( $table_design->fields as $field )
		{
			if( $field->name != $field_name_ ) continue;

			//	null許容の場合は、値未設定でチェックスルー
			if( $field->nullable === true && $this->{$field->name} === null )
				continue;

			$value = $this->{$field->name};
			$name = crow_msg::get( "db.".$this->m_table_name.".".$field->name, "(item)" );

			//	tinyint, int, bigint, unixtime, datetime, bit
			if( $field->type == "tinyint" || $field->type == "int" || $field->type == "bigint" ||
				$field->type == "unixtime" || $field->type == "datetime" || $field->type == "bit"
			){
				//	数値フォーマットであるか？
				if( strlen($value) > 0 && crow_validation::check_num($value) === false )
				{
					$err = str_replace(":name", $name, crow_msg::get('validation.err.number.format'));
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$int_val = intval($value);
					if( $field->valid_range_from !== false && $field->valid_range_from === $field->valid_range_to &&
						$int_val != intval($field->valid_range_from)
					){
						$err = str_replace(":num", $field->valid_range_from,
							str_replace(":name", $name, crow_msg::get('validation.err.num.range.just')));
						$this->push_validation_error($field_name_, $err);
						break;
					}
					else
					{
						if( $field->valid_range_from !== false && $int_val < intval($field->valid_range_from) )
						{
							$err = str_replace(":min", $field->valid_range_from,
								str_replace(":name", $name, crow_msg::get('validation.err.num.range.from')));
							$this->push_validation_error($field_name_, $err);
							break;
						}
						if( $field->valid_range_to !== false && $int_val > intval($field->valid_range_to) )
						{
							$err = str_replace(":max", $field->valid_range_to,
								str_replace(":name", $name, crow_msg::get('validation.err.num.range.to')));
							$this->push_validation_error($field_name_, $err);
							break;
						}
					}
				}

				//	mustのチェック
				if( $field->must && intval($value) == 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	tinyintの範囲チェック
				if( $field->type=="tinyint" )
				{
					//	deletedフラグの場合は、0か1のみ許可
					if( $table_design->deleted == $field->name )
					{
						if( $value!=0 && $value!=1 )
						{
							$err = crow_msg::get('validation.err.deleted');
							$err = str_replace(":name", $name, $err);
							$this->push_validation_error($field_name_, $err);
							break;
						}
					}
				}
			}
			//	float/double
			else if( $field->type == "float" || $field->type == "double" )
			{
				//	少数フォーマットであるか？
				if( strlen($value) > 0 && crow_validation::check_dec($value) === false )
				{
					$err = str_replace(":name", $name, crow_msg::get('validation.err.number.format'));
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$float_val = floatval($value);
					if( $field->valid_range_from !== false && $field->valid_range_from === $field->valid_range_to &&
						intval($float_val * 1000) != intval(floatval($field->valid_range_from) * 1000)
					){
						$err = str_replace(":num", $field->valid_range_from,
							str_replace(":name", $name, crow_msg::get('validation.err.num.range.just')));
						$this->push_validation_error($field_name_, $err);
						break;
					}
					else
					{
						if( $field->valid_range_from !== false && $float_val < floatval($field->valid_range_from) )
						{
							$err = str_replace(":min", $field->valid_range_from,
								str_replace(":name", $name, crow_msg::get('validation.err.num.range.from')));
							$this->push_validation_error($field_name_, $err);
							break;
						}
						else if( $field->valid_range_to !== false && $float_val > floatval($field->valid_range_to) )
						{
							$err = str_replace(":max", $field->valid_range_to,
								str_replace(":name", $name, crow_msg::get('validation.err.num.range.to')));
							$this->push_validation_error($field_name_, $err);
							break;
						}
					}
				}

				//	mustのチェック
				if( $field->must && ($value == 0 || strlen($value) <= 0) )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	geometry
			else if( $field->type == "geometry" )
			{
				//	少数フォーマットであるか？
				if(
					(strlen($value[0]) > 0 && crow_validation::check_dec($value[0]) === false) ||
					(strlen($value[1]) > 0 && crow_validation::check_dec($value[1]) === false)
				){
					$err = crow_msg::get('validation.err.geometry.format');
					$err = str_replace(":name", $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	mustのチェック
				if( $field->must && ($value[0]==0 || $value[1]==0) )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	boolean
			else if( $field->type == "boolean" )
			{
				if( $value!==false && $value!==true )
				{
					$err = crow_msg::get('validation.err.boolean');
					$err = str_replace(":name", $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	varchar
			else if( $field->type == "varchar" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$range_valid = $this->valid_range_str($field, $name, $value);
					if( $range_valid !== true )
					{
						$this->push_validation_error($field_name_, $range_valid);
						break;
					}
				}

				//	ケース、書式チェック
				$err = $this->valid_cases($field, $name, $value);
				if( $err === true ) $err = $this->valid_regexp($field, $name, $value);
				if( $err !== true )
				{
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数超過
				if( crow_validation::check_str_over($value, $field->size) )
				{
					$err = crow_msg::get('validation.err.varchar.over');
					$err = str_replace(":name", $name, $err);
					$err = str_replace(":size", $field->size, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	text, bigtext
			else if( $field->type == "text" || $field->type == "bigtext" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$range_valid = $this->valid_range_str($field, $name, $value);
					if( $range_valid !== true )
					{
						$this->push_validation_error($field_name_, $range_valid);
						break;
					}
				}

				//	ケース、書式チェック
				$err = $this->valid_cases($field, $name, $value);
				if( $err === true ) $err = $this->valid_regexp($field, $name, $value);
				if( $err !== true )
				{
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	url
			else if( $field->type == "url" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$range_valid = $this->valid_range_str($field, $name, $value);
					if( $range_valid !== true )
					{
						$this->push_validation_error($field_name_, $range_valid);
						break;
					}
				}

				//	URLフォーマット
				if( strlen($value) > 0 )
				{
					if( crow_validation::check_url($value) === false )
					{
						$err = crow_msg::get('validation.err.url');
						$err = str_replace(':name', $name, $err);
						$this->push_validation_error($field_name_, $err);
						break;
					}
				}
			}
			//	mail
			else if( $field->type == "mail" || $field->type == "mailcrypt" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$range_valid = $this->valid_range_str($field, $name, $value);
					if( $range_valid !== true )
					{
						$this->push_validation_error($field_name_, $range_valid);
						break;
					}
				}

				//	メールフォーマット
				if( strlen($value) > 0 )
				{
					if( crow_validation::check_mail_addr($value) === false )
					{
						$err = crow_msg::get('validation.err.mail');
						$err = str_replace(":name", $name, $err);
						$this->push_validation_error($field_name_, $err);
						break;
					}
				}
			}
			//	telno
			else if( $field->type == "telno" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$range_valid = $this->valid_range_str($field, $name, $value);
					if( $range_valid !== true )
					{
						$this->push_validation_error($field_name_, $range_valid);
						break;
					}
				}

				//	電話番号フォーマット
				if( strlen($value) > 0 )
				{
					if( crow_validation::check_telno($value) === false )
					{
						$err = crow_msg::get('validation.err.telno');
						$err = str_replace(":name", $name, $err);
						$this->push_validation_error($field_name_, $err);
						break;
					}
				}
			}
			//	varcrypt
			else if( $field->type == "varcrypt" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}

				//	文字数範囲チェック
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					$range_valid = $this->valid_range_str($field, $name, $value);
					if( $range_valid !== true )
					{
						$this->push_validation_error($field_name_, $range_valid);
						break;
					}
				}

				//	文字数超過
				if( crow_validation::check_str_over($value, $field->size) )
				{
					$err = crow_msg::get('validation.err.varchar.over');
					$err = str_replace(":name", $name, $err);
					$err = str_replace(":size", $field->size, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	crypt, bigcrypt
			else if( $field->type == "crypt" || $field->type == "bigcrypt" )
			{
				//	mustのチェック
				if( $field->must && strlen($value) <= 0 )
				{
					$err = crow_msg::get('validation.err.must');
					$err = str_replace(':name', $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}
			//	password
			else if( $field->type == "password" )
			{
				//	mustのチェック
				//	insertの時はチェックするが、updateの時は指定がなければ更新しないのでチェックなし
				//	他のフィールドは create_from_request_with_idの際に既存データがメンバに入るが、
				//	パスワードフィールドはその時に空でセットされるため、このチェックが必要になる。
				if( $field->must )
				{
					if( $this->is_set_primary_value() === false &&
						strlen($value) <= 0
					){
						$err = crow_msg::get('validation.err.must');
						$err = str_replace(':name', $name, $err);
						$this->push_validation_error($field_name_, $err);
						break;
					}
				}

				//	文字数範囲チェック
				//	insertの時はチェックし、updateの時は指定がある場合のみチェックする
				if( $field->valid_range_from !== false || $field->valid_range_to !== false )
				{
					if( $this->is_set_primary_value() === false || strlen($value) > 0 )
					{
						$range_valid = $this->valid_range_str($field, $name, $value);
						if( $range_valid !== true )
						{
							$this->push_validation_error($field_name_, $range_valid);
							break;
						}
					}
				}

				//	書式チェック
				if( strlen($value) > 0 )
				{
					$err = $this->valid_regexp($field, $name, $value);
					if( $err !== true )
					{
						$this->push_validation_error($field_name_, $err);
						break;
					}
				}

				//	文字数超過
				if( crow_validation::check_str_over($value, $field->size) )
				{
					$err = crow_msg::get('validation.err.password.over');
					$err = str_replace(":name", $name, $err);
					$err = str_replace(":size", $field->size, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}

			//	constパターンのチェック
			//	ただしbit型については行わない。bit型は組み合わせで何にでもなりえるので数値のみとする
			if( $field->type != "bit" && count($field->const_array) > 0 && strlen($value) > 0 )
			{
				$const_res = false;
				foreach( $field->const_array as $const_val )
				{
					$const_chk = $const_val;
					if( substr($const_chk,0,1) == "'" || substr($const_chk,0,1) == '"' )
						$const_chk = mb_substr($const_chk, 1, mb_strlen($const_chk)-2);

					if( $const_chk == $value )
					{
						$const_res = true;
						break;
					}
				}
				if( $const_res == false )
				{
					$err = crow_msg::get('validation.err.const');
					$err = str_replace(":name", $name, $err);
					$this->push_validation_error($field_name_, $err);
					break;
				}
			}

			$is_error = false;
			break;
		}
		return $is_error ? false : true;
	}
	private function valid_cases($field_, $name_, $value_)
	{
		//	ケース指定がないなら正常
		if( $field_->valid_charcase === false ) return true;

		//	正規表現の式を作成
		$regexp = "";
		$symbol = "";
		if( strstr($field_->valid_charcase, "a") !== false ){ $regexp .= "a-z"; $symbol .= "a"; }
		if( strstr($field_->valid_charcase, "A") !== false ){ $regexp .= "A-Z"; $symbol .= "A"; }
		if( strstr($field_->valid_charcase, "0") !== false ){ $regexp .= "0-9"; $symbol .= "0"; }
		if( strstr($field_->valid_charcase, "_") !== false ){ $regexp .= " -\\/:-@[-`{-~"; $symbol .= "_"; }
		if( $regexp == "" ) return true;

		//	チェック
		$regexp = "/^[".$regexp."]*$/";
		if( preg_match($regexp, $value_) !== 1 )
		{
			$err = crow_msg::get('validation.err.case.format');
			$err = str_replace(":case", crow_msg::get('validation.err.case.'.$symbol), $err);
			$err = str_replace(":name", $name_, $err);
			return $err;
		}
		return true;
	}
	private function valid_regexp($field_, $name_, $value_)
	{
		//	正規表現指定がないなら正常
		if( $field_->valid_regexp === false ) return true;
		if( preg_match($field_->valid_regexp, $value_) !== 1 )
		{
			$err = crow_msg::get('validation.err.regexp');
			$err = str_replace(":name", $name_, $err);
			return $err;
		}
		return true;
	}
	private function valid_range_str($field_, $name_, $value_)
	{
		$len = mb_strlen($value_);

		if( $field_->valid_range_from !== false && $field_->valid_range_from === $field_->valid_range_to &&
			$len != intval($field_->valid_range_from)
		){
			return str_replace(":len", $field_->valid_range_from,
				str_replace(":name", $name_, crow_msg::get('validation.err.str.range.just')));
		}
		if( $field_->valid_range_from !== false && $len < intval($field_->valid_range_from) )
		{
			return str_replace(":min", $field_->valid_range_from,
				str_replace(":name", $name_, crow_msg::get('validation.err.str.range.from')));
		}
		if( $field_->valid_range_to !== false && $len > intval($field_->valid_range_to) )
		{
			return str_replace(":max", $field_->valid_range_to,
				str_replace(":name", $name_, crow_msg::get('validation.err.str.range.to')));
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	プライマリキーに値がセットされているか調べる
	//--------------------------------------------------------------------------
	public function is_set_primary_value()
	{
		$table_design = crow::get_hdb_reader()->get_design( $this->m_table_name );
		if( $table_design->primary_key === false ) return false;

		//	単一プライマリーキーの場合
		if( is_array($table_design->primary_key) === false )
		{
			$field = $table_design->fields[$table_design->primary_key];
			switch( $field->type )
			{
				case 'varchar':
				case 'text':
				case 'bigtext':
				case 'url':
				case 'mail':
				case 'telno':
				case 'varcrypt':
				case 'crypt':
				case 'bigcrypt':
				case 'mailcrypt':
				case 'password':
					//	テキスト系フィールドの場合、文字列長で判定
					return strlen($this->{$table_design->primary_key}) > 0;
			}

			//	数字系フィールドの場合、0かどうかで判定
			return $this->{$table_design->primary_key} != 0;
		}

		//	複合プライマリキーの場合
		foreach( $table_design->primary_key as $pkey_name )
		{
			$field = $table_design->fields[$pkey_name];
			switch( $field->type )
			{
				case 'varchar':
				case 'text':
				case 'bigtext':
				case 'url':
				case 'mail':
				case 'telno':
				case 'varcrypt':
				case 'crypt':
				case 'bigcrypt':
				case 'mailcrypt':
				case 'password':
					//	テキスト系フィールドの場合、文字列長で判定
					if( strlen($this->{$pkey_name}) <= 0 ) return false;
			}

			//	数字系フィールドの場合、0かどうかで判定
			if( $this->{$pkey_name} == 0 ) return false;
		}
		return true;
	}


	//	テーブル名
	protected $m_table_name = "";

	//	エラーリスト
	protected $m_error_list = [];

	//	バリデーションエラーリスト
	protected $m_validation_errors = [];
}
?>
