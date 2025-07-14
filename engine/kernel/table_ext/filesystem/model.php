//--------------------------------------------------------------------------
//
//	db_designのテーブルオプションに、"filesystem"が付与されている場合に、
//	crow_db.php により追加で読み込まれるコード
//
//	例）POSTからDB登録までのサンプル
//
//	//	1. リクエストから入力
//	$data = model_sample::create_from_request();
//	$data->input_file_from_request("sample");
//
//	//	→ この時、もしファイルから入力するなら、
//	//	$data->input_file_from_disk("sample.png");
//	//	のようにする
//
//	//	2. DB登録とファイル取り込み
//	if( ! $data->check_and_save() ) return error();
//	if( ! $data->take_file() ) return error();
//
//
//	------------------
//	例）ファイルIDから実ファイルを取得する
//	$path = model_sample::get_path( $file_id );
//
//	------------------
//	例）ファイル情報を取得する
//	$row = model_sample::create_from_id($file_id);
//
//	echo $row->file_name;
//	echo $row->file_ext;
//	echo $row->file_length
//
//	------------------
//	例）ファイルIDからダウンロード
//	model_sample::create_from_id($file_id)->download();
//	exit;
//
//	------------------
//	例）実ファイル削除
//	$row = model_sample::create_from_id($file_id);
//	if( ! $row->delete_file() ) return error();
//	if( ! $row->trash() ) return error();
//
//
//	※※ primary_key は複数指定できない仕様とする
//
//--------------------------------------------------------------------------

private static $m_filesystem_path = "";
private static $m_filesystem_exts = array();
private static $m_filesystem_length = 0;


//--------------------------------------------------------------------------
//	リクエストパラメータで、ファイルがPOSTされているかチェックする
//--------------------------------------------------------------------------
public function is_posted_file( $input_name_ )
{
	if( $_FILES[$input_name_]['error'] != UPLOAD_ERR_OK )
		return false;

	$upload_path = $_FILES[$input_name_]['tmp_name'];
	return is_file($upload_path);
}

//--------------------------------------------------------------------------
//	リクエストパラメータで渡されたファイルをチェックする
//--------------------------------------------------------------------------
public function validation_ext()
{
	//	ファイルが必須となるのは新規のときのみで、
	//	編集や削除のとき（つまりprimary_keyがあるとき）は必須とはしない。
	$hdb = crow::get_hdb_reader();
	$class_name = get_called_class();
	$table_design = $hdb->get_design( $class_name::create()->m_table_name );
	$is_new = false;
	if( $this->{$table_design->primary_key} == 0 )
	{
		$is_new = true;
	}


	$input_name = $this->_input_name;
	if( strlen($input_name) <= 0 )
	{
		//	新規の場合のみエラーとする
		if( $is_new )
		{
			$this->push_validation_error('filesystem', 'no input file');
			return false;
		}
		return true;
	}

	$upload_path = $this->_input_from_disk ? $input_name : $_FILES[$input_name]['tmp_name'];
	$fname = "";
	if( $this->_input_from_disk )
	{
		$en = mb_strrpos($input_name, "\\");
		if( $en === false ){
			$en = mb_strrpos($input_name, "/");
		}
		if( $en !== false ) $fname = mb_substr( $input_name, $en+1 );
		else $fname = $input_name;
	}
	else
	{
		$fname = $_FILES[$input_name]['error'] != UPLOAD_ERR_OK ?
			"" : $_FILES[$input_name]['name'];
	}
	if( ! is_file($upload_path) )
	{
		$this->push_validation_error('filesystem', 'not found upload file');
		crow_log::warning( $this->get_last_error() );
		//	新規の場合のみエラーとする
		return $is_new ? false : true;
	}

	//	拡張子とファイルサイズのチェック
	$pos = mb_strrpos( $fname, "." );
	$ext = ($pos !== false) ?
		mb_substr($fname, $pos+1, mb_strlen($fname) - $pos) : "";
	$length = filesize( $upload_path );

	//	拡張子は小文字にする
	$ext = strtolower($ext);

	$class_name = get_called_class();
	$class_name::read_filesystem_defines();

	//	ファイル長チェック
	if( $length > $class_name::$m_filesystem_length )
	{
		$this->push_validation_error( 'filesystem', 'file length too large' );
		crow_log::warning( $this->get_last_error() );
		return false;
	}

	//	拡張子チェック
	if( ! in_array($ext, $class_name::$m_filesystem_exts) )
	{
		if( $class_name::$m_filesystem_exts[0] != '*' )
		{
			$this->push_validation_error( 'filesystem', 'the file name extension('.$ext.') can not upload' );
			crow_log::warning( $this->get_last_error() );
			return false;
		}
	}

	return true;
}

