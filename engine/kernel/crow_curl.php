<?php
/*

	HTTP 通信制御


	例）
		//	ログイン画面でPOSTしてクッキー取得
		$response = crow_curl::create("https://xxxx/auth")
			->method("post")
			->params(["id"=>"username", "pw"=>"password"])
			->request()
			;
		$cookies = $response->cookies();

		//	上記クッキーを付与してユーザ画面のHTML取得
		$response = crow_curl::create("https://xxxx/profile")
			->method("get")
			->cookies($cookies)
			->request()
			;
		echo $response->body();

*/
class crow_curl
{
	//--------------------------------------------------------------------------
	//	インスタンス作成
	//--------------------------------------------------------------------------
	public static function create( $url_ = "" )
	{
		$inst = new self();
		$inst->m_url = $url_;
		return $inst;
	}

	//--------------------------------------------------------------------------
	//	URL指定
	//--------------------------------------------------------------------------
	public function url( $val_ )
	{
		$this->m_url = $val_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	メソッド指定
	//--------------------------------------------------------------------------
	public function method( $val_ )
	{
		$this->m_method = strtoupper($val_);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータを連想配列で指定
	//--------------------------------------------------------------------------
	public function params( $params_ )
	{
		if( is_array($params_) !== true )
			crow_log::notice("crow_curl::params() please specify as array");
		else
			$this->m_params = $params_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	ファイル添付
	//
	//	mimetype_の指定がない場合は、デフォルトのmimetypeが適用される
	//--------------------------------------------------------------------------
	public function file( $param_name_, $file_path_, $mimetype_ = false )
	{
		$mimetype = $mimetype_;
		if( $mimetype === false )
		{
			$ext = crow_storage::extract_ext($file_path_);
			$mimetype = crow_storage::ext_content_type($ext);
		}

		$this->m_uploads[$param_name_] = [$file_path_, $mimetype];
		return $this;
	}

	//--------------------------------------------------------------------------
	//	コンテンツ種別と生データを指定
	//	post/put/delete時に有効となり、その際に他のリクエストパラメータは無視される
	//	それ以外のHTTPメソッド時にも送信したい場合には、$force_ に true を指定する
	//--------------------------------------------------------------------------
	public function raw_param( $content_type_, $body_, $force_ = false )
	{
		$this->m_raw_type = $content_type_;
		$this->m_raw_data = $body_;
		$this->m_raw_data_force = $force_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	ベーシック認証指定
	//--------------------------------------------------------------------------
	public function basic( $id_, $pw_ )
	{
		$this->m_basic_id = $id_;
		$this->m_basic_pw = $pw_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	クッキー指定
	//
	//	二次元配列を渡す
	//	例）
	//	[
	//		"SESSIONID" =>
	//		[
	//			"SESSIONID" => "abcdefjwoi22342gx",
	//			"Path" => "\/",
	//			"Secure" => false
	//		],
	//		"User" =>
	//		[
	//			"Domain" => "jalan.net",
	//			"Expires" => "Sat, 24-Sep-2022 11:50:05 GMT",
	//			"Path" => "\/"
	//		],
	//	]
	//	のような形で渡す
	//
	//--------------------------------------------------------------------------
	public function cookies( $cookies_ )
	{
		$this->m_cookies = $cookies_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	USERAGENT 指定
	//--------------------------------------------------------------------------
	public function user_agent( $ua_ )
	{
		$this->header("User-Agent", $ua_);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	Referer 指定
	//--------------------------------------------------------------------------
	public function referer( $ref_ )
	{
		$this->header("Referer", $ref_);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	リダイレクトヘッダをたどるかどうかを、最大回数と共に指定する
	//	指定しない場合のデフォルトは、「3回リダイレクトをたどる」とする
	//--------------------------------------------------------------------------
	public function follow_location( $enable_, $count_ )
	{
		$this->m_follow_location = $enable_===true ? $count_ : 0;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	接続タイムアウト時間をミリ秒で指定する、デフォルト(0)は無制限とする
	//	ただし、phpのバイナリによっては1秒未満を指定できずに常に接続失敗となる
	//--------------------------------------------------------------------------
	public function connection_timeout( $msec_ = 0 )
	{
		$this->m_connection_timeout = intval($msec_);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	実行タイムアウト時間をミリ秒で指定する、デフォルト(0)は無制限とする
	//--------------------------------------------------------------------------
	public function exec_timeout( $msec_ = 0 )
	{
		$this->m_exec_timeout = intval($msec_);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	curlオプションを直接指定する
	//	代替手段であり、本当に必要な場合はインタフェースの用意を検討すること
	//--------------------------------------------------------------------------
	public function curlopt( $key_, $val_ )
	{
		$this->m_curlopts[$key_] = $val_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	HTTPヘッダを指定、$val_ にfalseを指定すると該当キーを削除する
	//--------------------------------------------------------------------------
	public function header( $key_, $val_ )
	{
		if( $val_ === false )
		{
			if( isset($this->m_headers[$key_]) === true )
				unset($this->m_headers[$key_]);
		}
		else
		{
			$this->m_headers[$key_] = $val_;
		}
		return $this;
	}

	//--------------------------------------------------------------------------
	//	リクエスト実行、crow_curl_response のインスタンスを返却
	//--------------------------------------------------------------------------
	public function request()
	{
		$ch = curl_init();
		curl_setopt_array($ch, $this->build_curl_opts());

		//	curl実行
		$raw = curl_exec($ch);
		$response = new crow_curl_response($raw, curl_getinfo($ch), curl_errno($ch));
		return $response;
	}

	//	curl オプション構築
	public function build_curl_opts()
	{
		$this->m_opts = [];

		//	メソッドによりパラメータ指定方法が異なる
		if( $this->m_method == "GET" )
		{
			$request_url = $this->m_url;
			if( count($this->m_params) > 0 )
			{
				if( $this->m_method == "GET" )
				{
					$params = [];
					foreach( $this->m_params as $key => $val )
						$params[] = $key."=".urlencode($val);

					if( mb_strstr($this->m_url, "?") === false )
						$request_url .= "?".implode("&", $params);
					else
						$request_url .= "&".implode("&", $params);
				}
			}
			$this->m_opts[CURLOPT_URL] = $request_url;

			//	body部はforce指定の場合のみ送信する
			if( $this->m_raw_type !== false && $this->m_raw_data_force === true )
			{
				$this->m_headers["Content-Type"] = $this->m_raw_type;
				$this->m_opts[CURLOPT_POSTFIELDS] = $this->m_raw_data;
			}
		}
		else if( $this->m_method == "POST" )
		{
			$this->m_opts[CURLOPT_URL] = $this->m_url;
			$this->m_opts[CURLOPT_POST] = true;
			if( $this->m_raw_type !== false )
			{
				$this->m_headers["Content-Type"] = $this->m_raw_type;
				$this->m_opts[CURLOPT_POSTFIELDS] = $this->m_raw_data;
			}
			else
			{
				$params = [];
				$arr_params = [];

				//	通常パラメータを抽出
				if( count($this->m_params) > 0 )
				{
					foreach( $this->m_params as $key => $val )
					{
						if( is_array($val) === true ) $arr_params[$key] = $val;
						else $params[$key] = $val;
					}
				}

				//	ファイル添付がある場合は追加
				if( count($this->m_uploads) > 0 )
				{
					foreach( $this->m_uploads as $key => $val )
						$params[$key] = new CURLFile($val[0], $val[1]);

					//	同名への複数値指定を配列でマージする
					if( count($arr_params) > 0 )
					{
						foreach( $arr_params as $key => $val )
						{
							if( isset($params[$key]) === false ) $params[$key] = [];
							$params[$key] = array_merge((array)$params[$key], $val);
						}
					}
				}

				//	ファイル添付がない場合はクエリ文字列にする
				else
				{
					$params = http_build_query($params);

					//	同名への複数値指定を文字列として末尾に追加
					if( count($arr_params) > 0 )
					{
						foreach( $arr_params as $key => $val )
						{
							if( $params != "" ) $params .= "&";
							$params .= $key."=".urlencode($val);
						}
					}
				}

				$this->m_opts[CURLOPT_POSTFIELDS] = $params;
			}
		}
		else if( $this->m_method == "PUT" || $this->m_method == "DELETE" || $this->m_method == "PATCH" )
		{
			$this->m_opts[CURLOPT_URL] = $this->m_url;

			if( $this->m_raw_type !== false )
			{
				$this->m_headers["Content-Type"] = $this->m_raw_type;
				$this->m_opts[CURLOPT_POSTFIELDS] = $this->m_raw_data;
			}
			else
			{
				if( count($this->m_params) > 0 )
					$this->m_opts[CURLOPT_POSTFIELDS] = http_build_query($this->m_params);
			}
		}
		else
		{
			crow_log::notice("crow_curl::request() unknown request method : ".$this->m_method);
			return false;
		}

		//	クッキー情報
		if( count($this->m_cookies) > 0 )
		{
			if( isset($this->m_opts[CURLOPT_HTTPHEADER]) === false )
				$this->m_opts[CURLOPT_HTTPHEADER] = [];

			$cookie_lines = [];
			foreach( $this->m_cookies as $name => $cookie )
			{
				if( count($cookie) <= 0 ) continue;

				$items = [];
				foreach( $cookie as $ck => $cv )
					$items[] = $cv===false ? $ck : ($ck."=".$cv);
				$cookie_lines[] = implode(";",$items);
			}
			if( count($cookie_lines) > 0 )
				$this->m_opts[CURLOPT_HTTPHEADER][] = "Cookie: ".implode(";", $cookie_lines);
		}

		//	ヘッダ情報
		if( count($this->m_headers) > 0 )
		{
			if( isset($this->m_opts[CURLOPT_HTTPHEADER]) === false )
				$this->m_opts[CURLOPT_HTTPHEADER] = [];

			foreach( $this->m_headers as $key => $val )
			{
				$this->m_opts[CURLOPT_HTTPHEADER][] =
					$key.": ".$val;
			}
		}

		//	接続タイムアウト指定
		if( $this->m_connection_timeout > 0 )
			$this->m_opts[CURLOPT_CONNECTTIMEOUT_MS] = $this->m_connection_timeout;

		//	実行タイムアウト指定
		if( $this->m_exec_timeout > 0 )
			$this->m_opts[CURLOPT_TIMEOUT_MS] = $this->m_exec_timeout;

		//	その他オプション
		$this->m_opts[CURLOPT_CUSTOMREQUEST] = $this->m_method;
		$this->m_opts[CURLOPT_RETURNTRANSFER] = true;
		$this->m_opts[CURLOPT_SSL_VERIFYHOST] = false;
		$this->m_opts[CURLOPT_SSL_VERIFYPEER] = false;
		$this->m_opts[CURLOPT_FOLLOWLOCATION] = $this->m_follow_location > 0;
		$this->m_opts[CURLOPT_MAXREDIRS] = intval($this->m_follow_location);
		$this->m_opts[CURLOPT_HEADER] = true;
		if( $this->m_basic_id !== false )
			$this->m_opts[CURLOPT_USERPWD] = $this->m_basic_id.":".$this->m_basic_pw;

		//	自動リダイレクト時にクッキーを生かすために仮値をセットしておく
		$this->m_opts[CURLOPT_COOKIEJAR] = "";
		$this->m_opts[CURLOPT_COOKIEFILE] = "";

		//	設定上書き
		if( count($this->m_curlopts) > 0 )
		{
			foreach( $this->m_curlopts as $opt_key => $opt_val )
				$this->m_opts[$opt_key] = $opt_val;
		}

		return $this->m_opts;
	}

	//	リクエスト情報
	private $m_url = "";
	private $m_method = "GET";
	private $m_params = [];
	private $m_basic_id = false;
	private $m_basic_pw = false;
	private $m_cookies = [];
	private $m_headers = [];
	private $m_opts = [];
	private $m_uploads = [];
	private $m_follow_location = 3;
	private $m_connection_timeout = 0;
	private $m_exec_timeout = 0;
	private $m_curlopts = [];
	private $m_raw_type = false;
	private $m_raw_data = false;
	private $m_raw_data_force = false;
}

?>
