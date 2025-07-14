<?php
/*


	外部プロバイダ経由での認証
	Facebook

	ドキュメント
	https://developers.facebook.com/docs/facebook-login?locale=ja_JP


*/
class crow_auth_provider_facebook extends crow_auth_provider
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
			["provider"=>"facebook"]
		);

		$queries = array_merge
		(
			[
				"response_type" => "code",
				"client_id" => crow_config::get("auth.provider.facebook.app_id"),
				"redirect_uri" => $redirect_uri,
				"state" => crow_utility::random_str(),
				"scope" => "public_profile,email",
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
	//		expires_in
	//	}
	//--------------------------------------------------------------------------
	private function receive_token()
	{
		$error = crow_request::get("error", false);
		if( $error !== false )
		{
			crow_log::warning("error_reason: ".crow_request::get("error_reason", ""));
			return false;
		}

		$code = crow_request::get("code");

		$redirect_uri = crow::make_url
		(
			crow_config::get("auth.provider.callback.module"),
			crow_config::get("auth.provider.callback.action"),
			["provider"=>"facebook"]
		);

		$res = crow_curl::create(self::ENDPOINT_TOKEN)
			->method("get")
			->params
			([
				"code" => $code,
				"client_id" => crow_config::get("auth.provider.facebook.app_id"),
				"client_secret" => crow_config::get("auth.provider.facebook.app_secret"),
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

		$res = crow_curl::create(self::ENDPOINT_USERINFO)
			->method("get")
			->params
			([
				"access_token" => $this->_tokens["access_token"],
				"fields" => "id,name,email,picture",
			])
			->request()
			;

		$body = $res->body();
		$result = json_decode($body, true);
		if( $result === false )
		{
			crow_log::warning("userinfo decode error - \n".$body);
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

		return isset($this->_data["picture"]) === true ? $this->_data["picture"]["data"]["url"] : "";
	}

	private $_tokens = false;
	private $_data = false;

	const ENDPOINT_AUTH = "https://www.facebook.com/dialog/oauth";
	const ENDPOINT_TOKEN = "https://graph.facebook.com/v2.3/oauth/access_token";
	const ENDPOINT_USERINFO = "https://graph.facebook.com/me";
}

?>
