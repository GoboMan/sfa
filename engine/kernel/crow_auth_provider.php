<?php
/*

	外部プロバイダ経由での認証

	対応済
	- google
	- facebook
	- apple
	- amazon
	- line
	- twitter
	- yahoo

	使用例
	//	認証先へ飛ばす
	public function action_index()
	{
		//	例1)
		$instances = crow_auth_provider::instances();
		$google = $instances[crow_auth_provider::PROVIDER_GOOGLE];

		//	例2)
		$google = crow_auth_provider::create(self::PROVIDER_GOOGLE);

		//	例3)
		$google = crow_auth_provider::google();

		//	認証URLへリダイレクト
		$oauth_url = $google->create_url();
		header("Location: ".$oauth_url);
		exit;
	}

	//	コールバックアクションでうけとったパラメータから
	//	外部接続してさらに値を取得してくる
	public function action_auth_provider_callback()
	{
		$google = crow_auth_provider::create(self::PROVIDER_GOOGLE);
		$provider_user_id = $google->get_id();
		$provider_email = $google->get_email();
		$provider_user_name = $st->get_name();
	}

*/
class crow_auth_provider
{
	//--------------------------------------------------------------------------
	//	インスタンスリスト返却
	//--------------------------------------------------------------------------
	public static function instances()
	{
		foreach( self::get_enable_providers() as $provider )
		{
			if( isset(self::$m_instances[$provider]) === true ) continue;

			$class_name = "crow_auth_provider_".$provider;
			$path = CROW_PATH."engine/kernel/auth_ext/".$class_name.".php";
			if( file_exists($path) === false ) continue;
			require_once($path);
			self::$m_instances[$provider] = new $class_name();
		}
		return self::$m_instances;
	}

	//--------------------------------------------------------------------------
	//	インスタンス作成
	//--------------------------------------------------------------------------
	public static function create( $provider_ = "" )
	{
		if( in_array($provider_, self::get_enable_providers()) === false ) return false;
		if( isset(self::$m_instances[$provider_]) === true )
			return self::$m_instances[$provider_];

		$class_name = "crow_auth_provider_".$provider_;
		$path = CROW_PATH."engine/kernel/auth_ext/".$class_name.".php";
		if( file_exists($path) === false ) return false;
		require_once(CROW_PATH."engine/kernel/auth_ext/".$class_name.".php");
		self::$m_instances[$provider_] = new $class_name();
		return self::$m_instances[$provider_];
	}

	//--------------------------------------------------------------------------
	//	有効な外接プロバイダリストの取得
	//--------------------------------------------------------------------------
	public static function get_enable_providers()
	{
		return explode(",", crow_config::get("auth.provider.providers"));
	}

	//--------------------------------------------------------------------------
	//	JWTデコード
	//--------------------------------------------------------------------------
	public static function decode_jwt( $jwt_ )
	{
		$jwt_row = explode(".", $jwt_);
		if( count($jwt_row)!==3 ) return false;

		$ret = [];
		foreach( $jwt_row as $i => $jwt_comp )
		{
			$jwt_comp = str_replace(["-","_"], ["+","/"], $jwt_comp);
			$result = strlen($jwt_comp) % 4;
			$jwt_comp .= str_repeat("=", ($result > 0 ? 4 - $result : 0));
			$val = base64_decode($jwt_comp, true);
			$ret[] = $val;
		}

		$decode_row =
		[
			"header" => json_decode($ret[0], true),
			"payload" => json_decode($ret[1], true),
			"signature" => json_decode($ret[2], true),
		];
		return $decode_row;
	}

	//--------------------------------------------------------------------------
	//	エイリアス: Google
	//--------------------------------------------------------------------------
	public static function google()
	{
		return self::create(self::PROVIDER_GOOGLE);
	}

	//--------------------------------------------------------------------------
	//	エイリアス: Facebook
	//--------------------------------------------------------------------------
	public static function facebook()
	{
		return self::create(self::PROVIDER_FACEBOOK);
	}

	//--------------------------------------------------------------------------
	//	エイリアス: Apple
	//--------------------------------------------------------------------------
	public static function apple()
	{
		return self::create(self::PROVIDER_APPLE);
	}

	//--------------------------------------------------------------------------
	//	エイリアス: Amazon
	//--------------------------------------------------------------------------
	public static function amazon()
	{
		return self::create(self::PROVIDER_AMAZON);
	}

	//--------------------------------------------------------------------------
	//	エイリアス: LINE
	//--------------------------------------------------------------------------
	public static function line()
	{
		return self::create(self::PROVIDER_LINE);
	}

	//--------------------------------------------------------------------------
	//	エイリアス: Twitter
	//--------------------------------------------------------------------------
	public static function twitter()
	{
		return self::create(self::PROVIDER_TWITTER);
	}

	//--------------------------------------------------------------------------
	//	エイリアス: Yahoo
	//--------------------------------------------------------------------------
	public static function yahoo()
	{
		return self::create(self::PROVIDER_YAHOO);
	}

	private static $m_instances = [];

	const PROVIDER_GOOGLE = "google";
	const PROVIDER_FACEBOOK = "facebook";
	const PROVIDER_APPLE = "apple";
	const PROVIDER_AMAZON = "amazon";
	const PROVIDER_LINE = "line";
	const PROVIDER_TWITTER = "twitter";
	const PROVIDER_YAHOO = "yahoo";

	//	Cognitoに登録するプロバイダ名
	const COGNITO_PROVIDERS =
	[
		self::PROVIDER_GOOGLE => "Google",
		self::PROVIDER_FACEBOOK => "Facebook",
		self::PROVIDER_APPLE => "SignInWithApple",
		self::PROVIDER_AMAZON => "LoginWithAmazon",
		self::PROVIDER_LINE => "LINE",
		self::PROVIDER_TWITTER => "Twitter",
		self::PROVIDER_YAHOO => "Yahoo",
	];
}

?>
