<?php
/*

	DBインタフェース基底

	拡張テーブルの実装時、
	kernel/table_ext配下に拡張名でフォルダを作り、model.phpとfields.txtを配置する。
	注意点で、model.phpでコンストラクタが必要な場合は、
	model.php内に "__construct" はなく "construct" でメソッドを定義すればコンストラクタとして取り込まれるようになっている。

*/
class	crow_db
{
	//--------------------------------------------------------------------------
	//	生成、破棄
	//--------------------------------------------------------------------------
	public function __construct()
	{
	}
	public function __destruct()
	{
	}


	//
	//	↓ ここから、継承先での実装を必須とする
	//


	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	public function init()
	{
		crow_log::error( "not implemented crow_db::init" );
	}

	//--------------------------------------------------------------------------
	//	接続済み？
	//--------------------------------------------------------------------------
	public function is_connected()
	{
		crow_log::error( "not implemented crow_db::is_connected" );
	}

	//--------------------------------------------------------------------------
	//	接続
	//--------------------------------------------------------------------------
	public function connect()
	{
		crow_log::error( "not implemented crow_db::connect" );
	}

	//--------------------------------------------------------------------------
	//	接続先を指定して接続する
	//
	//	$dummy_ は過去の名残で残している、内部的には使われないパラメータとなる
	//--------------------------------------------------------------------------
	public function connect_to( $dummy_, $addr_, $name_, $uid_, $upass_ )
	{
		crow_log::error( "not implemented crow_db::connect_to" );
	}

	//--------------------------------------------------------------------------
	//	切断
	//--------------------------------------------------------------------------
	public function disconnect()
	{
		crow_log::error( "not implemented crow_db::disconnect" );
	}

