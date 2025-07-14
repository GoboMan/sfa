<?php
/*

	crow storage Tencent Cloud COS用

	--- crow_configでの定義 ---
	tc.default.secret_id	= キー
	tc.default.secret_key	= シークレット
	tc.default.region		= リージョン
	tc.default.cos_endpoint	= COSエンドポイント
	tc.default.bucket		= バケット

	上記の"default"の部分がcrow内におけるターゲット名となる。
	別の名前を定義することで、複数の接続先を実現できる。

	例）tc.defaultの他に、
	tc.second.key = セカンドサーバのキー
	....以下同様

	の定義を記述した状態で、

		$hs = crow_storage::get_instance("tc_cos", false, "second");

	とすると、セカンドサーバのハンドルを取得できる

	本クラスの利用にはQcloud cos-sdk-v5が必要になる。
	インストール方法
		$ cd [CROW_PATH/engine/]
		$ php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
		$ php composer-setup.php
		$ php -r "unlink('composer-setup.php');"
		$ sudo php ./composer.phar require qcloud/cos-sdk-v5
*/
require_once(CROW_PATH.'engine/vendor/autoload.php');
use Qcloud\Cos\Client;

class crow_storage_tc_cos extends crow_storage
{
	//--------------------------------------------------------------------------
	//	コンストラクタ
	//--------------------------------------------------------------------------
	function __construct( $bucket_ = false, $target_ = false )
	{
		$this->m_bucket = $bucket_;
		$this->m_target = $target_;
	}

