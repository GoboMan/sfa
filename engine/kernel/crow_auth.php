<?php
/*

	crow標準の認証機能

	----------------------------------------------------------------------------
	config
	----------------------------------------------------------------------------
	crow_configに下記のキーがあることが条件。

		auth.privilege
			認証を通過した際に、セッションに書き込む目印となる値
			この値はロールごとに異なるように設定すること。

		auth.error.lang.nohit
			認証が失敗したとき（ID/PWの不一致）のエラーメッセージのキー（crow_msgのキー）

		auth.error.lang.name
			ログイン名が入力されていない場合のエラーメッセージのキー（crow_msgのキー）

		auth.error.lang.password
			パスワードが入力されていない場合のエラーメッセージのキー（crow_msgのキー）

	下記のキーは省略可能

		auth.db.table
			DB認証でもcognito認証でも利用する

		auth.db.login_name
			DB認証の場合に利用する。※ 詳しくは crow_auth_db を参照

		auth.db.login_password
			DB認証の場合に利用する。※ 詳しくは crow_auth_db を参照

	----------------------------------------------------------------------------
	認証種別
	----------------------------------------------------------------------------
	crow_config の auth.type で認証の種別を指定できる。
	"db"を指定した場合には、DBのテーブルを使用して認証が行われる。
	"cognito"を指定した場合には、awsのcognitoを使用して認証が行われる。
	どちらの場合にも、結果として指定テーブルのレコードを取得することが可能

	----------------------------------------------------------------------------
	利用方法
	----------------------------------------------------------------------------

	認証が必要なアクションの最初に、ログイン済みかどうかをチェックするロジックを埋め込み、
	もしログイン済みでないならログインフォームへ飛ばす。

	class module_admin_top extends crow_module
	{
		public function action_index()
		{
			//	ログイン済みかチェックして、未ログインなら飛ばす
			if( ! crow_auth::is_logined() )
			{
				crow::redirect("auth");
				return;
			}

			//	ログイン済みテーブルモデルのインスタンスを取得
			$model = crow_auth::get_logined_row();

			//	...以降通常の処理...
		}
	}

	上記で飛ばした先のログイン、ログアウト用の画面を実装する。

	class module_admin_auth extends crow_module
	{
		//	ログイン用
		public function action_index(){}
		public function action_index_post()
		{
			if( ! crow_auth::login() )
			{
				//	ログイン失敗
				crow_response::set("error", crow_auth::get_last_error());
				return;
			}
			//	成功したらデフォルトアクションへリダイレクト
			crow::redirect_default();
		}

		//	ログアウト用
		public function action_logout()
		{
			crow_auth::logout();
			crow::redirect_default();
		}
	}

	外部認証の場合
	auth.provider.callback.module/actionに次の実装をする
	public function action_xxx()
	{
		$i_provider = crow_request::get("provider");

		//	もし該当ユーザがいない場合の生成処理
		$func_create = function() ues ($i_provider)
		{
			//	ユーザー生成処理と紐づけ処理
			$inst = crow_auth_provider::create($provider_code);
			$provider_user_id = $inst->get_id(); ←sub
			$provider_email = $inst->get_email();
			$provider_username = $inst->get_name();
			if( $provider_user_name === "" )
				$provider_user_name = crow_utility::random_str(8);

			$now = time();

			$hdb = crow::get_hdb();
			$hdb->begin();

			//	DBユーザ作成
			$user_row = model_user::create();
			$password = $user_row->generate_password();

			//	連携用のカラム更新
			$user_row->auth_mail_addr = $provider_email;
			$user_row->auth_login_pw = $password;
			$user_row->auth_provider_is_origin_external = true;
			$user_row->auth_provider_mail_login_enabled = false;
			//	外部連携生成のユーザーがcognitoにログインするために暗号化して
			//	保持しておく必要がある
			$user_row->auth_cognito_login_pw = crow_auth::get_auth_instance()->encrypt($password);
			$user_row->auth_provider_last_login = $i_provider;
			$user_row->auth_mail_addr_verified = true;

			if( $user_row->check_and_save() === false )
			{
				$hdb->rollback();
				return false;
			}

			//	プロバイダ紐づけテーブルに存在するか
			$provider_sub_colname = $i_provider."_sub";

			//	連携外部アカウントデータ生成
			$user_auth_provider = model_user_auth_provider::create();
			$user_auth_provider->user_id = $user_row->user_id;
			$user_auth_provider->{$provider_sub_colname} = $provider_user_id;
			if( $user_auth_provider->check_and_save() === false )
			{
				$hdb->rollback();
				return false;
			}

			//	cognitoに生成
			if( crow_config::get("auth.type") === "cognito" )
			{
				$cognito_attributes =
				[
					crow_auth_cognito::KEY_EMAIL => $provider_email,
					crow_auth_cognito::KEY_EMAIL_VERIFIED => "true",
					crow_auth_cognito::KEY_IS_ORIGIN_EXTERNAL => "true",
					crow_auth_cognito::KEY_ENABLE_MAIL_LOGIN => "false",
				];

				$result = crow_auth::get_auth_instance()->create_user
				(
					$cognito_user_id,
					$password
					$cognito_update_attributes,
					$mail_tpl_name
				);
			}

			if( crow_auth::login_with_provider($i_provider, $user_row) === false )
			{
				$hdb->rollback();
				return false;
			}

			crow_auth::set_logined_row($user_row);
			$hdb->commit();
		};

		//	ユーザの有無でユーザ生成とログインを実行する。true/false返却
		$is_logined = crow_auth::check_and_login_provider($i_provider, $func_create);
	}


	----------------------------------------------------------------------------
	Cognitoの場合
	----------------------------------------------------------------------------
	//	外部接続・認証紐づけの管理にCognitoの機能を利用する場合
		aws.default.cognito.signin_redirect_uri		: サインイン後のアクション
		aws.default.cognito.signout_redirect_uri	：サインアウト後のアクション
		各アクションでの処理は必要であれば記述する

	外部連携の場合は戻りアクションにcognitoユーザを作る処理を入れること

*/
class crow_auth
{
	//--------------------------------------------------------------------------
	//	crow_auth の挙動を指定した認証モジュールへ切り替える
	//	本メソッドは crow_config::auth.type で指定した種別以外に接続したい場合に利用する想定で、
	//	標準では crow_config::auth.type で指定した認証種別となっている
	//	aws_target_ は、auth_type_ に "cognito" を指定した場合のみ有効となる
	//--------------------------------------------------------------------------
	public static function switch_auth_type( $auth_type_, $aws_target_ = "default" )
	{
		$inst_key = $auth_type_;
		if( $inst_key == "cognito" ) $inst_key .= "_".$aws_target_;
		if( isset(self::$m_instance_caches[$inst_key]) === false )
		{
			$class = 'crow_auth_'.$auth_type_;
			self::$m_instance_caches[$inst_key] = $class::create(['target' => $aws_target_]);
		}
		self::$m_instance = self::$m_instance_caches[$inst_key];
	}

