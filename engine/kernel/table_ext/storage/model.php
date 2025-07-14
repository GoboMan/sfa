/*
	db_designのテーブルオプションに、"storage"が付与されている場合に、
	crow_db.php により追加で読み込まれるコード
	db_designで、

		node, storage()
		{
			...カラム...
		}

	とすると追加される。
	上記例では各種オプションが指定されていないため、
	各ロールのベースクラスなどで、最初に設定を行う。

	model_node::set_opt_src("s3");						//	データソース (s3, disk)
	model_node::set_opt_path("/path/to/");				//	パス、"/" 終端とすること
	model_node::set_opt_limit(100000);					//	許容ファイルサイズ
	model_node::set_opt_exts(["png", "gif", "jpg"]);	//	許容拡張子、"*"で全て受付
	model_node::set_opt_split_dir(true, 1000);			//	ディレクトリ分割

	それらをdb_designで一括で指定する場合には、

		; s3の場合
		node, storage(s3, /path/to/, 100000, png|jpg|gif)
		{
			...カラム...
		}

		; diskの場合（"[CROW_PATH]"の部分は置換される）
		node, storage(disk, [CROW_PATH]output/archive/, 100000, png|jpg|gif)
		{
			...カラム...
		}

	とすればよい。
	ディレクトリ分割に関するオプションについてはdb_designによる指定はできず、
	手動で指定する必要がある（デフォルトはOFF）

	※
	S3利用時には aws sdk が必要になる。
	インストール方法は crow_storage_s3 のコメントを参照
*/

//	db_designのオプションで指定した駆動条件
//	オプションを省略した場合や、自分で指定する場合には set_opt_xxx() で指定する
private static $m_opt_src = false;
private static $m_opt_bucket = false;
private static $m_opt_target = false;
private static $m_opt_path = false;
private static $m_opt_limit = false;
private static $m_opt_exts = false;

//	下記オプションは、db_designで指定できないので必要なら手動で指定する。
private static $m_opt_split_dir = false;
private static $m_opt_split_cnt = 1000;

//--------------------------------------------------------------------------
//	ファイルキー指定でインスタンスを作成
//--------------------------------------------------------------------------
public static function create_from_file_key( $key_ )
{
	return self::create_from_sql(
		self::sql_select_all()->and_where('file_key', $key_)
	);
}

//--------------------------------------------------------------------------
//	現在オプションの取得
//	これらのメソッドは手動で設定した値を取るもので、db_designで設定した値は取れない。
//	手動設定とdb_designの値をマージした値が必要な場合は、get_storage_defines()を使うこと
//--------------------------------------------------------------------------
public static function get_opt_src()
{
	return self::$m_opt_src;
}
public static function get_opt_bucket()
{
	return self::$m_opt_bucket;
}
public static function get_opt_target()
{
	return self::$m_opt_target;
}
public static function get_opt_path()
{
	return self::$m_opt_path;
}
public static function get_opt_limit()
{
	return self::$m_opt_limit;
}
public static function get_opt_exts()
{
	return self::$m_opt_exts;
}

//--------------------------------------------------------------------------
//	オプションの手動指定
//--------------------------------------------------------------------------

//	データソースの指定。"disk" or "s3"
public static function set_opt_src( $src_, $bucket_=false, $target_=false )
{
	self::$m_opt_src = $src_;
	self::$m_opt_bucket = $bucket_;
	self::$m_opt_target = $target_;
}

//	保存パスの指定
public static function set_opt_path( $path_ )
{
	if( substr($path_, -1) != "/" ) $path_ .= "/";
	self::$m_opt_path = $path_;
}

//	許容するサイズをバイト数で指定する
public static function set_opt_limit( $bytes_ )
{
	self::$m_opt_limit = intval($bytes_);
}

