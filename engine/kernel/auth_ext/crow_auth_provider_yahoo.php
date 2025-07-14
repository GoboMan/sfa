<?php
/*


	外部プロバイダ経由での認証
	Yahoo

	ドキュメント
	https://developer.yahoo.co.jp/yconnect/v2/authorization_code/


*/
class crow_auth_provider_yahoo extends crow_auth_provider
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
			["provider"=>"yahoo"]
		);

		$queries = array_merge
		(
			[
				"response_type" => "code",
				"client_id" => crow_config::get("auth.provider.yahoo.client_id"),
				"redirect_uri" => $redirect_uri,
				"scope" => crow_config::get("auth.provider.yahoo.scope"),

				"state" => crow_utility::random_str(),
				//	page/touch/ppup/inapp
				"display" => "page",
				//	consent/login/select_account/none
				"prompt" => "select_account",
			],
			$params_
		);
		return self::ENDPOINT_AUTH."?".http_build_query($queries);
	}

	//--------------------------------------------------------------------------
	//	トークン取得
	//
	//	{
	//		access_token
	//		token_type
	//		refresh_token
	//		id_token
	//		expires_in
	//	}
	//--------------------------------------------------------------------------
	private function receive_token()
	{
		$error = crow_request::get("error", false);
		if( $error !== false )
		{
			$msg = sprintf("error_code: %s / error_description: %s",
				crow_request::get("error_code", ""),
				crow_request::get("error_description", "")
			);
			crow_log::warning($msg);
			return false;
		}

		$client_id = crow_config::get("auth.provider.yahoo.client_id");
		$client_secret = crow_config::get("auth.provider.yahoo.client_secret");
		$basic = base64_encode($client_id.":".$client_secret);

		$code = crow_request::get("code");

		$redirect_uri = crow::make_url
		(
			crow_config::get("auth.provider.callback.module"),
			crow_config::get("auth.provider.callback.action"),
			["provider"=>"yahoo"]
		);

		$res = crow_curl::create(self::ENDPOINT_TOKEN)
			->method("post")
			->header("Authorization", "Basic ".$basic)
			->params
			([
				"code" => $code,
				"grant_type" => "authorization_code",
				"redirect_uri" => $redirect_uri,
			])
			->request()
			;

		$body = $res->body();
		$result = json_decode($body, true);
		if( $result === false )
		{
			crow_log::warning("cannot get access_token \n".$body);
			return false;
		}
		$this->_tokens = $result;

		//	IDトークンがあればデコードしてopen_idを取得
		//	UserInfoが取れるなら上書きされてもよい
		if( isset($this->_tokens["id_token"]) === true )
		{
			$decode_id_token = self::decode_jwt($this->_tokens["id_token"]);
			if( $decode_id_token !== false )
			{
				$this->_data = [];
				$this->_data["sub"] = isset($decode_id_token["payload"]["sub"]) === true
					? $decode_id_token["payload"]["sub"] : "";
			}
		}

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
	//	ユーザ情報の取得
	//--------------------------------------------------------------------------
	public function get_user_info()
	{
		if( $this->check_tokens() === false ) return false;
		if( crow_config::get("auth.provider.yahoo.enable_userinfo") != "true" ) return $this->_data;

		$res = crow_curl::create(self::ENDPOINT_USERINFO)
			->method("get")
			->header("Authorization", "Bearer ".$this->_tokens["access_token"])
			->request()
			;

		$body = $res->body();
		$result = json_decode($body, true);
		if( $result === false )
		{
			crow_log::warning("userinfo decode error - \n".$body);
			return false;
		}

		if( isset($result["Error"]) === true )
		{
			crow_log::warning("userinfo failed got - \n".$body);
			return false;
		}

		$this->_data = $result;
		return $this->_data;
	}

	//--------------------------------------------------------------------------
	//	IDの取得
	//--------------------------------------------------------------------------
	public function get_id()
	{
		if( $this->check_userinfo() === false ) return false;

		return isset($this->_data["sub"]) !== false ? $this->_data["sub"] : "";
	}

	//--------------------------------------------------------------------------
	//	名前の取得
	//--------------------------------------------------------------------------
	public function get_name()
	{
		if( $this->check_userinfo() === false ) return false;

		return isset($this->_data["nickname"]) !== false ? $this->_data["nickname"] : "";
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

		return isset($this->_data["picture"]) === true ? $this->_data["picture"] : "";
	}

	private $_tokens = false;
	private $_data = false;

	const ENDPOINT_AUTH = "https://auth.login.yahoo.co.jp/yconnect/v2/authorization";
	const ENDPOINT_TOKEN = "https://auth.login.yahoo.co.jp/yconnect/v2/token";
	const ENDPOINT_USERINFO = "https://userinfo.yahooapis.jp/yconnect/v2/attribute";

}

?>
