<?php
/*


	外部プロバイダ経由での認証
	LINE

	ドキュメント
	https://developers.line.biz/ja/services/line-login/


*/
class crow_auth_provider_line extends crow_auth_provider
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
			["provider"=>"line"]
		);

		$queries = array_merge
		(
			[
				"response_type" => "code",
				"client_id" => crow_config::get("auth.provider.line.channel_id"),
				"redirect_uri" => $redirect_uri,
				"client_secret" => crow_config::get("auth.provider.line.channel_secret"),
				"state" => crow_utility::random_str(),
				"scope" => crow_config::get("auth.provider.line.scope"),
				"nonce" => crow_utility::random_str(),
				"prompt" =>  "consent",
			],
			$params_
		);

		return self::ENDPOINT_AUTH."?".http_build_query($queries);
	}

	//--------------------------------------------------------------------------
	//	Token取得
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
			["provider"=>"line"]
		);

		$res = crow_curl::create(self::ENDPOINT_TOKEN)
			->method("post")
			->params
			([
				"code" => $code,
				"client_id" => crow_config::get("auth.provider.line.channel_id"),
				"redirect_uri" => $redirect_uri,
				"client_secret" => crow_config::get("auth.provider.line.channel_secret"),
				"grant_type" => "authorization_code",
			//	"code_verifier" => "",
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
	//	ユーザ情報のIdTokenからの取得
	//--------------------------------------------------------------------------
	public function get_user_info()
	{
		if( $this->check_tokens() === false ) return false;

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

	const ENDPOINT_AUTH = "https://access.line.me/oauth2/v2.1/authorize";
	const ENDPOINT_TOKEN = "https://api.line.me/oauth2/v2.1/token";
}

?>