//	許容する拡張子の指定。すべてを許容する場合には"*"を指定する
public static function set_opt_exts( $exts_ )
{
	$wild = false;
	if( is_array($exts_) === false )
	{
		if( $exts_ !== "*" )
		{
			crow_log::error("extensions not array but not a wildcard");
			return false;
		}
		$wild = true;
	}
	else
	{
		foreach( $exts_ as $ext )
		{
			if( $ext == "*" )
			{
				$wild = true;
				break;
			}
		}
	}
	self::$m_opt_exts = $wild==true ? ["*"] : $exts_;
}

//	ディレクトリを一定単位で分割するか？
//	このオプションはdb_designで指定できないため、必要な場合はこちらで指定する
public static function set_opt_split_dir( $enable_, $split_cnt_ = 1000 )
{
	self::$m_opt_split_dir = $enable_;
	self::$m_opt_split_cnt = intval($split_cnt_);
}

//--------------------------------------------------------------------------
//	リクエストパラメータで、ファイルがPOSTされているかチェックする
//--------------------------------------------------------------------------
public static function is_posted_file( $input_name_ )
{
	if( isset($_FILES[$input_name_]) === false ) return false;
	if( $_FILES[$input_name_]['error'] != UPLOAD_ERR_OK ) return false;
	return is_file($_FILES[$input_name_]['tmp_name']);
}

//--------------------------------------------------------------------------
//	ファイルデータのセット
//--------------------------------------------------------------------------

//	リクエストパラメータからセット
public function take_from_request( $input_name_ )
{
	$this->_taked_from = "request";
	$this->_taked_data = $input_name_;

	if( isset($_FILES[$input_name_]) === false ) return false;
	$this->file_name = $_FILES[$input_name_]['name'];
	$this->file_ext = crow_storage::extract_ext($this->file_name);
	$this->file_length = filesize($_FILES[$input_name_]['tmp_name']);
	$this->file_key = (strlen($this->file_key) > 0) ? $this->file_key : crow_utility::random_str(32);
	return true;
}

//	ディスクからセット
public function take_from_disk( $path_ )
{
	$this->_taked_from = "disk";
	$this->_taked_data = $path_;

	if( is_file($path_) === false ) return false;
	$this->file_name = crow_storage::extract_filename($path_);
	$this->file_ext = crow_storage::extract_ext($this->file_name);
	$this->file_length = filesize($path_);
	$this->file_key = (strlen($this->file_key) > 0) ? $this->file_key : crow_utility::random_str(32);
	return true;
}

//	メモリからセット
public function take_from_memory( $name_, $data_ )
{
	$this->_taked_from = "memory";
	$this->_taked_data = $data_;

	$this->file_name = $name_;
	$this->file_ext = crow_storage::extract_ext($this->file_name);
	$this->file_length = strlen($data_);
	$this->file_key = (strlen($this->file_key) > 0) ? $this->file_key : crow_utility::random_str(32);
	return true;
}

//--------------------------------------------------------------------------
//	実ファイルの存在チェック
//--------------------------------------------------------------------------
public function exists()
{
	//	ストレージハンドルはデータソースによって異なる
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$hs = crow_storage::get_instance($d_src, $d_bucket, $d_target);
	if( $hs===false )
	{
		crow_log::notice("unknown datasource : ".self::$m_opt_src);
		return false;
	}

	//	パス計算
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$path = self::get_dir_path($this->{$table_design->primary_key}).$this->file_key;

	//	存在チェック
	return $hs->exists($path);
}

//--------------------------------------------------------------------------
//	読み込み
//--------------------------------------------------------------------------
public function read()
{
	//	ストレージハンドルはデータソースによって異なる
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$hs = crow_storage::get_instance($d_src, $d_bucket, $d_target);
	if( $hs===false )
	{
		crow_log::notice("unknown datasource : ".self::$m_opt_src);
		return false;
	}

	//	パス計算
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$path = self::get_dir_path($this->{$table_design->primary_key}).$this->file_key;

	//	読み込み
	return $hs->read($path);
}

