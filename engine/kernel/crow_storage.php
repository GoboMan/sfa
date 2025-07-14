<?php
/*

	crow storage

	各ストレージ種別（disk / s3 / tc_cos）ごとのハンドルを取得する


	ディスク向けと、S3向けのハンドル取得例

		$hs_disk	= crow_storage::get_instance("disk");
		$hs_s3		= crow_storage::get_instance("s3");
		$hs_tc_cos	= crow_storage::get_instance("tc_cos");

	ショートカット
		$hs_disk	= crow_storage::disk();
		$hs_s3		= crow_storage::s3();
		$hs_tc_cos	= crow_storage::tc_cos();

	S3・TencentCloud COSで複数のバケットや接続先がある場合、第二/第三引数で指定できる

		//	・バケット指定
		$hs_s3_b2		= crow_storage::get_instance("s3", "bucket_name");
		$hs_tc_cos_b2	= crow_storage::get_instance("tc_cos", "bucket_name");

		//	・接続先を指定
		$hs_s3_conn		= crow_storage::get_instance("s3", false, "backup_srv");
		$hs_tc_cos_conn	= crow_storage::get_instance("tc_cos", false, "backup_srv");

		//	・バケットと接続先を指定
		$hs_s3_conn		= crow_storage::get_instance("s3", "bucket_name", "backup_srv");
		$hs_tc_cos_conn	= crow_storage::get_instance("tc_cos", "bucket_name", "backup_srv");

*/
class crow_storage
{
	//--------------------------------------------------------------------------
	//	各ストレージのハンドル取得
	//--------------------------------------------------------------------------
	public static function get_instance( $type_, $bucket_=false, $target_=false )
	{
		$class = "crow_storage_".$type_;
		if( class_exists($class) === false )
		{
			crow_log::error("not found storage class : ".$class);
			return false;
		}
		$key = implode("_", [ $class,
			($bucket_ !== false ? $bucket_ : ''),
			($target_ !== false ? $target_ : '')
		]);
		if( isset(self::$m_instances[$key]) === false )
			self::$m_instances[$key] = new $class($bucket_, $target_);
		return self::$m_instances[$key];
	}

	//	デフォルト指定でインスタンスを取得するショートカット
	public static function disk()
	{
		return self::get_instance("disk");
	}
	public static function s3()
	{
		return self::get_instance("s3");
	}
	public static function tc_cos()
	{
		return self::get_instance("tc_cos");
	}


	//	継承必須メソッド


	//--------------------------------------------------------------------------
	//	メモリからストレージへ出力
	//
	//	content_type_ を省略した場合は、出力先パスの拡張子から判断する
	//--------------------------------------------------------------------------
	public function write( $data_, $dst_path_, $content_type_ = false )
	{
		crow_log::error( "not implemented crow_storage::write" );
	}

	//--------------------------------------------------------------------------
	//	ファイルからストレージへ出力
	//
	//	content_type_ を省略した場合は、元ファイルの拡張子から判断する
	//--------------------------------------------------------------------------
	public function copy( $src_path_, $dst_path_ )
	{
		crow_log::error( "not implemented crow_storage::write_from_disk" );
	}

	//	※こちらは下位互換で残すが、通常は↑のcopy()を使うこと
	public function write_from_disk( $src_path_, $dst_path_, $content_type_ = false )
	{
		crow_log::error( "not implemented crow_storage::write_from_disk" );
	}