	//--------------------------------------------------------------------------
	//	生のDBハンドルを取得する
	//--------------------------------------------------------------------------
	public function get_raw_hdb()
	{
		crow_log::error( "not implemented crow_db::get_raw_hdb" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	一時的にSQLログの出力設定を変更する
	//--------------------------------------------------------------------------
	public function change_log_temp( $is_output_ )
	{
		crow_log::error( "not implemented crow_db::change_log_temp" );
		return false;
	}
	public function recovery_log_temp()
	{
		crow_log::error( "not implemented crow_db::recovery_log_temp" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	最後のinsert時で発行された、autoincrementの値を取得する
	//--------------------------------------------------------------------------
	public function get_insert_id()
	{
		crow_log::error( "not implemented crow_db::get_insert_id" );
	}

	//--------------------------------------------------------------------------
	//	シンボルをエスケープする
	//--------------------------------------------------------------------------
	public function escape_symbol( $symbol_ )
	{
		return $symbol_;
	}

	//--------------------------------------------------------------------------
	//	like文に指定する値をエスケープする (SQLServer向け)
	//--------------------------------------------------------------------------
	public function escape_like_value( $str_ )
	{
		return $str_;
	}

	//--------------------------------------------------------------------------
	//	トランザクション
	//--------------------------------------------------------------------------
	public function begin()
	{
		crow_log::error( "not implemented crow_db::begin" );
	}
	public function commit()
	{
		crow_log::error( "not implemented crow_db::commit" );
	}
	public function rollback()
	{
		crow_log::error( "not implemented crow_db::rollback" );
	}

	//--------------------------------------------------------------------------
	//	全テーブルを取得する
	//--------------------------------------------------------------------------
	public function get_tables()
	{
		crow_log::error( "not implemented crow_db::get_tables" );
	}

	//--------------------------------------------------------------------------
	//	テーブルの存在チェック
	//--------------------------------------------------------------------------
	public function exists_table( $name_ )
	{
		crow_log::error( "not implemented crow_db::exists_table" );
	}

	//--------------------------------------------------------------------------
	//	フィールド情報の一覧を取得
	//--------------------------------------------------------------------------
	public function get_fields( $table_name_ )
	{
		crow_log::error( "not implemented crow_db::get_fields" );
	}

	//--------------------------------------------------------------------------
	//	override : インデックスの一覧を取得する
	//
	//	keyはテーブル名、valはフィールド名の配列となる、連想配列を返却する。
	//--------------------------------------------------------------------------
	public function get_indexes()
	{
		crow_log::error( "not implemented crow_db::get_indexes" );
	}

	//--------------------------------------------------------------------------
	//	crow_db_sqlのオブジェクトから、SQL文字列を作成する
	//--------------------------------------------------------------------------
	public function build_sql( $sql_ )
	{
		crow_log::error( "not implemented crow_db::build_sql" );
	}

	//--------------------------------------------------------------------------
	//	sqlファイルからSQL文字列を作成する
	//	第二引数以降に、パラメータを可変引数で渡す。
	//	パラメータのDBデコードは不要（内部で行う）
	//--------------------------------------------------------------------------
	public function raw( /* name_, param1, param2 ... */ )
	{
		crow_log::error( "not implemented crow_db::raw" );
	}

	//--------------------------------------------------------------------------
	//	クエリ発行
	//
	//	成功時は crow_db_result のインスタンスを返却。失敗時はfalseを返却
	//--------------------------------------------------------------------------
	public function query( $sql_ )
	{
		crow_log::error( "not implemented crow_db::query" );
	}

	//--------------------------------------------------------------------------
	//	SELECTに特化したクエリ発行
	//
	//	成功時はレコード(連想配列)の配列を返却。失敗時はfalseを返却
	//--------------------------------------------------------------------------
	public function select( $sql_ )
	{
		crow_log::error( "not implemented crow_db::select" );
	}

	//	1行のみ取得する
	public function select_one( $sql_ )
	{
		crow_log::error( "not implemented crow_db::select_one" );
	}

	//--------------------------------------------------------------------------
	//	sqlファイルから取得したSQLを実行する
	//
	//	raw()で取得したクエリをquery()で発行しているだけ
	//--------------------------------------------------------------------------
	public function raw_query( /* name_, param1, param2 ... */ )
	{
		crow_log::error( "not implemented crow_db::raw_query" );
	}

	//--------------------------------------------------------------------------
	//	sqlファイルから取得したセレクトクエリを実行する
	//
	//	raw()で取得したクエリをselect()やselect_one()で発行しているだけ
	//--------------------------------------------------------------------------
	public function raw_select( /* name_, param1, param2 ... */ )
	{
		crow_log::error( "not implemented crow_db::raw_select" );
	}
	public function raw_select_one( /* name_, param1, param2 ... */ )
	{
		crow_log::error( "not implemented crow_db::raw_select_one" );
	}

	//--------------------------------------------------------------------------
	//	sqlを指定して結果をCSVファイルへ出力する
	//
	//	header_ : ヘッダ列のカラム配列を指定する。出力しない場合は false を指定する。
	//	line_custom_ : DB値から別の文字列に変更したい場合には、無名関数を指定して内容を変更できる
	//	select_span_ : 何行ずつセレクトするかを指定する。
	//
	//	例）取得ラインをカスタムする例
	//
	//		$hdb->output_csv_from_sql("out.csv", "select * xxx", false, function(&$line_)
	//		{
	//			//	3番目のカラムが1なら"male"、それ以外なら"female"に変更する例
	//			$line_[3] = $line_[3]==1 ? "male" : "female";
	//		});
	//
	//--------------------------------------------------------------------------
	public function output_csv_from_sql
	(
		$fname_,
		$query_,
		$header_ = false,
		$line_custom_ = false,
		$select_span_ = 1000
	){
		crow_log::error( "not implemented crow_db::output_csv_from_sql" );
	}

	//--------------------------------------------------------------------------
	//	最後のクエリエラー情報を取得
	//--------------------------------------------------------------------------
	public function get_last_error_code()
	{
		crow_log::error( "not implemented crow_db::get_last_error_code" );
	}
	public function get_last_error_msg()
	{
		crow_log::error( "not implemented crow_db::get_last_error_msg" );
	}

	//--------------------------------------------------------------------------
	//	クエリ結果から行数取得
	//--------------------------------------------------------------------------
	public function num_rows( $result_ )
	{
		crow_log::error( "not implemented crow_db::num_rows" );
	}

	//--------------------------------------------------------------------------
	//	クエリ結果から列数取得
	//--------------------------------------------------------------------------
	public function num_cols( $result_ )
	{
		crow_log::error( "not implemented crow_db::num_cols" );
	}

	//--------------------------------------------------------------------------
	//	クエリ結果から１行取得
	//
	//		html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function get_row( $result_, $html_escape_=false )
	{
		crow_log::error( "not implemented crow_db::get_row" );
	}

	//--------------------------------------------------------------------------
	//	クエリ結果から全行取得
	//
	//		html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function get_rows( $result_, $html_escape_=false )
	{
		crow_log::error( "not implemented crow_db::get_rows" );
	}

	//--------------------------------------------------------------------------
	//	文字列をDBエンコードする
	//--------------------------------------------------------------------------
	public function encode( $value_ )
	{
		crow_log::error( "not implemented crow_db::encode" );
	}

	//--------------------------------------------------------------------------
	//	文字列をDBデコードする
	//
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function decode( $value_, $html_escape_=false )
	{
		crow_log::error( "not implemented crow_db::decode" );
	}

	//--------------------------------------------------------------------------
	//	結果レコードをDBデコードする
	//
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function decode_row( $row_, $html_escape_=false )
	{
		crow_log::error( "not implemented crow_db::decode_row" );
	}

	//--------------------------------------------------------------------------
	//	結果レコードの配列をDBデコードする
	//
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function decode_rows( $rows_, $html_escape_=false )
	{
		crow_log::error( "not implemented crow_db::decode_rows" );
	}


	//
	//	これ以降は、crow内で利用する。
	//

	//--------------------------------------------------------------------------
	//	指定したDBモデルの内容で insert クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_insert_with_model( &$table_model_ )
	{
		crow_log::error( "not implemented crow_db::exec_insert_with_model" );
	}

	//--------------------------------------------------------------------------
	//	指定したDBモデルの内容で update クエリを発行する
	//
	//	update_columns_ にカラム名の配列を指定すると、そのカラムに対してのみの更新とする
	//--------------------------------------------------------------------------
	public function exec_update_with_model( &$table_model_, $update_columns_ = false )
	{
		crow_log::error( "not implemented crow_db::exec_update_with_model" );
	}

	//--------------------------------------------------------------------------
	//	指定したDBモデルの内容でゴミ箱へ移動するクエリを発行する
	//	（削除フラグがなければ完全削除となる）
	//--------------------------------------------------------------------------
	public function exec_trash_with_model( &$table_model_ )
	{
		crow_log::error( "not implemented crow_db::exec_trash_with_model" );
	}

	//--------------------------------------------------------------------------
	//	指定したDBモデルの内容で完全削除を行うクエリを発行する
	//--------------------------------------------------------------------------
	public function exec_delete_with_model( &$table_model_ )
	{
		crow_log::error( "not implemented crow_db::exec_delete_with_model" );
	}

	//--------------------------------------------------------------------------
	//	暗号フィールドのデコード項の作成
	//--------------------------------------------------------------------------
	public function sql_field_decrypt( $field_name_ )
	{
		crow_log::error( "not implemented crow_db::sql_field_decryt" );
	}

	//--------------------------------------------------------------------------
	//	指定したフィールド仕様の内容で
	//	create table や alter table 用のフィールド部分の構文を作成する
	//--------------------------------------------------------------------------
	public function sql_field_syntax_with_design( &$table_design_, &$field_design_ )
	{
		crow_log::error( "not implemented crow_db::sql_field_syntax_with_design" );
	}

	//--------------------------------------------------------------------------
	//	プライマリーキーを作成する構文取得
	//--------------------------------------------------------------------------
	public function sql_create_primary_key( &$table_design_, &$field_design_ )
	{
		crow_log::error( "not implemented crow_db::sql_create_primary_key" );
	}

	//--------------------------------------------------------------------------
	//	プライマリーキーを削除する構文取得
	//--------------------------------------------------------------------------
	public function sql_delete_primary_key( &$table_design_ )
	{
		crow_log::error( "not implemented crow_db::sql_create_primary_key" );
	}

	//--------------------------------------------------------------------------
	//	インデックスを作成する構文取得、インデックスの名前を指定する
	//--------------------------------------------------------------------------
	public function sql_create_index_syntax_with_design( &$table_design_, $index_name_ )
	{
		crow_log::error( "not implemented crow_db::sql_create_index_syntax_with_design" );
	}

	//--------------------------------------------------------------------------
	//	インデックスを削除する構文取得、インデックスの名前を指定する
	//--------------------------------------------------------------------------
	public function sql_drop_index_syntax_with_design( &$table_design_, $index_name_ )
	{
		crow_log::error( "not implemented crow_db::sql_drop_index_syntax_with_design" );
	}

	//--------------------------------------------------------------------------
	//	指定したテーブル仕様の内容で create クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_create_table_with_design( &$table_design_ )
	{
		crow_log::error( "not implemented crow_db::exec_create_table_with_design" );
	}

	//--------------------------------------------------------------------------
	//	指定したテーブル仕様の内容で drop クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_drop_table_with_design( &$table_design_ )
	{
		crow_log::error( "not implemented crow_db::exec_drop_table_with_design" );
	}


	//
	//	↑ 継承先での実装を必須とするメソッド群はここまで。
	//


	//--------------------------------------------------------------------------
	//	指定されたSQLファイルを実行する
	//
	//	SQLは、";"で区切って複数指定可能。
	//	行頭 "--" で始まる行はコメントとする。
	//
	//	実行するSQLが書かれたファイルを指定する。
	//	成功の場合はtrue/失敗の場合はfalseを返却する。
	//--------------------------------------------------------------------------
	public function exec_sql_file( $fname_ )
	{
		if( ! is_file($fname_) )
		{
			crow_log::warning( "not found query file ".$fname_ );
			return false;
		}


		//	バッファに取らず、1文字ずつ解析。
		$fp = fopen($fname_,"r");
		if( ! $fp )
		{
			crow_log::warning( "can not open query file ".$fname_ );
			return false;
		}

		$in_str = 0; //	0:none, 1:single quat, 2:double quat
		$is_head = true;
		$line = "";

		//	span単位でファイルから読み込み、1文字ずつ処理。
		$off = 0;
		$span = 128;
		$buff = false;
		$buff_st = 0;
		$len = 0;
		$cnt = 0;
		$get_mc = function( &$fp, &$off, &$span, &$buff, &$buff_st, &$len, &$cnt )
		{
			if( $len==0 || ($len===$span && ($off+10) > ($buff_st+$len)) )
			{
				fseek($fp, $off);
				$buff_st = $off;
				$buff = fread($fp, $span);
				$len = strlen($buff);
				$cnt = 0;
			}
			else if( $off >= $buff_st + $len )
			{
				return false;
			}

			$c = mb_substr($buff,$cnt,1);
			$cnt++;
			$off += strlen($c);
			return $c;
		};

		//	パース
		while(true)
		{
			$c = $get_mc($fp, $off, $span, $buff, $buff_st, $len, $cnt);
			if( $c === false ) break;

			if( $in_str === 0 )
			{
				//	行先頭はコメントチェック
				if( $is_head===true )
				{
					if( $c==="-" )
					{
						$c2 = $get_mc($fp, $off, $span, $buff, $buff_st, $len, $cnt);
						if( $c2==="-" )
						{
							//	\nまでスキップ
							while(true)
							{
								$c2 = $get_mc($fp, $off, $span, $buff, $buff_st, $len, $cnt);
								if( $c2===false || $c2=="\n" ) break;
							}
							continue;
						}

						$is_head = false;
						$query .= $c;
						$c = $c2;
					}
				}

				switch( $c )
				{
					case "\r":
						break;

					case "\n":
						$is_head = true;
						$line .= " ";
						break;

					case "'":
						$line .= $c;
						$in_str = 1;
						break;

					case "\"":
						$line .= $c;
						$in_str = 2;
						break;

					case ";":
					{
						if( ! $this->query($line) ) return false;
						$line = '';
						break;
					}

					default:
						$line .= $c;
						$is_head = false;
						break;
				}
			}
			else if( $in_str == 1 )
			{
				if( $c !== "\r" ) $line .= $c;

				switch( $c )
				{
					case "'":
						$in_str = 0;
						break;

					case "\\":
						$c = $get_mc($fp, $off, $span, $buff, $buff_st, $len, $cnt);
						$line .= $c;
						break;
				}
			}
			else if( $in_str == 2 )
			{
				if( $c !== "\r" ) $line .= $c;

				switch( $c )
				{
					case "\"":
						$in_str = 0;
						break;

					case "\\":
						$c = $get_mc($fp, $off, $span, $buff, $buff_st, $len, $cnt);
						$line .= $c;
						break;
				}
			}
		}
		fclose($fp);

		return true;
	}



	//--------------------------------------------------------------------------
	//	DB仕様に基づいてインストールを行う
	//--------------------------------------------------------------------------
	public function exec_install()
	{
		foreach( $this->table_designs as $design ){
			$this->exec_drop_table_with_design($design);
			if( ! $this->exec_create_table_with_design($design) ) return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	指定したテーブルの仕様を取得する
	//--------------------------------------------------------------------------
	public function get_design( $table_name_ )
	{
		return isset($this->table_designs[$table_name_]) ?
			$this->table_designs[$table_name_] : false
			;
	}

	//--------------------------------------------------------------------------
	//	指定したテーブルの全フィールド仕様を取得する
	//--------------------------------------------------------------------------
	public function get_table_fields( $table_name_ )
	{
		$table = $this->get_design($table_name_);
		return $table === false ? false : $table->fields;
	}

	//--------------------------------------------------------------------------
	//	指定したテーブルの指定したフィールドの仕様を取得する
	//--------------------------------------------------------------------------
	public function get_table_field( $table_name_, $field_name_ )
	{
		$fields = $this->get_table_fields($table_name_);
		return ($fields === false || isset($fields[$field_name_])===false)
			? false : $fields[$field_name_];
	}

	//--------------------------------------------------------------------------
	//	テーブルの全仕様を取得する
	//
	//	crow_db_table_design のインスタンスの配列が返却される
	//--------------------------------------------------------------------------
	public function get_design_all()
	{
		return $this->table_designs;
	}

	//--------------------------------------------------------------------------
	//	DB用モデルクラスのキャッシュを読み込む（なければ作成する）
	//--------------------------------------------------------------------------
	protected function read_model_classes()
	{
		//	requireはファイルが対象となるので、キャッシュをファイル固定とする
		crow_cache::force_file_begin();
		{
			if( ! crow_cache::exists("table_models.php") )
				$this->create_model_cache();
			$path = crow_cache::get_fname("table_models.php");
		}
		crow_cache::force_file_end();

		//	読み込み
		require_once( $path );
	}

	//--------------------------------------------------------------------------
	//	DB用モデルクラスのキャッシュを作成する
	//--------------------------------------------------------------------------
	protected function create_model_cache()
	{
		//	もしDB仕様の読み込みがまだなら、まずは読み込みから。
		if( count($this->table_designs) <= 0 ){
			if( ! $this->read_design_file() ) return false;
		}

		//	テーブルごとに処理
		$source = '<?php';
		$source .= "\n";
		foreach( $this->table_designs as $table_design ){
			$source .= $this->create_model_class_source($table_design);
		}
		$source .= "\n";
		$source .= '?>';
		crow_cache::save_as_text("table_models.php", $source);
		return true;
	}
	private function create_model_class_source( $table_design_ )
	{
		$targets = '';
		$contains_crypt = false;
		foreach( $table_design_->fields as $field )
		{
			if( $targets != '' ) $targets .= ",";

			if( $field->type=="tinycrypt" || $field->type=="varcrypt" || $field->type=="crypt" || $field->type=="bigcrypt" || $field->type=="mailcrypt" )
			{
				$targets .= "\"#".$field->name."\"";
				$contains_crypt = true;
			}
			else
			{
				$targets .= "\"".$field->name."\"";
			}
		}

		$pw_fields = '';
		foreach( $table_design_->fields as $field )
		{
			if( $field->type != "password" ) continue;
			if( $pw_fields != '' ) $pw_fields .= ",";
			$pw_fields .= $field->name;
		}

		//	標準セレクト作成のコード
		$select_code = "";
		$func1_head = "public static function sql_select_all()\n{\n";
		$func1_tail = "}\n";
		$func2_head = "public static function sql_select_one( ".'$primary_value_'." )\n{\n";
		$func2_tail = "}\n";
		if( $contains_crypt )
		{
			$make_select_prefix = '$cryptkey=crow_config::get("db.cryptkey", "");';
			$make_select_prefix .= 'return crow_db_sql::create_select()->from("'.$table_design_->name.'")->target('.$targets.')->set_model("model_'.$table_design_->name.'")'."\n";
			if( $pw_fields != "" ) $make_select_prefix .= "->pw_fields(\"".$pw_fields."\")\n";
		}
		else
		{
			$make_select_prefix = 'return crow_db_sql::create_select()->from("'.$table_design_->name.'")->set_model("model_'.$table_design_->name.'")'."\n";
			if( $pw_fields != "" ) $make_select_prefix .= "->pw_fields(\"".$pw_fields."\")\n";
		}

		$select_all_where = "";
		$select_one_where = "";
		if( $table_design_->primary_key !== false )
		{
			if( is_array($table_design_->primary_key) )
			{
				foreach( $table_design_->primary_key as $pidx => $pkey )
				{
					$select_one_where .= '->and_where("'.$pkey.'", $primary_value_['.$pkey.'])';
				}
			}
			else
			{
				$select_one_where .= '->and_where("'.$table_design_->primary_key.'", $primary_value_)';
			}
		}
		if( $table_design_->deleted !== false )
		{
			$select_all_where .= '->and_where("'.$table_design_->deleted.'", 0)';
			$select_one_where .= '->and_where("'.$table_design_->deleted.'", 0)';
		}

		if( $table_design_->order !== false )
		{
			if( $table_design_->order_vector == 'desc' )
			{
				$select_code = ''
					.$func1_head.$make_select_prefix
					.$select_all_where
					.'->orderby_desc("'.$table_design_->order.'");'."\n"
					.$func1_tail
					.$func2_head.$make_select_prefix
					.$select_one_where.";\n"
					.$func2_tail
					;
			}
			else
			{
				$select_code = ''
					.$func1_head.$make_select_prefix
					.$select_all_where
					.'->orderby("'.$table_design_->order.'");'."\n"
					.$func1_tail
					.$func2_head.$make_select_prefix
					.$select_one_where.";\n"
					.$func2_tail
					;
			}
		}
		else
		{
			$select_code = ''
				.$func1_head.$make_select_prefix
				.$select_all_where
				.';'."\n"
				.$func1_tail
				.$func2_head.$make_select_prefix
				.$select_one_where.";\n"
				.$func2_tail
				;
		}

		//	フィールド名でメンバ変数を用意する
		$field_member_code = "";
		$construct = "";
		$refer_tables = [];
		foreach( $table_design_->fields as $field )
		{
			//	定数指定がある？
			if( count($field->const_array) > 0 )
			{
				foreach( $field->const_array as $const_key => $const_val )
					$field_member_code .= "const ".$const_key."=".$const_val.";\n";
			}

			switch( $field->type )
			{
				case "tinyint":
				case "utinyint":
				case "int":
				case "uint":
				case "bigint":
				case "ubigint":
				case "unixtime":
				case 'datetime':
				case 'float':
				case "double":
				case "geometry":
				case "bit":
					$field_member_code .= 'public $'.$field->name;
					if( isset($field->default_value) )
					{
						if( $field->default_value === null )
							$field_member_code .= "=null";
						else if( $field->default_value === true )
							$field_member_code .= "=true";
						else if( $field->default_value === false )
							$field_member_code .= "=false";
						else
							$field_member_code .= "=".$field->default_value;
					}
					else if( $field->nullable === true )
					{
						$field_member_code .= "=null";
					}
					else
					{
						$field_member_code .= "=0";
					}
					$field_member_code .= ";";
					break;

				case "boolean":
					$field_member_code .= 'public $'.$field->name;
					if( isset($field->default_value) )
					{
						$field_member_code .= "=";
						$field_member_code .= $field->default_value ? 'true' : 'false';
					}
					else
					{
						$field_member_code .= "=false";
					}
					$field_member_code .= ";";
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
				case "password":
					$field_member_code .= 'public $'.$field->name;
					if( isset($field->default_value) )
					{
						if( $field->default_value === null )
							$field_member_code .= "=null";
						else if( $field->default_value === true )
							$field_member_code .= "=true";
						else if( $field->default_value === false )
							$field_member_code .= "=false";
						else
							$field_member_code .= "='".$field->default_value."'";
					}
					else if( $field->nullable === true )
					{
						$field_member_code .= "=null";
					}
					else
					{
						$field_member_code .= "=''";
					}
					$field_member_code .= ";";
					break;

				default:
					$field_member_code .= 'public $'.$field->name;
					if( isset($field->default_value) )
					{
						if( $field->default_value === null )
							$field_member_code .= "=null";
						else if( $field->default_value === true )
							$field_member_code .= "=true";
						else if( $field->default_value === false )
							$field_member_code .= "=false";
						else
							$field_member_code .= "='".$field->default_value."'";
					}
					else if( $field->nullable === true )
					{
						$field_member_code .= "=null";
					}
					else
					{
						$field_member_code .= "=''";
					}
					$field_member_code .= ";";
					break;
			}
			$field_member_code .= "\n";

			//	テーブル参照コード
			if( $field->refer_table !== false )
			{
				//	同じテーブルへ別のフィールドからも参照がある場合、引数によって区別するため
				//	関数としては一つのみにする制御
				if( isset($refer_tables[$field->refer_table]) === false )
				{
					$refer_tables[$field->refer_table] = true;

					if( isset($this->table_designs[$field->refer_table]) === false )
					{
						crow_log::write('not found table ['.$field->refer_table.'] refer from '.$table_design_->name, "system");
						exit;
					}
					$field_member_code .= 'public function '.$field->refer_table.'_row($field_="'.$field->name.'"){'
						.'return model_'.$field->refer_table.'::create_from_id($this->{$field_});'
						."}\n"
						;
				}
			}

			//	初期化コードがある？
			if( $field->default_code )
				$construct .= '$this->'.$field->name."=".$field->default_code.";";
		}

		//	定数取得コード
		$const_code = '';
		foreach( $table_design_->fields as $field )
		{
			//	定数指定がある？
			if( count($field->const_array) <= 0 ) continue;

			$const_keys = [];
			$const_parts = [];
			$symbols = [];
			$symbol_vals = [];
			foreach( $field->const_array as $const_key => $const_val )
			{
				$const_keys[] = "self::".$const_key;
				$const_parts[] = $const_val."=>crow_msg::get('db.".$table_design_->name.".const.".$const_key."', '".$const_key."')";
				$symbols[] = "'".$const_key."' => ".$const_val;
				$symbol_vals[] = "'".$const_val."' => '".$const_key."'";
			}

			$const_code .= ''
				.'public static function get_'.$field->name.'_keys(){'."\n"
					. 'return ['.implode(',', $const_keys).'];'."\n"
				."}\n"
				.'public static function get_'.$field->name.'_map(){'."\n"
					. 'return ['.implode(',', $const_parts).'];'."\n"
				."}\n"
				.'public static function get_'.$field->name.'_symbols(){'."\n"
					. 'return ['.implode(',', $symbols).'];'."\n"
				."}\n"
				.'public static function get_'.$field->name.'_symbol_vals(){'."\n"
					. 'return ['.implode(',', $symbol_vals).'];'."\n"
				."}\n"
				.'public static function get_'.$field->name.'_str( $val_ )'."\n"
				."{\n"
					.'$map = self::get_'.$field->name.'_map();'."\n"
					.'if( isset($map[$val_])===false )'."\n"
					.'{'."\n"
						.'crow_log::notice("model_'.$table_design_->name.' get_'.$field->name.'_str() illegal '.$field->name.' specified : ".$val_);'."\n"
						.'return ""'.";\n"
					.'}'."\n"
					.'return $map[$val_];'."\n"
				."}\n"
				.'public function '.$field->name.'_str()'."\n"
				.'{'."\n"
					.'$map = self::get_'.$field->name.'_map();'."\n"
					.'if( isset($map[$this->'.$field->name.'])===false )'."\n"
					.'{'."\n"
						.'crow_log::notice("model_'.$table_design_->name.' '.$field->name.'_str() illegal '.$field->name.' specified : ".$this->'.$field->name.');'."\n"
						.'return ""'.";\n"
					.'}'."\n"
					.'return $map[$this->'.$field->name.'];'."\n"
				."}\n"
				.'public function '.$field->name.'_unpack()'."\n"
				.'{'."\n"
					.'return unpack_bits($this->'.$field->name.');'
				."}\n"
				.'public function '.$field->name.'_unpack_str()'."\n"
				.'{'."\n"
					.'if( $this->'.$field->name.' <= 0 ) return [];'."\n"
					.'$map = self::get_'.$field->name.'_map();'."\n"
					.'$strs = [];'."\n"
					.'foreach(unpack_bits($this->'.$field->name.') as $bit) $strs[] = $map[$bit];'."\n"
					.'return $strs;'."\n"
				."}\n"
				;
		}

		//	テーブルオプションで指定された拡張用のコードを読み込む
		$option_code = "";
		if( count($table_design_->options) > 0 )
		{
			foreach( $table_design_->options as $option )
			{
				$option_code_fname = CROW_PATH."engine/kernel/table_ext/".$option['name']."/model.php";
				$lines = file($option_code_fname);
				foreach( $lines as $line )
				{
					$line = trim($line);
					$pos = mb_strpos($line, "//");
					if( $pos!==false ) $line = mb_substr($line, 0, $pos);
					if( strlen($line) <= 0 ) continue;
					$option_code .= $line;
					if( $line=="{" || $line=="}" ) $option_code .= "\n";
				}
			}
		}

		//	CROW_PATH/app/classes/_common_/配下にユーザ定義のクラスがあるなら、それを読み込む
		$class_source = false;
		$model_fname = CROW_PATH."app/classes/_common_/model_".$table_design_->name.".php";
		while( is_file($model_fname) )
		{
			$lines = file($model_fname);
			$stream = "";
			foreach( $lines as $line ) $stream .= $line;
			$stream = mb_str_replace( '<?php', "", $stream );
			$stream = mb_str_replace( '?>', "", $stream );

			$pos = mb_strpos($stream,"{");
			if( $pos===false ) break;
			$class_source = ''
				.mb_substr($stream, 0, $pos + 1)."\n"
				.$field_member_code."\n"
				.'public $m_table_name="'.$table_design_->name.'";'."\n"
				.'const table_name="'.$table_design_->name.'";'."\n"
				.'const primary_key="'.$table_design_->primary_key.'";'."\n"
				.$select_code."\n"
				.$const_code."\n"
				.$option_code
				;

			if( $construct != "" )
			{
				$class_source .= ''
					."public function __construct(){\n"
					.$construct
					.'if(method_exists($this, "construct")) $this->construct();'
					."}\n"
					;
			}

			$class_source .= ''
				.mb_substr($stream, $pos + 1)
				;

			break;
		}
		//	ファイルが存在しないか、読み込めなかった場合は標準のものを使用する。
		if( $class_source === false )
		{
			$class_source = ''
				."class model_".$table_design_->name." extends crow_db_table_model\n{\n"
				.$field_member_code."\n"
				.'public $m_table_name="'.$table_design_->name.'";'."\n"
				.'const table_name="'.$table_design_->name.'";'."\n"
				.'const primary_key="'.$table_design_->primary_key.'";'."\n"
				.$select_code."\n"
				.$const_code."\n"
				.$option_code
				;

			if( $construct != "" )
			{
				$class_source .= ''
					."public function __construct()\n{\n"
					.$construct
					."}\n"
					;
			}
			$class_source .= "}\n";
		}
		return $class_source;
	}

	//--------------------------------------------------------------------------
	//	フィールドのサイズ定義を計算する (DBによって異なる)
	//--------------------------------------------------------------------------
	protected function calc_field_byte_size( $type_, $def_size_=-1 )
	{
		if( $def_size_ > 0 ) return $def_size_;

		switch($type_)
		{
			case 'tinyint':
			case 'utinyint':
				return 1;
			case 'int':
				return 11;
			case 'uint':
				return 10;
			case 'bigint':
			case 'ubigint':
				return 20;
		}
		return 1;
	}

	//--------------------------------------------------------------------------
	//	DB仕様を解析する
	//--------------------------------------------------------------------------
	protected function read_design_file()
	{
		//	DB仕様がキャッシュにあるならそれを読み込む
		if( crow_cache::exists("db_design") )
		{
			$this->table_designs = crow_cache::load("db_design");
			return true;
		}

		//	デザインファイルを計算する
		$design_fname = crow_config::get('db.design','');
		$design_path = CROW_PATH."app/config/".$design_fname;
		if( ! is_file($design_path) )
		{
			crow_log::write( "not found db design file : ".$design_path, "system" );
			exit;
		}

		//	パース
		$this->parse_design($design_path);

		//	読み込んだ内容をキャッシュに保存しておく
		crow_cache::save("db_design", $this->table_designs);
		return true;
	}

	//--------------------------------------------------------------------------
	//	ファイルを指定してDB仕様のパース
	//
	//	DBに接続せずにパースのみ行う場合は次のようにする
	//	$design = new crow_db();
	//	$design->parse_design("/path/to/db_design.txt");
	//	$design = $design->get_design_all();
	//--------------------------------------------------------------------------
	public function parse_design($design_path_)
	{
		$file_lines = file( $design_path_ );
		$this->parse_design_from_lines($file_lines);
	}

	//--------------------------------------------------------------------------
	//	文字列配列を指定してDB仕様のパース
	//--------------------------------------------------------------------------
	public function parse_design_from_lines($lines_)
	{
		//	コメントスキップして行の配列作成
		$lines = [];
		foreach( $lines_ as $line )
		{
			$cmt_pos = mb_strpos( $line, ";" );
			if( $cmt_pos !== false ) $line = mb_substr( $line, 0, $cmt_pos );
			$line = trim($line);
			if( strlen($line) <= 0 ) continue;
			$lines[] = $line;
		}

		//	解析
		$db_table = false;
		$this->table_designs = [];
		$stat = 0;
		$line_no = 0;
		$table_logical_name = "";
		$table_remark = "";
		foreach( $lines as $line )
		{
			if( $stat==0 )
			{
				//	#で始まる行は、次のテーブルの論理名と備考
				if( substr($line,0,1) == "#" )
				{
					$pos = mb_strpos($line, ",");
					if( $pos===false )
					{
						$table_logical_name = trim(mb_substr($line,1));
						$table_remark = "";
					}
					else
					{
						$table_logical_name = trim(mb_substr($line,1,$pos-1));
						$table_remark = trim(mb_substr($line,$pos+1));
					}
					continue;
				}

				//	ドット"."で拡張情報を記述できる。
				if( substr($line, 0, 1) == "." )
				{
					//	コロンまでがコマンド
					$opt_args = mb_split_multiple('(:|,)', $line);
					if( count($opt_args) > 0 && count($this->table_designs) > 0 )
					{
						$cmd = trim($opt_args[0]);
						$tindex = end(array_keys($this->table_designs));

						//	定数コマンド
						if( substr($cmd, 0, 6) == ".const" )
						{
							$const_field_name = substr($cmd, 6 + 1);
							if( isset($this->table_designs[$tindex]->fields[$const_field_name]) === true )
							{
								for( $idx = 1; $idx < count($opt_args); $idx++ )
								{
									$arr = explode("=",$opt_args[$idx]);
									$const_key = trim($arr[0]);
									$const_val = trim($arr[1]);
									$this->table_designs[$tindex]->fields[$const_field_name]
										->const_array[$const_key] = trim($const_val);

									//	初期化コードに定数名が指定されていないかチェックする。
									//	指定されていたら、正しいコードに置き換える
									if( $this->table_designs[$tindex]->fields[$const_field_name]->default_value
										== $const_key
									){
										$this->table_designs[$tindex]->fields[$const_field_name]->default_value =
											"self::".$const_key;
									}
								}
							}
							else
							{
								$tindex = end(array_keys($this->table_designs));
								$error_tname = $this->table_designs[$tindex]->name;
								crow_log::write('unknown const field '.$error_tname."::".$const_field_name, "system");
								exit;
							}
						}

						//	リレーションコマンド
						else if( substr($cmd, 0, 6) == ".refer" )
						{
							$refer_field_name = substr($cmd, 6 + 1);
							if( isset($this->table_designs[$tindex]->fields[$refer_field_name]) === true )
							{
								$tindex = end(array_keys($this->table_designs));
								$opt_cnt = count($opt_args);
								if( count($opt_args) < 3 )
								{
									$error_tname = $this->table_designs[$tindex]->name;
									crow_log::write('unknown refer args '.$error_tname."::".$refer_field_name, "system");
									exit;
								}
								$this->table_designs[$tindex]->fields[$refer_field_name]
									->refer_table = trim($opt_args[1]);
								$this->table_designs[$tindex]->fields[$refer_field_name]
									->refer_remove = trim($opt_args[2]);
							}
							else
							{
								$tindex = end(array_keys($this->table_designs));
								$error_tname = $this->table_designs[$tindex]->name;
								crow_log::write('unknown refer field '.$error_tname."::".$refer_field_name, "system");
								exit;
							}
						}

						//	ユニーク付きインデックス指定コマンド
						else if( substr($cmd, 0, 10) == ".index_unq" )
						{
							$index_name = substr($cmd, 10 + 1);
							for( $i = 1; $i < count($opt_args); $i++ )
								$this->table_designs[$tindex]->indexes_with_unique[$index_name][] = trim($opt_args[$i]);
						}

						//	インデックス指定コマンド
						else if( substr($cmd, 0, 6) == ".index" )
						{
							$index_name = substr($cmd, 6 + 1);
							for( $i = 1 ; $i < count($opt_args); $i++ )
								$this->table_designs[$tindex]->indexes[$index_name][] = trim($opt_args[$i]);
						}

						//	バリデーションコマンド
						else if( substr($cmd, 0, 13) == ".valid_regexp" )
						{
							$tname = $this->table_designs[$tindex]->name;
							$colname = substr($cmd, 13 + 1);

							if( isset($this->table_designs[$tindex]->fields[$colname]) === true )
							{
								$regexp = trim(substr($line, strpos($line, ":") + 1));
								$this->table_designs[$tindex]->fields[$colname]->valid_regexp = $regexp;
							}
							else
							{
								crow_log::write('unkonwn valid regexp field '.$tname."::".$colname, "system");
							}
						}
					}
					continue;
				}

				//	テーブルの定義。#またはカンマ(,)で区切って拡張できる。
				$db_table = new crow_db_design_table();
				$cols = explode("#",$line);
				if( count($cols) <= 1 )
				{
					$cpos = strpos($line, ",");
					if( $cpos !== false )
					{
						$cols =
						[
							substr($line, 0, $cpos),
							substr($line, $cpos + 1)
						];
					}
				}
				$db_table->name = trim($cols[0]);
				$db_table->logical_name = $table_logical_name;
				$db_table->remark = $table_remark;
				$table_logical_name = "";
				$table_remark = "";
				if( count($cols) > 1 )
				{
					for( $i = 1; $i < count($cols); $i++ )
					{
						$opt = trim($cols[$i]);
						if( strlen($opt) <= 0 ) continue;

						//	#オプション名(引数, 引数, ...)
						//	の形で指定できる。
						$opt_args = mb_split_multiple('(\(|,|\))', $opt);
						$opt_index = count($db_table->options);
						$db_table->options[$opt_index]["name"] = $opt_args[0];
						for( $j = 1; $j < count($opt_args); $j++ )
						{
							$db_table->options[$opt_index]["args"][] = trim($opt_args[$j]);
						}
					}
				}
				$stat = 1;
			}
			else if( $stat==1 )
			{
				if( $line=="{" )
				{
					//	定義開始
					$stat = 2;
				}
			}
			else if( $stat==2 )
			{
				if( $line=="{" )
				{
					//	const定義開始
					$stat = 3;
				}
				if( $line=="}" )
				{
					//	定義終了
					$stat = 0;

					//	テーブルオプションがある場合、追加でフィールドを読み込む
					//	場所は、「CROW_PATH/engine/kernel/table_ext/オプション名/fields.txt」とする
					if( count($db_table->options) > 0 )
					{
						foreach( $db_table->options as $option )
						{
							$this->read_design_extension( $db_table, $option );
						}
					}

					//	テーブル一覧に追加
					$this->table_designs[$db_table->name] = $db_table;
				}
				else
				{
					//	フィールド定義
					$this->read_design_column( $db_table, $line );
				}
			}
			else if( $stat==3 )
			{
				if( $line=="}" )
				{
					//	const定義終了
					$stat = 2;

					//	const定義終了したフィールドに初期値指定がある場合は必要に応じて"self::"を付与する
					$last_field_key = end(array_keys($db_table->fields));
					$last_field_val = $db_table->fields[$last_field_key]->default_value;
					$const_keys = array_keys($db_table->fields[$last_field_key]->const_array);
					if( in_array($last_field_val, $const_keys) )
						$db_table->fields[$last_field_key]->default_value = "self::".$last_field_val;
				}
				else
				{
					//	定数定義
					//$field_name = '';
					//foreach( $db_table->fields as $field_key => $field_val ) $field_name = $field_key;
					$field_name = end(array_keys($db_table->fields));
					$this->read_design_const( $db_table, $field_name, $line );
				}
			}
		}

		//	最後、参照解決を行う
		foreach( $this->table_designs as $tname => $table )
		{
			foreach( $table->fields as $fname => $field )
			{
				if( $field->refer_table === false ) continue;

				$ref_table = isset($this->table_designs[$field->refer_table]) ? $this->table_designs[$field->refer_table] : false;
				if( $ref_table === false )
				{
					crow_log::write('unknown refer table '.$field->refer_table." from ".$tname."::".$fname, "system");
					exit;
				}

				//	複合キーのテーブルに対してrefできない
				if( is_array($ref_table->primary_key) === true )
				{
					crow_log::write('cant refer table with multi primary key, from '.$tname."::".$fname, "system");
					exit;
				}

				if( isset($ref_table->referrers[$tname]) == true )
					$ref_table->referrers[$tname][] = [$fname, $field->refer_remove];
				else
					$ref_table->referrers[$tname] = [[$fname, $field->refer_remove]];
			}
		}

		return true;
	}
	protected function read_design_const( &$db_table_, $field_name_, $line_ )
	{
		$const_logical_name = "";
		$pos = mb_strpos($line_, "#");
		if( $pos !== false )
		{
			$remarks = mb_substr($line_, $pos+1);
			$line_ = mb_substr($line_, 0, $pos);

			$pos = mb_strpos($remarks, ",");
			if( $pos !== false )
				$const_logical_name = trim(mb_substr($remarks,0,$pos));
			else
				$const_logical_name = trim($remarks);
		}

		$cols = explode(",", $line_);
		if( count($cols) >= 2 )
		{
			$const_key = $cols[0];
			$const_val = trim($cols[1]);

			//	二進数指定？
			if( preg_match('/^b[0-9]+$/', $const_val) )
				$db_table_->fields[$field_name_]->const_array[$const_key] = bindec(substr($const_val, 1));
			else
				$db_table_->fields[$field_name_]->const_array[$const_key] = $const_val;

			$db_table_->fields[$field_name_]->const_logical_names[$const_key] = $const_logical_name;
		}
	}
	protected function read_design_column( &$db_table_, $line_ )
	{
		$field_logical_name = "";
		$field_remark = "";

		$pos = mb_strpos($line_, "#");
		if( $pos !== false )
		{
			$remarks = mb_substr($line_, $pos+1);
			$line_ = mb_substr($line_, 0, $pos);

			$pos = mb_strpos($remarks, ",");
			if( $pos !== false )
			{
				$field_logical_name = trim(mb_substr($remarks,0,$pos));
				$field_remark = trim(mb_substr($remarks,$pos+1));
			}
			else
			{
				$field_logical_name = trim($remarks);
				$field_remark = "";
			}
		}

		$cols = explode(",", $line_);
		if( count($cols) >= 2 )
		{
			$col_cnt = 0;

			//	フィールド名と型
			$field = new crow_db_design_field();
			$field->name = trim($cols[$col_cnt]); $col_cnt++;
			$field->type = strtolower(trim($cols[$col_cnt])); $col_cnt++;
			$field->logical_name = $field_logical_name;
			$field->remark = $field_remark;

			if( substr($field->type,0,1)=="[" &&
				substr($field->type,-1)=="]"
			){
				//	生の型指定
			}
			else
			{
				//	型のチェック
				$types =
				[
					'tinyint', 'utinyint', 'int', 'uint', 'bigint', 'ubigint',
					'varchar', 'text', 'bigtext',
					'varcrypt', 'crypt', 'bigcrypt', "mailcrypt", 'password',
					'datetime', 'unixtime', 'boolean', 'url', 'mail', 'telno',
					'geometry', 'float', 'double',
					'bit',
				];
				if( ! in_array($field->type, $types) )
				{
					crow_log::write('unknown field type "'.$field->type.'" in table "'.$db_table_->name.'"', "system");
					exit;
				}
			}

			//	フィールド名部分はデフォルト値がくっついてる場合がある
			$eq_pos = strpos( $field->name, "=" );
			$exists_default = false;
			if( $eq_pos !== false )
			{
				$exists_default = true;
				$field->default_value = trim(substr($field->name, $eq_pos+1));
				$field->name = trim(substr($field->name, 0, $eq_pos));
				if( substr($field->default_value, -1)==")" &&
					strpos($field->default_value, "(")!==false
				){
					//	コードを保持
					$field->default_code = $field->default_value;

					//	値は標準のものにする
					if( $field->type=='tinyint' || $field->type=='utinyint' ||
						$field->type=='int' || $field->type=='uint' ||
						$field->type=='bigint' || $field->type=='ubigint' ||
						$field->type=='datetime' || $field->type=='unixtime' ||
						$field->type=='float' || $field->type=='double' || $field->type=='bit'
					)
						$field->default_value = 0;
					else if( $field->type=='boolean' )
						$field->default_value = false;
					else if( $field->type=='geometry' )
						$field->default_value = "[0,0]";
					else
						$field->default_value = "";
				}
			}
			else{
				$field->name = $field->name;

				//	指定がない場合は型によってデフォルト値が異なる
				if( $field->type=='tinyint' || $field->type=='utinyint' ||
					$field->type=='int' || $field->type=='uint' ||
					$field->type=='bigint' || $field->type=='ubigint' ||
					$field->type=='datetime' || $field->type=='unixtime' ||
					$field->type=='float' || $field->type=='double' || $field->type=='bit'
				)
					$field->default_value = 0;
				else if( $field->type=='boolean' )
					$field->default_value = false;
				else if( $field->type=='geometry' )
					$field->default_value = "[0,0]";
				else
					$field->default_value = "";
			}

			//	型がvarchar/varcrypt/passwordならサイズが次にくる
			if( $field->type=='varchar' || $field->type=='varcrypt' || $field->type=='password' )
			{
				$field->size = intval( trim($cols[$col_cnt]) );
				$col_cnt++;
			}

			//	bit型の場合はバイトサイズが次に来る「可能性」がある。ビットサイズではないことに注意
			else if( $field->type=='bit' )
			{
				//	指定がある場合
				if( isset($cols[$col_cnt])===true && crow_validation::check_num(trim($cols[$col_cnt])) )
				{
					$field->size = intval(trim($cols[$col_cnt])) * 8;
					$col_cnt++;
				}
				//	指定がない場合は1byte(8bits)とする
				else
				{
					$field->size = 8;
				}
			}

			//	数値のカラムの場合は、サイズが次に来る「可能性」がある
			else if( $field->type=='tinyint' || $field->type=='utinyint' ||
				$field->type=='int' || $field->type=='uint' ||
				$field->type=='bigint' || $field->type=='ubigint'
			){
				//	数値の場合はサイズとする
				if( isset($cols[$col_cnt])===true && crow_validation::check_num(trim($cols[$col_cnt])) )
				{
					$field->size = $this->calc_field_byte_size($field->type,intval( trim($cols[$col_cnt]) ));
					$col_cnt++;
				}
				//	そうでない場合は、デフォルトのサイズとする
				else
				{
					$field->size = $this->calc_field_byte_size($field->type);
				}
			}

			for( ; $col_cnt<count($cols); $col_cnt++ )
			{
				$opt_org = trim($cols[$col_cnt]);
				$opt = strtolower($opt_org);
				if( $opt == 'pk' )
				{
					$field->primary_key = true;
					if( $db_table_->primary_key===false )
						$db_table_->primary_key = $field->name;
					else if( ! is_array($db_table_->primary_key) )
						$db_table_->primary_key = [$db_table_->primary_key, $field->name];
					else
						$db_table_->primary_key[] = $field_name;
				}
				else if( $opt == 'ai' ) $field->auto_increment = true;
				else if( $opt == 'nullable' )
				{
					$field->nullable = true;

					//	nullable フィールドのデフォルトは指定がなければ null とする
					if( $exists_default === false ) $field->default_value = null;
				}
				else if( $opt == 'i' )
				{
					$db_table_->indexes['index_'.$field->name] = [$field->name];
				}
				else if( $opt == 'unq' ) $field->unique = true;
				else if( $opt == 'del' )
				{
					$field->deleted = true;
					$db_table_->deleted = $field->name;
				}
				else if( $opt == 'must' ) $field->must = true;
				else if( $opt == 'order' || $opt == 'order_asc' )
				{
					$field->order = true;
					$field->order_vector = 'asc';
					$db_table_->order = $field->name;
					$db_table_->order_vector = 'asc';
				}
				else if( $opt == 'order_desc' )
				{
					$field->order = true;
					$field->order_vector = 'desc';
					$db_table_->order = $field->name;
					$db_table_->order_vector = 'desc';
				}
				else if( preg_match('/^[aA0_]+$/', $opt_org) )
				{
					$field->valid_charcase = $opt_org;
				}
				else if( preg_match('/^[-]?[0-9]*:[-]?[0-9]*$/', $opt) )
				{
					$ranges = explode(":", $opt);
					if( count($ranges) != 2 )
					{
						crow_log::write('syntax error of range option "'.$field->name.'" in table "'.$db_table->name.'"', "system");
						exit;
					}
					$from = trim($ranges[0]);
					$to = trim($ranges[1]);

					if( $to == "" &&
						($field->type == "varchar" || $field->type == "varcrypt" || $field->type == "password")
					)	$to = $field->size;


					if( in_array($field->type, ["varchar", "varcrypt", "password"]) === true )
					{
						if( $to == "" ) $to = $field->size;

						$field->valid_range_from = in_array($field->type, ["float", "double"]) ? floatval($from) : intval($from);
						$field->valid_range_to = in_array($field->type, ["float", "double"]) ? floatval($to) : intval($to);
					}
					else
					{
						$field->valid_range_from = $from == "" ?
							false :
							(in_array($field->type, ["float", "double"]) ? floatval($from) : intval($from))
							;

						$field->valid_range_to = $to == "" ?
							false :
							(in_array($field->type, ["float", "double"]) ? floatval($to) : intval($to))
							;
					}
				}
				else if( strlen($opt) > 0 )
				{
					//	エラー
					crow_log::write('unknown option parameter "'.$opt_org.'" in field "'.$field->name.'" in table "'.$db_table->name.'"', "system");
					exit;
				}
			}

			//	フィールド追加
			$db_table_->fields[$field->name] = $field;
		}
	}
	protected function read_design_extension( &$db_table_, $option_ )
	{
		//	テーブルオプションがある場合、追加でフィールドを読み込む
		//	場所は、「CROW_PATH/engine/kernel/table_ext/オプション名/fields.txt」とする
		$path = CROW_PATH."engine/kernel/table_ext/".$option_["name"]."/fields.txt";
		if( ! is_file($path) ) return;
		$lines = file($path);

		$stat = 2;
		foreach( $lines as $line )
		{
			//	コメントアウト
			$cmt_pos = mb_strpos( $line, ";" );
			if( $cmt_pos !== false ) $line = mb_substr( $line, 0, $cmt_pos );
			$line = trim($line);
			if( mb_strlen($line) <= 0 ) continue;

			//	const定義開始
			if( $line == "{" )
			{
				$stat = 3;
				continue;
			}
			//	const定義終了
			else if( $line == "}" )
			{
				$stat = 2;
				continue;
			}
			else if( $stat == 3 )
			{
				//	定数定義
				$field_name = '';
				foreach( $db_table_->fields as $field_key => $field_val ) $field_name = $field_key;
				$this->read_design_const( $db_table_, $field_name, $line );
			}
			else
			{
				//	フィールド解析
				$this->read_design_column( $db_table_, $line );
			}
		}
	}

	//--------------------------------------------------------------------------
	//	プライベートメンバ
	//--------------------------------------------------------------------------
	protected $table_designs = [];
}
?>
