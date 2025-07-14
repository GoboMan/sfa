<?php
/*

	crow storage disk用

*/
class crow_storage_disk extends crow_storage
{
	//--------------------------------------------------------------------------
	//	コンストラクタ
	//--------------------------------------------------------------------------
	function __construct( $bucket_ = false, $target_ = false )
	{
	}

	//--------------------------------------------------------------------------
	//	override : メモリからストレージへ出力
	//--------------------------------------------------------------------------
	public function write( $data_, $dst_path_, $content_type_ = false )
	{
		$fp = fopen($dst_path_, "wb");
		if( $fp === false )
		{
			crow_log::notice("crow_storage_disk failed to output file to [".$dst_path_."]");
			return false;
		}
		if( fwrite($fp, $data_) === false )
		{
			crow_log::notice("crow_storage_disk failed to write to file [".$dst_path_."]");
			return false;
		}
		fclose($fp);
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルからストレージへ出力
	//--------------------------------------------------------------------------
	public function copy( $src_path_, $dst_path_ )
	{
		return copy($src_path_, $dst_path_);
	}

	//	※こちらは下位互換で残すが、通常は↑のcopy()を使うこと
	public function write_from_disk( $src_path_, $dst_path_, $content_type_ = false )
	{
		return copy($src_path_, $dst_path_);
	}

	//--------------------------------------------------------------------------
	//	override : 読み込み
	//--------------------------------------------------------------------------
	public function read( $path_ )
	{
		$file_size = filesize($path_);
		$fp = fopen($path_, "rb");
		if( $fp === false )
		{
			crow_log::notice("crow_storage_disk failed to open file : ".$path_);
			exit;
		}
		$data = fread($fp, $file_size);
		fclose($fp);
		return $data;
	}

	//--------------------------------------------------------------------------
	//	override : 読み込んでダウンロード
	//--------------------------------------------------------------------------
	public function download( $path_, $download_file_name_ = false, $use_cache_ = true )
	{
		$this->download_core($path_, $download_file_name_, $use_cache_, false);
	}

	//--------------------------------------------------------------------------
	//	override : 読み込んでダウンロードのインライン指定版
	//--------------------------------------------------------------------------
	public function download_inline( $path_, $download_file_name_ = false, $use_cache_ = true )
	{
		$this->download_core($path_, $download_file_name_, $use_cache_, true);
	}

	//--------------------------------------------------------------------------
	//	ダウンロードコア処理
	//--------------------------------------------------------------------------
	private function download_core( $path_, $download_file_name_, $use_cache_, $is_inline_ )
	{
		$ext = self::extract_ext($download_file_name_ === false ? $path_ : $download_file_name_);
		$content_type = crow_storage::ext_content_type($ext);
		$file_size = filesize($path_);
		$dl_fname = $download_file_name_ !== false ? $download_file_name_ : self::extract_filename($path_);

		//	ファイルが更新されていないなら304を返却して
		//	ブラウザにキャッシュを使うように促す
		$stat = @stat($path_);
		if( $use_cache_ === true && $stat != false )
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
			$handle = fopen($path_, 'rb');
			if( $handle === false )
			{
				crow_log::notice('crow_storage_disk failed open file : '.$path_);
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
				header("Content-Disposition: ".($is_inline_ === true ? 'inline' : 'attachment')."; filename=\"".$dl_fname."\"; filename*=UTF-8''".rawurlencode($dl_fname));
				header("Content-Range: bytes ".$range_start."-".$range_end."/".$file_size);
				header("Etag:\"".md5($_SERVER["REQUEST_URI"]).$file_size."\"");
				header("Last-Modified:".gmdate("D,d M Y H:i:s", filemtime($path_))." GMT");
				fseek($handle, $range_start);
			}
			//	初回リクエスト
			else
			{
				$content_length = $file_size;
				header("Content-Type: ".$content_type);
				header("Content-Length: ".$content_length);
				header("Content-Disposition: ".($is_inline_ === true ? 'inline' : 'attachment')."; filename=\"".$dl_fname."\"; filename*=UTF-8''".rawurlencode($dl_fname));
				header("Etag:\"".md5($_SERVER["REQUEST_URI"]).$file_size."\"");
				header("Last-Modified:".gmdate("D,d M Y H:i:s", $stat[9])." GMT");
			}

			//	出力
			@ob_end_clean();
			while( feof($handle) === false && connection_status() == 0 && connection_aborted() == 0 )
			{
				set_time_limit(0);
				$buffer = fread($handle,8192);
				echo $buffer;
				@flush();
				@ob_flush();
			}
			fclose($handle);
		}
		exit;
	}

