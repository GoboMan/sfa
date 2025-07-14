<?php
/*


	外部プロバイダ経由での認証
	Google

	ドキュメント
	https://developers.google.com/identity/sign-in/web/sign-in


*/
class crow_auth_provider_google extends crow_auth_provider
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
			["provider"=>"google"]
		);

		$queries = array_merge
		(
			[
				"client_id" => crow_config::get("auth.provider.google.client_id"),
				"redirect_uri" => $redirect_uri,
				"scope" => crow_config::get("auth.provider.google.scope"),
				"response_type" => "code",
				"prompt" => crow_config::get("auth.provider.google.prompt"),
			//	"state" => random_str(),
			],
			$params_
		);
		return self::ENDPOINT_AUTH."?".http_build_query($queries);
	}

	//--------------------------------------------------------------------------
	//	トークン取得
	//--------------------------------------------------------------------------
	private function receive_token()
	{
		$code = crow_request::get("code", false);
		if( $code === false )
		{
			crow_log::warning("cannot get code");
			return false;
		}

		$redirect_uri = crow::make_url
		(
			crow_config::get("auth.provider.callback.module"),
			crow_config::get("auth.provider.callback.action"),
			["provider"=>"google"]
		);

		$res = crow_curl::create(self::ENDPOINT_TOKEN)
			->method("post")
			->params
			([
				"code" => $code,
				"client_id" => crow_config::get("auth.provider.google.client_id"),
				"client_secret" => crow_config::get("auth.provider.google.client_secret"),
				"redirect_uri" => $redirect_uri,
				"grant_type" => "authorization_code",
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
		if( array_key_exists("id_token", $this->_tokens) === false )
		{
			crow_log::warning("not found id_token for google");
			return false;
		}

		$jwt = parent::decode_jwt($this->_tokens["id_token"]);
		$this->_data = $jwt["payload"];
		return $this->_data;
	}

	//--------------------------------------------------------------------------
	//	IDの取得
	//--------------------------------------------------------------------------
	public function get_id()
	{
		if( $this->check_userinfo() === false ) return false;

		return $this->_data["sub"];
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

		return isset($this->_data["picture"]) === true ? $this->_data["picture"] : "";
	}

	private $_tokens = false;
	private $_data = false;

	const ENDPOINT_AUTH = "https://accounts.google.com/o/oauth2/auth";
	const ENDPOINT_TOKEN = "https://oauth2.googleapis.com/token";
}

?>
