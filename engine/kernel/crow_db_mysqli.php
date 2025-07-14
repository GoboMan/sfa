<?php
/*

	DBインタフェース : mysqli

*/
class crow_db_mysqli extends crow_db
{
	//--------------------------------------------------------------------------
	//	生成、破棄
	//--------------------------------------------------------------------------
	public function __construct( $server_type_ = "writer" )
	{
		$autoconn = crow_config::get_if_exists('db.autoconn.'.$server_type_,
			crow_config::get_if_exists('db.autoconn', false));
		if( $autoconn === false ) return crow_log::error("not specified 'db.autoconn'");
		if( $autoconn == "true" ) $this->connect($server_type_);
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
		$this->m_output_log = crow_config::get_if_exists("log.sql", "false")=="true" ? true : false;

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
		if( $this->m_hdb !== false ) $this->disconnect();

		//	接続パラメータ取得
		$addr = crow_config::get_if_exists('db.address.'.$server_type_, crow_config::get_if_exists('db.address', ''));
		$name = crow_config::get_if_exists('db.name.'.$server_type_, crow_config::get_if_exists('db.name', ''));
		$user = crow_config::get_if_exists('db.userid.'.$server_type_, crow_config::get_if_exists('db.userid', ''));
		$pass = crow_config::get_if_exists('db.password.'.$server_type_, crow_config::get_if_exists('db.password', ''));
		if( strlen($addr)<=0 || strlen($name)<=0 || strlen($user)<=0 )
		{
			crow_log::write( sprintf(
				"illegal database setting, addr[%s] name[%s] user[%s]",
				$addr, $name, $user
			), "system" );
			exit;
		}

		//	接続
		$this->m_hdb = mysqli_connect( $addr, $user, $pass );
		if( ! $this->m_hdb )
		{
			crow_log::write( sprintf(
				"failed in the connection to database, addr[%s] name[%s] user[%s] errcode[%s], %s",
				$addr, $name, $user, mysqli_connect_errno(), mysqli_connect_error()
			), "system" );
			exit;
		}

		//	DBセレクト
		if( ! mysqli_select_db($this->m_hdb, $name) )
		{
			crow_log::write( sprintf(
				"failed in the select database, addr[%s] name[%s] user[%s] errcode[%s], %s",
				$addr, $name, $user, mysqli_errno($this->m_hdb), mysqli_error($this->m_hdb)
			), "system" );

			$this->disconnect();
			exit;
		}

		//	文字セット設定
		mysqli_set_charset($this->m_hdb, crow_config::get("db.charset"));

		mysqli_report(MYSQLI_REPORT_OFF);

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
		$org_addr	= crow_config::get_if_exists('db.address');
		$org_name	= crow_config::get_if_exists('db.name');
		$org_user	= crow_config::get_if_exists('db.userid');
		$org_pass	= crow_config::get_if_exists('db.password');
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
		mysqli_close( $this->m_hdb );
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
		return mysqli_insert_id($this->m_hdb);
	}

	//--------------------------------------------------------------------------
	//	override : シンボルをエスケープする
	//--------------------------------------------------------------------------
	public function escape_symbol( $symbol_ )
	{
		return "`".$symbol_."`";
	}

	//--------------------------------------------------------------------------
	//	override : トランザクション
	//--------------------------------------------------------------------------
	public function begin()
	{
		if( ! $this->m_hdb ) return;
		mysqli_query( $this->m_hdb, "start transaction" );
	}
	public function commit()
	{
		if( ! $this->m_hdb ) return;
		mysqli_query( $this->m_hdb, "commit" );
	}
	public function rollback()
	{
		if( ! $this->m_hdb ) return;
		mysqli_query( $this->m_hdb, "rollback" );
	}

	//--------------------------------------------------------------------------
	//	override : 全テーブルを取得する
	//--------------------------------------------------------------------------
	public function get_tables()
	{
		$names = [];
		if( ! $this->m_hdb ) return $names;

		$rset = $this->query("show tables from `".$this->m_name."`");
		$rows = $rset->get_rows();
		$names = [];
		foreach( $rows as $row )
		{
			$names[] = $row['Tables_in_'.strtolower($this->m_name)];
		}
		return $names;
	}