	//--------------------------------------------------------------------------
	//	override : 指定パス直下のサブディレクトリとファイルの一覧を取得
	//--------------------------------------------------------------------------
	public function get_dirs_and_files( $dir_, $detail_ = false )
	{
		crow_log::error("...not implement yet...");
		return false;
	}

	//--------------------------------------------------------------------------
	//	override : 指定パス直下のファイル一覧を取得
	//--------------------------------------------------------------------------
	public function get_files( $dir_, $detail_ = false, $exts_ = [], $recursive_ = false )
	{
		if( substr($dir_, -1) != DIRECTORY_SEPARATOR ) $dir_ .= DIRECTORY_SEPARATOR;

		if( $recursive_ === true )
			return self::_get_dir_files_recursive($dir_, $detail_, $exts_);

		$ret = [];
		$fnames = self::_get_dir_files($dir_, $exts_);
		foreach( $fnames as $fname ) $ret[] = $dir_.$fname;
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	override : 指定パス直下のサブディレクトリ一覧を取得
	//--------------------------------------------------------------------------
	public function get_dirs( $dir_ )
	{
		if( substr($dir_, -1) != DIRECTORY_SEPARATOR ) $dir_ .= DIRECTORY_SEPARATOR;
		if( ! is_dir($dir_) ) return [];
		$dirs = [];
		$hdir = dir($dir_);
		while( $file = $hdir->read() )
		{
			if( $file == "." || $file == ".." ) continue;
			if( is_dir($dir_.$file) )
			{
				$dirs[] = $dir_.$file.DIRECTORY_SEPARATOR;
			}
		}
		$hdir->close();
		return $dirs;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルを削除する
	//--------------------------------------------------------------------------
	public function remove( $path_, $recursive_ = false )
	{
		//	diskの場合、危険なのでrecursiveオプションは無視する
		return is_file($path_) === true ? unlink($path_) : true;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルサイズ取得
	//--------------------------------------------------------------------------
	public function size( $path_ )
	{
		return $this->exists($path_) ? filesize($path_) : 0;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルの存在チェック
	//--------------------------------------------------------------------------
	public function exists( $path_ )
	{
		return file_exists($path_);
	}

	//	ファイル名一覧
	//	ディレクトリを含めないファイル名の配列が返却される
	private static function _get_dir_files( $dir_, $exts_=[] )
	{
		if( substr($dir_, -1) != DIRECTORY_SEPARATOR ) $dir_ .= DIRECTORY_SEPARATOR;
		if( ! is_dir($dir_) ) return [];
		$files = [];
		$hdir = dir($dir_);
		while( $file = $hdir->read() )
		{
			if( $file == "." || $file == ".." ) continue;
			if( ! is_file($dir_.$file) ) continue;

			if( count($exts_) > 0 )
			{
				if( in_array(self::extract_ext($file), $exts_) === true )
					$files[] = $file;
			}
			else $files[] = $file;
		}
		$hdir->close();
		sort( $files );
		return $files;
	}

	//	指定したディレクトリ以下を再帰検索し、ファイルの相対パス一覧を取得する
	//	ディレクトリを含めたファイル名の配列が返却される
	private static function _get_dir_files_recursive( $dir_, $detail_, $exts_=[] )
	{
		if( substr($dir_, -1) != DIRECTORY_SEPARATOR ) $dir_ .= DIRECTORY_SEPARATOR;
		if( ! is_dir($dir_) ) return [];
		$files = [];

		//	まず直下のファイルを処理
		$hdir = dir($dir_);
		while( $file = $hdir->read() )
		{
			if( $file == "." || $file == ".." ) continue;
			if( ! is_file($dir_.$file) ) continue;

			if( count($exts_) > 0 )
			{
				if( in_array(self::extract_ext($file), $exts_) === true )
				{
					if( $detail_ === false )
						$files[] = $dir_.$file;
					else
						$files[] = ["path" => $dir_.$file];
				}
			}
			else
			{
				if( $detail_ === false )
					$files[] = $dir_.$file;
				else
					$files[] = ["path" => $dir_.$file];
			}
		}
		$hdir->close();

		//	名前で並び替える
		sort($files);

		//	サブフォルダを処理
		$hdir = dir($dir_);
		while( $file = $hdir->read() )
		{
			if( $file == "." || $file == ".." ) continue;
			if( is_dir($dir_.$file) )
			{
				$rows = self::_get_dir_files_recursive( $dir_.$file.DIRECTORY_SEPARATOR, $detail_, $exts_ );
				foreach( $rows as $row )
				{
					$files[] = $row;
				}
			}
		}

		$hdir->close();
		sort( $files );
		return $files;
	}
}

?>
