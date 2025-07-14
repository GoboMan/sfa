<?php
/*

	AWS Cognito コアメソッド群

	awsのアクセス設定とターゲット名の関係については、
	crow_storage_s3 のコメントを参照

	----------------------------------------------------------------------------
	インタフェース
	----------------------------------------------------------------------------

	・ユーザ登録/サインイン
		signup /admin_create_user		: ユーザ作成
		confirm_signup					: メール、電話に送信された認証コードの検証
		resend_confirmation_code		: 認証コードの再送
		initial_auth					: サインイン

	・パスワード変更
		forgot_password					: パスワードリセット
		change_password					: パスワード変更

	・その他メソッド一覧
		create							: インスタンス生成
		get_raw_handle					: Cognito直操作ハンドル取得

		//	API
		list_users						: ユーザ一覧
		list_users_in_group				: グループ内ユーザ一覧
		change_password					: ユーザパスワード変更
		forgot_password					: ユーザパスワード忘れ時の検証コード発行
		confirm_forgot_password			: パスワードリセット時検証コード発行
		resend_confirmation_code		: ユーザ検証コードの再送
		revoke_token					: ユーザアクセストークン破棄
		associate_software_token		: ワンタイムコード生成
		verify_software_token			: ソフトウェアトークン認証

		//	トークン利用API
		signup							: ユーザ登録
		confirm_signup					: ユーザ検証コード確認
		get_user_attribute_verification_code : ユーザ検証コード送信先の取得
		verify_user_attribute			: ユーザ属性検証
		get_user						: ユーザ情報取得
		delete_user						: ユーザ削除
		initiate_auth					: ユーザサインイン
		global_signout					: ユーザサインアウト
		update_user_attributes			: ユーザ属性追加・更新
		delete_user_attributes			: ユーザ属性削除
		list_devices					: ユーザ端末一覧取得
		get_device						: ユーザ端末情報取得
		confirm_device					: ユーザ端末追跡確認
		forget_device					: ユーザ端末追跡除外
		set_user_settings				: ユーザ設定の上書き

		//	シークレットキー利用API
		admin_create_user				: ユーザ登録
		admin_get_user					: ユーザ取得
		admin_delete_user				: ユーザ削除
		admin_update_user_attributes	: ユーザ属性追加・更新
		admin_delete_user_attributes	: ユーザ属性削除
		admin_change_user_enabled		: admin_enable_user/admin_disable_userをまとめたメソッド
		admin_enable_user				: ユーザ有効化
		admin_disable_user				: ユーザ無効化
		admin_confirm_signup			: ユーザ認証
		admin_reset_user_password		: ユーザパスワードリセット
		admin_set_user_password			: ユーザパスワード任意設定
		admin_initiate_auth				: ユーザサインイン
		admin_auth_with_refresh_token	: ユーザーサインイン(リフレッシュトークン)
		admin_auth_with_srp				: ユーザーサインイン(SecureRemotePassword)
		admin_auth_with_password		: ユーザーサインイン(ユーザー名とパスワード)
		admin_user_global_signout		: ユーザサインアウト
		admin_list_devices				: ユーザ端末一覧取得
		admin_get_device				: ユーザ端末情報取得
		admin_forget_device				: ユーザ端末削除
		admin_update_device_status		: ユーザ端末有効・無効化
		admin_set_user_settings			: ユーザ設定の上書き
		admin_link_provider_for_user	: ユーザログインプロバイダー紐づけ登録
		admin_disable_provider_for_user	: ユーザログインプロバイダー紐づけ解除
		admin_list_user_auth_events		: ユーザ認証操作履歴取得

		//	データ整形,バリデーション,エラー処理
		create_secret_hash				: 指定のシークレットハッシュの生成
		format_user_attribute			: 連想配列からAPIの指定形式への変換
		format_user_info				: Cognitoユーザ情報を整形して返却
		get_msg_from_exception			: Exceptinoのエラーコードから日本語エラーメッセージ返却
		reset_errors					: エラーリセット
		get_errors						: エラー取得
		get_last_error					: 最終エラー取得
		push_errors						: エラー追加

	・未対応 Cognito API
		//	ユーザプール系
		CreateUserPool,DeleteUserPool,UpdateUserPool
		//	ユーザプールの接続クライアント系
		CreateUserPoolClient,DeleteUserPoolClient,ListUserPoolClients,DescribeUserPoolClient,UpdateUserPoolClient
		//	ユーザプールドメイン系
		CreateUserPoolDomain,DeleteUserPoolDomain,DescribeUserPoolDomain,UpdateUserPoolDomain,
		//	属性
		AddCustomAttributes
		//	外部接続系
		ListIdentityProviders,CreateIdentityProvider,DeleteIdentityProvider,UpdateIdentityProvider,GetIdentityProviderByIdentifier
		//	ユーザグループ系
		ListGroups,CreateGroup,DeleteGroup,UpdateGroup
		//	リソースサーバー系
		CreateResourceServer,DeleteResourceServer,UpdateResourceServer,DescribeResourceServer,ListResourceServers
		//	ユーザインポート系
		CreateUserImportJob,ListUserImpotJobs,StartUserImportJob,StopUserImportJob,GetCSVHeader
		//	タグ系
		UntagResource,TagResource,ListTagsForResource
		//	MFA関連系
		SetUserMFAPreference,AdminSetUserMFAPreference,GetUserPoolMfaConfig,SetUserPoolMfaConfig
		//	その他
		GetUICustomization
		SetRiskConfiguration
		RespondToAuthChallenge
		AdminRespondToAuthChallenge
		AdminUpdateAuthEventFeedback
		GetSigningCertificate
		AssociateSoftwareToken

	メールアドレス変更はCognito側の仕様不備を調整するためにLambdaも設定する必要がある
	また、Lambdaで送信メールの内容に独自の認証URLを載せる。(メッセージングのメール設定はリンクではなくコードにする)

*/
require_once(CROW_PATH."engine/vendor/autoload.php");
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CognitoIdentity\CognitoIdentityClient;

class crow_cognito_core
{
	//--------------------------------------------------------------------------
	//	インスタンス作成
	//--------------------------------------------------------------------------
	public static function create( $target_ = false )
	{
		$target = $target_ !== false ? $target_ : "default";
		$inst = new self();
		$inst->initialize($target);
		return $inst;
	}

	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	private function initialize( $target_ )
	{
		$this->m_handle = new CognitoIdentityProviderClient(
		[
			'credentials'	=>
			[
				'key'		=> crow_config::get('aws.'.$target_.'.key'),
				'secret'	=> crow_config::get('aws.'.$target_.'.secret'),
			],
			'region'		=> crow_config::get('aws.'.$target_.'.region'),
			'version'		=> crow_config::get('aws.'.$target_.'.version'),
		]);

		$this->m_handle_identity = new CognitoIdentityClient(
		[
			'credentials'	=>
			[
				'key'		=> crow_config::get('aws.'.$target_.'.key'),
				'secret'	=> crow_config::get('aws.'.$target_.'.secret'),
			],
			'region'		=> crow_config::get('aws.'.$target_.'.region'),
			'version'		=> crow_config::get('aws.'.$target_.'.version'),
		]);

		$this->m_target = $target_;
		$this->m_user_pool_id			= crow_config::get('aws.'.$target_.'.cognito.user_pool_id');
		$this->m_client_id				= crow_config::get('aws.'.$target_.'.cognito.client_id');
		$this->m_client_secret			= crow_config::get('aws.'.$target_.'.cognito.client_secret');
		$this->m_endpoint				= crow_config::get('aws.'.$target_.'.cognito.endpoint');
		$this->m_signin_redirect_uri	= crow_config::get('aws.'.$target_.'.cognito.signin_redirect_uri');
		$this->m_signout_redirect_uri	= crow_config::get('aws.'.$target_.'.cognito.signout_redirect_uri');
		$this->m_crypto_key				= crow_config::get('auth.mail.cryptkey');
	}

