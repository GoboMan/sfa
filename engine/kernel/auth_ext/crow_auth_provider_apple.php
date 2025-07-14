<?php
/*


	外部プロバイダ経由での認証
	Apple

	ドキュメント
	https://developer.apple.com/documentation/sign_in_with_apple


*/
class crow_auth_provider_apple extends crow_auth_provider
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
			["provider"=>"apple"]
		);

		$queries = array_merge
		(
			[
				"response_type" => "code id_token",
				"client_id" => crow_config::get("auth.provider.apple.client_id"),
				"redirect_uri" => $redirect_uri,
				"scope" => crow_config::get("auth.provider.apple.scope"),
				"response_mode" => "form_post",
				"state" => crow_utility::random_str(),
				"nonce" => crow_utility::random_str(),
				"use_popup" => false
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
		$id_token = crow_request::get("id_token", false);

		$this->_tokens = ["id_token" => $id_token];

		//	名前は一回目以降は取得不可なので空文字とする
		$jwt_row = parent::decode_jwt($id_token);
		$payload = $jwt_row["payload"];
		$this->_data = 
		[
			"sub" => $payload["sub"],
			"email" => $payload["email"],
			"name" => "",
			"picture" => "",
		];

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

	const ENDPOINT_AUTH = "https://appleid.apple.com/auth/authorize";
	const ENDPOINT_TOKEN = "https://appleid.apple.com/auth/token";

}

?>