	//--------------------------------------------------------------------------
	//	認証モジュールのインスタンス取得
	//--------------------------------------------------------------------------
	public static function get_auth_instance()
	{
		if( self::$m_instance === false )
		{
			self::switch_auth_type(crow_config::get('auth.type', 'db'));
		}
		return self::$m_instance;
	}

	//--------------------------------------------------------------------------
	//	ログイン済み？
	//--------------------------------------------------------------------------
	public static function is_logined( $privilege_ = '' )
	{
		return self::get_auth_instance()->is_logined($privilege_);
	}

	//--------------------------------------------------------------------------
	//	ログイン処理
	//--------------------------------------------------------------------------
	public static function login( $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		return self::get_auth_instance()->login(
			$privilege_, $table_name_, $param1_, $param2_);
	}

	//--------------------------------------------------------------------------
	//	ログアウト処理
	//--------------------------------------------------------------------------
	public static function logout( $privilege_ = "" )
	{
		return self::get_auth_instance()->logout($privilege_);
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードのモデルインスタンスを取得
	//--------------------------------------------------------------------------
	public static function get_logined_row( $privilege_ = "", $table_name_ = false )
	{
		return self::get_auth_instance()->get_logined_row($privilege_, $table_name_);
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードをDBから直接取得
	//--------------------------------------------------------------------------
	public static function get_logined_full_row( $privilege_ = "", $table_name_ = false )
	{
		return self::get_auth_instance()->get_logined_full_row($privilege_, $table_name_);
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードのプライマリキー値を取得
	//--------------------------------------------------------------------------
	public static function get_logined_id( $privilege_ = "", $table_name_ = false )
	{
		return self::get_auth_instance()->get_logined_id($privilege_, $table_name_);
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードのモデルインスタンスを更新する
	//--------------------------------------------------------------------------
	public static function set_logined_row( $row_, $privilege_ = "" )
	{
		return self::get_auth_instance()->set_logined_row($row_, $privilege_);
	}

	//--------------------------------------------------------------------------
	//	DB認証なしに強制でログイン成功とする
	//--------------------------------------------------------------------------
	public static function login_force( $row_, $privilege_ = "" )
	{
		return self::get_auth_instance()->login_force($row_, $privilege_);
	}

	//--------------------------------------------------------------------------
	//	ユーザ登録時のメールアドレス認証コードのメール送信
	//--------------------------------------------------------------------------
	public static function send_confirmation_code( $unique_id_, $password_, $to_mail_, $template_name_, $replace_map_, $attributes_ )
	{
		return self::get_auth_instance()->send_confirmation_code
		(
			$unique_id_, $password_, $to_mail_,
			$template_name_, $replace_map_,
			$attributes_
		);
	}

	//--------------------------------------------------------------------------
	//	認証コードチェック
	//--------------------------------------------------------------------------
	public static function verify_confirmation_code()
	{
		return self::get_auth_instance()->verify_confirmation_code
		(
			crow_request::get("hash", ""),
			crow_request::get("code", "")
		);
	}

	//--------------------------------------------------------------------------
	//	ユーザー登録時のメールアドレス認証コード再送
	//--------------------------------------------------------------------------
	public static function resend_confirmation_code( $to_mail_, $template_name_, $replace_map_ )
	{
		return self::get_auth_instance()->resend_confirmation_code
		(
			$to_mail_,
			$template_name_, $replace_map_
		);
	}

	//--------------------------------------------------------------------------
	//	パスワード忘れ用のURLを発行し、ユーザにメールを送信する
	//
	//	再発行用URLが記載されたメールを送信する。メール文面はtemplateで指定する。
	//	追加のパラメータを付与したい場合は、$more_params_ に指定する。
	//	成功 : true、失敗 : false を返却
	//--------------------------------------------------------------------------
	public static function forgot_password_start( $to_mail_, $template_name_, $replace_map_, $more_params_ = [], $sql_ext_ = false )
	{
		return self::get_auth_instance()->forgot_password_start
		(
			$to_mail_,
			$template_name_, $replace_map_,
			$more_params_,
			$sql_ext_
		);
	}

	//--------------------------------------------------------------------------
	//	パスワード忘れ時の再発行処理
	//
	//	forgot_password() で指定したactionで新しいパスワードの入力を促し、
	//	post した際に本メソッドを実行すること
	//	成功 : true、失敗 : false を返却
	//--------------------------------------------------------------------------
	public static function forgot_password_exec( $new_pw_ )
	{
		return self::get_auth_instance()->forgot_password_exec
		(
			$new_pw_,
			crow_request::get("hash", ""),
			crow_request::get("code", "")
		);
	}

	//--------------------------------------------------------------------------
	//	パスワードの強制変更
	//--------------------------------------------------------------------------
	public static function change_password( $new_pw_, $unique_id_ )
	{
		return self::get_auth_instance()->change_password($new_pw_, $unique_id_);
	}

	//--------------------------------------------------------------------------
	//	外部プロバイダ認証ユーザのメールログイン許可
	//--------------------------------------------------------------------------
	public static function change_mail_login_enabled( $unique_id_, $enabled_, $template_name_, $replace_map_ = [], $password_ = false )
	{
		return self::get_auth_instance()->change_mail_login_enabled
		(
			$unique_id_, $enabled_,
			$template_name_, $replace_map_,
			$password_
		);
	}

	//--------------------------------------------------------------------------
	//	クエリ指定でログイン処理
	//--------------------------------------------------------------------------
	public static function login_with_sqlext( $ext_func_, $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		return self::get_auth_instance()->login_with_sqlext(
			$ext_func_, $privilege_, $table_name_, $param1_, $param2_);
	}

	//--------------------------------------------------------------------------
	//	外部プロバイダ認証ログイン
	//--------------------------------------------------------------------------
	public static function login_with_provider( $provider_, $row_, $privilege_ = "" )
	{
		return self::get_auth_instance()->login_with_provider($provider_, $row_, $privilege_);
	}

	//--------------------------------------------------------------------------
	//	外部プロバイダユーザ確認作成・ログイン
	//--------------------------------------------------------------------------
	public static function check_and_login_provider( $provider_code_, $func_create_user_ )
	{
		return self::get_auth_instance()->check_and_login_provider($provider_code_, $func_create_user_);
	}

	//--------------------------------------------------------------------------
	//	外部プロバイダユーザ確認作成・ログイン sql拡張
	//--------------------------------------------------------------------------
	public static function login_with_provider_with_sqlext( $provider_code_, $func_create_user_, $ext_func_ = false, $privilege_ = "", $table_name_ = false, $param1_ = false )
	{
		return self::get_auth_instance()->login_with_provider_with_sqlext( $provider_code_, $func_create_user_, $ext_func_, $privilege_, $table_name_, $param1_ );
	}

	//--------------------------------------------------------------------------
	//	外部認証用URL遷移用アクションリスト取得
	//--------------------------------------------------------------------------
	public static function create_auth_provider_start_urls()
	{
		$ret = [];
		$providers = explode(",", crow_config::get("auth.provider.providers"));
		foreach( $providers as $provider )
		{
			$url = crow::make_url
			(
				crow_config::get("auth.provider.start.module"),
				crow_config::get("auth.provider.start.action"),
				["provider"=>$provider]
			);
			$ret[$provider] = $url;
		}

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	外部認証用URLリスト取得
	//--------------------------------------------------------------------------
	public static function create_auth_provider_urls()
	{
		$ret = [];
		$providers = explode(",", crow_config::get("auth.provider.providers"));
		foreach( $providers as $provider )
			$ret[$provider] = self::create_auth_provider_url($provider);

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	外部認証URL取得
	//--------------------------------------------------------------------------
	public static function create_auth_provider_url( $provider_ )
	{
		return crow_auth_provider::create($provider_)->create_url();
	}

	//--------------------------------------------------------------------------
	//	最後のエラー取得
	//--------------------------------------------------------------------------
	public static function get_last_error()
	{
		return self::$m_instance === false ? '' :
			self::$m_instance->get_last_error();
	}

	//--------------------------------------------------------------------------
	//	全てのエラー取得
	//--------------------------------------------------------------------------
	public static function get_errors()
	{
		return self::$m_instance === false ? [] :
			self::$m_instance->get_errors();
	}

	//	private
	private static $m_instance = false;
	private static $m_instance_caches = [];

}
?>