//--------------------------------------------------------------------------
//	読み込んでダウンロード
//--------------------------------------------------------------------------
public function download( $download_file_name_ = false )
{
	//	ストレージハンドルはデータソースによって異なる
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$hs = crow_storage::get_instance($d_src, $d_bucket, $d_target);
	if( $hs===false )
	{
		crow_log::notice("unknown datasource : ".self::$m_opt_src);
		return false;
	}

	//	パス計算
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$path = self::get_dir_path($this->{$table_design->primary_key}).$this->file_key;

	//	ダウンロード
	return $hs->download($path, $download_file_name_ !== false ?
		$download_file_name_ : $this->file_name);
}

//--------------------------------------------------------------------------
//	読み込んでダウンロード : インライン指定版 (pdf別タブ表示などを想定)
//--------------------------------------------------------------------------
public function download_inline( $download_file_name_ = false )
{
	//	ストレージハンドルはデータソースによって異なる
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$hs = crow_storage::get_instance($d_src, $d_bucket, $d_target);
	if( $hs===false )
	{
		crow_log::notice("unknown datasource : ".self::$m_opt_src);
		return false;
	}

	//	パス計算
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$path = self::get_dir_path($this->{$table_design->primary_key}).$this->file_key;

	//	ダウンロード
	return $hs->download_inline($path, $download_file_name_ !== false ?
		$download_file_name_ : $this->file_name);
}

//--------------------------------------------------------------------------
//	実ファイルの削除
//--------------------------------------------------------------------------
public function delete_file()
{
	//	ストレージハンドルはデータソースによって異なる
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$hs = crow_storage::get_instance($d_src, $d_bucket, $d_target);
	if( $hs===false )
	{
		crow_log::notice("unknown datasource : ".self::$m_opt_src);
		return false;
	}

	//	パス計算
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$path = self::get_dir_path($this->{$table_design->primary_key}).$this->file_key;

	//	削除
	return $hs->remove($path);
}

//--------------------------------------------------------------------------
//	バリデーションの拡張
//--------------------------------------------------------------------------
public function validation_ext()
{
	//	ファイル指定がない場合には成功とする
	if( $this->_taked_from === false ) return true;

	//	入力種別ごとにチェック
	if( $this->_taked_from == "request" )
	{
		if( isset($_FILES[$this->_taked_data]) === false )
		{
			$this->push_validation_error("storage", crow_msg::get('storage.err.no_input'));
			return false;
		}
		if( $_FILES[$this->_taked_data]['error'] != UPLOAD_ERR_OK )
		{
			$err = str_replace(":error", $_FILES[$this->_taked_data]['error'], crow_msg::get('storage.err.upload'));
			$this->push_validation_error("storage", $err);
			return false;
		}
	}
	else if( $this->_taked_from == "disk" )
	{
		if( is_file($this->_taked_data) === false )
		{
			$err = str_replace(":filename", $this->_taked_data, crow_msg::get('storage.err.not_found'));
			$this->push_validation_error("storage", $err);
			return false;
		}
	}
	else if( $this->_taked_from == "memory" )
	{
	}

	//	自動/手動で指定した定義をマージして取得
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();

	//	拡張子、ファイルサイズのチェック
	if( $d_limit > 0 && $this->file_length > $d_limit )
	{
		$err = crow_msg::get('storage.err.too_large');
		$err = str_replace(":length", $this->file_length, $err);
		$err = str_replace(":limit", $d_limit, $err);
		$this->push_validation_error("storage", $err);
		return false;
	}
	if( count($d_exts) > 0 )
	{
		if( $d_exts[0] != "*" && in_array($this->file_ext, $d_exts) === false )
		{
			$err = str_replace(":exts", implode("/",$d_exts), crow_msg::get('storage.err.not_allowed_ext'));
			$this->push_validation_error("storage", $err);
			return false;
		}
	}

	return true;
}