	//--------------------------------------------------------------------------
	//	override : メモリからストレージへ出力
	//--------------------------------------------------------------------------
	public function write( $data_, $dst_path_, $content_type_ = false )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($dst_path_, 0, 1) == "/" ) $dst_path_ = substr($dst_path_, 1);

		//	content_type 省略時は出力ファイルの拡張子から判断
		$content_type = $content_type_ !== false ?
			$content_type_ : self::ext_content_type(self::extract_ext($dst_path_));

		$result = false;
		try
		{
			//	アップロード
			$result = $this->m_handle->putObject(
			[
				'Bucket'		=> $this->m_bucket,
				'Key'			=> $dst_path_,
				'Body'			=> $data_,
				'ContentType'	=> $content_type,
			]);
			gc_collect_cycles();
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to write %s/%s", $this->m_bucket, $dst_path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}

		return $result !== false;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルからストレージへ出力
	//--------------------------------------------------------------------------
	public function write_from_disk( $src_path_, $dst_path_, $content_type_ = false )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($dst_path_, 0, 1) == "/" ) $dst_path_ = substr($dst_path_, 1);

		$file_size = filesize($src_path_);
		$fp = fopen($src_path_, "rb");
		if( $fp === false )
		{
			crow_log::notice("crow_storage_tc_cos failed to open src file : ".$src_path_);
			exit;
		}
		$data = fread($fp, $file_size);
		fclose($fp);

		//	content_type 省略時は元ファイルの拡張子から判断
		$content_type = $content_type_ !== false ? $content_type_ : self::ext_content_type(self::extract_ext($src_path_));
		return $this->write($data, $dst_path_, $content_type);
	}

	//--------------------------------------------------------------------------
	//	override : 読み込み
	//--------------------------------------------------------------------------
	public function read( $path_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		if( $this->m_cdn_enabled === true )
		{
			$path = $this->m_cdn_host."/".$path_;

			//	ファイルがない場合のログ抑制
			$error_level = ini_get('error_reporting');
			ini_set('error_reporting', E_ERROR | E_PARSE);
			$result = crow_curl::get_contents($path);
			ini_set('error_reporting', $error_level);
			if( $result !== false ) return $result;
		}

		try
		{
			$result = $this->m_handle->getObject(
			[
				'Bucket'	=> $this->m_bucket,
				'Key'		=> $path_,
			]);
			$length = (int) $result["ContentLength"];
			$result['Body']->rewind();
			return $result['Body']->read($length);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to read %s/%s", $this->m_bucket, $this->path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $result;
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
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		$dl_fname = $download_file_name_ !== false ? $download_file_name_ : self::extract_filename($path_);
		$ext = self::extract_ext($dl_fname);
		$content_type = crow_storage::ext_content_type($ext);
		$data = $this->read($path_);
		$file_size = strlen($data);

		crow_response::set_header("Content-Type", $content_type);
		crow_response::set_header("Content-Disposition", ($is_inline_ === true ? 'inline' : 'attachment')."; filename=\"".$dl_fname."\"; filename*=UTF-8''".rawurlencode($dl_fname));
		crow_response::set_header("Content-Length", $file_size);
		crow::output_start();
		echo $data;
		crow::output_end();
		exit;
	}

	//--------------------------------------------------------------------------
	//	override : 指定パス直下のファイルとディレクトリ一覧を取得
	//
	//	詳細指定での1レコードは次のキーを持つ連想配列とする
	//		"name"		: ファイル名
	//		"size"		: ファイルサイズ (bytes)
	//		"update"	: 更新日のUNIXタイムスタンプ
	//		"class"		: COSのストレージクラス
	//		"etag"		: COSのハッシュ値
	//		"owner"		: 所有者の名前
	//		"owner_id"	: 所有者のID
	//--------------------------------------------------------------------------
	public function get_dirs_and_files( $dir_, $detail_ = true )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	末尾デリミタがなければ追加、先頭デリミタを削除
		if( substr($dir_, -1) != "/" ) $dir_ .= "/";
		if( substr($dir_, 0, 1) == "/" ) $dir_ = substr($dir_, 1);
		$path_len = strlen($dir_);

		$opts =
		[
			'Bucket'		=> $this->m_bucket,
			'Prefix'		=> $dir_,
			'FetchOwner'	=> true,
			'Delimiter'		=> "/",
		];

		$dirs = [];
		$files = [];
		try
		{
			//	最大1000件ずつ取得できる
			$next_token = null;
			while(1)
			{
				$opts['ContinuationToken'] = $next_token;
				$result = $this->m_handle->listObjects($opts);

				if( isset($result['CommonPrefixes']) === true )
				{
					foreach( $result['CommonPrefixes'] as $common_prefix )
					{
						//	最後のデリミタを削除しておく
						$dirs[] = substr($common_prefix['Prefix'], 0, strlen($common_prefix['Prefix']) - 1);
					}
				}

				if( isset($result['Contents']) === true )
				{
					foreach( $result['Contents'] as $contents )
					{
						$name = substr($contents['Key'], $path_len);
						if( $detail_ === false )
						{
							$files[] = $name;
						}
						else
						{
							$files[] =
							[
								"name"		=> $name,
								"size"		=> $contents['Size'],
								"update"	=> strtotime($contents['LastModified']),
								"class"		=> $contents['StorageClass'],
								"etag"		=> substr($contents['ETag'], 1, strlen($contents['ETag'])-2),
								"owner"		=> $contents['Owner']['DisplayName'],
								"owner_id"	=> $contents['Owner']['ID'],
							];
						}
					}
				}

				//	継続あり？
				if( isset($result['IsTruncated']) === true && $result['IsTruncated']===true )
				{
					//	次のページのトークンを取得して、処理継続
					$next_token = $result['NextContinuationToken'];
					continue;
				}
				break;
			}
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to get_files_and_dirs %s/%s", $this->m_bucket, $dir_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return [$dirs, $files];
	}

	//--------------------------------------------------------------------------
	//	override : 指定パス直下のファイル一覧を取得
	//
	//	詳細指定での1レコードは次のキーを持つ連想配列とする
	//		"name"		: ファイル名
	//		"size"		: ファイルサイズ (bytes)
	//		"update"	: 更新日のUNIXタイムスタンプ
	//		"class"		: COSのストレージクラス
	//		"etag"		: COSのハッシュ値
	//		"owner"		: 所有者の名前
	//		"owner_id"	: 所有者のID
	//--------------------------------------------------------------------------
	public function get_files( $dir_, $detail_ = false, $exts_ = [], $recursive_ = false )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	末尾デリミタがなければ追加、先頭デリミタを削除
		if( substr($dir_, -1) != "/" ) $dir_ .= "/";
		if( substr($dir_, 0, 1) == "/" ) $dir_ = substr($dir_, 1);
		$path_len = strlen($dir_);

		$opts =
		[
			'Bucket'		=> $this->m_bucket,
			'Prefix'		=> $dir_,
			'FetchOwner'	=> true,
		];
		if( $recursive_ === false ) $opts['Delimiter'] = "/";

		$keys = [];
		try
		{
			//	最大1000件ずつ取得できる
			$next_token = null;
			while(1)
			{
				$opts['ContinuationToken'] = $next_token;
				$result = $this->m_handle->listObjects($opts);

				if( isset($result['Contents']) === true )
				{
					foreach( $result['Contents'] as $contents )
					{
						$name = "";
						if( count($exts_) > 0 )
						{
							if( in_array(self::extract_ext($contents['Key']), $exts_) === true )
								$name = substr($contents['Key'], $path_len);
						}
						else
						{
							$name = substr($contents['Key'], $path_len);
						}

						if( $detail_ === false )
						{
							$keys[] = $name;
						}
						else
						{
							$keys[] =
							[
								"name"		=> $name,
								"size"		=> $contents['Size'],
								"update"	=> strtotime($contents['LastModified']),
								"class"		=> $contents['StorageClass'],
								"etag"		=> substr($contents['ETag'], 1, strlen($contents['ETag'])-2),
								"owner"		=> $contents['Owner']['DisplayName'],
								"owner_id"	=> $contents['Owner']['ID'],
							];
						}
					}
				}

				//	継続あり？
				if( isset($result['IsTruncated']) === true && $result['IsTruncated'] === true )
				{
					//	次のページのトークンを取得して、処理継続
					$next_token = $result['NextContinuationToken'];
					continue;
				}
				break;
			}
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to get_files %s/%s", $this->m_bucket, $dir_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $keys;
	}

	//--------------------------------------------------------------------------
	//	override : 指定パス直下のサブディレクトリ一覧を取得
	//--------------------------------------------------------------------------
	public function get_dirs( $dir_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	末尾デリミタがなければ追加、先頭デリミタを削除
		if( substr($dir_, -1) != "/" ) $dir_ .= "/";
		if( substr($dir_, 0, 1) == "/" ) $dir_ = substr($dir_, 1);
		$opts =
		[
			'Bucket'			=> $this->m_bucket,
			'Prefix'			=> $dir_,
			'Delimiter'			=> "/",
		];

		$keys = [];
		try
		{
			//	最大1000件ずつ取得できる
			$next_token = null;
			while(1)
			{
				$opts['ContinuationToken'] = $next_token;
				$result = $this->m_handle->listObjects($opts);

				if( isset($result['CommonPrefixes']) === true )
				{
					foreach( $result['CommonPrefixes'] as $common_prefix )
					{
						//	最後のデリミタを削除しておく
						$keys[] = substr($common_prefix['Prefix'], 0, strlen($common_prefix['Prefix']) - 1);
					}
				}

				//	継続あり？
				if( isset($result['IsTruncated']) === true && $result['IsTruncated'] === true )
				{
					//	次のページのトークンを取得して、処理継続
					$next_token = $result['NextContinuationToken'];
					continue;
				}
				break;
			}
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to get_dirs %s/%s", $this->m_bucket, $dir_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $keys;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルを削除する
	//--------------------------------------------------------------------------
	public function remove( $path_, $recursive_ = false )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		$result = false;
		if( $recursive_ === false )
		{
			try
			{
				$result = $this->m_handle->deleteObject(
				[
					'Bucket'	=> $this->m_bucket,
					'Key'		=> $path_,
				]);
			}
			catch( Exception $e_ )
			{
				$msg = "crow_storage_tc_cos failed to remove object ".$path_;
				crow_log::notice($msg);
				crow_log::notice($e_->getMessage());
				return false;
			}
			return $result;
		}

		//	再帰指定の場合には、pathはディレクトリであること。
		if( substr($path_, -1) != "/" ) $path_ .= "/";
		$files = $this->get_files($path_, true);
		foreach( $files as $file )
		{
			if( $this->remove($file, false) !== true )
			{
				$msg = "crow_storage_tc_cos failed to remove ".$path_;
				crow_log::notice($msg);
				return false;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : ファイルサイズ取得
	//--------------------------------------------------------------------------
	public function size( $path_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		$result = false;
		try
		{
			$result = $this->m_handle->HeadObject(
			[
				'Bucket'	=> $this->m_bucket,
				'Key'		=> $path_,
			]);
		}
		catch( Exception $e_ )
		{
			return 0;
		}
		if( ! $result ) return 0;
		if( ! isset($result['ContentLength']) ) return 0;

		return $result['ContentLength'];
	}

	//--------------------------------------------------------------------------
	//	override : ファイルの存在チェック
	//--------------------------------------------------------------------------
	public function exists( $path_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		$result = false;
		try
		{
			$result = $this->m_handle->HeadObject(
			[
				'Bucket'	=> $this->m_bucket,
				'Key'		=> $path_,
			]);
		}
		catch( Exception $e_ )
		{
			return false;
		}
		return $result !== false;
	}

	//--------------------------------------------------------------------------
	//	ファイルを複製する
	//--------------------------------------------------------------------------
	public function copy( $path_, $to_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1)=="/" ) $path_ = substr($path_, 1);
		if( substr($to_, 0, 1) == "/" ) $to_ = substr($to_, 1);

		$result = false;
		try
		{
			$result = $this->m_handle->copyObject(
			[
				'Bucket'		=> $this->m_bucket,
				'Key'			=> $path_,
				'CopySource'	=> urlencode($this->m_endpoint.'/'.$to_),
			]);
		}
		catch( Exception $e_ )
		{
			$msg = "crow_storage_tc_cos failed to copy object ".$path_." to ".$to_;
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $result;
	}

	//--------------------------------------------------------------------------
	//	COSの情報取得
	//	ContentType,LastModified,ContentLength,Etag,Expires,@metadata)
	//--------------------------------------------------------------------------
	public function get_head_object( $path_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		$result = false;
		try
		{
			//	キーからobjectのデータを取得
			$result = $this->m_handle->HeadObject(
			[
				'Bucket'	=> $this->m_bucket,
				'Key'		=> $path_,
			]);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to get head object %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $result;
	}

	//--------------------------------------------------------------------------
	//	署名付きURL生成(expiratioin_は文字列指定可能 +1 minutes, 1 hourなど )
	//	optにはarray('ResponseContentDisposition'=>'attachment; filename="downloadname"');
	//	なども設定可能
	//--------------------------------------------------------------------------
	public function generate_presigned_url( $path_, $expiration_, $opt_=[], $command_ = "getObject" )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		$opt = ! is_array($opt_) ? [] : $opt_;
		$url = '';
		$get_object_opt =
		[
			'Bucket'	=> $this->m_bucket,
			'Key'		=> $path_,
		];
		$options = array_merge($get_object_opt, $opt);
		try
		{
			$url = $this->m_handle->getPreSignedUrl(
				$command_,
				$options,
				$expiration_
			);
			$url = strval($url);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to generate presigned url %s/%s", $this->m_bucket , $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}

		return $url;
	}

	//--------------------------------------------------------------------------
	//	オブジェクトURL取得
	//--------------------------------------------------------------------------
	public function get_object_url( $path_ )
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);

		//	CDNありの場合
		if( $this->m_cdn_enabled === true )
		{
			return $this->m_cdn_host."/".$path_;
		}

		//	CDNなしの場合
		$url = '';
		try
		{
			$url = $this->m_handle->getObjectUrl($this->m_bucket, $path_);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_tc_cos failed to get object url %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $url;
	}

	//--------------------------------------------------------------------------
	//	接続
	//--------------------------------------------------------------------------
	private function connect()
	{
		$target = $this->m_target !== false ? $this->m_target : "default";

		$this->m_handle = new Client(
		[
			'credentials'	=>
			[
				'secretId'	=> crow_config::get('tc.'.$target.'.secret_id'),
				'secretKey'	=> crow_config::get('tc.'.$target.'.secret_key'),
			],
			'region'		=> crow_config::get('tc.'.$target.'.region'),
			'schema'		=> 'https',
		]);
		if( ! $this->m_handle )
		{
			crow_log::notice("failed to create factory of tencent cloud cos");
			return false;
		}
		$this->m_bucket = $this->m_bucket !== false ? $this->m_bucket : crow_config::get('tc.'.$target.'.bucket');
		$this->m_endpoint = $this->m_endpoint !== false ? $this->m_endpoint : crow_config::get('tc.'.$target.'.cos_endpoint');

		return true;
	}

	//	private
	private $m_bucket = false;
	private $m_endpoint = false;
	private $m_target = false;
	private $m_handle = false;
	private $m_cdn_enabled = false;
	private $m_cdn_host = '';
}

?>
