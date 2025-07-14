<?php
/*

	SQLクラス


	最初に実行したいクエリ種に合わせてインスタンスを作成する
	・create_select()
	・create_update()
	・create_insert()
	・create_delete()

	SELECT時のフィールドの指定

		基本的には create_select() の引数で指定できるが、
		target() メソッドでも同様に指定可能。

		//	select user_id, age
		$sql = crow_db_sql::create_select( "user_id", "age" );

		//	または、
		$sql = crow_db_sql::create_selec()->target( "user_id", "age" );

		暗号化されたフィールドをデコードして取得したい場合には、
		フィールド名の先頭にシャープ(#)を付与する。

		//	select user_id, name
		$sql = crow_db_sql::create_select( "user_id", "#name" );


	FROMの指定例

		//	select * from user
		$sql = crow_db_sql::create_select()
			->from( "user" );

		//	select name,age from user
		$sql = crow_db_sql::create_select("name","age")
			->from( "user" );

		//	または、
		$sql = crow_db_sql::create_select()
			->target("name","age")
			->from( "user" );


	WHEREの指定例

		//	where deleted=0
		$sql->where( "deleted", "0" );

		//	where age <= 10
		$sql->where( "age", "<=", "10" );

		//	where deleted=0 and name='taro'
		$sql->where("deleted", 0)
			->and_where("name", "taro")

		//	where deleted=0 and (name='hanako' or name='taro')
		$sql->where("deleted", 0)
			->and_where_open()
			->where("name","hanako")
			->or_where("name","taro")
			->where_close()
			;

		//	括弧のネスト例
		//
		//	where deleted=0 or (name="taro" and (age=10 or age=20))
		$sql->where("deleted", 0)
			->or_where_open()
			->where(name, "taro")
			->and_where_open()
			->where(age,10)->or_where(age,20)
			->where_close()
			->where_close()
			;

	大文字小文字を区別せずに比較する _keyval を使う
		$sql->and_where_rawkeyval("name collate utf8_unicode_ci", '=', 'Bigsmall');

	並び替えのキーを複数指定したい場合
		$sql->and_orderby("name")->and_orderby("birthday");

	並び替えで大文字小文字を区別しないようにする
		$sql->orderby("name", "utf8_unicode_ci");

	復号したデータとWHEREで比較する場合、第四引数 true まで指定する。

		例）blobdataというフィールドがcrowにより暗号化されている場合、
			"taro"と一致するかを調べる。
		$sql->where( "blobdata", "=", "taro", true );


*/
class crow_db_sql
{
	//--------------------------------------------------------------------------
	//	SELECT 文の作成を開始する
	//
	//	crow_db_sqlのインスタンスを返却する。
	//	引数に何も指定しない場合は select * のSQLとなり、
	//	引数に何か指定した場合には、指定した数だけカンマで区切られたターゲットとなる。
	//	例）
	//		「select *」の場合は下記のように記述する。
	//
	//			crow_db_sql::select();
	//
	//
	//		「select test_id, count(test_dat) as cnt」の場合は下記のように記述する。
	//
	//			crow_db_sql::select( 'test_id', 'count(test_dat) as cnt' );
	//
	//		暗号化された特定フィールドをデコードして取得するためには、
	//		フィールド名の先頭に"#"を付与する。
	//		「select user_id, AES_DECRYPT(name) as name」の場合は下記のように記述する。
	//
	//			crow_db_sql::select( 'user_id', '#name' );
	//
	//--------------------------------------------------------------------------
	public static function create_select( /*...*/ )
	{
		$sql = new crow_db_sql();
		$sql->m_command = "select";
		$sql->m_targets = func_get_args();
		return $sql;
	}

	//--------------------------------------------------------------------------
	//	INSERT 文の作成を開始する
	//--------------------------------------------------------------------------
	public static function create_insert()
	{
		$sql = new crow_db_sql();
		$sql->m_command = "insert";
		return $sql;
	}

	//--------------------------------------------------------------------------
	//	UPDATE 文の作成を開始する
	//--------------------------------------------------------------------------
	public static function create_update()
	{
		$sql = new crow_db_sql();
		$sql->m_command = "update";
		return $sql;
	}