	//--------------------------------------------------------------------------
	//	生ハンドル取得
	//--------------------------------------------------------------------------
	public function get_raw_handle()
	{
		return $this->m_handle;
	}

	//--------------------------------------------------------------------------
	//	シークレットハッシュ利用の有無
	//--------------------------------------------------------------------------
	public function use_secret_hash()
	{
		return $this->m_client_secret !== "";
	}

	//--------------------------------------------------------------------------
	//	シークレットハッシュ生成
	//
	//	https://docs.aws.amazon.com/ja_jp/cognito/latest/developerguide/signing-up-users-in-your-app.html#cognito-user-pools-computing-secret-hash
	//	Base64 ( HMAC_SHA256 ( "Client Secret Key", "Username" + "Client Id" ) )
	//	https://aws.amazon.com/jp/premiumsupport/knowledge-center/cognito-unable-to-verify-secret-hash/
	//--------------------------------------------------------------------------
	public function create_secret_hash( $username_ )
	{
		$seed = $username_.$this->m_client_id;
		$hash = hash_hmac("sha256", $seed, $this->m_client_secret, true);
		return base64_encode($hash);
	}

	//--------------------------------------------------------------------------
	//	ユーザ一覧取得
	//	IAM : cognito-idp:ListUsers
	//	https://docs.aws.amazon.com/ja_jp/cognito-user-identity-pools/latest/APIReference/API_ListUsers.html
	//	request
	//	{
	//		AttributesToGet	: 1~60個
	//		Filter			: 1~256文字(<AttributeName><Filter-Type<=,^=>><AttributeValue>, familty_name=\"xxx\",given_name^=\"xxx\",
	//		Limit			: 0~60まで
	//		PaginationToken	:
	//		UserPoolId		:
	//	}
	//	Filterに使えるのは基本の属性のみでカスタム属性は利用不可能
	//	{
	//		username (case-sensitive)
	//		email
	//		phone_number
	//		name
	//		given_name
	//		family_name
	//		preferred_username
	//		cognito:user_status (called Status in the Console) (case-insensitive)
	//		status (called Enabled in the Console) (case-sensitive)
	//		sub
	//	}
	//	https://docs.aws.amazon.com/ja_jp/cognito-user-identity-pools/latest/APIReference/API_UserType.html
	//	response
	//	[
	//		[
	//			Username => xxx,
	//			Attributes => [ [Name=>xxx, Value=>xxx], ...]
	//			UserCreateDate => [date => xxx-xx-xx xx:xx:xx.xxxxx, timezone_type=>3, timezone => UTC]
	//			UserLastModifiedDate => [date => xxx-xx-xx xx:xx:xx.xxxxx, timezone_type=>3, timezone => UTC]
	//			Enabled => 1
	//			UserStatus => UNCONFIRMED / CONFIRMED / ARCHIVED / RESET_REQUIRED / FORCE_CHANGE_PASSWORD / UNKNOWN
	//		]
	//	]
	//--------------------------------------------------------------------------
	public function list_users( $target_attrs_ = [], $filter_ = "", $limit_ = 0 )
	{
		$result_rows = [];
		$next_token = "";
		while(1)
		{
			$result = $this->list_users_once($target_attrs_, $filter_, 0, $next_token);
			if( $result === false ) return false;

			$result_rows = array_merge($result_rows, $result["users"]);
			if( $result["next_token"] === "" ) break;
			$next_token = $result["next_token"];
		}

		//	件数指定があればその分だけ返却
		if( $limit_ > 0 )
		{
			$ret = [];
			foreach( $result_rows as $k => $v )
			{
				$ret[$k] = $v;
				if( count($ret) === $limit_ ) break;
			}
			return $ret;
		}

		return $result_rows;
	}