//--------------------------------------------------------------------------
//	ディレクトリパスの取得
//--------------------------------------------------------------------------
public static function get_dir_path( $id_ )
{
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$path = $d_path;

	if( self::$m_opt_split_dir === false )
		return $path;

	$path .= (int)($id_/self::$m_opt_split_cnt)."/";
	if( is_dir($path) === false ) mkdir($path);
	return $path;
}

//--------------------------------------------------------------------------
//	保存後処理の拡張
//--------------------------------------------------------------------------
public function save_ext()
{
	//	パスの作成
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$new_path = self::get_dir_path($this->{$table_design->primary_key}).$this->file_key;

	//	ストレージハンドルはデータソースによって異なる
	list($d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts) = self::get_storage_defines();
	$hs = crow_storage::get_instance($d_src, $d_bucket, $d_target);
	if( $hs === false )
	{
		crow_log::notice("unknown datasource : ".self::$m_opt_src);
		return false;
	}
	$ctype = $hs->ext_content_type($this->file_ext);

	//	ファイル出力
	if( $this->_taked_from == "request" )
	{
		$data_path = $_FILES[$this->_taked_data]['tmp_name'];
		if( $hs->write_from_disk($data_path, $new_path, $ctype) === false ) return false;
	}
	else if( $this->_taked_from == "disk" )
	{
		if( $hs->write_from_disk($this->_taked_data, $new_path, $ctype) === false ) return false;
	}
	else if( $this->_taked_from == "memory" )
	{
		if( $hs->write($this->_taked_data, $new_path, $ctype) === false ) return false;
	}

	return true;
}

//--------------------------------------------------------------------------
//	db_design 上でのオプション指定を読み込む
//	すでに手動で指定されていた場合は何もしない（手動指定が優先される）
//--------------------------------------------------------------------------
public static function get_storage_defines()
{
	$d_src = "disk";
	$d_bucket = false;
	$d_target = false;
	$d_path = "";
	$d_limit = 0;
	$d_exts = [];

	$hdb = crow::get_hdb_reader();
	if( $hdb === false )
	{
		crow_log::error('failed to get db handle, storage::read_storage_defines');
		return [$d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts];
	}

	//	手動指定の分を適用
	if( self::$m_opt_src !== false ) $d_src = self::$m_opt_src;
	if( self::$m_opt_bucket !== false ) $d_bucket = self::$m_opt_bucket;
	if( self::$m_opt_target !== false ) $d_target = self::$m_opt_target;
	if( self::$m_opt_path !== false ) $d_path = self::$m_opt_path;
	if( self::$m_opt_limit !== false ) $d_limit = self::$m_opt_limit;
	if( self::$m_opt_exts !== false ) $d_exts = self::$m_opt_exts;

	//	デフォルト定義を適用
	$table_design = $hdb->get_design(self::create()->m_table_name);
	foreach( $table_design->options as $option )
	{
		if( $option['name'] != "storage" ) continue;
		if( self::$m_opt_src === false )
		{
			$d_src = isset($option['args'][0]) ? $option['args'][0] : '';
		}
		if( self::$m_opt_path === false )
		{
			$d_path = isset($option['args'][1]) ? $option['args'][1] : '';
			$d_path = str_replace("[CROW_PATH]", CROW_PATH, $d_path);
		}
		if( self::$m_opt_limit === false )
		{
			$d_limit = isset($option['args'][2]) ? intval($option['args'][2]) : 0;
		}
		if( self::$m_opt_exts === false )
		{
			$ext = trim(isset($option['args'][3]) ? $option['args'][3] : '');
			$d_exts = $ext=="" ? [] : explode("|",$ext);
		}
		break;
	}
	return [$d_src, $d_bucket, $d_target, $d_path, $d_limit, $d_exts];
}

//	private
private $_taked_from = false;
private $_taked_data = false;