	//--------------------------------------------------------------------------
	//	DELETE 文の作成を開始する
	//--------------------------------------------------------------------------
	public static function create_delete()
	{
		$sql = new crow_db_sql();
		$sql->m_command = "delete";
		return $sql;
	}

	//--------------------------------------------------------------------------
	//	SELECT のターゲットを指定（create_select()の引数と同様）
	//--------------------------------------------------------------------------
	public function target( /*...*/ )
	{
		$this->m_command = "select";
		$this->m_targets = func_get_args();
		return $this;
	}

	//	クォートで括らない版
	public function target_raw( /*...*/ )
	{
		$this->m_command = "select";
		$this->m_targets = [];
		foreach( func_get_args() as $v )
			$this->m_targets[] = "NC#".$v;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	FROM 句指定
	//--------------------------------------------------------------------------
	public function from( $from_ )
	{
		$this->m_from = $from_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	パスワードのフィールドがある場合はINSERT/UPDATEの前に、このメソッドで指定しておく
	//--------------------------------------------------------------------------
	public function pw_fields( $pw_fields_ )
	{
		$this->m_pw_fields = explode( ",", $pw_fields_ );
		return $this;
	}

	//--------------------------------------------------------------------------
	//	INSERT/UPDATE時のVALUES/SET を指定
	//
	//	KEY=VALUEの連想配列で指定する
	//--------------------------------------------------------------------------
	public function values( $values_ )
	{
		$this->m_values = $values_;
		$this->m_values_rawval = false;

		foreach( $this->m_values as $k => $v )
		{
			if( in_array($k, $this->m_pw_fields) )
			{
				$this->m_values[$k] = password_hash($v, PASSWORD_DEFAULT);
			}
		}

		return $this;
	}

	//	値部分をクォーテーションで括らない
	public function values_rawval( $values_ )
	{
		$this->m_values = false;
		$this->m_values_rawval = $values_;

		foreach( $this->m_values_rawval as $k => $v )
		{
			if( in_array($k, $this->m_pw_fields) )
			{
				$this->m_values_rawval[$k] = password_hash($v, PASSWORD_DEFAULT);
			}
		}
		return $this;
	}

	//	一つだけ追加
	public function value( $key_, $value_ )
	{
		if( in_array($key_, $this->m_pw_fields) )
		{
			$this->m_values[$k] = password_hash($v, PASSWORD_DEFAULT);
		}
		else
		{
			$this->m_values[$key_] = $value_;

			if( $this->m_values_rawval !== false &&
				isset($this->m_values_rawval[$key_]) === true
			)	unset($this->m_values_rawval[$key_]);
		}
		return $this;
	}

	//	値部分をクォーテーションで括らない
	public function value_rawval( $key_, $value_ )
	{
		if( in_array($key_, $this->m_pw_fields) )
		{
			$this->m_values_rawval[$k] = password_hash($v, PASSWORD_DEFAULT);
		}
		else
		{
			$this->m_values_rawval[$key_] = $value_;

			if( $this->m_values !== false &&
				isset($this->m_values[$key_]) === true
			)	unset($this->m_values[$key_]);
		}
		return $this;
	}

	//--------------------------------------------------------------------------
	//	WHERE句 条件追加
	//
	//	1.引数0個の場合は、where句をクリアする
	//	2.引数2個パターンの演算子は"="になる。
	//		$sql->where( "deleted", "0" );
	//	3.引数3個パターン
	//		$sql->where( "deleted", "=", "0" );
	//	4.引数4個パターン（暗号データをデコードする）
	//		$sql->where( "blobdata", "=", "0", true );
	//--------------------------------------------------------------------------
	public function where( /* ... */ )
	{
		$args = func_get_args();
		if( count($args)<=0 ) $this->m_where = [];
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($args)==4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、シンボルを括る必要がない旨を知らせる
				false
			];
		}
		else
		{
			$this->m_where[] = [$args[0], $exp, $right, false, false];
		}
		return $this;
	}

	//	where in の指定
	//
	//	例）$sql->where_in("name", ["ichiro", "jiro", "saburo"]);
	//
	public function where_in( $col_, $list_ )
	{
		if( is_array($list_) === false || count($list_) <= 0 ) return $this;
		$rawval = "('".implode("','", $list_)."')";
		return $this->where_rawval($col_, "in", $rawval);
	}