	//--------------------------------------------------------------------------
	//	ユーザ一覧取得(ループなし)
	//--------------------------------------------------------------------------
	public function list_users_once( $target_attrs_ = [], $filter_ = "", $limit_ = 0, $next_token_ = "" )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id
			];
			if( count($target_attrs_)>0 )
				$args["AttributesToGet"] = $target_attrs_;
			if( $filter_ !== "" )
				$args["Filter"] = $filter_;
			if( $limit_ > 0 )
				$args["Limit"] = $limit_;
			if( $next_token_ !== "" )
				$args["PaginationToken"] = $next_token_;

			$result = $this->m_handle->listUsers($args);
			return
			[
				"users" => $this->format_user_info($result["Users"], "list_users"),
				"next_token" => (isset($result["PaginationToken"]) === true ? $result["PaginationToken"] : ""),
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザ一覧取得(条件オブジェクトわたしてlist_users)
	//	crow_cognito_cond インスタンス
	//	例)
	//	$obj = crow_cognito_cond::create_list_users_cond()->where("attr", "val");
	//	$obj = crow_cognito_cond::create_list_users_cond()->where("attr", "=", "val");
	//	$obj = crow_cognito_cond::create_list_users_cond()->where("attr", "^=", "val");
	//	$obj = crow_cognito_cond::create_list_users_cond()
	//			->where("attr", "val")
	//			->where_status("=", STATUS_XXX);
	//
	//	$cognito_core->list_users_from_cond($obj);
	//--------------------------------------------------------------------------
	public function list_users_from_cond( /* crow_cognito_cond::create_list_users_cond */ $cond_obj_ )
	{
		$params = $cond_obj_->build();
		$rows = $this->list_users(
			$params["target"],
			$params["filter"],
			$params["limit"]
		);
		if( is_array($rows) === false ) return false;
		if( $params["status"] == "" ) return $rows;
		if( in_array($params["status_cond"], ["=", "!="]) ) return false;

		$ret = [];
		foreach( $rows as $key => $row )
		{
			if( $row["status_cond"] == "="
				&& $row["status"] == $params["status"]
			){
				$ret[$key] = $row;
			}
			else if( $row["status_cond"] == "!="
				&& $row["status"] !== $params["status"]
			)
			{
				$ret[$key] = $row;
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	検索ユーザーを1件取得
	//--------------------------------------------------------------------------
	public function get_user_from_cond( /* crow_cognito_cond::create_list_users_cond */ $cond_obj_ )
	{
		$rows = $this->list_users_from_cond($cond_obj_);
		if( $rows === false ) return false;
		if( count($rows) <= 0 ) return false;
		return reset($rows);
	}

	//--------------------------------------------------------------------------
	//	グループ内ユーザ取得
	//--------------------------------------------------------------------------
	public function list_users_in_group( $group_name_, $limit_ = 0 )
	{
		$result_rows = [];
		$next_token = "";
		while(1)
		{
			$result = $this->list_users_in_group_once($group_name_, 0, $next_token);
			if( $result === false ) return false;

			$result_rows = array_merge($result_rows, $result);
			if( $result["next_token"] == "" ) break;

			$next_token = $result["next_token"];
		}

		if( $limit_ > 0 )
		{
			$ret = [];
			foreach( $result_rows as $k => $v )
			{
				$ret[$k] = $v;
				if( count($ret) == $limit_ ) break;
			}
			return $ret;
		}

		return $result_rows;
	}

	//--------------------------------------------------------------------------
	//	グループ内ユーザ取得(ループなし)
	//--------------------------------------------------------------------------
	public function list_users_in_group_once( $group_name_, $limit_ = 0, $next_token_ = "" )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"GroupName" => $group_name_,
			];
			if( $limit_ > 0 )
				$args["Limit"] = $limit_;
			if( $next_token_ !== "" )
				$args["NextToken"] = $next_token_;

			$result = $this->m_handle->listUsersInGroup($args);
			return
			[
				"users" => $this->format_user_info($result["Users"], "list_users"),
				"next_token" => (isset($result["NextToken"]) === true ? $result["NextToken"] : ""),
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	サインアップ
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#signup
	//	username : 重複不可能
	//--------------------------------------------------------------------------
	public function signup( $username_, $password_, $attrs_, $client_meta_data_ = false )
	{
		try
		{
			$attributes = $this->attributes_to_dimensional_array($attrs_);

			$args =
			[
				"ClientId" => $this->m_client_id,
				"Password" => $password_,
				"UserAttributes" => $attributes,
				"Username" => $username_,
			];

			//	ClientSecretをアプリケーションクライアントで利用する設定の場合には
			//	パラメータに追加する
			if( $this->use_secret_hash() )
			{
				$args["SecretHash"] = $this->create_secret_hash($username_);
			}
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->signUp($args);
			return
			[
				"UserConfirmed" => $result["UserConfirmed"],
				"CodeDeliveryDetails" => $result["CodeDeliveryDetails"],
				"UserSub" => $result["UserSub"],
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage()."\n".$username_);
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	サインアップ後のユーザ検証コード確認
	//--------------------------------------------------------------------------
	public function confirm_signup( $username_, $confirmation_code_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"ClientId" => $this->m_client_id,
				"Username" => $username_,
				"ConfirmationCode" => $confirmation_code_,
			];
			if(  $this->use_secret_hash() )
			{
				$args["SecretHash"] = $this->create_secret_hash($username_);
			}
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$this->m_handle->confirmSignUp($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザの検証媒体の取得
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#getuserattributeverificationcode
	//	返却のDeliveryMediumはSMS|EMAIL
	//--------------------------------------------------------------------------
	public function get_user_attribute_verification_code( $access_token_, $attr_name_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"AttributeName" => $attr_name_,
			];
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->getUserAttributeVerificationCode($args);
			return $result["CodeDeliveryDetails"];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザの認証方法の属性の検証実行
	//--------------------------------------------------------------------------
	public function verify_user_attribute( $access_token_, $attr_name_, $code_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"AttributeName" => $attr_name_,
				"Code" => $code_,
			];
			$result = $this->m_handle->verifyUserAttribute($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザ情報取得
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#getuser
	//--------------------------------------------------------------------------
	public function get_user( $access_token_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
			];
			$result = $this->m_handle->getUser($args);
			$data = $this->format_user_info([$result], "get_user");
			return reset($data);
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザ削除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#deleteuser
	//--------------------------------------------------------------------------
	public function delete_user( $access_token_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
			];
			$result = $this->m_handle->deleteUser($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ログイン
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#initiateauth
	//--------------------------------------------------------------------------
	public function initiate_auth( $auth_flow_, $auth_params_, $client_meta_data_ = false )
	{
		try
		{
			if( isset($auth_params_["SECRET_HASH"]) === false )
			{
				if( $this->use_secret_hash() )
				{
					$auth_params_["SECRET_HASH"] = $this->create_secret_hash($auth_params_["USERNAME"]);
				}
			}

			$args =
			[
				"ClientId" => $this->m_client_id,
				"UserPoolId" => $this->m_user_pool_id,
				"AuthFlow" => $auth_flow_,
				"AuthParameters" => $auth_params_
			];
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->initiateAuth($args);
			if( count($result["ChallengeParameters"])>0 )
			{
				$this->push_errors(print_r($result["ChallengeParameters"],1));
				crow_log::warning("Invalid AuthFlow - ".print_r($result["ChallengeParameters"],1));
				return false;
			}
			return
			[
				"ChallengeName" => $result["ChallengeName"],
				"AuthenticationResult" => $result["AuthenticationResult"],
				"Session" => $result["Session"],
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	サインイン(リフレッシュトークン)
	//	initiate_authのラッパー。AccessTokenとIdTokenをリフレッシュする
	//--------------------------------------------------------------------------
	public function auth_with_refresh_token( $username_, $refresh_token_ )
	{
		$args =
		[
			"USERNAME" => $username_,
			"REFRESH_TOKEN" => $refresh_token_,
		];
		return $this->initiate_auth(self::FLOW_REFRESH_TOKEN_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	サインイン(ユーザ名とSRP_A, SRP(SecureRemotePassword))
	//	initiate_authのラッパー
	//--------------------------------------------------------------------------
	public function auth_with_srp( $username_, $srp_a )
	{
		$args =
		[
			"USERNAME" => $username_,
			"SRP_A" => $srp_a,
		];
		return $this->initiate_auth(self::FLOW_USER_SRP_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	サインイン(ユーザ名とSRP_A, SRP(SecureRemotePassword))
	//	admin_initiate_authのラッパー
	//--------------------------------------------------------------------------
	public function auth_with_password( $username_, $password_ )
	{
		$args =
		[
			"USERNAME" => $username_,
			"PASSWORD" => $password_,
		];
		return $this->initiate_auth(self::FLOW_USER_PASSWORD_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	サインイン(メールアドレス)
	//	initiate_authのラッパー
	//	※クライアントシークレットありの場合ecret_hash生成に結局usernameの指定が必要なため適さない
	//--------------------------------------------------------------------------
	public function auth_with_mail_password( $mail_, $password_, $username_ = false )
	{
		$args =
		[
			"USERNAME" => $mail_,
			"PASSWORD" => $password_,
		];
		if( $this->use_secret_hash() )
		{
			$args["SECRET_HASH"] = $this->create_secret_hash($username_);
		}
		return $this->initiate_auth(self::FLOW_USER_PASSWORD_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	サインイン(電話番号)
	//	initiate_authのラッパー
	//	※クライアントシークレットありの場合ecret_hash生成に結局usernameの指定が必要なため適さない
	//--------------------------------------------------------------------------
	public function auth_with_telno_password( $telno_, $password_, $username_ = false )
	{
		$args =
		[
			"USERNAME" => $telno_,
			"PASSWORD" => $password_,
		];
		if( $this->use_secret_hash() )
		{
			$args["SECRET_HASH"] = $this->create_secret_hash($username_);
		}
		return $this->initiate_auth(self::FLOW_USER_PASSWORD_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	すべてのサインアウト
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#globalsignout
	//--------------------------------------------------------------------------
	public function global_signout( $access_token_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
			];
			$this->m_handle->globalSignOut($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザパスワード変更
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#changepassword
	//	外部プロバイダ認証経由の場合にはトークンのスコープの問題で利用できない
	//--------------------------------------------------------------------------
	public function change_password( $access_token_, $pwd_old_, $pwd_new_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"PreviousPassword" => $pwd_old_,
				"ProposedPassword" => $pwd_new_,
			];
			$this->m_handle->changePassword($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザパスワード忘れ時用の検証コードの発行
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#forgotpassword
	//--------------------------------------------------------------------------
	public function forgot_password( $username_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"ClientId" => $this->m_client_id,
				"Username" => $username_,
			];
			if( $this->use_secret_hash() )
			{
				$args["SecretHash"] = $this->create_secret_hash($username_);
			}
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->forgotPassword($args);
			return $result["CodeDeliveryDetails"];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	パスワードリセット時発行の検証コード確認とパスワード入力
	//--------------------------------------------------------------------------
	public function confirm_forgot_password( $username_, $password_, $confirmation_code_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"ClientId" => $this->m_client_id,
				"Username" => $username_,
				"ConfirmationCode" => $confirmation_code_,
				"Password" => $password_,
			];
			if( $this->use_secret_hash() )
			{
				$args["SecretHash"] = $this->create_secret_hash($username_);
			}
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->confirmForgotPassword($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	確認コード再送
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#resendconfirmationcode
	//--------------------------------------------------------------------------
	public function resend_confirmation_code( $username_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"ClientId" => $this->m_client_id,
				"Username" => $username_,
			];
			if( $this->use_secret_hash() )
			{
				$args["SecretHash"] = $this->create_secret_hash($username_);
			}
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->resendConfirmationCode($args);
			return $result["CodeDeliveryDetails"];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	リフレッシュトークンから生成したアクセストークン破棄
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#revoketoken
	//--------------------------------------------------------------------------
	public function revoke_token( $refresh_token_ )
	{
		try
		{
			$args =
			[
				"ClientId" => $this->m_client_id,
				"ClientSecret" => $this->m_client_secret,
				"Token" => $refresh_token_,
			];
			$this->m_handle->revokeToken($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ワンタイムコード生成
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#associatesoftwaretoken
	//--------------------------------------------------------------------------
	public function associate_software_token( $params_ )
	{
		try
		{
			$args = [];
			if( isset($params_["token"]) === true )
			{
				$args["AccessToken"] = $params_["token"];
			}
			else if( isset($params_["session"]) === true )
			{
				$args["Session"] = $params_["session"];
			}
			$result = $this->m_handle->associateSoftwareToken($args);
			return $result;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ワンタイムコード生成(アクセストークン)
	//--------------------------------------------------------------------------
	public function create_token_with_access_token( $access_token_ )
	{
		return $this->associate_software_token(["token"=>$access_token_]);
	}

	//--------------------------------------------------------------------------
	//	ワンタイムコード生成(セッション)
	//--------------------------------------------------------------------------
	public function create__with_session( $session_ )
	{
		return $this->associate_software_token(["session"=>$session_]);
	}

	//--------------------------------------------------------------------------
	//	ワンタイムコード認証
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#verifysoftwaretoken
	//	usercode : AssocicateSoftwareTokenで生成したワンタイムコード
	//--------------------------------------------------------------------------
	public function verify_software_token( $usercode_, $params_ )
	{
		try
		{
			$args =
			[
				"Usercode" => $usercode_
			];
			if( isset($params_["token"]) === true )
			{
				$args["AccessToken"] = $params_["token"];
			}
			else if( isset($params_["session"]) === true )
			{
				$args["Session"] = $params_["session"];
			}
			$result = $this->m_handle->verifySoftwareToken($args);
			return $result;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ワンタイムコード認証(アクセストークン)
	//--------------------------------------------------------------------------
	public function veirfy_token_with_access_token( $access_token_ )
	{
		return $this->verify_software_token(["token"=>$access_token_]);
	}

	//--------------------------------------------------------------------------
	//	ワンタイムコード生成(セッション)
	//--------------------------------------------------------------------------
	public function verify_token_with_session( $session_ )
	{
		return $this->verify_software_token(["session"=>$session_]);
	}

	//--------------------------------------------------------------------------
	//	ユーザ属性更新
	//--------------------------------------------------------------------------
	public function update_user_attributes( $access_token_, $attrs_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"UserAttributes" => $this->attributes_to_dimensional_array($attrs_),
			];
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->updateUserAttributes($args);
			return $result["CodeDeliveryDetailsList"];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザ属性削除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#deleteuserattributes
	//--------------------------------------------------------------------------
	public function delete_user_attributes( $access_token_, $attr_names_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"UserAttributeNames" => $attr_names_,
			];
			$this->m_handle->DeleteUserAttributes($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	端末一覧取得
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#listdevices
	//--------------------------------------------------------------------------
	public function list_devices( $access_token_, $limit_ = 0 )
	{
		$result_rows = [];
		$next_token = "";
		while(1)
		{
			$result = $this->list_devices_once($access_token_, 0, $next_token);
			if( $result === false ) return false;

			$result_rows = array_merge($result_rows, $result);
			if( $result["next_token"] == "" ) break;

			$next_token = $result["next_token"];
		}

		if( $limit_ > 0 )
		{
			$ret = [];
			foreach( $result_rows as $k => $v )
			{
				$ret[$k] = $v;
				if( count($ret) == $limit_ ) break;
			}
			return $ret;
		}

		return $result_rows;
	}

	//--------------------------------------------------------------------------
	//	端末一覧取得
	//--------------------------------------------------------------------------
	public function list_devices_once( $access_token_, $limit_ = 0, $next_token_ = "" )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
			];
			if( $limit_ > 0 )
			{
				$args["Limit"] = $limit_;
			}
			if( $next_token_ !== "" )
			{
				$args["PaginationToken"] = $next_token_;
			}
			$result = $this->m_handle->listDevices($args);
			return
			[
				"devices" => $this->format_device_info($result["Devices"]),
				"next_token" => (isset($result["PaginationToken"]) === true ? $result["PaginationToken"] : "")
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	端末情報取得
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#getdevice
	//--------------------------------------------------------------------------
	public function get_device( $access_token_, $device_key_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"DeviceKey" => $device_key_,
			];
			$result = $this->m_handle->getDevice($args);
			return $result["Device"];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	端末追跡の確認
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#confirmdevice
	//--------------------------------------------------------------------------
	public function confirm_device( $access_token_, $device_key_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"DeviceKey" => $device_key_,
			];
			$result = $this->m_handle->confirmDevice($args);
			return $result["UserConfirmationNecessary"];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	端末情報削除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#forgetdevice
	//--------------------------------------------------------------------------
	public function forget_device( $access_token_, $device_key_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"DeviceKey" => $deviec_key_,
			];
			$result = $this->m_handle->forgetDevice($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	端末状態の更新
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#updatedevicestatus
	//--------------------------------------------------------------------------
	public function update_device_status( $access_token_, $device_key_, $status_ )
	{
		try
		{
			$result = $this->m_handle->UpdateDeviceStatus(
			[
				"AccessToken" => $access_token_,
				"DeviceKey" => $device_key_,
				"DeviceRememberedStatus" => $status_,
			]);
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザ設定の上書き
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#setusersettings
	//--------------------------------------------------------------------------
	public function set_user_settings( $access_token_, $delivery_medium_ )
	{
		try
		{
			$args =
			[
				"AccessToken" => $access_token_,
				"MFAOptions" =>
				[
					"AttributeName" => "phone_number",
					"DeliveryMedium" => $delivery_medium_,
				]
			];
			$this->m_handle->setUserSettings($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	@AdminMethods
	//	UserPoolId指定で実行する
	//--------------------------------------------------------------------------

	//--------------------------------------------------------------------------
	//	Admin: ユーザ登録
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admincreateuser
	//--------------------------------------------------------------------------
	public function admin_create_user( $username_, $attrs_, $password_ = false, $client_meta_data_ = false, $is_mail_send_ = false )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			if( count($attrs_)>0 )
			{
				$args["UserAttributes"] = $this->attributes_to_dimensional_array($attrs_);
			}
			if( $password_ !== false )
			{
				$args["TemporaryPassword"] = $password_;
			}
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			if( $is_mail_send_ === false )
			{
				$args["MessageAction"] = "SUPPRESS";
			}

			$result = $this->m_handle->AdminCreateUser($args);
			return $this->format_user_info([$result["User"]], "admin_create_user");
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	管理権限:ユーザ取得
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admingetuser
	//--------------------------------------------------------------------------
	public function admin_get_user( $username_ )
	{
		try
		{
			$args =
			[
				"Username" => $username_,
				"UserPoolId" => $this->m_user_pool_id,
			];
			$result = $this->m_handle->adminGetUser($args);
			$data = $this->format_user_info([$result], "admin_get_user");
			return reset($data);
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin:ユーザ削除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindeleteuser
	//--------------------------------------------------------------------------
	public function admin_delete_user( $username_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			$this->m_handle->adminDeleteUser($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ属性更新
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminupdateuserattributes
	//--------------------------------------------------------------------------
	public function admin_update_user_attributes( $username_, $attrs_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"UserAttributes" => $this->attributes_to_dimensional_array($attrs_),
			];
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$this->m_handle->AdminUpdateUserAttributes($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ属性削除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindeleteuserattributes
	//--------------------------------------------------------------------------
	public function admin_delete_user_attributes( $username_, $attr_names_ )
	{
		try
		{
			if( count($attr_names_)<=0 )
			{
				$this->push_errors("削除する属性がありません。");
				crow_log::warning("AdminDeleteUserAttributes - no target attributes");
				return false;
			}

			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"UserAttributeNames" => $attr_names_,
			];
			$this->m_handle->AdminDeleteUserAttributes($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ有効・無効変更
	//--------------------------------------------------------------------------
	public function admin_change_user_enabled( $username_, $enabled_ )
	{
		return $enabled_ ? $this->admin_enable_user($username_) : $this->admin_disable_user($username_);
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ有効化
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminenableuser
	//--------------------------------------------------------------------------
	public function admin_enable_user( $username_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			$this->m_handle->AdminEnableUser($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ無効化
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindisableuser
	//--------------------------------------------------------------------------
	public function admin_disable_user( $username_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			$this->m_handle->AdminDisableUser($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: サインアップ後のユーザの検証コードをスキップして
	//	確認ステータスの確認済みへの変更
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminconfirmsignup
	//	client_meta_data : Lambdaへのパラメータ受渡用の配列
	//--------------------------------------------------------------------------
	public function admin_confirm_signup( $username_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			if( $client_meta_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$this->m_handle->adminConfirmSignUp($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザパスワードリセット
	//	※メールまたは電話場で認証済みになっている必要がある
	//	実行すると検証コードが送信されて確認ステータスが[リセットが必要]に変更される
	//	cognitoがホストするログインページで検証コードの入力を求められる
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminresetuserpassword
	//--------------------------------------------------------------------------
	public function admin_reset_user_password( $username_, $client_meta_data_ = false )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$this->m_handle->adminResetUserPassword($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザパスワード設定
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminsetuserpassword
	//	Permanent : true/false
	//	設定したパスワードで確定か、一時パスワードで次回ログイン時に変更を強制
	//	falseの場合は確認ステータスが[パスワードを強制的に変更]になる
	//--------------------------------------------------------------------------
	public function admin_set_user_password( $username_, $password_, $permanent_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"Password" => $password_,
				"Permanent" => $permanent_,
			];
			$this->m_handle->adminSetUserPassword($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	管理権限:ユーザ認証
	//	https://docs.aws.amazon.com/ja_jp/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admininitiateauth
	//
	//	request
	//	{
	//		AuthFlow		: USER_SRP_AUTH|REFRESH_TOKEN_AUTH|REFRESH_TOKEN|CUSTOM_AUTH|USER_PASSWORD_AUTH
	//							|ADMIN_NO_SRP_AUTH|ADMIN_USER_PASSWORD_AUTH
	//		ClientId		:
	//		UserPoolId		:
	//		AuthParameters	: AuthFlowごとの適切な値
	//		ContextData		:
	//	}
	//	response
	//	{
	//		ChallengeParameters		:
	//		AuthenticationResult	:
	//		{
	//			AccessToken
	//			ExpiresIn
	//			TokenType
	//			RefreshToken
	//			IdToken
	//		}
	//	}
	//--------------------------------------------------------------------------
	public function admin_initiate_auth( $auth_flow_, $auth_params_, $client_meta_data_ = false )
	{
		try
		{
			if( isset($auth_params_["SECRET_HASH"]) === false )
			{
				if( $this->use_secret_hash() )
				{
					$auth_params_["SECRET_HASH"] = $this->create_secret_hash($auth_params_["USERNAME"]);
				}
			}
			$args =
			[
				"ClientId" => $this->m_client_id,
				"UserPoolId" => $this->m_user_pool_id,
				"AuthFlow" => $auth_flow_,
				"AuthParameters" => $auth_params_
			];
			if( $client_meta_data_ !== false )
			{
				$args["ClientMetadata"] = $client_meta_data_;
			}
			$result = $this->m_handle->adminInitiateAuth($args);
			if( count($result["ChallengeParameters"])>0 )
			{
				$this->push_errors(print_r($result["ChallengeParameters"],1));
				crow_log::warning("Invalid AuthFlow - ".print_r($result["ChallengeParameters"],1));
				return false;
			}
			return
			[
				"ChallengeName" => $result["ChallengeName"],
				"AuthenticationResult" => $result["AuthenticationResult"],
				"Session" => $result["Session"],
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin:サインイン(リフレッシュトークン)
	//	admin_initiate_authのラッパー。AccessTokenとIdTokenをリフレッシュする
	//--------------------------------------------------------------------------
	public function admin_auth_with_refresh_token( $username_, $refresh_token_ )
	{
		$args =
		[
			"USERNAME" => $username_,
			"REFRESH_TOKEN" => $refresh_token_,
		];
		return $this->admin_initiate_auth(self::FLOW_REFRESH_TOKEN_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	Admin:サインイン(ユーザ名とSRP_A, SRP(SecureRemotePassword))
	//	admin_initiate_authのラッパー
	//--------------------------------------------------------------------------
	public function admin_auth_with_srp( $username_, $srp_a )
	{
		$args =
		[
			"USERNAME" => $username_,
			"SRP_A" => $srp_a,
		];
		return $this->admin_initiate_auth(self::FLOW_USER_SRP_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	Admin:サインイン(ユーザ名)
	//	admin_initiate_authのラッパー
	//--------------------------------------------------------------------------
	public function admin_auth_with_password( $username_, $password_ )
	{
		$args =
		[
			"USERNAME" => $username_,
			"PASSWORD" => $password_,
		];
		return $this->admin_initiate_auth(self::FLOW_ADMIN_USER_PASSWORD_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	Admin:サインイン(メールアドレス)
	//	※クライアントシークレットありの場合ecret_hash生成に結局usernameの指定が必要なため適さない
	//--------------------------------------------------------------------------
	public function admin_auth_with_mail_password( $mail_, $password_, $username_ = false )
	{
		$args =
		[
			"USERNAME" => $mail_,
			"PASSWORD" => $password_,
		];
		if( $this->use_secret_hash() )
		{
			$args["SECRET_HASH"] = $this->create_secret_hash($username_);
		}
		return $this->admin_initiate_auth(self::FLOW_ADMIN_USER_PASSWORD_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	Admin:サインイン(電話番号)
	//	※クライアントシークレットありの場合ecret_hash生成に結局usernameの指定が必要なため適さない
	//--------------------------------------------------------------------------
	public function admin_auth_with_telno_password( $telno_, $password_, $username_ = false )
	{
		$args =
		[
			"USERNAME" => $telno_,
			"PASSWORD" => $password_,
		];
		if( $this->use_secret_hash() )
		{
			$args["SECRET_HASH"] = $this->create_secret_hash($username_);
		}
		return $this->admin_initiate_auth(self::FLOW_ADMIN_USER_PASSWORD_AUTH, $args);
	}

	//--------------------------------------------------------------------------
	//	Admin:サインイン(ユーザ名とSRP_A, SRP(SecureRemotePassword))
	//	admin_initiate_authのラッパー
	//--------------------------------------------------------------------------
	public function admin_initiate_auth_custom( $username_ )
	{
		$args =
		[
			"USERNAME" => $username_,
		];
		return $this->admin_initiate_auth("CUSTOM_AUTH", $args);
	}

	//--------------------------------------------------------------------------
	//	Admin:全サインアウト
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminglobalsignout
	//	AccessToken/RefreshTokenが無効化されてIdToken(JWT)は無効にならない
	//--------------------------------------------------------------------------
	public function admin_user_global_signout( $username_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			$this->m_handle->adminUserGlobalSignOut($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: グループのユーザ追加
	//--------------------------------------------------------------------------
	public function admin_add_user_to_group( $username_, $group_name_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"GroupName" => $group_name_,
			];
			$this->m_handle->adminAddUserToGroup($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: グループのユーザ除外
	//--------------------------------------------------------------------------
	public function admin_remove_user_from_group( $username_, $group_name_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"GroupName" => $group_name_,
			];
			$this->m_handle->adminRemoveUserFromGroup($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザの所属グループ一覧取得
	//--------------------------------------------------------------------------
	public function admin_list_groups_for_user( $username_, $limit_ = 0 )
	{
		$result_rows = [];
		$next_token = "";
		while(1)
		{
			$result = $this->admin_list_groups_for_user_once($username_, 0, $next_token);
			if( $result === false ) return false;

			$result_rows = array_merge($result_rows, $result["groups"]);
			if( $result["next_token"] == "" ) break;
			$next_token = $result["next_token"];
		}

		//	件数指定があればその分だけ返却
		if( $limit_ > 0 )
		{
			$ret = [];
			foreach( $result_rows as $k => $v )
			{
				$ret[$k] = $v;
				if( count($ret) === $limit_ ) break;
			}
			return $ret;
		}

		return $result_rows;
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザの所属グループ一覧取得(ループなし)
	//--------------------------------------------------------------------------
	public function admin_list_groups_for_user_once( $username_, $limit_ = 0, $next_token_ = "" )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			if( $limit_ > 0 )
			{
				$args["Limit"] = $limit_;
			}
			if( $next_token_ != "" )
			{
				$args["NextToken"] = $next_token_;
			}

			$result = $this->m_handle->adminRemoveUserFromGroup($args);
			return
			[
				"groups" => $result["Groups"],
				"next_token" => $result["NextToken"],
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: デバイス一覧
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminlistdevices
	//--------------------------------------------------------------------------
	public function admin_list_devices( $username_, $limit_ = 0 )
	{
		$result_rows = [];
		$next_token = "";
		while(1)
		{
			$result = $this->admin_list_devices_once($username_, 0, $next_token);
			if( $result === false ) return false;

			$result_rows = array_merge($result_rows, $result);
			if( $result["next_token"] == "" ) break;

			$next_token = $result["next_token"];
		}

		if( $limit_ > 0 )
		{
			$ret = [];
			foreach( $result_rows as $k => $v )
			{
				$ret[$k] = $v;
				if( count($ret) == $limit_ ) break;
			}
			return $ret;
		}

		return $result_rows;
	}

	//--------------------------------------------------------------------------
	//	Admin: デバイス一覧ループなし
	//--------------------------------------------------------------------------
	public function admin_list_devices_once( $username_, $limit_ = 0, $token_ = false )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			];
			if( $limit_ > 0 )
				$args["Limit"] = $limit_;
			if( $token_ !== false )
				$args["PaginationToken"] = $token_;

			$result = $this->m_handle->AdminListDevices($args);
			return
			[
				"devices" => $this->format_device_info($result["Devices"]),
				"next_token" => (isset($result["PaginationToken"]) === true ? $result["PaginationToken"] : "")
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: デバイス取得
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admingetdevice
	//--------------------------------------------------------------------------
	public function admin_get_device( $username_, $device_key_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"DeviceKey" => $device_key_,
				"Username" => $username_,
			];
			$result = $this->m_handle->AdminGetDevice($args);
			return $result;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin:端末情報削除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminforgetdevice
	//--------------------------------------------------------------------------
	public function admin_forget_device( $username_, $device_key_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"DeviceKey" => $deviec_key_,
			];
			$result = $this->m_handle->adminForgetDevice($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: デバイスステータス更新
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#AdminUpdateDeviceStatus
	//--------------------------------------------------------------------------
	public function admin_update_device_status( $username_, $device_key_, $remembered_ )
	{
		try
		{
			if( in_array($remembered_, [true,false], true) === false )
			{
				$this->push_errors("Invalid DeviceRememberedStatus - not true/false");
				crow_log::warning("Invalid DeviceRememberedStatus - not true/false");
				return false;
			}
			$status = $remembered_ === true ? "remembered" : "not_remembered";

			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"DeviceKey" => $device_key_,
				"DeviceRememberedStatus" => $status,
				"Username" => $username_,
			];
			$this->m_handle->AdminUpdateDeviceStatus($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ設定の上書き
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminsetusersettings
	//--------------------------------------------------------------------------
	public function admin_set_user_settings( $username_, $delivery_medium_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
				"MFAOptions" =>
				[
					"AttributeName" => "phone_number",
					"DeliveryMedium" => $delivery_medium_,
				]
			];
			$this->m_handle->adminSetUserSettings($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: 既存ユーザの他の外部サービスの認証情報との紐づけ
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminlinkproviderforuser
	//	Cognitoユーザを作成したうえで外部接続作成ユーザーを紐づける
	//	ひとつのCognitoユーザにつきリンクできるのは最大5件まで
	//	自他問わずユーザにすでに紐づけられている外部接続アカウント情報の場合にはエラー
	//	emailの検証済みが外れるのでアップデート必要
	//--------------------------------------------------------------------------
	public function admin_link_provider_for_user( $username_, $link_provider_name_, $link_username_ )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"DestinationUser" =>
				[
					"ProviderAttributeValue" => $username_,
					"ProviderName" => "Cognito",
				],
				"SourceUser" =>
				[
					"ProviderAttributeName" => "Cognito_Subject",
					"ProviderAttributeValue" => $link_username_,
					"ProviderName" => $link_provider_name_,
				]
			];
			$this->m_handle->adminLinkProviderForUser($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: 既存ユーザの他の外部サービスの認証情報との紐づけ解除
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindisableproviderforuser
	//	Cognito,Cognito_Subject,UsernameとするとCognitoユーザーが外部プロバイダー扱いになる
	//	link_username_: 外部プロバイダーのuserId(identitiesに含まれてる値)
	//	emailの検証済みが外れるのでアップデート必要
	//--------------------------------------------------------------------------
	public function admin_disable_provider_for_user( $provider_name_, $link_username_ )
	{
		try
		{
			if( $provider_name_ == "COGNITO" )
			{
				crow_log::warning("cannnot disable cognito user - ".$link_username_);
				return false;
			}
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"User" =>
				[
					"ProviderAttributeName" => "Cognito_Subject",
					"ProviderAttributeValue" => $link_username_,
					"ProviderName" => $provider_name_,
				]
			];
			$this->m_handle->adminDisableProviderForUser($args);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ認証イベント履歴
	//--------------------------------------------------------------------------
	public function admin_list_user_auth_events( $username_, $max_results_=0 )
	{
		$result_rows = [];
		$next_token = "";
		while(1)
		{
			$result = $this->admin_list_user_auth_events_once($username_, 0, $next_token);
			if( $result === false ) return false;

			$result_rows = array_merge($result_rows, $result["auth_events"]);
			if( $result["next_token"] == "" ) break;
			$next_token = $result["next_token"];
		}

		//	件数指定があればその分だけ返却
		if( $limit_ > 0 )
		{
			$ret = [];
			foreach( $result_rows as $k => $v )
			{
				$ret[$k] = $v;
				if( count($ret) == $limit_ ) break;
			}
			return $ret;
		}

		return $result_rows;
	}

	//--------------------------------------------------------------------------
	//	Admin: ユーザ認証イベント履歴(ループなし)
	//	管理画面でユーザプールに対しての高度なセキュリティの設定を有効化していないと利用できない
	//	https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminlistuserauthevents
	//	max_results : 何件まで履歴を返すか指定
	//	next_token : 次のページを取得するなら指定
	//--------------------------------------------------------------------------
	public function admin_list_user_auth_events_once( $username_, $max_results_ = 0, $next_token_ = "" )
	{
		try
		{
			$args =
			[
				"UserPoolId" => $this->m_user_pool_id,
				"Username" => $username_,
			//	"MaxResults" => $max_results_
			//	"NextToken" => $next_token_,
			];
			if( $max_results_ > 0 )
			{
				$args["MaxResults"] = $max_results_;
			}
			if( $next_token_ != "" )
			{
				$args["NextToken"] = $next_token_;
			}
			$result = $this->m_handle->adminListUserAuthEvents($args);
			return
			[
				"auth_events" => $result["AuthEvents"],
				"next_token" => (isset($result["NextToken"]) === true ? $result["NextToken"] : ""),
			];
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ユーザプール設定の更新
	//--------------------------------------------------------------------------
	public function update_user_pool( $settings_ )
	{
		try
		{
			$this->m_handle->UpdateUserPool($settings_);
			return true;
		}
		catch( Exception $ex_ )
		{
			$this->push_errors($this->get_msg_from_exception($ex_));
			crow_log::warning($ex_->getMessage());
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	属性のフォーマット(連想配列から二次元配列に変換)
	//--------------------------------------------------------------------------
	private function attributes_to_dimensional_array( $attr_row_ )
	{
		$ret = [];
		foreach( $attr_row_ as $k => $v )
			$ret[] = ["Name"=>$k, "Value"=>$v];

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	属性の二次元配列から連想配列への変換
	//--------------------------------------------------------------------------
	private function named_array_from_attributes( $attributes_ )
	{
		$ret = [];
		foreach( $attributes_ as $attr )
			$ret[$attr["Name"]] = $attr["Value"];

		//	外部認証の紐づけがある場合にはさらに展開する
		if( isset($ret["identities"]) === true )
		{
			$identity_rows = [];
			$identities = json_decode($ret["identities"], true);
			if( $identities !== false )
			{
				foreach( $identities as $identity )
				{
					$prov_name = $identity["providerName"];
					$identity_rows[$prov_name] = $identity;
				}
				$ret["identities"] = $identity_rows;
			}
		}

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	ユーザ情報フォーマット
	//--------------------------------------------------------------------------
	private function format_user_info( $users_, $type_ )
	{
		$ret = [];
		foreach( $users_ as $user )
		{
			$attributes_key = "";
			if( in_array($type_, ["list_users","admin_create_user"]) )
				$attributes_key = "Attributes";
			else if( in_array($type_, ["get_user","admin_get_user"]) )
				$attributes_key = "UserAttributes";

			$attributes = $this->named_array_from_attributes($user[$attributes_key]);
		//	dateが取得できないので返却に含めない
		//	$create_date = $user["UserCreateDate"]->date;
		//	$pos = strpos($create_date, ".");
		//	if( $pos !== false )
		//		$create_date = substr($create_date, 0, $pos);

		//	$update_date_obj = $user["UserLastModifiedDate"]->date;
		//	$pos = strpos($update_date, ".");
		//	if( $pos !== false )
		//		$update_date = substr($update_date, 0, $pos);

			$row =
			[
				"username" => $user["Username"],
				"attributes" => $attributes,
			//	"create_date" => $create_date,
			//	"update_date" => $update_date,
				//	enabled, statusはadmin_の場合のみ取得可能
				"enabled" => (isset($user["Enabled"]) ? $user["Enabled"] : ""),
				"status" => (isset($user["UserStatus"]) ? $user["UserStatus"] : ""),
			];
			$ret[$row["username"]] = $row;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	端末情報フォーマット
	//--------------------------------------------------------------------------
	private function format_device_info( $devices_ )
	{
		$ret = [];
		foreach( $devices_ as $device )
		{
			$attributes = $this->attributes_to_dimensional_array($device["DeviceAttributes"]);
			$row =
			[
				"attributes" => $attributes,
				"create_date" => $device["DeviceCreateDate"],
				"device_key" => $device["DeviceKey"],
				"last_authenticate_date" => $device["DeviceLastAuthenticatedDate"],
				"last_modified_date" => $device["DeviceLastModifiedDate"],
			];

			$ret[$row["device_key"]] = $row;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	JWTでコード
	//--------------------------------------------------------------------------
	public static function decode_jwt( $jwt_ )
	{
		$sections = explode(".", $jwt_);
		if( count($sections) != 3 )
		{
			crow_log::warning("Invalid JWT Strings : ".$jwt_);
			return false;
		}
		return
		[
			"header" => base64_decode($sections[0]),
			"payload" => base64_decode($sections[1]),
			"signature" => base64_decode($sections[2]),
		];
	}

	//--------------------------------------------------------------------------
	//	JWTのペイロードの取得
	//--------------------------------------------------------------------------
	public static function get_payload_from_token( $token_ )
	{
		return json_decode(self::decode_jwt($token_)["payload"], true);
	}

	//--------------------------------------------------------------------------
	//	Awsエラーの変換
	//	$ex_->getAwsErrorCode() or $ex_->getAwsErrorMessage()
	//--------------------------------------------------------------------------
	private function get_msg_from_exception( $ex_ )
	{
		$ret = "";
		if( method_exists($ex_, "getAwsErrorCode") && method_exists($ex_, "getAwsErrorMessage") )
		{
			$code = $ex_->getAwsErrorCode();
			$ret = $code.":".$ex_->getAwsErrorMessage();
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	Tokenの連想配列変換
	//--------------------------------------------------------------------------
	public static function format_tokens( $tokens_ )
	{
		return
		[
			"id_token" => $tokens_["IdToken"],
			"access_token" => $tokens_["AccessToken"],
			"refresh_token" => $tokens_["RefreshToken"],
			"expires_in" => $tokens_["ExpiresIn"],
		];
	}

	//--------------------------------------------------------------------------
	//	エンドポイント取得
	//--------------------------------------------------------------------------
	public function get_endpoint( $type_ )
	{
		switch( $type_ )
		{
			case "login"	: return $this->m_endpoint.self::ENDPOINT_LOGIN;
			case "logout"	: return $this->m_endpoint.self::ENDPOINT_LOGOUT;
			case "auth"		: return $this->m_endpoint.self::ENDPOINT_AUTH;
			case "token"	: return $this->m_endpoint.self::ENDPOINT_TOKEN;
			case "revoke"	: return $this->m_endpoint.self::ENDPOINT_REVOKE;
			case "userinfo"	: return $this->m_endpoint.self::ENDPOINT_USERINFO;
		}
	}

	//--------------------------------------------------------------------------
	//	外部認証プロバイダ取得
	//--------------------------------------------------------------------------
	public function get_auth_providers()
	{
		$auth_providers = explode(",", crow_config::get("aws.".$this->m_target.".cognito.auth_providers"));
		$map = [];
		foreach( $auth_providers as $i => $code_and_provider )
		{
			list($code, $provider) = explode(":", $code_and_provider);
			$map[$code] = $provider;
		}

		return $map;
	}

	//--------------------------------------------------------------------------
	//	外部認証URL取得
	//--------------------------------------------------------------------------
	public function get_provider_auth_urls()
	{
		$base_url = self::get_endpoint("auth");
		$base_params =
		[
			"redirect_uri" => $this->m_signin_redirect_uri,
			"response_type" => self::AUTH_RESPONSE_TYPE,
			"scope" => self::AUTH_SCOPE,
			"client_id" => $this->m_client_id,
		];

		$urls = [];
		foreach( array_keys($this->get_auth_providers()) as $provider_code )
		{
			$params = $base_params;
			$params["identity_provider"] = $provider_code;
			$url = $base_url."?".http_build_query($params);
			$urls[$provider_code]  = $url;
		}
		return $urls;
	}
	public function get_provider_auth_url( $provider_name_ )
	{
		self::get_provider_auth_urls()[$provider_name_];
	}

	//--------------------------------------------------------------------------
	//	エラーリセット
	//--------------------------------------------------------------------------
	public function reset_errors()
	{
		$this->m_errors = [];
	}

	//--------------------------------------------------------------------------
	//	すべてのエラーの取得
	//--------------------------------------------------------------------------
	public function get_errors()
	{
		return $this->m_errors;
	}

	//--------------------------------------------------------------------------
	//	最終エラー取得
	//--------------------------------------------------------------------------
	public function get_last_error()
	{
		return end($this->m_errors);
	}

	//--------------------------------------------------------------------------
	//	エラー追加
	//	exception->getAwsErrorCode()
	//	exception->getAwsErrorMessage()
	//	exception->getAwsErrorShape()
	//--------------------------------------------------------------------------
	public function push_errors( /* error1, error2, ... */ )
	{
		$errors = func_get_args();
		foreach( $errors as $error )
			$this->m_errors[] = $error;
	}

	public $m_handle = false;
	public $m_handle_identity = false;

	public $m_target = false;
	public $m_user_pool_id = false;
	public $m_client_id = false;
	public $m_client_secret = false;
	public $m_endpoint = false;
	public $m_signin_redirect_uri = false;
	public $m_signout_redirect_uri = false;
	public $m_crypto_key = false;

	private $m_errors = [];

	//	ユーザステータス
	const STATUS_UNCONFIRMED			= "UNCONFIRMED";
	const STATUS_CONFIRMED				= "CONFIRMED";
	const STATUS_ARCHIVED				= "ARCHIVED";
	const STATUS_UNKNOWN				= "UNKNOWN";
	const STATUS_RESET_REQUIRED			= "RESET_REQUIRED";
	const STATUS_FORCE_CHANGE_PASSWORD	= "FORCE_CHANGE_PASSWORD";
	const STATUS_MAP =
	[
		self::STATUS_UNCONFIRMED,
		self::STATUS_CONFIRMED,
		self::STATUS_ARCHIVED,
		self::STATUS_UNKNOWN,
		self::STATUS_RESET_REQUIRED,
		self::STATUS_FORCE_CHANGE_PASSWORD,
	];

	//	認証方法指定
	//	ADMIN_NO_SRP_AUTHは廃止でADMIN_USER_PASSWORD_AUTHを利用する
	const FLOW_USER_SRP_AUTH			= "USER_SRP_AUTH";
	const FLOW_REFRESH_TOKEN_AUTH		= "REFRESH_TOKEN_AUTH";
	const FLOW_REFRESH_TOKEN			= "REFRESH_TOKEN";
	const FLOW_CUSTOM_AUTH				= "CUSTOM_AUTH";
	//	const FLOW_ADMIN_NO_SRP_AUTH		= "ADMIN_NO_SRP_AUTH";
	const FLOW_USER_PASSWORD_AUTH		= "USER_PASSWORD_AUTH";
	const FLOW_ADMIN_USER_PASSWORD_AUTH	= "ADMIN_USER_PASSWORD_AUTH";
	const AUTH_FLOW_MAP =
	[
		self::FLOW_USER_SRP_AUTH,
		self::FLOW_REFRESH_TOKEN_AUTH,
		self::FLOW_REFRESH_TOKEN,
		self::FLOW_CUSTOM_AUTH,
	//	self::FLOW_ADMIN_NO_SRP_AUTH,
		self::FLOW_USER_PASSWORD_AUTH,
		self::FLOW_ADMIN_USER_PASSWORD_AUTH,
	];

	//	認証方法の候補
	const CHALLENGE_SMS_MFA						= "SMS_MFA";
	const CHALLENGE_PASSWORD_VERIFIER			= "PASSWORD_VERIFIER";
	const CHALLENGE_CUSTOM_CHALLENGE			= "CUSTOM_CHALLENGE";
	const CHALLENGE_DEVICE_SRP_AUTH				= "DEVICE_SRP_AUTH";
	const CHALLENGE_DEVICE_PASSWORD_VERIFIER	= "DEVICE_PASSWORD_VERIFIER";
	const CHALLENGE_NEW_PASSWORD_REQUIRED		= "NEW_PASSWORD_REQUIRED";
	const CHALLENGE_MFA_SETUP					= "MFA_SETUP";
	const CHALLENGE_NAME_MAP =
	[
		self::CHALLENGE_SMS_MFA,
		self::CHALLENGE_PASSWORD_VERIFIER,
		self::CHALLENGE_CUSTOM_CHALLENGE,
		self::CHALLENGE_DEVICE_SRP_AUTH,
		self::CHALLENGE_DEVICE_PASSWORD_VERIFIER,
		self::CHALLENGE_NEW_PASSWORD_REQUIRED,
		self::CHALLENGE_MFA_SETUP,
	];

	//	エンドポイントパス
	const ENDPOINT_LOGIN	= "/login";
	const ENDPOINT_LOGOUT	= "/logout";
	const ENDPOINT_AUTH		= "/oauth2/authorize";
	const ENDPOINT_TOKEN	= "/token";
	const ENDPOINT_REVOKE	= "/revoke";
	const ENDPOINT_USERINFO	= "/oauth2/userInfo";

	//	レスポンスタイプ
	const AUTH_RESPONSE_TYPE = "CODE";
	const AUTH_SCOPE = "openid email phone";

	const CODE_COGNITO = "cognito";
	const NAME_COGNITO = "COGNITO";
	const VALUE_COGNITO = "1";
}
?>