//--------------------------------------------------------------------------
//	リクエストパラメータで渡されたファイルの情報を入力する
//--------------------------------------------------------------------------
public function input_file_from_request( $input_name_ )
{
	if( ! $this->is_posted_file($input_name_) ) return;

	//	情報の分解
	$upload_path = $_FILES[$input_name_]['tmp_name'];
	$fname = $_FILES[$input_name_]['name'];
	$pos = mb_strrpos( $fname, "." );
	$ext = ($pos !== false) ?
		mb_substr($fname, $pos+1, mb_strlen($fname) - $pos) : "";
	$length = filesize( $upload_path );

	//	自メンバにいれておく
	$this->file_name = $fname;
	$this->file_ext = $ext;
	$this->file_length = $length;
	$this->_input_name = $input_name_;
	$this->_input_from_disk = false;

	return $this;
}

//--------------------------------------------------------------------------
//	ローカルディスクからファイルを入力
//--------------------------------------------------------------------------
public function input_file_from_disk( $file_path_ )
{
	if( ! is_file($file_path_) ) return false;

	//	情報の分解
	$fname = "";
	$en = mb_strrpos($file_path_, "\\");
	if( $en === false ){
		$en = mb_strrpos($file_path_, "/");
	}
	if( $en !== false ) $fname = mb_substr( $file_path_, $en+1 );
	else $fname = $file_path_;
	$pos = mb_strrpos( $fname, "." );
	$ext = ($pos !== false) ?
		mb_substr($fname, $pos+1, mb_strlen($fname) - $pos) : "";
	$length = filesize( $file_path_ );

	//	自メンバにいれておく
	$this->file_name = $fname;
	$this->file_ext = $ext;
	$this->file_length = $length;
	$this->_input_name = $file_path_;
	$this->_input_from_disk = true;

	return $this;
}

//--------------------------------------------------------------------------
//	ファイルの実際の取り込み
//--------------------------------------------------------------------------
public function take_file()
{
	//	念のためパラメータチェックする
	if( ! $this->validation_ext() ) return false;
	if( ! $this->validation_crow_ext() ) return false;

	$input_name = $this->_input_name;
	if( strlen($input_name) <= 0 )
	{
		$this->m_error_list[] = 'no input file';
		return false;
	}

	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design( $this->m_table_name );
	$file_id = $this->{$table_design->primary_key};

	//	ファイルのコピー
	$class_name = get_called_class();
	$upload_path = $this->_input_from_disk ? $input_name : $_FILES[$input_name]['tmp_name'];
	$path = $class_name::get_path( $file_id );
	if( ! copy( $upload_path, $path ) )
	{
		$this->m_error_list[] = 'copy file failed';
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------
//	実ファイルの削除
//--------------------------------------------------------------------------
public function delete_file()
{
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design( $this->m_table_name );
	return unlink( self::get_path($this->{$table_design->primary_key}) );
}

//--------------------------------------------------------------------------
//	ダウンロード
//--------------------------------------------------------------------------
public function download( $fname_=false, $use_cache_=true )
{
	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design( $this->m_table_name );

	$fname = $fname_;
	if( $fname===false )
	{
		$fname = $this->file_name;
	}

	$class_name = get_called_class();
	$path = $class_name::get_path($this->{$table_design->primary_key});
	$content_type = "application/octet-stream";
	switch( strtolower($this->file_ext) )
	{
		case 'jpg':
		case 'jpeg':
			$content_type = "image/jpeg"; break;
		case 'gif':
			$content_type = "image/gif"; break;
		case 'png':
			$content_type = "image/png"; break;
		case 'txt':
			$content_type = "image/plain"; break;
		case 'html':
			$content_type = "image/html"; break;
		case 'xml':
			$content_type = "image/xml"; break;
		case 'css':
			$content_type = "image/css"; break;
		case '3gp':
			$content_type = "video/3gpp"; break;
		case '3g2':
			$content_type = "video/3gpp2"; break;
		case 'mp4':
			$content_type = "video/mp4"; break;
	}

	$stat = @stat($path);
	$file_size = $this->file_length;

	//	ファイルが更新されていないなら、304を返却して
	//	ブラウザにキャッシュを使うように促す
	if( $use_cache_ && $stat!==false )
	{
		$ims = $this->get_if_modified_since();
		if( $ims !== false && $stat[9] <= $ims )
		{
			header('HTTP/1.1 304 Not Modified');
			header("Cache-Control: max-age=0");
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $stat[9]).' GMT' );
			exit;
		}
	}

	//	部分リクエスト対応
	{
		header( "Accept-Ranges: bytes" );
		$handle = fopen($path, 'rb');
		if( $handle === false )
		{
			crow_log::notice( 'failed open file : '.$handle );
			exit;
		}

		//	部分リクエスト
		if( isset($_SERVER['HTTP_RANGE']) )
		{
			list($key, $range) = explode("=", $_SERVER['HTTP_RANGE']);
			list($range_start, $range_end) = explode("-", $range);
			if( strlen($range_end) <= 0 )
			{
				$range_end = $file_size - 1;
			}
			$content_length = $range_end - $range_start + 1;
			header('HTTP/1.1 206 Partial Content');
			header("Content-Type: ".$content_type);
			header("Content-Length: ".$content_length);
			header("Content-Disposition: attachment; filename=".$fname);
			header("Content-Range: bytes ".$range_start."-".$range_end."/".$file_size);
			header("Etag:\"".md5($_SERVER["REQUEST_URI"]).$file_size."\"");
			header("Last-Modified:".gmdate("D,d M Y H:i:s", filemtime($path))." GMT");
			fseek($handle, $range_start);
		}
		//	初回リクエスト
		else
		{
			$content_length = $file_size;
			header("Content-Type: ".$content_type);
			header("Content-Length: ".$content_length);
			header("Content-Disposition: attachment; filename=".$fname);
			header("Etag:\"".md5($_SERVER["REQUEST_URI"]).$file_size."\"");
			header("Last-Modified:".gmdate("D,d M Y H:i:s", $stat[9])." GMT");
		}

		//	出力
		@ob_end_clean();
		while( !feof($handle) && connection_status() == 0 && !connection_aborted() )
		{
			set_time_limit(0);
			$buffer = fread($handle,8192);
			echo $buffer;
			@flush();
			@ob_flush();
		}
		fclose($handle);
		exit;
	}
	exit;
}