	//	値部分をクォーテーションで括らない
	public function where_rawval( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) <= 0 ) $this->m_where = [];
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($args)==4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				true	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		else
		{
			$this->m_where[] = [$args[0], $exp, $right, false, true];
		}
		return $this;
	}

	//	キーと値部分をクォーテーションで括らない
	public function where_rawkeyval( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) <= 0 ) $this->m_where = [];
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				true	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		else
		{
			$this->m_where[] =
			[
				$args[0], $exp, $right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				true	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		return $this;
	}

	//	where句 優先演算開始 "("
	public function where_open()
	{
		$this->m_where[] = "(";
		return $this;
	}

	//	where句 優先演算終了 ")"
	public function where_close()
	{
		$this->m_where[] = ")";
		return $this;
	}

	//	where句 and指定。引数はwhere()と同じ
	//	まだwhere句が空だった場合には、where()と全く同じ処理となる
	public function and_where( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($this->m_where) > 0 ) $this->m_where[] = "and";
		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				false
			];
		}
		else
		{
			$this->m_where[] = [$args[0], $exp, $right, false, false];
		}
		return $this;
	}

	//	where in のand指定
	//
	//	例）$sql->and_where_in("name", ["ichiro", "jiro", "saburo"]);
	//
	public function and_where_in( $col_, $list_ )
	{
		if( is_array($list_) === false || count($list_) <= 0 ) return $this;
		$rawval = "('".implode("','", $list_)."')";
		return $this->and_where_rawval($col_, "in", $rawval);
	}

	//	and where のエンコードなし版
	public function and_where_rawval( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($this->m_where) > 0 ) $this->m_where[] = "and";
		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				true	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		else
		{
			$this->m_where[] = [$args[0], $exp, $right, false, true];
		}
		return $this;
	}

	//	and where のキーと値のエンコードなし版
	public function and_where_rawkeyval( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($this->m_where) > 0 ) $this->m_where[] = "and";
		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				true	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		else
		{
			$this->m_where[] =
			[
				$args[0], $exp, $right,
				true,	//	クエリを組み立てる側へ、左辺を括る必要がない旨を知らせる
				true	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		return $this;
	}

	//	where句 and指定。直後に優先演算を開始する
	//	まだwhere句が空だった場合には、and_where()と全く同じ処理となる
	public function and_where_open()
	{
		if( count($this->m_where) > 0 ) $this->m_where[] = "and";
		$this->m_where[] = "(";
		return $this;
	}

	//	where句 or追加。引数はwhere()と同じ
	//	まだwhere句が空だった場合には、where()と全く同じ処理となる
	public function or_where( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($this->m_where) > 0 ) $this->m_where[] = "or";
		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、シンボルを括る必要がない旨を知らせる
				false
			];
		}
		else
		{
			$this->m_where[] = [$args[0], $exp, $right, false, false];
		}
		return $this;
	}

	//	where in のor指定
	//
	//	例）$sql->or_where_in("name", ["ichiro", "jiro", "saburo"]);
	//
	public function or_where_in( $col_, $list_ )
	{
		if( is_array($list_) === false || count($list_) <= 0 ) return $this;
		$rawval = "('".implode("','", $list_)."')";
		return $this->or_where_rawval($col_, "in", $rawval);
	}

	//	or where のエンコードなし版
	public function or_where_rawval( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($this->m_where) > 0 ) $this->m_where[] = "or";
		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
				true,	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		else
		{
			$this->m_where[] = [$args[0], $exp, $right, false, true];
		}
		return $this;
	}

	//	or where のキーと値のエンコードなし版
	public function or_where_rawkeyval( /* ... */ )
	{
		$args = func_get_args();
		if( count($args) < 2 ) return $this;
		$exp = count($args) >= 3 ? $args[1] : "=";
		$right = count($args) >= 3 ? $args[2] : $args[1];

		if( count($this->m_where) > 0 ) $this->m_where[] = "or";
		if( count($args) == 4 )
		{
			$this->m_where[] =
			[
				crow::get_hdb_reader()->sql_field_decrypt($args[0]),
				$exp,
				$right,
				true,	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
				true,	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		else
		{
			$this->m_where[] =
			[
				$args[0], $exp, $right,
				true,	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
				true,	//	クエリを組み立てる側へ、右辺を括る必要がない旨を知らせる
			];
		}
		return $this;
	}

	//	where句 or指定。直後に優先演算を開始する
	public function or_where_open()
	{
		if( count($this->m_where) > 0 ) $this->m_where[] = "or";
		$this->m_where[] = "(";
		return $this;
	}

	//--------------------------------------------------------------------------
	//	ORDER BY 句指定
	//--------------------------------------------------------------------------
	public function orderby( $field_=false, $collate_=false )
	{
		$this->m_orderby = [$field_];
		$this->m_orderby_vector = ['asc'];
		$this->m_orderby_collate = [$collate_];
		return $this;
	}
	public function orderby_desc( $field_=false, $collate_=false )
	{
		$this->m_orderby = [$field_];
		$this->m_orderby_vector = ['desc'];
		$this->m_orderby_collate = [$collate_];
		return $this;
	}

	//--------------------------------------------------------------------------
	//	ORDER BY 句の追加指定
	//--------------------------------------------------------------------------
	public function and_orderby( $field_=false, $collate_=false )
	{
		$this->m_orderby[] = $field_;
		$this->m_orderby_vector[] = 'asc';
		$this->m_orderby_collate[] = $collate_;
		return $this;
	}
	public function and_orderby_desc( $field_=false, $collate_=false )
	{
		$this->m_orderby[] = $field_;
		$this->m_orderby_vector[] = 'desc';
		$this->m_orderby_collate[] = $collate_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	GROUP BY 句指定
	//--------------------------------------------------------------------------
	public function groupby( $field_=false, $collate_=false )
	{
		$this->m_groupby = $field_;
		$this->m_groupby_collate = $collate_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	表示オフセットと、読み込み数指定
	//
	//	引数なしでリセットする
	//--------------------------------------------------------------------------
	public function limit( $offset_=false, $size_=false )
	{
		$this->m_limit_offset = $offset_;
		$this->m_limit_size = $size_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	for update 付与
	//--------------------------------------------------------------------------
	public function for_update( $enable_ = true )
	{
		$this->m_for_update = $enable_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	文字列にビルドする
	//--------------------------------------------------------------------------
	public function build()
	{
		$hdb = crow::get_hdb_reader();
		return $hdb->build_sql($this);
	}

	//--------------------------------------------------------------------------
	//	selectを実行して、最初のレコードを1件だけ取得する。
	//	予め set_model() でモデルクラスを指定しておくか、本メソッドの引数でモデルクラスを渡すこと
	//--------------------------------------------------------------------------
	public function get_row( $model_class_ = false )
	{
		$class = $model_class_ === false ? $this->m_model : false;
		if( $class === false )
		{
			crow_log::warning("not specified model class for crow_db_sql::get_row");
			return false;
		}
		return $class::create_from_sql($this);
	}

	//--------------------------------------------------------------------------
	//	selectを実行して、キーをプライマリキー値とするモデルインスタンスの連想配列を取得する。
	//	予め set_model() でモデルクラスを指定しておくか、本メソッドの引数でモデルクラスを渡すこと
	//--------------------------------------------------------------------------
	public function get_rows( $model_class_ = false )
	{
		$class = $model_class_ === false ? $this->m_model : false;
		if( $class === false )
		{
			crow_log::warning("not specified model class for crow_db_sql::get_rows");
			return false;
		}
		return $class::create_array_from_sql($this);
	}

	//--------------------------------------------------------------------------
	//	モデルクラスの指定
	//	モデルクラスの sql_select_xxx系メソッドで、本クラスのインスタンス生成時に実行される
	//--------------------------------------------------------------------------
	public function set_model( $model_class_ )
	{
		$this->m_model = $model_class_;
		return $this;
	}

	//	要素
	public $m_command = false;
	public $m_targets = [];
	public $m_from = false;
	public $m_values = [];
	public $m_values_rawval = [];
	public $m_where = [];
	public $m_orderby = [];
	public $m_orderby_vector = [];
	public $m_orderby_collate = [];
	public $m_groupby = false;
	public $m_groupby_collate = false;
	public $m_limit_offset = false;
	public $m_limit_size = false;
	public $m_for_update = false;
	public $m_pw_fields = [];
	public $m_model = false;
}
?>
