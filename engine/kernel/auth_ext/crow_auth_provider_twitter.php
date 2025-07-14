<?php
/*


	外部プロバイダ経由での認証
	Twitter

	ドキュメント
	https://developer.twitter.com/en/docs/authentication/oauth-1-0a


*/
class crow_auth_provider_twitter extends crow_auth_provider
{
	//--------------------------------------------------------------------------
	//	認証URL生成
	//--------------------------------------------------------------------------
	public function create_url( $params_ = [] )
	{
		$redirect_uri = crow::make_url
		(
			crow_config::get("auth.provider.callback.module"),
			crow_config::get("auth.provider.callback.action"),
			["provider"=>"twitter"]
		);

		$params = $this->twitter_request_token
		(
			[
				"oauth_callback" => $redirect_uri,
			]
		);

		return $params["redirect_uri"];
	}

	//--------------------------------------------------------------------------
	//	Token取得
	//
	//	{
	//		access_token
	//		refresh_token
	//		token_type
	//		expires_in
	//	}
	//--------------------------------------------------------------------------
	private function receive_token()
	{
		$params =
		[
			"oauth_token" => crow_request::get("oauth_token", ""),
			"oauth_verifier" => crow_request::get("oauth_verifier", ""),
		];
		$result = $this->twitter_access_token($params);
		if( $result === false ) return false;

		$this->_tokens = $result;

		return true;
	}