	//--------------------------------------------------------------------------
	//	読み込み
	//--------------------------------------------------------------------------
	public function read( $path_ )
	{
		crow_log::error( "not implemented crow_storage::read" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	読み込んでダウンロード
	//
	//	download_file_name_ にはダウンロードする時のファイル名を指定する
	//	省略した場合には、パス文字列から判断する
	//--------------------------------------------------------------------------
	public function download( $path_, $download_file_name_ = false, $use_cache_ = true )
	{
		crow_log::error( "not implemented crow_storage::download" );
	}

	//--------------------------------------------------------------------------
	//	読み込んでダウンロードのインライン指定版
	//
	//	download()と異なり、こちらはインライン指定でダウンロードする。
	//	PDFを別タブで開きたい場合などを想定する
	//--------------------------------------------------------------------------
	public function download_inline( $path_, $download_file_name_ = false, $use_cache_ = true )
	{
		crow_log::error( "not implemented crow_storage::download_inline" );
	}

	//--------------------------------------------------------------------------
	//	指定パス直下のサブディレクトリとファイルの一覧を取得
	//
	//	返却は
	//		[0] : dirs
	//		[1] : files
	//	の配列とする
	//
	//	$detail_ に false を指定すると、dirsとfilesは名前の一覧となり、
	//	true を指定すると連想配列レコードの一覧となる
	//	詳細指定でのレコードが持つキーは、継承先の各ストレージによって異なる
	//--------------------------------------------------------------------------
	public function get_dirs_and_files( $dir_, $detail_ = false )
	{
	}

	//--------------------------------------------------------------------------
	//	指定パス直下のファイル一覧を取得
	//
	//	絞りたい拡張子の配列を、exts_ に指定できる。空配列の場合は全て対象
	//	指定したディレクトリ文字列が含まれるパス一覧を返却
	//
	//	$detail_ に false を指定すると、filesとdirsは名前の一覧となり、
	//	true を指定すると連想配列レコードの一覧となる
	//	詳細指定でのレコードが持つキーは、継承先の各ストレージによって異なる
	//--------------------------------------------------------------------------
	public function get_files( $dir_, $detail_ = false, $exts_ = [], $recursive_ = false )
	{
		crow_log::error( "not implemented crow_storage::get_list" );
	}

	//--------------------------------------------------------------------------
	//	指定パス直下のサブディレクトリ一覧を取得
	//
	//	指定したディレクトリ文字列が含まれるパス一覧を返却
	//	各ディレクトリの文字列の終端はスラッシュ(/)となる
	//--------------------------------------------------------------------------
	public function get_dirs( $dir_ )
	{
		crow_log::error( "not implemented crow_storage::get_list" );
	}

	//--------------------------------------------------------------------------
	//	ファイルを削除する
	//--------------------------------------------------------------------------
	public function remove( $path_, $recursive_ = false )
	{
		crow_log::error( "not implemented crow_storage::remove" );
	}

	//--------------------------------------------------------------------------
	//	ファイルサイズ取得
	//--------------------------------------------------------------------------
	public function size( $path_ )
	{
		crow_log::error( "not implemented crow_storage::size" );
	}

	//--------------------------------------------------------------------------
	//	ファイルの存在チェック
	//--------------------------------------------------------------------------
	public function exists( $path_ )
	{
		crow_log::error( "not implemented crow_storage::get_exists" );
	}

	//--------------------------------------------------------------------------
	//	一括削除
	//--------------------------------------------------------------------------
	public function batch_delete( $prefix_ /*, $is_promise_ = false*/ )
	{
		crow_log::error( "not implemented crow_storage::batch_delete" );
	}


	//	共通ユーティリティ


	//--------------------------------------------------------------------------
	//	ファイルパスから拡張子を小文字で取得する
	//
	//	例）/aa/bb/cc/dd.png -> png
	//--------------------------------------------------------------------------
	public static function extract_ext( $path_ )
	{
		$pos = mb_strrpos($path_,".");
		if( $pos === false ) return "";
		return strtolower(mb_substr($path_, $pos+1));
	}

	//--------------------------------------------------------------------------
	//	ファイルパスからファイル名を取得する
	//
	//	例）/aa/bb/cc/dd.png -> dd.png
	//--------------------------------------------------------------------------
	public static function extract_filename( $path_ )
	{
		$path = mb_str_replace("\\","/",$path_);
		$pos = mb_strrpos($path,"/");
		if( $pos === false ) return $path_;
		return mb_substr($path, $pos+1);
	}

	//--------------------------------------------------------------------------
	//	ファイルパスから拡張子を除いたファイル名を取得する
	//
	//	例）/aa/bb/cc/dd.png -> dd
	//--------------------------------------------------------------------------
	public static function extract_filename_without_ext( $path_ )
	{
		$path = mb_str_replace("\\","/",$path_);
		$pos = mb_strrpos($path,"/");
		if( $pos !== false ) $path = mb_substr($path, $pos+1);

		$pos = mb_strrpos($path,".");
		if( $pos === false ) return $path;
		return mb_substr($path, 0, $pos );
	}

	//--------------------------------------------------------------------------
	//	ファイルパスからディレクトリ名を取得する
	//
	//	例）
	//		/aa/bb/cc/dd.png -> cc
	//		/aa/bb/cc/dd -> cc
	//		/aa/bb/cc/dd/ -> dd
	//--------------------------------------------------------------------------
	public static function extract_dirname( $path_ )
	{
		$path = mb_str_replace("\\","/",$path_);
		$pos = mb_strrpos($path,"/");
		if( $pos === false ) return "";

		$path = mb_substr($path, 0, $pos);
		$pos = mb_strrpos($path,"/");
		if( $pos === false ) return $path;

		return mb_substr($path, $pos+1);
	}

	//--------------------------------------------------------------------------
	//	ファイルパスからディレクトリパスを取得する
	//
	//	例）/aa/bb/cc/dd.png -> /aa/bb/cc
	//--------------------------------------------------------------------------
	public static function extract_dirpath( $path_ )
	{
		$path = mb_str_replace("\\","/",$path_);
		$pos = mb_strrpos($path,"/");
		if( $pos === false ) return "";
		return mb_substr($path, 0, $pos);
	}

	//--------------------------------------------------------------------------
	//	拡張子から適切なContentTypeを取得する
	//--------------------------------------------------------------------------
	public static function ext_content_type( $ext_ )
	{
		switch($ext_)
		{
			case 'jpg':
			case 'jpeg':
				return 'image/jpeg';
			case 'gif':
				return 'image/gif';
			case 'png':
				return 'image/png';
			case 'svg':
				return 'image/svg+xml';
			case 'txt':
				return 'text/plain';
			case 'html':
				return 'text/html';
			case 'xml':
				return 'text/xml';
			case 'css':
				return 'text/css';
			case 'mp3':
				return 'audio/mpeg';
			case 'mp4':
				return 'video/mp4';
			case 'mpeg':
				return 'video/mpeg';
			case 'zip':
				return 'application/zip';
			case 'pdf':
				return 'application/pdf';
		}
		return 'application/octet-stream';
	}

	//--------------------------------------------------------------------------
	//	リクエストヘッダの値を、UNIX時刻で取得する
	//--------------------------------------------------------------------------
	public function get_if_modified_since()
	{
		static $MONTHS =
		[
			'Jan' => '01', 'Feb' => '02', 'Mar' => '03',
			'Apr' => '04', 'May' => '05', 'Jun' => '06',
			'Jul' => '07', 'Aug' => '08', 'Sep' => '09',
			'Oct' => '10', 'Nov' => '11', 'Dec' => '12',
		];

		//	リクエストヘッダーの値は実行中不変なので、get_if_modified_since()の値は一度だけ評価する
		if( self::$m_unixtime !== null ) return self::$m_unixtime;
		$rh = apache_request_headers() ;
		if( ! isset($rh['If-Modified-Since']) )
		{
			//	If-Modified-Sinceがない
			self::$m_unixtime = false;
			return false;
		}

		$rh = $rh['If-Modified-Since'];
		if( preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun), ([0-3][0-9]) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT$/', $rh, $match) )
		{
			$hour = $match[5];
			$minute = $match[6];
			$second = $match[7];
			$month = $MONTHS[$match[3]];
			$day = $match[2];
			$year = $match[4];
		}
		else if( preg_match( '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday), ([0-3][0-9])-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-([0-9]{2}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) GMT$/', $rh, $match) )
		{
			$hour = $match[5];
			$minute = $match[6];
			$second = $match[7];
			$month = $MONTHS[$match[3]];
			$day = $match[2];
			$year = 1900 + $match[4];
		}
		else if( preg_match( '/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-3 ][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9]) ([0-9]{4})$/', $rh, $match) )
		{
			$hour = $match[4];
			$minute = $match[5];
			$second = $match[6];
			$month = $MONTHS[$match[2]];
			$day = str_replace(' ', 0, $match[3]);
			$year = $match[7];
		}
		else
		{
			self::$m_unixtime = false;
			return false;
		}

		//	unixtimeに変換
		self::$m_unixtime = gmmktime($hour, $minute, $second, $month, $day, $year);
		return self::$m_unixtime;
	}

	//	private
	private static $m_instances = [];
	private static $m_unixtime = null;
}

?>