	//--------------------------------------------------------------------------
	//	override : テーブルの存在チェック
	//--------------------------------------------------------------------------
	public function exists_table( $name_ )
	{
		if( ! $this->m_hdb ) return;

		$names = $this->get_tables();
		foreach( $names as $name )
		{
			if( $name==$name_ ) return true;
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//	override : フィールド情報の一覧を取得
	//--------------------------------------------------------------------------
	public function get_fields( $table_name_ )
	{
		$rset = $this->query( "show columns from `".$table_name_."`" );
		if( ! $rset ) return [];

		$rows = $rset->get_rows();
		$ret = [];
		foreach( $rows as $row )
		{
			$match_res = preg_match("/^(.*)\((.*)\)/", $row['Type'], $matches);
			if( $match_res===false || $match_res < 0 ) continue;

			$type = count($matches) > 0 ? $matches[1] : $row['Type'];
			$len = count($matches) > 0 ? $matches[2] : 0;

			$unsigned = strpos($row['Type'], "unsigned") !== false;
			if( strlen($type) <= 0 )
			{
				$type = $row['Type'];
				$len = 0;
			}
			$ret[$row['Field']] =
			[
				'name'				=> $row['Field'],
				'type'				=> $type,
				'length'			=> $len,
				'unsigned'			=> $unsigned,
				'primary_key'		=> strtolower($row['Key'])=="pri" ? true : false,
				'auto_increment'	=> strtolower($row['Extra'])=="auto_increment" ? true : false,
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
			."select "
				."table_name,non_unique,index_name,column_name "
			."from "
				."information_schema.statistics "
			."where "
				."table_schema='".$this->m_name."' "
			."order by seq_in_index "
		);
		if( $rset === false ) return [];

		$rows = $rset->get_rows();
		$ret = ["indexes" => [], "uniques" => []];

		foreach( $rows as $row )
		{
			foreach( $row as $k => $v )
			{
				unset($row[$k]);
				$row[strtolower($k)] = $v;
			}

			if( strtolower($row['index_name']) != "primary" )
			{
				if( $row['non_unique'] == "1" )
				{
					$ret["indexes"][$row['table_name']][$row['index_name']][] = strtolower($row['column_name']);
				}
				else
				{
					$ret["uniques"][$row['table_name']][$row['index_name']][] = strtolower($row['column_name']);
				}
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
		$cryptkey = "";
		$cmd = strtolower($sql_->m_command);
		if( $cmd == 'select' )
		{
			$target = "";
			foreach( $sql_->m_targets as $t )
			{
				if( $target!="" ) $target .= ",";
				if( substr($t,0,1)=="#" )
				{
					$name = substr($t,1);
					if( $cryptkey=="" )
						$cryptkey = $this->encode(crow_config::get_if_exists('db.cryptkey', ''));

					$target .= "AES_DECRYPT(`".$name."`,'".$cryptkey."') as `".$name."`";
				}
				else if( substr($t,0,3)=="NC#" )
				{
					$target .= substr($t, 3);
				}
				else
				{
					if( strpos($t,"`")===false )
					{
						$pos = strpos($t, " ");
						if( $pos===false )
							$target .= "`".$t."`";
						else
							$target .= $t;
					}
					else $target .= $t;
				}
			}


			$sql .= sprintf("select %s from `%s`",
				count($sql_->m_targets)<=0 ? "*" : $target,
				$sql_->m_from
			);
			if( count($sql_->m_where) > 0 ) $sql .= " where ".$this->build_where($sql_->m_where);
			if( $sql_->m_groupby !== false )
			{
				$sql .= " group by `".$sql_->m_groupby."`";
				if( $sql_->m_groupby_collate !== false )
					$sql .= " collate ".$sql_->m_groupby_collate;
			}
			if( count($sql_->m_orderby) > 0 )
			{
				$order_queries = [];
				for( $oi=0; $oi<count($sql_->m_orderby); $oi++ )
				{
					$order_query = "`".$sql_->m_orderby[$oi]."`";
					if( isset($sql_->m_orderby_collate[$oi]) === true && $sql_->m_orderby_collate[$oi] !== false )
						$order_query .= " collate ".$sql_->m_orderby_collate[$oi];
					if( isset($sql_->m_orderby_vector[$oi]) === true )
						$order_query .= " ".$sql_->m_orderby_vector[$oi];
					$order_queries[] = $order_query;
				}
				$sql .= " order by ".implode(",", $order_queries);
			}
			if( $sql_->m_limit_offset !== false &&
				$sql_->m_limit_size !== false
			){
				$sql .= sprintf( " limit %d, %d",
					$sql_->m_limit_offset, $sql_->m_limit_size );
			}

			if( $sql_->m_for_update === true )
				$sql .= " for update";
		}
		else if( $cmd == 'insert' )
		{
			$values = [];
			if( $sql_->m_values_rawval !== false && count($sql_->m_values_rawval) > 0 )
			{
				foreach( $sql_->m_values_rawval as $index => $val )
					$values["`".$index."`"] = $this->encode($val);
			}
			if( $sql_->m_values !== false && count($sql_->m_values) > 0 )
			{
				foreach( $sql_->m_values as $index => $val )
					$values["`".$index."`"] = "'".$this->encode($val)."'";
			}
			$sql .= sprintf("insert into %s (%s) values (%s)",
				$sql_->m_from, implode(",",array_keys($values)), implode(",",$values) );
		}
		else if( $cmd == 'update' )
		{
			$sets = [];
			if( $sql_->m_values_rawval !== false && count($sql_->m_values_rawval) > 0 )
			{
				foreach( $sql_->m_values_rawval as $key => $val ){
					$sets[] = "`".$key."`=".$this->encode($val);
				}
			}
			if( $sql_->m_values !== false && count($sql_->m_values) > 0 )
			{
				foreach( $sql_->m_values as $key => $val ){
					$sets[] = "`".$key."`='".$this->encode($val)."'";
				}
			}
			$sql .= sprintf("update `%s` set %s", $sql_->m_from, implode(",",$sets));
			if( count($sql_->m_where) > 0 ) $sql .= " where ".$this->build_where($sql_->m_where);
		}
		else if( $cmd == 'delete' )
		{
			$sql .= "delete from `".$sql_->m_from."`";
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
			if( $item == "(" ) $sql .= "(";
			else if( $item == ")" ) $sql .= ")";
			else if( $item == "and" ) $sql .= " and ";
			else if( $item == "or" ) $sql .= " or ";
			else if( is_array($item) === true )
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
						$sql .= "`".$item[0].'` '.$item[1]." ".$item[2];
					else
						$sql .= "`".$item[0].'` '.$item[1]." '".$this->encode($item[2])."'";
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
		if( isset($this->m_raws[$name]) === false )
		{
			crow_log::error("not found raw query : ".$name);
			return "";
		}

		//	フォーマットして返却
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ ) $args[] = $this->encode(func_get_arg($i));
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
		if( isset($this->m_raws[$name]) === false )
		{
			crow_log::error("not found raw query : ".$name);
			return "";
		}

		//	フォーマットして返却
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ ) $args[] = func_get_arg($i);
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
				$dbtype = crow_config::get_if_exists('db.type', '');
				$dir = CROW_PATH."app/assets/query_".$dbtype."/_common_/";
				$paths = crow_storage::disk()->get_files($dir, "sql");
				foreach( $paths as $path ) $this->append_load_raw($path);
				//	app/assets/query_<dbtype>/<ROLE>/<ROLE>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query_".$dbtype."/".$role."/".$role.".sql");
				//	app/assets/query_<dbtype>/<ROLE>/<ROLE>_<MODULE>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query_".$dbtype."/".$role."/".$role."_".$module.".sql");
				//	app/assets/query_<dbtype>/<ROLE>/<ROLE>_<MODULE>_<ACTION>.txt
				$this->append_load_raw(CROW_PATH."app/assets/query_".$dbtype."/".$role."/".$role."_".$module."_".$action.".sql");

				//	拡張パスがあるなら追加読み込み
				$ext_dirs = crow::get_query_dirs();
				if( count($ext_dirs) > 0 )
				{
					foreach( $ext_dirs as $ext_dir )
					{
						$this->append_load_raw($ext_dir.$role.".sql");
						$this->append_load_raw($ext_dir.$role."_".$module.".sql");
						$this->append_load_raw($ext_dir.$role."_".$module."_".$action.".sql");
					}
				}

				//	キャッシュ更新
				crow_cache::save( $cache_name, $this->m_raws );
			}
		}
	}
	private function append_load_raw( $fname_ )
	{
		if( is_file($fname_) === false ) return false;
		if( is_readable($fname_) === false ) return false;

		//	読込み
		$lines = file( $fname_ );
		$query = "";
		$name = "";
		foreach( $lines as $line )
		{
			if( substr(trim($line), 0, 2) == "--" ) continue;
			if( substr(trim($line), 0, 1) == "@" )
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
		{
			//	認証がDBの場合に無限ループになりそうなので、SQLではログインIDを出力しない
			crow_log::log_with_name( 'sql', $sql_ );
		}

		//	実行
		if( ! ($ret = mysqli_query($this->m_hdb, $sql_)) )
		{
			$err_code	= mysqli_errno($this->m_hdb);
			$err_msg	= mysqli_error($this->m_hdb);
			crow_log::error( mb_str_replace("%","%%",$sql_)."\nerrcode[".$err_code."], ".$err_msg );

			if( strtolower(substr($sql_,0,6)) == "select" )
			{
				//	失敗時はバッチでないならデフォルトエラーに飛ばす
				if( crow::is_batch() === false )
				{
					crow::redirect_default_error();
				}
				return false;
			}

			if( strtolower(substr($sql_,0,7)) == "explain" ||
				strtolower(substr($sql_,0,4)) == "show"
			){
				$inst = new crow_db_result();
				$inst->result_set(false, $err_code, $err_msg);
				return $inst;
			}
			return false;
		}

		//	selectの場合は、結果セットを返却する
		if(
			strtolower(substr($sql_,0,6)) == "select" ||
			strtolower(substr($sql_,0,7)) == "explain" ||
			strtolower(substr($sql_,0,4)) == "show"
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
	//	override : sqlを指定して結果をCSVファイルへ出力する
	//--------------------------------------------------------------------------
	public function output_csv_from_sql
	(
		$fname_,
		$query_,
		$header_ = false,
		$line_custom_ = false,
		$select_span_ = 1000
	){
		$fp = fopen($fname_, "wb");
		if( $fp === false )
		{
			crow_log::notice("failed to create file : ".$fname_);
			return false;
		}

		//	必要ならヘッダ出力
		if( $header_ !== false && is_array($header_) )
		{
			$csv = crow_utility::array_to_csv([$header_]);
			fwrite($fp, $csv);
		}

		//	数件ずつ出力
		$offset = 0;
		while(1)
		{
			$limit = " limit ".intval($offset).", ".intval($select_span_);
			$rows = $this->select($query_.$limit);
			if( $rows === false )
			{
				crow_log::notice("output stopped because select query failed");
				break;
			}
			if( count($rows) <= 0 ) break;

			if( $line_custom_ !== false )
			{
				for( $i=0; $i<count($rows); $i++ )
					$line_custom_($rows[$i]);
			}
			$csv = crow_utility::array_to_csv($rows);
			fwrite($fp, $csv);
			$offset += $select_span_;
		}
		fclose($fp);

		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 最後のクエリエラー情報を取得
	//--------------------------------------------------------------------------
	public function get_last_error_code()
	{
		return mysqli_errno($this->m_hdb);
	}
	public function get_last_error_msg()
	{
		return mysqli_error($this->m_hdb);
	}
	public function get_last_sqlstate()
	{
		return mysqli_sqlstate($this->m_hdb);
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から行数取得
	//--------------------------------------------------------------------------
	public function num_rows( $result_ )
	{
		if( ! $this->m_hdb ) return 0;
		if( ! $result_ ) return 0;
		return mysqli_num_rows($result_);
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から列数取得
	//--------------------------------------------------------------------------
	public function num_cols( $result_ )
	{
		if( ! $this->m_hdb ) return 0;
		if( ! $result_ ) return 0;
		return mysqli_num_fields($result_);
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
		return $this->decode_row(mysqli_fetch_array($result_, MYSQLI_ASSOC), $html_escape_);
	}

	//--------------------------------------------------------------------------
	//	override : クエリ結果から全行取得（第二引数でHTMLタグを有効にするか指定）
	//--------------------------------------------------------------------------
	public function get_rows( $result_, $html_escape_=false )
	{
		if( ! $this->m_hdb ) return false;
		if( ! $result_ ) return false;

		$ret = [];
		if( mysqli_num_rows($result_) > 0 ) mysqli_data_seek($result_, 0);
		while( $arr = mysqli_fetch_array($result_, MYSQLI_ASSOC) ) $ret[] = $arr;
		return $this->decode_rows($ret, $html_escape_);
	}

	//--------------------------------------------------------------------------
	//	override : 文字列をDBエンコードする
	//--------------------------------------------------------------------------
	public function encode( $value_ )
	{
		return addslashes($value_);
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
		foreach( $row_ as $key => $val )
			$ret[$key] = $this->decode( $val, $html_escape_ );

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
		if( $design === false ) return false;
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
			$keys[] = "`".$field->name."`";


			//	nullableの処理
			if( $field->nullable === true && $table_model_->{$field->name} === null )
			{
				$vals[] = "null";
				continue;
			}


			if( $field->type == "tinycrypt" || $field->type == "crypt" || $field->type == "bigcrypt" || $field->type == "varcrypt" || $field->type == "mailcrypt" )
			{
				if( $cryptkey=="" ) $cryptkey = $this->encode(crow_config::get_if_exists('db.cryptkey', ''));
				$vals[] = "AES_ENCRYPT('".$this->encode($table_model_->{$field->name})."', '".$cryptkey."')";
			}
			else if( $field->type == "password" )
			{
				$vals[] = "'".$this->encode(password_hash($table_model_->{$field->name}, PASSWORD_DEFAULT))."'";
			}
			else if( $field->type == "datetime" )
			{
				$vals[] = "'".date('Y-m-d H:i:s', intval($table_model_->{$field->name}))."'";
			}
			else if( $field->type == "boolean" )
			{
				$vals[] = $table_model_->{$field->name} ? "'1'" : "'0'";
			}
			else if( $field->type == "tinyint" || $field->type == "utinyint" || $field->type == "int" || $field->type == "uint" || $field->type == "bigint" || $field->type == "ubigint")
			{
				$vals[] = "'".intval($table_model_->{$field->name})."'";
			}
			else if( $field->type == "float" )
			{
				$vals[] = "'".floatval($table_model_->{$field->name})."'";
			}
			else if( $field->type == "double" )
			{
				$vals[] = "'".doubleval($table_model_->{$field->name})."'";
			}
			else if( $field->type == "geometry" )
			{
				//	point()は、point(経度 緯度)なので注意（緯度経度ではない）
				$geodata = $table_model_->{$field->name};
				$geodata_0 = isset($geodata[0]) === true ? doubleval($geodata[0]) : 0;
				$geodata_1 = isset($geodata[1]) === true ? doubleval($geodata[1]) : 0;
				$vals[] = "ST_GeomFromText('point(".$geodata_1." ".$geodata_0.")', 4326)";
			}
			else if( $field->type == "bit" )
			{
				$vals[] = "b'".decbin(intval($table_model_->{$field->name}))."'";
			}
			else
			{
				$vals[] = "'".$this->encode($table_model_->{$field->name})."'";
			}
		}
		$query = sprintf("insert into `%s` (%s) values (%s)",
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
		if( $design === false ) return false;
		if( $design->primary_key === false ) return false;

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

			//	nullableの処理
			if( $field->nullable === true && $table_model_->{$field->name} === null )
			{
				$sets[] = "`".$field->name."`=null";
				continue;
			}

			if( $field->type == "tinycrypt" || $field->type == "crypt" || $field->type == "bigcrypt" || $field->type == "varcrypt" || $field->type == "mailcrypt")
			{
				if( $cryptkey=="" ) $cryptkey = $this->encode(crow_config::get_if_exists('db.cryptkey', ''));
				$sets[] = "`".$field->name."`=AES_ENCRYPT('".$this->encode($table_model_->{$field->name})."', '".$cryptkey."')";
			}
			else if( $field->type == "password" )
			{
				//	パスワードは指定がある時のみ更新する
				if( strlen($table_model_->{$field->name}) > 0 )
				{
					$sets[] = "`".$field->name."`='".$this->encode(password_hash($table_model_->{$field->name}, PASSWORD_DEFAULT))."'";
				}
			}
			else if( $field->type == "datetime" )
			{
				$sets[] = "`".$field->name."`='".date('Y-m-d H:i:s', intval($table_model_->{$field->name}))."'";
			}
			else if( $field->type == "boolean" )
			{
				$sets[] = "`".$field->name."`=".($table_model_->{$field->name} ? "'1'" : "'0'");
			}
			else if( $field->type == "tinyint" || $field->type == "utinyint" || $field->type == "int" || $field->type == "uint" || $field->type == "bigint" || $field->type == "ubigint")
			{
				$sets[] = "`".$field->name."`='".intval($table_model_->{$field->name})."'";
			}
			else if( $field->type=="float" )
			{
				$sets[] = "`".$field->name."`=".floatval($table_model_->{$field->name});
			}
			else if( $field->type=="double" )
			{
				$sets[] = "`".$field->name."`=".doubleval($table_model_->{$field->name});
			}
			else if( $field->type=="geometry" )
			{
				//	point()は、point(経度 緯度)なので注意（緯度経度ではない）
				$geodata = $table_model_->{$field->name};
				$geodata_0 = isset($geodata[0]) === true ? doubleval($geodata[0]) : 0;
				$geodata_1 = isset($geodata[1]) === true ? doubleval($geodata[1]) : 0;
				$sets[] = "`".$field->name."`="."ST_GeomFromText('point(".$geodata_1." ".$geodata_0.")', 4326)";
			}
			else if( $field->type == "bit" )
			{
				$sets[] = "`".$field->name."`=b'".decbin(intval($table_model_->{$field->name}))."'";
			}
			else{
				$sets[] = "`".$field->name."`='".$this->encode($table_model_->{$field->name})."'";
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
			$where .= "`".$pk_name."`='".$this->encode($table_model_->{$pk_name})."'";
		}

		$query = sprintf("update `%s` set %s where %s",
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
		if( $design === false ) return false;
		if( $design->primary_key === false ) return false;
		if( $design->deleted === false ) return $this->exec_delete_with_model($table_model_);

		$where = '';
		$pkey_value = false;
		if( is_array($design->primary_key) )
		{
			foreach( $design->primary_key as $pkey )
			{
				if( $where != '' ) $where .= " and ";
				$where .= "`".$pkey."`='".$this->encode($table_model_->{$pkey})."'";
			}
		}
		else
		{
			$pkey_value = $table_model_->{$design->primary_key};
			$where = "`".$design->primary_key."`='".$this->encode($pkey_value)."'";
		}

		$query = sprintf
		(
			"update `%s` set `%s`=1 where %s",
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
					("`".$fdef[0]."` in ('".implode("','", $pkey_val_)."')") :
					("`".$fdef[0]."`='".$this->encode($pkey_val_)."'")
					;
				if( $fdef[1] == "trash" ) $exists_trash_field = true;
			}

			//	参照元のテーブルにも、参照するテーブルがある場合、再帰処理
			//	ただし、ゼロ埋めの場合には処理なし。
			if( $exists_trash_field == true )
			{
				$from_design = $this->table_designs[$from_tname];
				if( count($from_design->referrers) > 0 )
				{
					//	削除予定のキー一覧取得 
					$query = sprintf("select `%s` from `%s` where %s",
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
					$query = sprintf
					(
						"delete from `%s` where %s",
						$from_tname,
						implode(" or ", $wheres)
					);
					if( $this->query($query) === false ) return false;
				}
				//	削除フラグがあるなら論理削除
				else
				{
					$query = sprintf
					(
						"update `%s` set `%s`=1 where %s",
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
					$query = sprintf
					(
						"update `%s` set `%s`=0 where %s",
						$from_tname,
						$fdef[0],
						implode(" or ", $wheres)
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
		$design = isset($this->table_designs[$table_model_->get_table_name()]) ?
			$this->table_designs[$table_model_->get_table_name()] : false;
		if( $design === false ) return false;
		if( $design->primary_key === false ) return false;

		$where = '';
		$pkey_value = false;
		if( is_array($design->primary_key) )
		{
			foreach( $design->primary_key as $pkey )
			{
				if( $where != '') $where .= " and ";
				$where .= "`".$pkey."`='".$this->encode($table_model_->{$pkey})."'";
			}
		}
		else
		{
			$pkey_value = $table_model_->{$design->primary_key};
			$where = "`".$design->primary_key."`='".$this->encode($pkey_value)."'";
		}

		$query = sprintf
		(
			"delete from `%s` where %s",
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
					("`".$fdef[0]."` in ('".implode("','", $pkey_val_)."')") :
					("`".$fdef[0]."`='".$this->encode($pkey_val_)."'")
					;
			}

			//	参照元のテーブルにも、参照するテーブルがある場合、再帰処理
			//	ただし、ゼロ埋めの場合には処理なし。
			$from_design = $this->table_designs[$from_tname];
			if( $from_design->referrers !== false && count($from_design->referrers) > 0 )
			{
				//	削除予定のキー一覧取得 
				$query = sprintf("select `%s` from `%s` where %s",
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
				$query = sprintf("delete from `%s` where %s",
					$from_tname,
					implode(" or ", $wheres)
				);
				if( $this->query($query) === false ) return false;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : デコード項の作成
	//--------------------------------------------------------------------------
	public function sql_field_decrypt( $field_name_ )
	{
		$cryptkey = $this->encode(crow_config::get_if_exists('db.cryptkey', ''));
		return "AES_DECRYPT(`".$field_name_."`,'".$cryptkey."')";
	}

	//--------------------------------------------------------------------------
	//	override : 指定したフィールド仕様の内容で
	//	create table や alter table 用のフィールド部分の構文を作成する
	//--------------------------------------------------------------------------
	public function sql_field_syntax_with_design( &$table_design_, &$field_design_ )
	{
		$syntax = "";
		$comment = $this->encode($field_design_->logical_name);
		$nullable = $field_design_->nullable === true ? 'null' : 'not null';
		switch( $field_design_->type )
		{
			case 'tinyint':

				if( $field_design_->size == 1 )
					$syntax = sprintf("`%s` tinyint(1) %s comment '%s'",
						$field_design_->name, $nullable, $comment);
				else
					$syntax = sprintf("`%s` tinyint %s comment '%s'",
						$field_design_->name, $nullable, $comment);
				break;

			case 'utinyint':
				$syntax = sprintf("`%s` tinyint unsigned %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'int':
				$syntax = sprintf("`%s` int %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'uint':
				$syntax = sprintf("`%s` int unsigned %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'bigint':
				$syntax = sprintf("`%s` bigint %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'ubigint':
				$syntax = sprintf("`%s` bigint unsigned %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'varchar':
				$syntax = sprintf("`%s` varchar(%d) character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate")." %s comment '%s'",
					$field_design_->name, $field_design_->size, $nullable, $comment);
				break;

			case 'text':
				$syntax = sprintf("`%s` text character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate")." %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'bigtext':
				$syntax = sprintf("`%s` longtext character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate")." %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'tinycrypt':
				$syntax = sprintf("`%s` tinyblob %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'crypt':
				$syntax = sprintf("`%s` blob %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'bigcrypt':
				$syntax = sprintf("`%s` longblob %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'varcrypt':
				$syntax = sprintf("`%s` tinyblob %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'mailcrypt':
				$syntax = sprintf("`%s` tinyblob %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'password':	//	password_hash()の結果として255確保の必要あり。
				$syntax = sprintf("`%s` varchar(255) %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'unixtime':
				$syntax = sprintf("`%s` bigint(20) unsigned %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'datetime':
				if( $field_design_->nullable === true )
				{
					$syntax = sprintf("`%s` datetime %s comment '%s'", $field_design_->name, $nullable, $comment);
				}
				else
				{
					$syntax = sprintf("`%s` datetime %s default current_timestamp comment '%s'", $field_design_->name, $nullable, $comment);
				}
				break;

			case 'boolean':
				$syntax = sprintf("`%s` tinyint(1) %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'url':
				$syntax = sprintf("`%s` text character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate")." %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'mail':
				$syntax = sprintf("`%s` varchar(255) character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate")." %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'telno':	//	ITU勧告により世界標準最大15桁だが、ハイフンなどを加味して20としておく。
				$syntax = sprintf("`%s` varchar(20) character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate")." %s comment '%s'",
					$field_design_->name, $nullable, $comment);
				break;

			case 'geometry':

				//	mysl5ではsrid指定ができない
				$major = intval($this->get_version() / 10000);
				if( $major <= 5 )
				{
					$syntax = sprintf("`%s` geometry null comment '%s'",
						$field_design_->name, $comment);
				}
				else
				{
					$syntax = sprintf("`%s` geometry srid 4326 null comment '%s'",
						$field_design_->name, $comment);
				}
				break;

			case 'float':
				$syntax = sprintf("`%s` float %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'double':
				$syntax = sprintf("`%s` double %s comment '%s'", $field_design_->name, $nullable, $comment);
				break;

			case 'bit':
				$syntax = sprintf("`%s` bit(%d) %s comment '%s'",
					$field_design_->name, $field_design_->size, $nullable, $comment);
				break;

			default:
				if( substr($field_design_->type,0,1)=='[' &&
					substr($field_design_->type,-1)==']'
				){
					$syntax = sprintf(
						"`%s` %s %s comment '%s'",
						$field_design_->name,
						substr($field_design_->type,1,strlen($field_design_->type)-2),
						$nullable,
						$comment
					);
				}
				else
				{
					$syntax = sprintf("`%s` %s %s comment '%s'",
						$field_design_->name, $field_design_->type, $nullable, $comment);
				}
		}

		//	options
		if( $field_design_->primary_key )
		{
			//	複合キー指定がある場合には、ここで指定しない
			if( ! is_array($table_design_->primary_key) )
				$syntax .= " primary key";
		}
		if( $field_design_->auto_increment ){
			$syntax .= " auto_increment";
		}
		return $syntax;
	}

	//--------------------------------------------------------------------------
	//	override : プライマリキーを作成する構文取得
	//--------------------------------------------------------------------------
	public function sql_create_primary_key( &$table_design_, &$field_design_ )
	{
		return sprintf( "alter table `%s` on add primary key (`%s`)",
			$table_design_->name, $field_design_->name );
	}

	//--------------------------------------------------------------------------
	//	override : プライマリキーを削除する構文取得
	//--------------------------------------------------------------------------
	public function sql_delete_primary_key( &$table_design_ )
	{
		return sprintf( "alter table `%s` drop primary key", $table_design_->name );
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
				"create index %s on `%s` (`%s`)",
				$table_design_->name."_".$index_name_, $table_design_->name,
				implode("`,`",$table_design_->indexes[$index_name_])
			);
		}
		else if( isset($table_design_->indexes_with_unique[$index_name_]) )
		{
			return sprintf
			(
				"create unique index %s on `%s` (`%s`)",
				$table_design_->name."_".$index_name_, $table_design_->name,
				implode("`,`",$table_design_->indexes_with_unique[$index_name_])
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
			return sprintf("alter table `%s` drop index %s",
				$table_design_->name,
				$table_design_->name."_".$index_name_);
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
				$cols[] = "primary key(`"
					.implode("`,`", $table_design_->primary_key)
					."`)"
					;
			}
		}

		$query = sprintf("create table `%s` (%s) engine=InnoDB comment='%s'",
			$table_design_->name, implode(",", $cols), $this->encode($table_design_->logical_name));
		if( ! $this->query($query) ) return false;

		//	utf8 or utf8mb4
		$query = sprintf("alter table `%s` default character set ".crow_config::get("db.charset")." collate ".crow_config::get("db.collate"),
			$table_design_->name );
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
		$query = sprintf("drop table if exists `%s`", $table_design_->name );
		if( ! $this->query($query) ) return false;
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : 現状のDBのエクスポート
	//--------------------------------------------------------------------------
	public function export()
	{
		$create_part = '';
		$insert_part = '';

		$tables = $this->get_tables();
		$indexes = $this->get_indexes();

		foreach( $tables as $table )
		{
			$fields = $this->get_fields($table);
			$rset = $this->query("show create table `".$table."`");
			if( ! $rset ) return false;
			$row = $rset->get_row();

			$create_part .= "drop table if exists `".$table."` cascade;\n";
			$create_part .= $row['Create Table'].";\n";

			//	MySQLで if not exists が使えなくなっている。
			//	create table構文内でインデックス作成しているので、ここは不要
			/*
			if( isset($indexes["indexes"][$table]) )
			{
				foreach( $indexes["indexes"][$table] as $index_name => $index_cols )
				{
					$create_part .= sprintf("create index if not exists %s on `%s` (`%s`);\n",
						$index_name, $table, implode("`,`",$index_cols));
				}
			}
			*/
			if( isset($indexes["uniques"][$table]) )
			{
				foreach( $indexes["uniques"][$table] as $index_name => $index_cols )
				{
					$create_part .= sprintf("create unique index if not exists %s on `%s` (`%s`);\n",
						$index_name, $table, implode("`,`",$index_cols));
				}
			}

			$rset = $this->query("select * from `".$table."`");
			if( $rset->num_rows() <= 0 ) continue;

			$insert_part .= "insert into `".$table."`\n";
			$collected_cols = false;
			$first_row = true;
			while( $arr = $rset->get_row() )
			{
				if( $first_row === false ) $insert_part .= ",\n";
				if( $collected_cols === false )
				{
					$insert_part .= "(`".implode("`,`", array_keys($arr))."`) values\n";
					$collected_cols = true;
				}
				$insert_part .= "(";
				$first_col = true;
				foreach( $arr as $filed_name => $val )
				{
					if( $first_col === false ) $insert_part .= ",";

					$field_type = isset($fields[$filed_name]['type']) === true ? $fields[$filed_name]['type'] : '';
					if( $field_type == "tinyblob" || $field_type == "blob" || $field_type == "longblob" )
					{
						if( strlen($val) <= 0 ) $insert_part .= "null";
						else $insert_part .= "0x".bin2hex($val);
					}
					else
					{
						$insert_part .= "'".$this->encode($val)."'";
					}
					$first_col = false;
				}
				$insert_part .= ")";
				$first_row = false;
			}
			$insert_part .= ";\n";
		}
		return $create_part.$insert_part;
	}

	//--------------------------------------------------------------------------
	//	DBバージョン取得
	//--------------------------------------------------------------------------
	public function get_version()
	{
		if( $this->m_hdb === false ) return false;
		if( $this->m_version === false ) $this->m_version = mysqli_get_server_version($this->m_hdb);
		return $this->m_version;
	}

	//--------------------------------------------------------------------------
	//	プライベートメンバ
	//--------------------------------------------------------------------------
	private $m_hdb = false;
	private $m_version = false;
	private $m_addr;
	private $m_name;
	private $m_user;
	private $m_output_log = false;
	private $m_output_log_back = false;
	private $m_raws = false;
}
?>