	//--------------------------------------------------------------------------
	//	Token取得チェック
	//--------------------------------------------------------------------------
	private function check_tokens()
	{
		if( $this->_tokens === false )
		{
			$this->receive_token();

			if( $this->_tokens === false ) return false;
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	ユーザ情報チェック
	//--------------------------------------------------------------------------
	private function check_userinfo()
	{
		if( $this->_data === false )
		{
			$this->get_user_info();

			if( $this->_data === false ) return false;
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	Token取得
	//--------------------------------------------------------------------------
	public function tokens()
	{
		if( $this->check_tokens() === false ) return false;

		return $this->_tokens();
	}

	//--------------------------------------------------------------------------
	//	ユーザ情報のIdTokenからの取得
	//	{
	//		user_id
	//		name
	//		email
	//	}
	//--------------------------------------------------------------------------
	public function get_user_info()
	{
		if( $this->check_tokens() === false ) return false;
		if( array_key_exists("oauth_token", $this->_tokens) === false ) return false;

		$params =
		[
			"oauth_token" => $this->_tokens["oauth_token"],
			"include_email" => "true",
		];
		$this->_data = $this->twitter_verify_credentials($params, $this->_tokens["oauth_token_secret"]);

		return $this->_data;
	}

	//--------------------------------------------------------------------------
	//	IDの取得
	//--------------------------------------------------------------------------
	public function get_id()
	{
		if( $this->check_userinfo() === false ) return false;

		return $this->_data["id"];
	}

	//--------------------------------------------------------------------------
	//	名前の取得
	//--------------------------------------------------------------------------
	public function get_name()
	{
		if( $this->check_userinfo() === false ) return false;

		return $this->_data["name"];
	}

	//--------------------------------------------------------------------------
	//	メールアドレスの取得
	//--------------------------------------------------------------------------
	public function get_email()
	{
		if( $this->check_userinfo() === false ) return false;

		return isset($this->_data["email"]) === true ? $this->_data["email"] : "";
	}

	//--------------------------------------------------------------------------
	//	画像の取得
	//--------------------------------------------------------------------------
	public function get_picture()
	{
		if( $this->check_userinfo() === false ) return false;

		return isset($this->_data["profile_image_url_https"]) === true ? $this->_data["profile_image_url_https"] : "";
	}

	//--------------------------------------------------------------------------
	//	Twitter : request_token 認証用ページのURLを取得する
	//	返却値
	//	oauth_token, oauth_token_secret, redirect_url
	//--------------------------------------------------------------------------
	private function twitter_request_token( $params_ )
	{
		//	パラメータ等設定
		$method = "post";
		$header_list =
		[
			"Content-type"	=> "application/json; charset=utf8"
		];

		//	実行
		$res = $this->exec_curl_twitter
		(
			self::ENDPOINT_REQUEST_TOKEN,
			$method,
			$header_list,
			$params_
		);

		//	返却(json)
		$params = json_decode($res->body(), true);
		if( $params === false )
		{
			crow_log::warning("twitter error request_token - ".$res->body());
			return false;
		}

		$res_params = [];

		//	配列で返却
		parse_str($res->body(), $res_params);
		if( ! isset($res_params["oauth_token"]) )
		{
			crow_log::warning("no oauth_token");
			return false;
		}

		$res_params['redirect_uri'] = self::ENDPOINT_AUTH."?oauth_token=".$res_params['oauth_token'];
		return $res_params;
	}

	//--------------------------------------------------------------------------
	//	Twitter : access_token アクセストークンを取得する
	//	返却値
	//	oauth_token, oauth_token_secret
	//--------------------------------------------------------------------------
	private function twitter_access_token( $params_ )
	{
		$method = "post";
		$header_list =
		[
			"Content-type" => "application/json; charset=utf8"
		];
		$res = $this->exec_curl_twitter
		(
			self::ENDPOINT_ACCESS_TOKEN,
			$method,
			$header_list,
			$params_
		);

		//	返却(json)
		$body = $res->body();
		$result = json_decode($body, true);
		if( $result === false )
		{
			crow_log::warning("twitter error access_token - \n".$body);
			return false;
		}

		$res_params = [];
		parse_str($res->body(), $res_params);
		return $res_params;
	}

	//--------------------------------------------------------------------------
	//	Twitter : verify_credentials 名前・メアドを取得
	//	返却値 (jsonでいろいろ返却されるがその中でほしいもの)
	//	name, email(オブジェクト返却)
	//--------------------------------------------------------------------------
	private function twitter_verify_credentials( $params_, $token_secret_ )
	{
		$method = "get";
		$header_list =
		[
			"Content-type"	=> "application/json; charset=utf8",
		];

		$res = $this->exec_curl_twitter
		(
			self::ENDPOINT_USERINFO,
			$method,
			$header_list,
			$params_,
			$token_secret_
		);

		$body = $res->body();

		//	返却(json)
		$params = json_decode($body, true);
		if( $params === false )
		{
			crow_log::warning("twitter error verify_credentials -- no json");
			return false;
		}
		if( isset($params["errors"]) === true )
		{
			crow_log::warning("twitter error verify_credentials - ".$body);
			return false;
		}

		return $params;
	}

	//--------------------------------------------------------------------------
	//	[Private] Twitter : signatureを作成
	//--------------------------------------------------------------------------
	private function build_twitter_signature( $method_, $request_url_, $parameter_, $algo_, $token_secret_ = "" )
	{
		$key = crow_config::get("auth.provider.twitter.api_secret")."&";
		if( strlen($token_secret_)>0 )
			$key .= $token_secret_;
		$signature = ""
			.strtoupper($method_)
			."&".rawurlencode($request_url_)
			."&".rawurlencode($parameter_)
			;
		return base64_encode(hash_hmac("sha1", $signature, $key, true));;
	}

	//--------------------------------------------------------------------------
	//	Twitterに対してのcurl実行ラッパー
	//--------------------------------------------------------------------------
	private function exec_curl_twitter( $url_, $method_ = "post", $add_header_ = [], $add_auth_list_ = [], $token_secret_ = "" )
	{
		//	Twitterにコール
		$algo = "HMAC-SHA1";

		//	Curlインスタンス
		$curl = crow_curl::create($url_);

		//	署名をする
		$auth_list =
		[
			"oauth_nonce"				=> crow_utility::random_str(),
			"oauth_signature_method"	=> $algo,
			"oauth_timestamp"			=> time(),
			"oauth_consumer_key"		=> crow_config::get("auth.provider.twitter.api_key"),
			"oauth_version"				=> "1.0",
		];

		//	署名リストとcurlにパラメータ追加
		if( count($add_auth_list_)>0 )
		{
			$auth_list = array_merge($auth_list, $add_auth_list_);
			$curl->params($add_auth_list_);
		}
		ksort($auth_list);
		$signature = $this->build_twitter_signature($method_, $url_, http_build_query($auth_list, '', '&', PHP_QUERY_RFC3986), $algo, $token_secret_);
		$auth_list["oauth_signature"] = $signature;

		//	認証ヘッダの値をダブルクォーテーションでくくる
		$auth_header_list = [];
		foreach( $auth_list as $key => $val )
			$auth_header_list[] = rawurlencode($key)."=\"".rawurlencode($val)."\"";

		//	ヘッダ
		$header_list =
		[
			"Authorization"	=> "OAuth ".implode(', ',$auth_header_list),
		];
		if( count($add_header_)>0 )
			$haeder_list = array_merge($header_list, $add_header_);

		//	CURL実行
		foreach( $header_list as $key => $header )
			$curl->header($key, $header);

		//	タイムアウトとメソッド指定してリクエスト
		return $curl->method($method_)->request();
	}

	private $_tokens = false;
	private $_data = false;

	const ENDPOINT_API = "https://api.twitter.com/";
	const ENDPOINT_AUTH = "https://api.twitter.com/oauth/authorize";
	const ENDPOINT_ACCESS_TOKEN = "https://api.twitter.com/oauth/access_token";
	const ENDPOINT_REQUEST_TOKEN = "https://api.twitter.com/oauth/request_token";
	const ENDPOINT_USERINFO = "https://api.twitter.com/1.1/account/verify_credentials.json";
}

?>
