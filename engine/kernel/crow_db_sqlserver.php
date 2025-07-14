<?php
/*

	DBインタフェース : sqlserver

*/
class crow_db_sqlserver extends crow_db
{
	//--------------------------------------------------------------------------
	//	生成、破棄
	//--------------------------------------------------------------------------
	public function __construct( $server_type_ = "writer" )
	{
		$autoconn = crow_config::get('db.autoconn.'.$server_type_,
			crow_config::get('db.autoconn', false));
		if( $autoconn === false ) return crow_log::error("not specified 'db.autoconn'");
		if( $autoconn == "true" ) $this->connect();
	}
	public function __destruct()
	{
		$this->disconnect();
	}

	//--------------------------------------------------------------------------
	//	override : 初期化
	//--------------------------------------------------------------------------
	public function init()
	{
		//	ログの設定を読み込む
		$this->m_output_log = crow_config::get("log.sql", "false")=="true" ? true : false;

		//	DB仕様をキャッシュから読み込む or キャッシュを作成する
		if( ! $this->read_design_file() ) return( false );

		//	DB用モデルクラスのキャッシュを読み込む or キャッシュを作成する
		$this->read_model_classes();

		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 接続済み？
	//--------------------------------------------------------------------------
	public function is_connected()
	{
		return $this->m_hdb !== false;
	}

	//--------------------------------------------------------------------------
	//	override : 接続
	//--------------------------------------------------------------------------
	public function connect( $server_type_ = "writer" )
	{
		if( $this->m_hdb ) $this->disconnect();

		//	接続パラメータ取得
		$addr = crow_config::get('db.address.'.$server_type_, crow_config::get('db.address', ''));
		$name = crow_config::get('db.name.'.$server_type_, crow_config::get('db.name', ''));
		$user = crow_config::get('db.userid.'.$server_type_, crow_config::get('db.userid', ''));
		$pass = crow_config::get('db.password.'.$server_type_, crow_config::get('db.password', ''));
		if( strlen($addr)<=0 || strlen($name)<=0 || strlen($user)<=0 )
		{
			crow_log::write( sprintf(
				"illegal database setting, addr[%s] name[%s] user[%s]",
				$addr, $name, $user
			), "system" );
			exit;
		}

		//	接続
		$this->m_hdb = sqlsrv_connect(
			$addr,
			[
				"UID"			=> $user,
				"PWD"			=> $pass,
				"Database"		=> $name,
				"CharacterSet"	=> "UTF-8",
				"TrustserverCertificate" => 0,
				"ReturnDatesAsStrings"=>1,
			]
		);
		if( ! $this->m_hdb )
		{
			$errs = sqlsrv_errors();
			foreach( $errs as $err )
				crow_log::write("code[".$err['code']."] msg[".$err['message']."]", "system");
			crow_log::write( sprintf(
				"failed in the connection to database, addr[%s] name[%s] user[%s]",
				$addr, $name, $user
			), "system" );
			exit;
		}

		$this->m_addr = $addr;
		$this->m_name = $name;
		$this->m_user = $user;
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 接続先を指定して接続する
	//--------------------------------------------------------------------------
	public function connect_to( $dummy_, $addr_, $name_, $uid_, $upass_ )
	{
		//	現在接続中で且つ、接続先が同じなら、何もしない。
		if( $this->m_hdb )
		{
			if(
				$this->m_addr == $addr_ &&
				$this->m_name == $name_ &&
				$this->m_user == $uid_
			)	return true;
		}

		//	設定値を、一時的に別のものにする
		$org_addr	= crow_config::get('db.address');
		$org_name	= crow_config::get('db.name');
		$org_user	= crow_config::get('db.userid');
		$org_pass	= crow_config::get('db.password');
		crow_config::set( 'db.address',		$addr_ );
		crow_config::set( 'db.name',		$name_ );
		crow_config::set( 'db.userid',		$uid_ );
		crow_config::set( 'db.password',	$upass_ );

		//	接続
		$ret = $this->connect("__temp__");

		//	設定値を戻しておく
		crow_config::set( 'db.address',		$org_addr );
		crow_config::set( 'db.name',		$org_name );
		crow_config::set( 'db.userid',		$org_user );
		crow_config::set( 'db.password',	$org_pass );
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : 切断
	//--------------------------------------------------------------------------
	public function disconnect()
	{
		if( ! $this->m_hdb ) return;
		sqlsrv_close( $this->m_hdb );
		$this->m_hdb = false;
	}

	//--------------------------------------------------------------------------
	//	override : 生のDBハンドルを取得する
	//--------------------------------------------------------------------------
	public function get_raw_hdb()
	{
		return $this->m_hdb;
	}

	//--------------------------------------------------------------------------
	//	override : 一時的にSQLログの出力設定を変更する
	//--------------------------------------------------------------------------
	public function change_log_temp( $is_output_ )
	{
		$this->m_output_log_back = $this->m_output_log;
		$this->m_output_log = $is_output_;
	}
	public function recovery_log_temp()
	{
		$this->m_output_log = $this->m_output_log_back;
	}

	//--------------------------------------------------------------------------
	//	override : 最後のinsert時で発行された、autoincrementの値を取得する
	//--------------------------------------------------------------------------
	public function get_insert_id()
	{
		if( ! $this->m_hdb ) return false;

		$rid = sqlsrv_query($this->m_hdb, "select scope_identity() as insert_id");
		if( ! $rid ) return false;
		$row = sqlsrv_fetch_array($rid);
		return $row['insert_id'];
	}

	//--------------------------------------------------------------------------
	//	override : シンボルをエスケープする
	//--------------------------------------------------------------------------
	public function escape_symbol( $symbol_ )
	{
		return "[".$symbol_."]";
	}

	//--------------------------------------------------------------------------
	//	override : like文に指定する値をエスケープする
	//--------------------------------------------------------------------------
	public function escape_like_value( $str_ )
	{
		return mb_str_replace( "%", "[%]",
			mb_str_replace("_", "[_]",
				mb_str_replace("[", "[[]", $str_)
			)
		);
	}

	//--------------------------------------------------------------------------
	//	override : トランザクション
	//--------------------------------------------------------------------------
	public function begin()
	{
		if( ! $this->m_hdb ) return;
		sqlsrv_begin_transaction($this->m_hdb);
	}
	public function commit()
	{
		if( ! $this->m_hdb ) return;
		sqlsrv_commit($this->m_hdb);
	}
	public function rollback()
	{
		if( ! $this->m_hdb ) return;
		sqlsrv_rollback($this->m_hdb);
	}

	//--------------------------------------------------------------------------
	//	override : 全テーブルを取得する
	//--------------------------------------------------------------------------
	public function get_tables()
	{
		$names = [];
		if( ! $this->m_hdb ) return $names;
		$rset = $this->query("select name from sys.objects where type='U'");
		if( ! $rset ) return false;
		return array_column($rset->get_rows(), 'name');
	}

	//--------------------------------------------------------------------------
	//	override : テーブルの存在チェック
	//--------------------------------------------------------------------------
	public function exists_table( $name_ )
	{
		return in_array($name_, $this->get_tables());
	}

	//--------------------------------------------------------------------------
	//	override : フィールド情報の一覧を取得
	//--------------------------------------------------------------------------
	public function get_fields( $table_name_ )
	{
		//	カラム情報取得
		$rset = $this->query( ""
			."select "
				."c.name column_name,"
				."ty.name as type_name,"
				."c.max_length as len,"
				."c.is_identity as idval "
			."from "
				."sys.tables as t "
			."join "
				."sys.columns as c "
			."on "
				."c.object_id=t.object_id "
			."join "
				."sys.types as ty "
			."on "
				."c.system_type_id=ty.system_type_id "
			."where "
				."t.name='".$table_name_."' "
		);
		if( ! $rset ) return [];
		$col_info = $rset->get_rows();

		//	PrimaryKey情報の取得
		$rset = $this->query( ""
			."select "
				."kt.name as name, "
				."kt.is_primary_key as primary_key "
			."from "
				."sys.indexes as kt "
			."join "
				."sys.tables as t "
			."on "
				."t.object_id=kt.object_id "
			."where "
				."t.name='".$table_name_."'"
		);
		if( ! $rset ) return [];
		$key_info = $rset->get_rows();

		//	整形
		$ret = [];
		foreach( $col_info as $col )
		{
			$pk_found = false;
			foreach( $key_info as $key )
			{
				if(
					$key['name']==$col['column_name'] &&
					$key['primary_key']=="1"
				){
					$pk_found = true;
					break;
				}
			}
			$ret[$col['column_name']] =
			[
				'name'				=> $col['column_name'],
				'type'				=> $col['type_name'],
				'length'			=> $col['len'],
				'unsigned'			=> false,
				'primary_key'		=> $pk_found,
				'auto_increment'	=> $col['idval']=="1",
			];
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : インデックスの一覧を取得する
	//
	//	ret
	//	{
	//		indexes =>
	//		{
	//			table名1 =>
	//			{
	//				index名1 => [カラム1, カラム2...]
	//				index名2 => [カラム1, カラム2...]
	//				...
	//			},
	//			table名2 =>
	//			{
	//				....
	//		},
	//		uniques =>
	//		{
	//			上記indexesと同じフォーマット
	//		}
	//	}
	//--------------------------------------------------------------------------
	public function get_indexes()
	{
		$rset = $this->query( ""
			.""
			."select "
				."t.name as table_name, "
				."sit.name as index_name, "
				."ct.name as col, "
				."sit.is_unique as is_unique, "
				."sit.is_primary_key as is_primary_key "
			."from "
				."sys.index_columns as sict "
			."join "
				."sys.indexes as sit "
			."on "
				."sit.object_id=sict.object_id "
			."join "
				."sys.tables as t "
			."on "
				."t.object_id=sict.object_id "
			."join "
				."sys.columns as ct "
			."on "
				."ct.object_id=sict.object_id and "
				."ct.column_id=sict.index_column_id "
			."order by "
				."sict.object_id, sict.index_id, sict.index_column_id "
		);
		if( ! $rset ) return [];

		$rows = $rset->get_rows();
		$ret = [ "indexes" => [], "uniques" => [] ];

		foreach( $rows as $row )
		{
			if( $row["is_unique"]=="1" )
			{
				$ret["uniques"][$row['table_name']][$row['index_name']][] = $row['col'];
			}
			else
			{
				$ret["indexes"][$row['table_name']][$row['index_name']][] = $row['col'];
			}
		}

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : crow_sqlのオブジェクトから、SQL文字列を作成する
	//--------------------------------------------------------------------------
	public function build_sql( $sql_ )
	{
		$sql = "";
		$cmd = strtolower($sql_->m_command);
		if( $cmd=='select' )
		{
			$target = "";
			foreach( $sql_->m_targets as $t )
			{
				if( $target!="" ) $target .= ",";
				if( substr($t,0,1)=="#" )
				{
crow_log::notice("** incompatible crypt column");
/*
一旦暗号化は非対応
					$name = substr($t,1);
					if( $cryptkey=="" )
						$cryptkey = $this->encode(crow_config::get('db.cryptkey'), '');

					$target .= "AES_DECRYPT([".$name."],'".$cryptkey."') as [".$name."]";
*/
				}
				else if( substr($t,0,3)=="NC#" )
				{
					$target .= substr($t, 3);
				}
				else
				{
					if( strpos($t,"[")===false )
					{
						$pos = strpos($t, " ");
						if( $pos===false )
							$target .= "[".$t."]";
						else
							$target .= $t;
					}
					else $target .= $t;
				}
			}


			$sql .= sprintf("select %s from [%s]",
				count($sql_->m_targets)<=0 ? "*" : $target,
				$sql_->m_from
			);
			if( count($sql_->m_where) > 0 ) $sql .= " where ".$this->build_where($sql_->m_where);
			if( $sql_->m_groupby !== false ) $sql .= " group by [".$sql_->m_groupby."]";
			if( count($sql_->m_orderby) > 0 )
			{
				$order_queries = [];
				for( $oi=0; $oi<count($sql_->m_orderby); $oi++ )
				{
					$order_query = $sql_->m_orderby[$oi];
					if( isset($sql_->m_orderby_vector[$oi]) === true )
						$order_query .= " ".$sql_->m_orderby_vector[$oi];
					$order_queries[] = $order_query;
				}
				$sql .= " order by [".implode(",", $order_queries)."]";
			}
			if( $sql_->m_limit_offset !== false &&
				$sql_->m_limit_size !== false
			){
crow_log::notice("** incompatible limit rows");
//一旦リミットは非対応

				//	仮で動くサンプル
				//	offset fetchを使うときはorder byが必須になる
				if( strpos('order',$sql)===false && $sql_->m_orderby===false )
				{
					$sql .= " order by";

					//	order by 指定する必要があるので仮でfrom句で指定されたものに_idで指定しておく
					//	クエリ文字列またはdb_designから持ってくるべき？
					$id = $sql_->m_from."_id";
				}

				$sql .= sprintf(' %s offset %d rows fetch next %d rows only',
					$id, $sql_->m_limit_offset, $sql_->m_limit_size 
				);

				//
				//	between を使用する場合、whereの設定が必要
				//
				//	if( strpos('where',$sql)===false )
				//		$sql .= ' where';
				//	$id = $sql_->m_from."_id";
				//	$sql .= sprintf( " %s between %d and %d",
				//		$id, $sql_->m_limit_offset, $sql_->m_limit_size );

			}
		}
		else if( $cmd=='insert' )
		{
			$values = $sql_->m_values;
			foreach( $values as $index => $val ){
				$values["[".$index."]"] = "'".$this->encode($val)."'";
			}
			$sql .= sprintf("insert into %s (%s) values (%s)",
				$sql_->m_from, implode(",",array_keys($values)), implode(",",$values) );
		}
		else if( $cmd=='update' )
		{
			$sets = [];
			foreach( $sql_->m_values as $key => $val ){
				$sets[] = "[".$key."]='".$this->encode($val)."'";
			}
			$sql .= sprintf("update [%s] set %s", $sql_->m_from, implode(",",$sets));
			if( count($sql_->m_where) > 0 ) $sql .= " where ".$this->build_where($sql_->m_where);
		}
		else if( $cmd=='delete' )
		{
			$sql .= "delete from [".$sql_->m_from."]";
			if( count($sql_->m_where) > 0 ) $sql .= " where ".$this->build_where($sql_->m_where);
		}
		else
		{
			crow_log::error('query cmd unknown:'.$cmd);
		}
		return $sql;
	}
	private function build_where( $where_ )
	{
		$sql = "";
		foreach( $where_ as $item )
		{
			if( $item=="(" ) $sql .= "(";
			else if( $item==")" ) $sql .= ")";
			else if( $item=="and" ) $sql .= " and ";
			else if( $item=="or" ) $sql .= " or ";
			else if( is_array($item) )
			{
				if( $item[3] === true )
				{
					if( $item[4] === true )
						$sql .= $item[0].' '.$item[1]." ".$item[2];
					else
						$sql .= $item[0].' '.$item[1]." '".$this->encode($item[2])."'";
				}
				else
				{
					if( $item[4] === true )
						$sql .= "[".$item[0].'] '.$item[1]." ".$item[2];
					else
						$sql .= "[".$item[0].'] '.$item[1]." '".$this->encode($item[2])."'";
				}
			}

		}
		return $sql;
	}

	//--------------------------------------------------------------------------
	//	override : sqlファイルからSQL文字列を作成する
	//--------------------------------------------------------------------------
	public function raw( /* name_, param1, param2 ... */ )
	{
		//	必要なら初期化
		if( $this->m_raws === false ) $this->raw_init();

		//	クエリ定義のチェック
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return "";
		$name = func_get_arg(0);
		if( ! isset($this->m_raws[$name]) )
		{
			crow_log::error("not found raw query : ".$name);
			return "";
		}

		//	フォーマットして返却
		$args = [];
		for( $i=1; $i<$arg_num; $i++ ) $args[] = $this->encode(func_get_arg($i));
		return vsprintf($this->m_raws[$name], $args);
	}

	public function raw_noencode( /* name_, param1, param2 ... */ )
	{
		//	必要なら初期化
		if( $this->m_raws === false ) $this->raw_init();

		//	クエリ定義のチェック
		$arg_num = func_num_args();
		if( $arg_num < 1 ) return "";
		$name = func_get_arg(0);
		if( ! isset($this->m_raws[$name]) )
		{
			crow_log::error("not found raw query : ".$name);
			return "";
		}

		//	フォーマットして返却
		$args = [];
		for( $i=1; $i<$arg_num; $i++ ) $args[] = func_get_arg($i);
		return vsprintf($this->m_raws[$name], $args);
	}
	private function raw_init()
	{
		if( $this->m_raws === false )
		{
			$role = crow_request::get_role_name();
			$module = crow_request::get_module_name();
			$action = crow_request::get_action_name();
			$cache_name = 'raw_'.$role."_".$module."_".$action;

			//	キャッシュにあるならそれを使い、ないなら読み込む
			$this->m_raws = crow_cache::load($cache_name);
			if( $this->m_raws===false )
			{
				$this->m_raws = [];

				//	app/assets/query/_common_/*.sql
				$dir = CROW_PATH."app/assets/query/_common_/";
				$paths = crow_storage::disk()->get_files($dir, "sql");
				foreach( $paths as $path ) $this->append_load_raw($path);
				//	app/assets/query/<ROLE>/<ROLE>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query/".$role."/".$role.".sql");
				//	app/assets/query/<ROLE>/<ROLE>_<MODULE>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query/".$role."/".$role."_".$module.".sql");
				//	app/assets/query/<ROLE>/<ROLE>_<MODULE>_<ACTION>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query/".$role."/".$role."_".$module."_".$action.".sql");

				//	app/assets/query_<dbtype>/_common_/*.sql
				$dbtype = crow_config::get('db.type', '');
				$dir = CROW_PATH."app/assets/query_".$dbtype."/_common_/";
				$paths = crow_storage::disk()->get_files($dir, "sql");
				foreach( $paths as $path ) $this->append_load_raw($path);
				//	app/assets/query_<dbtype>/<ROLE>/<ROLE>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query_".$dbtype."/".$role."/".$role.".sql");
				//	app/assets/query_<dbtype>/<ROLE>/<ROLE>_<MODULE>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query_".$dbtype."/".$role."/".$role."_".$module.".sql");
				//	app/assets/query_<dbtype>/<ROLE>/<ROLE>_<MODULE>_<ACTION>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query_".$dbtype."/".$role."/".$role."_".$module."_".$action.".sql");

				//	キャッシュ更新
				crow_cache::save( $cache_name, $this->m_raws );
			}
		}
	}
	private function append_load_raw( $fname_ )
	{
		if( ! is_file($fname_) ) return false;
		if( ! is_readable($fname_) ) return false;

		//	読込み
		$lines = file( $fname_ );
		$query = "";
		$name = "";
		foreach( $lines as $line )
		{
			if( substr($line, 0, 2) == "--" ) continue;
			if( substr($line, 0, 1) == "@" )
			{
				if( trim($query) != "" && $name != "" )
					$this->m_raws[$name] = trim($query);

				$name = trim(substr($line,1));
				$query = "";
				continue;
			}
			$query .= trim($line)." ";
		}
		if( trim($query) != "" && $name != "" )
			$this->m_raws[$name] = trim($query);

		return true;
	}

	//--------------------------------------------------------------------------
	//	override : クエリ発行
	//
	//	成功時は結果ハンドルを返却。失敗時はfalseを返却
	//--------------------------------------------------------------------------
	public function query( $sql_ )
	{
		if( ! $this->m_hdb ) return false;

		//	ログ出力
		if( $this->m_output_log )
			crow_log::log_with_name( 'sql', $sql_ );

		//	実行
		if( ! ($ret = sqlsrv_query($this->m_hdb, $sql_, [], ['Scrollable'=>SQLSRV_CURSOR_CLIENT_BUFFERED])) )
		{
			$errs = sqlsrv_errors();
			$err_code = 0;
			$err_msg = "";
			foreach( $errs as $err )
			{
				$err_code = $err['code'];
				$err_msg  = $err['message'];
				crow_log::notice( sprintf(
					$sql_." errcode[%s], %s",
					$err_code, $err_msg
				) );
			}

			if(
				strtolower(substr($sql_,0,6))=="select" ||
				strtolower(substr($sql_,0,4))=="show"
			){
				$inst = new crow_db_result();
				$inst->result_set(false, $err_code, $err_msg);
				return $inst;
			}
			return false;
		}

		//	selectの場合は、結果セットを返却する
		if(
			strtolower(substr($sql_,0,6))=="select" ||
			strtolower(substr($sql_,0,4))=="show"
		)
		{
			$inst = new crow_db_result();
			$inst->result_set($ret);
			return $inst;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : SELECTに特化したクエリ発行
	//
	//	成功時はレコード(連想配列)の配列を返却。失敗時はfalseを返却
	//--------------------------------------------------------------------------
	public function select( $sql_ )
	{
		$rset = $this->query($sql_);
		if( ! $rset ) return false;
		if( $rset->num_rows() <= 0 ) return [];
		return $rset->get_rows();
	}

	//	1行のみ取得する（結果がない場合、false返却とする）
	public function select_one( $sql_ )
	{
		$rset = $this->query($sql_);
		if( ! $rset ) return false;
		if( $rset->num_rows() <= 0 ) return false;
		return $rset->get_row();
	}

	//--------------------------------------------------------------------------
	//	override : sqlファイルから取得したSQLを実行する
	//--------------------------------------------------------------------------
	public function raw_query( /* name_, param1, param2 ... */ )
	{
		return $this->query( call_user_func_array(
			[$this, "raw"], func_get_args()) );
	}
	public function raw_query_noencode( /* name_, param1, param2 ... */ )
	{
		return $this->query( call_user_func_array(
			[$this, "raw_noencode"], func_get_args()) );
	}
	public function raw_select( /* name_, param1, param2 ... */ )
	{
		return $this->select( call_user_func_array(
			[$this, "raw"], func_get_args()) );
	}
	public function raw_select_noencode( /* name_, param1, param2 ... */ )
	{
		return $this->select( call_user_func_array(
			[$this, "raw_noencode"], func_get_args()) );
	}
	public function raw_select_one( /* name_, param1, param2 ... */ )
	{
		return $this->select_one( call_user_func_array(
			[$this, "raw"], func_get_args()) );
	}
	public function raw_select_one_noencode( /* name_, param1, param2 ... */ )
	{
		return $this->select_one( call_user_func_array(
			[$this, "raw_noencode"], func_get_args()) );
	}

	//--------------------------------------------------------------------------
	//	override : 最後のクエリエラー情報を取得
	//--------------------------------------------------------------------------
	public function get_last_error_code()
	{
		$errs = sqlsrv_errors();
		if( count($errs) > 0 )
		{
			return $errs[0]['code'];
		}
		return 0;
	}
	public function get_last_error_msg()
	{
		$errs = sqlsrv_errors();
		if( count($errs) > 0 )
		{
			return $errs[0]['message'];
		}
	}
	public function get_last_sqlstate()
	{
crow_log::notice("** incompatible get last state");
/*
ステート取得は一旦非対応

		return mysqli_sqlstate($this->m_hdb);
*/
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から行数取得
	//--------------------------------------------------------------------------
	public function num_rows( $result_ )
	{
		if( ! $this->m_hdb ) return 0;
		if( ! $result_ ) return 0;
		return sqlsrv_num_rows($result_);
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から列数取得
	//--------------------------------------------------------------------------
	public function num_cols( $result_ )
	{
		if( ! $this->m_hdb ) return 0;
		if( ! $result_ ) return 0;
		return sqlsrvi_num_fields($result_);
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から１行取得
	//
	//		html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function get_row( $result_, $html_escape_=false )
	{
		if( ! $this->m_hdb ) return false;
		if( ! $result_ ) return false;
		return $this->decode_row(sqlsrv_fetch_array($result_), $html_escape_);
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から全行取得（第二引数でHTMLタグを有効にするか指定）
	//--------------------------------------------------------------------------
	public function get_rows( $result_, $html_escape_=false )
	{
		if( ! $this->m_hdb ) return false;
		if( ! $result_ ) return false;

		$ret = [];
		while( $arr=sqlsrv_fetch_array($result_) ) $ret[] = $arr;
		return $this->decode_rows($ret, $html_escape_);
	}

	//--------------------------------------------------------------------------
	//	override : 文字列をDBエンコードする
	//--------------------------------------------------------------------------
	public function encode( $value_ )
	{
		return mb_str_replace("'", "''", $value_);
	}

	//--------------------------------------------------------------------------
	//	override : 文字列をDBデコードする
	//
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function decode( $value_, $html_escape_=false )
	{
		return $html_escape_ ?
			htmlspecialchars($value_) : $value_;
	}

	//--------------------------------------------------------------------------
	//	override : 結果レコードをDBデコードする
	//
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function decode_row( $row_, $html_escape_=false )
	{
		$ret = [];
		if( ! is_array($row_) ) return $ret;
		if( count($row_) <= 0 ) return $ret;
		foreach( $row_ as $key => $val ){
			$ret[$key] = $this->decode( $val, $html_escape_ );
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : 結果レコードの配列をDBデコードする
	//
	//	html_escape_をtrueにすると文字列がHTMLエスケープされる。
	//--------------------------------------------------------------------------
	public function decode_rows( $rows_, $html_escape_=false )
	{
		$ret = [];
		if( ! is_array($rows_) ) return $ret;
		if( count($rows_) <= 0 ) return $ret;
		foreach( $rows_ as $key => $val )
		{
			foreach( $val as $key2 => $val2 )
				$ret[$key][$key2] = $this->decode( $val2, $html_escape_ );
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : 指定したDBモデルの内容で insert クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_insert_with_model( &$table_model_ )
	{
		$design = $this->get_design($table_model_->get_table_name());
		if( $design===false ) return false;
		$keys = [];
		$vals = [];
		$found_ai = false;
		$ai_name = "";
		$cryptkey = "";
		foreach( $design->fields as $field )
		{
			if( $field->auto_increment )
			{
				$found_ai = true;
				$ai_name = $field->name;
				continue;
			}
			$keys[] = "[".$field->name."]";

			if( $field->type=="tinycrypt" || $field->type=="crypt" || $field->type=="bigcrypt" || $field->type=="varcrypt" || $field->type=="mailcrypt" )
			{
				if( $cryptkey=="" ) $cryptkey = $this->encode(crow_config::get('db.cryptkey'), '');
				$vals[] = "AES_ENCRYPT('".$this->encode($table_model_->{$field->name})."', '".$cryptkey."')";
			}
			else if( $field->type=="password" )
			{
				$vals[] = "'".$this->encode(password_hash($table_model_->{$field->name}, PASSWORD_DEFAULT))."'";
			}
			else if( $field->type=="datetime" )
			{
				$vals[] = "'".date('Y-m-d H:i:s',intval($table_model_->{$field->name}))."'";
			}
			else if( $field->type=="boolean" )
			{
				$vals[] = $table_model_->{$field->name} ? "'1'" : "'0'";
			}
			else if( $field->type=="tinyint" || $field->type=="utinyint" || $field->type=="int" || $field->type=="uint" || $field->type=="bigint" || $field->type=="ubigint" || $field->type=="float" || $field->type=="double")
			{
				$vals[] = "'".intval($table_model_->{$field->name})."'";
			}
			else
			{
				$vals[] = "N'".$this->encode($table_model_->{$field->name})."'";
			}
		}
		$query = sprintf("insert into [%s] (%s) values (%s)",
			$design->name, implode(",",$keys), implode(",",$vals));
		if( ! $this->query($query) ) return false;

		//	auto increment のフィールドがあれば、設定して返却する
		if( $found_ai )
		{
			$new_id = $this->get_insert_id();
			$table_model_->{$ai_name} = $new_id;
			return $new_id;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 指定したDBモデルの内容で update クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_update_with_model( &$table_model_, $update_columns_ = false )
	{
		if( ! $table_model_ ) return false;
		$design = $this->get_design($table_model_->get_table_name());
		if( $design===false ) return false;
		if( $design->primary_key===false ) return false;

		$sets = [];
		$found_pk = false;
		$pk_names = [];
		$cryptkey = "";
		foreach( $design->fields as $field )
		{
			if( $field->primary_key )
			{
				$found_pk = true;
				$pk_names[] = $field->name;
				continue;
			}

			//	カラム指定がある場合はチェック
			if( $update_columns_ !== false && in_array($field->name, $update_columns_) === false )
				continue;


			if( $field->type=="tinycrypt" || $field->type=="crypt" || $field->type=="bigcrypt" || $field->type=="varcrypt" || $field->type=="mailcrypt" )
			{

				if( $cryptkey=="" ) $cryptkey = $this->encode(crow_config::get('db.cryptkey'), '');
				$sets[] = "[".$field->name."]=AES_ENCRYPT('".$this->encode($table_model_->{$field->name})."', '".$cryptkey."')";
			}
			else if( $field->type=="password" )
			{
				//	パスワードは指定がある時のみ更新する
				if( strlen($table_model_->{$field->name}) > 0 )
				{
					$sets[] = "[".$field->name."]='".$this->encode(password_hash($table_model_->{$field->name}, PASSWORD_DEFAULT))."'";
				}
			}
			else if( $field->type=="datetime" )
			{
				$sets[] = "[".$field->name."]='".date('Y-m-d H:i:s', intval($table_model_->{$field->name}))."'";
			}
			else if( $field->type=="boolean" )
			{
				$sets[] = "[".$field->name."]=".($table_model_->{$field->name} ? "'1'" : "'0'");
			}
			else if( $field->type=="tinyint" || $field->type=="utinyint" || $field->type=="int" || $field->type=="uint" || $field->type=="bigint" || $field->type=="ubigint")
			{
				$sets[] = "[".$field->name."]='".intval($table_model_->{$field->name})."'";
			}
			else
			{
				$sets[] = "[".$field->name."]=N'".$this->encode($table_model_->{$field->name})."'";
			}
		}

		//	primary key がないなら失敗
		if( ! $found_pk )
		{
			crow_log::warning( "need key to update query : ".$design->name );
			return false;
		}

		$where = "";
		foreach( $pk_names as $pk_index => $pk_name )
		{
			if( $pk_index != 0 ) $where .= " and ";
			$where .= "[".$pk_name."]='".$this->encode($table_model_->{$pk_name})."'";
		}

		$query = sprintf("update [%s] set %s where %s",
			$design->name,
			implode(",",$sets),
			$where
		);
		return $this->query($query);
	}

	//--------------------------------------------------------------------------
	//	override : 指定したDBモデルの内容でゴミ箱へ移動するクエリを発行する
	//	（削除フラグがなければ完全削除となる）
	//--------------------------------------------------------------------------
	public function exec_trash_with_model( &$table_model_ )
	{
		if( ! $table_model_ ) return false;
		$design = isset($this->table_designs[$table_model_->get_table_name()]) ?
			$this->table_designs[$table_model_->get_table_name()] : false;
		if( $design===false ) return false;
		if( $design->primary_key===false ) return false;
		if( $design->deleted===false ) return $this->exec_delete_with_model($table_model_);

		$where = '';
		$pkey_value = false;
		if( is_array($design->primary_key) )
		{
			foreach( $design->primary_key as $pkey )
			{
				if( $where != '' ) $where .= " and ";
				$where .= "[".$pkey."]='".$this->encode($table_model_->{$pkey})."'";
			}
		}
		else
		{
			$pkey_value = $table_model_->{$design->primary_key};
			$where = "[".$design->primary_key."]='".$this->encode($pkey_value)."'";
		}

		$query = sprintf("update [%s] set [%s]=1 where %s",
			$design->name,
			$design->deleted,
			$where
		);
		if( $this->query($query) === false ) return false;

		//	必要ならリレーション削除、但し複合プライマリキーは非対応
		if( count($design->referrers) > 0 && is_array($design->primary_key) == false )
		{
			if( $this->trash_recursive($design, $pkey_value) === false )
				return false;
		}
		return true;
	}
	private function trash_recursive( &$table_design_, $pkey_val_ )
	{
		foreach( $table_design_->referrers as $from_tname => $from_fields )
		{
			//	削除条件作成
			$wheres = [];
			$exists_trash_field = false;
			foreach( $from_fields as $fdef )
			{
				$wheres[] = is_array($pkey_val_) ?
					('['.$fdef[0]."] in ('".implode("','", $pkey_val_)."')") :
					('['.$fdef[0]."]='".$this->encode($pkey_val_)."'")
					;
				if( $fdef[1]=="trash" ) $exists_trash_field = true;
			}

			//	参照元のテーブルにも、参照するテーブルがある場合、再帰処理
			//	ただし、ゼロ埋めの場合には処理なし。
			if( $exists_trash_field == true )
			{
				$from_design = $this->table_designs[$from_tname];
				if( count($from_design->referrers) > 0 )
				{
					//	削除予定のキー一覧取得 
					$query = sprintf("select [%s] from [%s] where %s",
						$from_design->primary_key, $from_tname, implode(" or ", $wheres));
					$rows = $this->select($query);
					$ref_keys = array_column($rows, $from_design->primary_key);

					//	参照元をさらに参照するテーブルたちへ再帰
					if( count($ref_keys) <= 0 ) {}
					else if( count($ref_keys) == 1 )
					{
						if( $this->trash_recursive($from_design, $ref_keys[0]) === false )
							return false;
					}
					else
					{
						if( $this->trash_recursive($from_design, $ref_keys) === false )
							return false;
					}
				}
			}

			//	一つでも削除指定のカラムがあれば、対処カラム値「or」で削除する
			if( $exists_trash_field == true )
			{
				//	参照元テーブルに削除フラグがなければ完全削除
				if( $this->table_designs[$from_tname]->deleted === false )
				{
					$query = sprintf("delete from [%s] where %s",
						$from_tname,
						implode(" or ", $wheres)
					);
					if( $this->query($query) === false ) return false;
				}
				//	削除フラグがあるなら論理削除
				else
				{
					$query = sprintf("update [%s] set [%s]=1 where %s",
						$from_tname,
						$this->table_designs[$from_tname]->deleted,
						implode(" or ", $wheres)
					);
					if( $this->query($query) === false ) return false;
				}
			}

			//	削除指定のカラムがなければゼロ埋め
			else
			{
				foreach( $from_fields as $fdef )
				{
					$query = sprintf("update [%s] set [%s]=0 where [%s]='%s'",
						$from_tname,
						$fdef[0],
						$fdef[0],
						$this->encode($pkey_val_)
					);
					if( $this->query($query) === false ) return false;
				}
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 指定したDBモデルの内容で完全削除を行うクエリを発行する
	//--------------------------------------------------------------------------
	public function exec_delete_with_model( &$table_model_ )
	{
		if( ! $table_model_ ) return false;
		$design = isset($this->table_design[$table_model_->get_table_name()]) ?
			$this->table_design[$table_model_->get_table_name()] : false;
		if( $design===false ) return false;
		if( $design->primary_key===false ) return false;

		$where = '';
		$pkey_value = false;
		if( is_array($design->primary_key) )
		{
			foreach( $design->primary_key as $pkey )
			{
				if( $where != '') $where .= " and ";
				$where .= "[".$pkey."]='".$this->encode($table_model_->{$pkey})."'";
			}
		}
		else
		{
			$pkey_value = $table_model_->{$design->primary_key};
			$where = "[".$design->primary_key."]='".$this->encode($pkey_value)."'";
		}

		$query = sprintf("delete from [%s] where %s",
			$design->name,
			$where
		);
		if( $this->query($query) === false ) return false;

		//	必要ならリレーション削除、但し複合プライマリキーは非対応
		if( count($design->referrers) > 0 && is_array($design->primary_key) == false )
		{
			if( $this->delete_recursive($design, $pkey_value) === false )
				return false;
		}
		return true;
	}
	private function delete_recursive( &$table_design_, $pkey_val_ )
	{
		foreach( $table_design_->referrers as $from_tname => $from_fields )
		{
			//	削除条件作成
			$wheres = [];
			foreach( $from_fields as $fdef )
			{
				$wheres[] = is_array($pkey_val_) ?
					('['.$fdef[0]."] in ('".implode("','", $pkey_val_)."')") :
					('['.$fdef[0]."]='".$this->encode($pkey_val_)."'")
					;
			}

			//	参照元のテーブルにも、参照するテーブルがある場合、再帰処理
			//	ただし、ゼロ埋めの場合には処理なし。
			$from_design = $this->table_designs[$from_tname];
			if( $from_design->referrers !== false && count($from_design->referrers) > 0 )
			{
				//	削除予定のキー一覧取得 
				$query = sprintf("select [%s] from [%s] where %s",
					$from_design->primary_key, $from_tname, implode(" or ", $wheres));
				$rows = $this->select($query);
				$ref_keys = array_column($rows, $from_design->primary_key);

				//	参照元をさらに参照するテーブルたちへ再帰
				if( count($ref_keys) <= 0 )
				{
				}
				else if( count($ref_keys) == 1 )
				{
					if( $this->delete_recursive($from_design, $ref_keys[0]) === false )
						return false;
				}
				else
				{
					if( $this->delete_recursive($from_design, $ref_keys) === false )
						return false;
				}
			}

			//	完全削除
			if( $this->table_designs[$from_tname]->deleted === false )
			{
				$query = sprintf("delete from [%s] where %s",
					$from_tname,
					implode(" or ", $wheres)
				);
				if( $this->query($query) === false ) return false;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 指定したフィールド仕様の内容で
	//	create table や alter table 用のフィールド部分の構文を作成する
	//--------------------------------------------------------------------------
	public function sql_field_syntax_with_design( &$table_design_, &$field_design_ )
	{
		$syntax = "";
		switch( $field_design_->type )
		{
			case 'tinyint':
				$syntax = sprintf("[%s] tinyint not null", $field_design_->name);
				break;

			case 'utinyint':
				$syntax = sprintf("[%s] tinyint not null", $field_design_->name);
				break;

			case 'int':
				$syntax = sprintf("[%s] int not null", $field_design_->name);
				break;

			case 'uint':
				$syntax = sprintf("[%s] int not null", $field_design_->name);
				break;

			case 'bigint':
				$syntax = sprintf("[%s] bigint not null", $field_design_->name);
				break;

			case 'ubigint':
				$syntax = sprintf("[%s]  bigint not null", $field_design_->name);
				break;

			case 'varchar':
				if( $field_design_->size > 4000 )
				{
					$syntax = sprintf("[%s] nvarchar(max) collate Japanese_CI_AS not null",
						$field_design_->name);
				}
				else
				{
					$syntax = sprintf("[%s] nvarchar(%d) collate Japanese_CI_AS not null",
						$field_design_->name, $field_design_->size);
				}
				break;

			case 'text':
				$syntax = sprintf("[%s] ntext collate Japanese_CI_AS not null",
					$field_design_->name);
				break;

			case 'bigtext':
				$syntax = sprintf("[%s] longtext collate Japanese_CI_AS not null",
					$field_design_->name);
				break;

			case 'varcrypt':
				$syntax = sprintf("[%s] tinyblob not null", $field_design_->name);
				break;

			case 'crypt':
				$syntax = sprintf("[%s] blob not null", $field_design_->name);
				break;

			case 'bigcrypt':
				$syntax = sprintf("[%s] longblob not null", $field_design_->name);
				break;

			case 'mailcrypt':
				$syntax = sprintf("[%s] tinyblob not null", $field_design_->name);
				break;

			case 'password':	//	password_hash()の結果として255確保の必要あり。
				$syntax = sprintf("[%s] varchar(255) not null", $field_design_->name);
				break;

			case 'unixtime':
				$syntax = sprintf("[%s] bigint not null", $field_design_->name);
				break;

			case 'datetime':
				$syntax = sprintf("[%s] datetime not null default current_timestamp", $field_design_->name);
				break;

			case 'boolean':
				$syntax = sprintf("[%s] tinyint not null", $field_design_->name);
				break;

			case 'url':
				$syntax = sprintf("[%s] ntext collate Japanese_CI_AS not null",
					$field_design_->name);
				break;

			case 'mail':
				$syntax = sprintf("[%s] nvarchar(255) collate Japanese_CI_AS not null",
					$field_design_->name);
				break;

			case 'telno':	//	ITU勧告により世界標準最大15桁だが、ハイフンなどを加味して20としておく。
				$syntax = sprintf("[%s] varchar(20) collate Japanese_CI_AS not null",
					$field_design_->name);
				break;

			case 'geometry':
				$syntax = sprintf("[%s] geometry not null", $field_design_->name);
				break;

			case 'float':
			case 'double':
				$syntax = sprintf("[%s] float not null", $field_design_->name);
				break;

			default:
				if( substr($field_design_->type,0,1)=='[' &&
					substr($field_design_->type,-1)==']'
				){
					$syntax = sprintf(
						"[%s] %s not null",
						$field_design_->name,
						substr($field_design_->type,1,strlen($field_design_->type)-2)
					);
				}
				else
				{
					$syntax = sprintf("[%s] %s not null", $field_design_->name, $field_design_->type);
				}
		}

		//	options
		if( $field_design_->primary_key )
		{
			//	複合キー指定がある場合には、ここで指定しない
			if( ! is_array($table_design_->primary_key) )
				$syntax .= sprintf(" CONSTRAINT [%s] primary key clustered( %s asc )", $field_design_->name, $field_design_->name);
		}
		if( $field_design_->auto_increment ){
			$syntax .= " identity";
		}
		return $syntax;
	}

	//--------------------------------------------------------------------------
	//	override : プライマリキーを作成する構文取得
	//--------------------------------------------------------------------------
	public function sql_create_primary_key( &$table_design_, &$field_design_ )
	{
		return sprintf( "alter table [%s] add primary key ([%s])",
			$table_design_->name, $field_design_->name );
	}

	//--------------------------------------------------------------------------
	//	override : プライマリキーを削除する構文取得
	//--------------------------------------------------------------------------
	public function sql_delete_primary_key( &$table_design_ )
	{
		return sprintf( "alter table [%s] drop constraint [%s]",
			$table_design_->name, $table_design_->primary_key );
	}

	//--------------------------------------------------------------------------
	//	override : インデックスを作成する構文取得、インデックスの名前を指定する
	//--------------------------------------------------------------------------
	public function sql_create_index_syntax_with_design( &$table_design_, $index_name_ )
	{
		if( isset($table_design_->indexes[$index_name_]) )
		{
			return sprintf
			(
				"create index %s on [%s] ([%s])",
				$index_name_, $table_design_->name,
				implode("],[",$table_design_->indexes[$index_name_])
			);
		}
		else if( isset($table_design_->indexes_with_unique[$index_name_]) )
		{
			return sprintf
			(
				"create unique index %s on [%s] ([%s])",
				$index_name_, $table_design_->name,
				implode("],[",$table_design_->indexes_with_unique[$index_name_])
			);
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//	override : インデックスを削除する構文取得、インデックスの名前を指定する
	//--------------------------------------------------------------------------
	public function sql_drop_index_syntax_with_design( &$table_design_, $index_name_ )
	{
		if( isset($table_design_->indexes[$index_name_]) ||
			isset($table_design_->indexes_with_unique[$index_name_])
		){
			return sprintf("drop index %s on [%s]",
				$index_name_, $table_design_->name);
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//	override : 指定したテーブル仕様の内容で create クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_create_table_with_design( &$table_design_ )
	{
		//	create table
		$cols = [];
		foreach( $table_design_->fields as $field )
		{
			$field_syntax = $this->sql_field_syntax_with_design($table_design_, $field);
			$cols[] = $field_syntax;
		}

		//	複合キーの指定
		if( $table_design_->primary_key !== false )
		{
			if( is_array($table_design_->primary_key) )
			{
				$cols[] = "primary key(["
					.implode("],[", $table_design_->primary_key)
					."])"
					;
			}
		}

		$query = sprintf("create table [%s] (%s)",
			$table_design_->name, implode(",", $cols) );
		if( ! $this->query($query) ) return false;

		//	index
		foreach( $table_design_->indexes as $index_name => $index_cols )
		{
			$syntax = $this->sql_create_index_syntax_with_design($table_design_, $index_name);
			if( ! $syntax ) continue;
			if( ! $this->query($syntax) ) return false;
		}
		foreach( $table_design_->indexes_with_unique as $index_name => $index_cols )
		{
			$syntax = $this->sql_create_index_syntax_with_design($table_design_, $index_name);
			if( ! $syntax ) continue;
			if( ! $this->query($syntax) ) return false;
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 指定したテーブル仕様の内容で drop クエリを発行する
	//--------------------------------------------------------------------------
	public function exec_drop_table_with_design( &$table_design_ )
	{
		$query = sprintf("drop table [%s]", $table_design_->name );
		if( ! $this->query($query) ) return false;
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : フィールドのサイズ定義を計算する
	//--------------------------------------------------------------------------
	public function calc_field_byte_size( $type_, $def_size_=-1 )
	{
		switch($type_)
		{
			case 'tinyint':
			case 'utinyint':
				return 1;
			case 'int':
				return 4;
			case 'uint':
				return 4;
			case 'bigint':
			case 'ubigint':
				return 8;
		}
		return $def_size_ > 0 ? $def_size_ : 1;
	}

	//--------------------------------------------------------------------------
	//	override : 現状のDBのエクスポート
	//--------------------------------------------------------------------------
	public function export()
	{
crow_log::notice("** incomaptible export");

/*
エクスポートは非対応

		$create_part = '';
		$insert_part = '';

		$tables = $this->get_tables();
		$indexes = $this->get_indexes();

		foreach( $tables as $table )
		{
			$rset = $this->query("show create table [".$table."]");
			if( ! $rset ) return false;
			$row = $rset->get_row();

			$create_part .= "drop table if exists [".$table."] cascade;\n";
			$create_part .= $row['Create Table'].";\n";

			if( isset($indexes["indexes"][$table]) )
			{
				foreach( $indexes["indexes"][$table] as $index_name => $index_cols )
				{
					$create_part .= sprintf("create index if not exists %s on [%s] ([%s]);\n",
						$index_name, $table, implode("],[",$index_cols));
				}
			}
			if( isset($indexes["uniques"][$table]) )
			{
				foreach( $indexes["uniques"][$table] as $index_name => $index_cols )
				{
					$create_part .= sprintf("create unique index if not exists %s on [%s] ([%s]);\n",
						$index_name, $table, implode("],[",$index_cols));
				}
			}

			$rset = $this->query("select * from [".$table."]");
			if( $rset->num_rows() <= 0 ) continue;

			$insert_part .= "insert into [".$table."]\n";
			$collected_cols = false;
			$first_row = true;
			while( $arr = $rset->get_row() )
			{
				if( $first_row===false ) $insert_part .= ",\n";
				if( $collected_cols===false )
				{
					$insert_part .= "([".implode("],[", array_keys($arr))."]) values\n";
					$collected_cols = true;
				}
				$insert_part .= "(";
				$first_col = true;
				foreach( $arr as $val )
				{
					if( $first_col===false ) $insert_part .= ",";
					$insert_part .= "'".$this->encode($val)."'";
					$first_col = false;
				}
				$insert_part .= ")";
				$first_row = false;
			}
			$insert_part .= ";\n";
		}
		return $create_part.$insert_part;
*/
	}

	//--------------------------------------------------------------------------
	//	プライベートメンバ
	//--------------------------------------------------------------------------
	private $m_hdb = false;
	private $m_addr;
	private $m_name;
	private $m_user;
	private $m_output_log = false;
	private $m_output_log_back = false;
	private $m_raws = false;
}
?>