//--------------------------------------------------------------------------
//	パス取得
//--------------------------------------------------------------------------
public static function get_path( $file_id_ )
{
	$class_name = get_called_class();
	$class_name::read_filesystem_defines();
	$path = $class_name::$m_filesystem_path;
	$path .= (int)($file_id_/1000)."/";
	if( ! is_dir($path) ) mkdir($path);
	$path .= $file_id_;
	return $path;
}

//--------------------------------------------------------------------------
//	Filesystemの定義を読み込む
//--------------------------------------------------------------------------
public static function read_filesystem_defines()
{
	$class_name = get_called_class();
	if( strlen($class_name::$m_filesystem_path) > 0 ) return;

	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design( $class_name::create()->m_table_name );
	foreach( $table_design->options as $option )
	{
		if( $option['name'] != "filesystem" ) continue;
		$class_name::$m_filesystem_path = isset($option['args'][0]) ? $option['args'][0] : '';
		$class_name::$m_filesystem_path = mb_str_replace("[CROW_PATH]", CROW_PATH, $class_name::$m_filesystem_path);
		$class_name::$m_filesystem_length = isset($option['args'][1]) ? $option['args'][1] : 0;
		$ext = isset($option['args'][2]) ? $option['args'][2] : '';
		$class_name::$m_filesystem_exts = explode("|",$ext);
	}
}

//--------------------------------------------------------------------------
//	リクエストヘッダの値を、UNIX時刻で取得する
//--------------------------------------------------------------------------
public function get_if_modified_since()
{
	static $MONTHS = array(
		'Jan' => '01', 'Feb' => '02', 'Mar' => '03',
		'Apr' => '04', 'May' => '05', 'Jun' => '06',
		'Jul' => '07', 'Aug' => '08', 'Sep' => '09',
		'Oct' => '10', 'Nov' => '11', 'Dec' => '12',
	);

	//	リクエストヘッダーの値は実行中不変なので、get_if_modified_since()の値は一度だけ評価する
	static $unixtime = null ;
	if( $unixtime !== null ) return $unixtime;

	$rh = apache_request_headers() ;
	if( ! isset($rh['If-Modified-Since']) )
	{
		$unixtime = false ;
		return false ; // If-Modified-Sinceがない
	}

	$rh = $rh['If-Modified-Since'] ;
	if( preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun), ([0-3][0-9]) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT$/', $rh, $match) )
	{
		$hour = $match[5] ;
		$minute = $match[6] ;
		$second = $match[7] ;
		$month = $MONTHS[$match[3]] ;
		$day = $match[2] ;
		$year = $match[4] ;
	}
	else if( preg_match( '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), ([0-3][0-9])-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-([0-9]{2}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT$/', $rh, $match) )
	{
		$hour = $match[5] ;
		$minute = $match[6] ;
		$second = $match[7] ;
		$month = $MONTHS[$match[3]] ;
		$day = $match[2] ;
		$year = 1900 + $match[4] ;
	}
	else if( preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-3 ][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) ([0-9]{4})$/', $rh, $match) )
	{
		$hour = $match[4] ;
		$minute = $match[5] ;
		$second = $match[6] ;
		$month = $MONTHS[$match[2]] ;
		$day = str_replace(' ', 0, $match[3]) ;
		$year = $match[7] ;
	}
	else
	{
		$unixtime = false ;
		return false ;
	}

	//	unixtimeに変換
	$unixtime = gmmktime($hour, $minute, $second, $month, $day, $year) ;
	return $unixtime ;
}


//	入力コントロールの名前を保持しておく
private $_input_name = "";
private $_input_from_disk = false;
