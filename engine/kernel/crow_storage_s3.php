<?php
/*

	crow storage s3用

	--- crow_configでの定義 ---
	aws.default.key		= キー
	aws.default.secret	= シークレット
	aws.default.region	= リージョン
	aws.default.version	= バージョン
	aws.default.bucket	= バケット
	aws.default.cloudfront.enabled	= CF有効？
	aws.default.cloudfront.host		= CFホスト

	上記の"default"の部分がcrow内におけるターゲット名となる。
	別の名前を定義することで、複数の接続先を実現できる。

	例）aws.defaultの他に、
	aws.second.key = セカンドサーバのキー
	....以下同様

	の定義を記述した状態で、

		$hs = crow_storage::get_instance("s3", false, "second");

	とすると、セカンドサーバのハンドルを取得できる

	本クラスの利用にはAWS-SDKが必要になる。
	インストール方法
		$ cd [CROW_PATH/engine/]
		$ php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
		$ php composer-setup.php
		$ php -r "unlink('composer-setup.php');"
		$ sudo php ./composer.phar require aws/aws-sdk-php
*/
require_once(CROW_PATH.'engine/vendor/autoload.php');
use Aws\S3\S3Client;

class crow_storage_s3 extends crow_storage
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
			$msg = sprintf("crow_storage_s3 failed to write %s/%s", $this->m_bucket, $dst_path_);
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
			crow_log::notice("crow_storage_s3 failed to open src file : ".$src_path_);
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

		if( $this->m_cloudfront_enabled === true )
		{
			$path = $this->m_cloudfront_host."/".$path_;

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
			$length = $result["ContentLength"];
			$result['Body']->rewind();
			return $result['Body']->read($length);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to read %s/%s", $this->m_bucket, $path_);
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
	//		"class"		: S3のストレージクラス
	//		"etag"		: S3オブジェクトのハッシュ値
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
				$result = $this->m_handle->listObjectsV2($opts);

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
			$msg = sprintf("crow_storage_s3 failed to get_files_and_dirs %s/%s", $this->m_bucket, $dir_);
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
	//		"class"		: S3のストレージクラス
	//		"etag"		: S3オブジェクトのハッシュ値
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
				$result = $this->m_handle->listObjectsV2($opts);

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
			$msg = sprintf("crow_storage_s3 failed to get_files %s/%s", $this->m_bucket, $dir_);
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
				$result = $this->m_handle->listObjectsV2($opts);

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
			$msg = sprintf("crow_storage_s3 failed to get_dirs %s/%s", $this->m_bucket, $dir_);
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
				$msg = "crow_storage_s3 failed to remove object ".$path_;
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
				$msg = "crow_storage_s3 failed to remove ".$path_;
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
	//
	//	※ACL(権限)まではコピーしないので注意
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
				'Key'			=> $to_,
				'CopySource'	=> $this->m_bucket.'/'.$path_,
			]);
		}
		catch( Exception $e_ )
		{
			$msg = "crow_storage_s3 failed to copy object ".$path_." to ".$to_;
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $result;
	}

	//--------------------------------------------------------------------------
	//	s3objectの情報取得
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
			$msg = sprintf("crow_storage_s3 failed to get head object %s/%s", $this->m_bucket, $path_);
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
	//
	//	$command_ が "GetObject" だと取得用となり、"PutObject" だとアップロード用となる。
	//	HttpMethodはputで、multipartで送信すればよいが、結果はhtmlではないのでajaxで送信する想定となる。
	//--------------------------------------------------------------------------
	public function generate_presigned_url( $path_, $expiration_, $opt_=[], $command_ = "GetObject" )
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
			$command = $this->m_handle->getCommand($command_, $options);
			$req = $this->m_handle->createPresignedRequest($command, $expiration_);
			$url = strval($req->getUri());
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to generate presigned url %s/%s", $this->m_bucket , $path_);
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

		//	クラウドフロントありの場合
		if( $this->m_cloudfront_enabled === true )
		{
			return $this->m_cloudfront_host."/".$path_;
		}

		//	クラウドフロントなしの場合
		$url = '';
		try
		{
			$url = $this->m_handle->getObjectUrl($this->m_bucket, $path_);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to get object url %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $url;
	}

	//--------------------------------------------------------------------------
	//	マルチパートアップロード開始
	//
	//	5G以上のファイルはマルチパートでアップロードする必要がある。
	//	本メソッドはマルチパートアップロードの開始を行う。
	//
	//	成功した場合は、aws-sdkの返却するオブジェクトをそのまま戻すが、
	//	以降の処理（完了/キャンセル）で主に使うのは、"uploadId" と "Key" の2つになるはず
	//
	//		$handle = $hs3->create_multipart_upload($path);
	//		$ret_upload_id = $handle->get("uploadId");
	//		$ret_path = $handle->get("Key");
	//
	//	上記が成功した場合、その後のリクエストを以て必ず結果の処理も行う必要がある。
	//	・「完了」（complete_multipart_upload）
	//	・「キャンセル」（abort_multipart_upload）
	//
	//	もしどちらも行わなかった場合、AWSコンソール画面には表示されず裏でパーツが残り続ける。
	//	裏で浮いたパーツも課金対象になるため、上記の完了/キャンセルとは別に保険として、
	//	s3のライフサイクルルールで必ず削除されるように設定しておくこと
	//--------------------------------------------------------------------------
	public function create_multipart_upload( $path_, $opt_ = [] )
	{
		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	アップロード開始
		$opt = is_array($opt_) === false ? [] : $opt_;
		$object_opt =
		[
			"Bucket" => $this->m_bucket,
			"Key" => $path_
		];
		$options = array_merge($object_opt, $opt);
		try
		{
			$ret = $this->m_handle->createMultipartUpload($options);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to start multipart upload %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	マルチパートアップロード完了
	//--------------------------------------------------------------------------
	public function complete_multipart_upload( $path_, $upload_id_, $opt_ = [])
	{
		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	アップロードされているpartのEtag取得
		$list = $this->get_list_parts( $path_, $upload_id_ );
		if( $list === false ) return false;
		$parts = $list->get('Parts');
		$part_list = [];
		foreach( $parts as $index => $part )
		{
			$part_list['Parts'][$index]['PartNumber'] = $part['PartNumber'];
			$part_list['Parts'][$index]['ETag'] = $part['ETag'];
		}

		//	アップロード完了
		$opt = is_array($opt_) === false ? [] : $opt_;
		$object_opt =
		[
			"Bucket" => $this->m_bucket,
			"Key" => $path_,
			"UploadId" => $upload_id_,
			"MultipartUpload" => $part_list
		];
		$options = array_merge($object_opt, $opt);
		try
		{
			$ret = $this->m_handle->completeMultipartUpload($options);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to complete multipart upload %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	マルチパートアップロードリスト取得
	//--------------------------------------------------------------------------
	public function get_list_parts( $path_, $upload_id_, $opt_ = [] )
	{
		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	リスト取得
		$opt = is_array($opt_) === false ? [] : $opt_;
		$object_opt =
		[
			"Bucket" => $this->m_bucket,
			"Key" => $path_,
			"UploadId" => $upload_id_
		];
		$options = array_merge($object_opt, $opt);
		try
		{
			$ret = $this->m_handle->listParts($options);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to get multipart upload list %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	マルチパートアップロードキャンセル
	//--------------------------------------------------------------------------
	public function abort_multipart_upload( $path_, $upload_id_, $opt_ = [] )
	{
		//	先頭デリミタを削除
		if( substr($path_, 0, 1) == "/" ) $path_ = substr($path_, 1);
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	アップロードキャンセル
		$opt = is_array($opt_) === false ? [] : $opt_;
		$object_opt =
		[
			"Bucket" => $this->m_bucket,
			"Key" => $path_,
			"UploadId" => $upload_id_
		];
		$options = array_merge($object_opt, $opt);
		try
		{
			$ret = $this->m_handle->abortMultipartUpload($options);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to abort multipart upload %s/%s", $this->m_bucket, $path_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	一括削除
	//
	//	プレフィックスによる前方一致で一括削除する
	//	間違えるとバケット内全削除になる場合もあるため、
	//	関係ないフォルダはバケットポリシーで保護すること
	//--------------------------------------------------------------------------
	public function batch_delete( $prefix_ /*, $is_promise_ = false*/ )
	{
		//	先頭デリミタを削除
		if( substr($prefix_, 0, 1) == "/" ) $prefix_ = substr($prefix_, 1);
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}
		$params =
		[
			"Bucket" => $this->m_bucket,
			"Prefix" => $prefix_
		];
		try
		{
			$del = Aws\S3\BatchDelete::fromListObjects($this->m_handle, $params);

			//	削除実行
			if( $is_promise_ === true )
			{
				//	非同期削除
				$promise = $del->promise();
				return $promise;
			}
			else
				$del->delete();
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to batch delete %s/%s", $this->m_bucket, $prefix_);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	ライフサイクルルール作成
	//--------------------------------------------------------------------------
	public function create_life_cycle($name_, $rule_, $status_ = "Enabled")
	{
		if( strlen($name_) <= 0 || is_array( $rule_ ) === false ) return false;
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		$rule_["Status"] = $status_;
		$rule_["ID"] = $name_;
		return $this->create_life_cycles([$rule_]);
	}

	//--------------------------------------------------------------------------
	//	ライフサイクルルール作成
	//	既存の設定値を取得してマージする
	//--------------------------------------------------------------------------
	public function create_life_cycles( $rules_ )
	{
		if( is_array( $rules_ ) === false ) return false;
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	既存の設定を取得
		$rules = $this->get_life_cycles();
		$rules = array_merge($rules, $rules_);
		return $this->exec_put_life_cycles($rules);
	}

	//--------------------------------------------------------------------------
	//	ライフサイクルルール作成
	//	既存の設定は上書きされるので注意
	//--------------------------------------------------------------------------
	public function exec_put_life_cycles( $rules_ )
	{
		if( is_array( $rules_ ) === false ) return false;
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		$params =
		[
			"Bucket" => $this->m_bucket,
			"LifecycleConfiguration" =>
			[
				"Rules" => $rules_
			]
		];

		//	結果は常に空
		try
		{
			$this->m_handle->putBucketLifecycleConfiguration($params);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to put bucket lifecycle bucket : %s", $this->m_bucket);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	ライフサイクルの取得
	//--------------------------------------------------------------------------
	public function get_life_cycles()
	{
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}
		$params =
		[
			"Bucket" => $this->m_bucket
		];

		try
		{
			$res = $this->m_handle->getBucketLifecycleConfiguration($params);
		}
		catch( Exception $e_ )
		{
			$msg = sprintf("crow_storage_s3 failed to get bucket lifecycle bucket : %s", $this->m_bucket);
			crow_log::notice($msg);
			crow_log::notice($e_->getMessage());
			return false;
		}
		$res = $res->toArray();
		return $res["Rules"];
	}

	//--------------------------------------------------------------------------
	//	ライフサイクルの個別削除
	//--------------------------------------------------------------------------
	public function remove_life_cycle( $id_ )
	{
		if( strlen( $id_ ) <= 0 ) return false;
		if( $this->m_handle === false )
		{
			if( $this->connect() === false ) return false;
		}

		//	既存のライフサイクルを取得
		$life_cycle_rows = $this->get_life_cycles();
		foreach( $life_cycle_rows as $index => $life_cycle_row )
		{
			if( $life_cycle_row["ID"] == $id_ ) unset($life_cycle_rows[$index]);
		}

		//	更新
		if( $this->exec_put_life_cycles( $life_cycle_rows ) === false )
		{
			$msg = sprintf("crow_storage_s3 failed to remove bucket lifecycle bucket : %s", $this->m_bucket);
			crow_log::notice($msg);
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	接続
	//--------------------------------------------------------------------------
	private function connect()
	{
		$target = $this->m_target !== false ? $this->m_target : "default";

		$this->m_handle = S3Client::factory(
		[
			'credentials'	=>
			[
				'key'		=> crow_config::get('aws.'.$target.'.key'),
				'secret'	=> crow_config::get('aws.'.$target.'.secret'),
			],
			'region'		=> crow_config::get('aws.'.$target.'.region'),
			'version'		=> crow_config::get('aws.'.$target.'.version'),
		]);
		if( ! $this->m_handle )
		{
			crow_log::notice("failed to create factory of aws s3");
			return false;
		}

		$this->m_bucket = $this->m_bucket !== false ? $this->m_bucket : crow_config::get('aws.'.$target.'.bucket');
		$this->m_cloudfront_enabled = crow_config::get('aws.'.$target.'.cloudfront.enabled', '') === true;
		$this->m_cloudfront_host = crow_config::get('aws.'.$target.'.cloudfront.host', '');

		return true;
	}

	//	private
	private $m_bucket = false;
	private $m_target = false;
	private $m_handle = false;
	private $m_cloudfront_enabled = false;
	private $m_cloudfront_host = '';
}

?>
