<?php
/*


	外部プロバイダ経由での認証
	Amazonn

	ドキュメント
	https://developers.line.biz/ja/services/line-login/


*/
class crow_auth_provider_amazon extends crow_auth_provider
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
			["provider"=>"amazon"]
		);

		$queries = array_merge
		(
			[
				"client_id" => crow_config::get("auth.provider.amazon.client_id"),
				"scope" => crow_config::get("auth.provider.amazon.scope"),
				"response_type" => "code",
				"redirect_uri" => $redirect_uri,
				"state" => crow_utility::random_str(),
			],
			$params_
		);
		return self::ENDPOINT_AUTH."?".http_build_query($queries);
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
			["provider"=>"amazon"]
		);

		$client_id = crow_config::get("auth.provider.amazon.client_id");
		$client_secret = crow_config::get("auth.provider.amazon.client_secret");
		$basic = base64_encode($client_id.":".$client_secret);

		$res = crow_curl::create(self::ENDPOINT_TOKEN)
			->method("post")
			->header("Authorization", "Basic ".$basic)
			->params
			([
				"code" => $code,
				"client_id" => $client_id,
				"redirect_uri" => $redirect_uri,
				"client_secret" => $client_secret,
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
	//	ユーザ情報の取得
	//--------------------------------------------------------------------------
	public function tokens()
	{
		if( $this->check_tokens() === false ) return false;

		return $this->_tokens();
	}

	//--------------------------------------------------------------------------
	//	ユーザ情報の取得
	//	{
	//		user_id
	//		name
	//		email
	//	}
	//--------------------------------------------------------------------------
	public function get_user_info()
	{
		if( $this->check_tokens() === false ) return false;

		$url = self::ENDPOINT_USERINFO."?access_token=".$this->_tokens["access_token"];
		$res = crow_curl::create($url)
			->method("get")
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

		return $this->_data["user_id"];
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

		return "";
	}

	private $_tokens = false;
	private $_data = false;

	const ENDPOINT_AUTH = "https://www.amazon.com/ap/oa";
	const ENDPOINT_TOKEN = "https://api.amazon.co.jp/auth/o2/token";
	const ENDPOINT_USERINFO = "https://api.amazon.com/user/profile";
}

?>
