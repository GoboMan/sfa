<?php
/*

	crow auth 認証モジュール aws cognito 向け


	----------------------------------------------------------------------------
	config
	----------------------------------------------------------------------------

	"<default>"の部分はターゲットを指定して、次のキーが必要となる
	aws.<default>.cognito.client_id
	aws.<default>.cognito.secret
	aws.<default>.cognito.user_pool_id

	cognito内でのユニークIDとして使用する値は、
	"auth.db.table" と "auth.db.login_name" で定義したテーブルカラムの値とする。
	もしこれを変更したい場合には、set_uniqueid_func() で、ユニークID生成関数を登録すればよい。

	----------------------------------------------------------------------------
	Lambdaの設定
	----------------------------------------------------------------------------
	Cognitoへのユーザの登録・更新時をトリガーとしてLambdaが起動し、メールの内容をカスタマイズして送信する

	aws consoleからCognitoの画面を表示
	ユーザープール→利用するユーザープールを選択
		→メッセージング
			→メッセージテンプレートの各種メッセージタイプでは検証タイプはCodeを選択(Linkの場合はカスタムできないため)

		→ユーザープールのプロパティ→Lambdaトリガー→Lambdaトリガーを追加
			→トリガータイプはメッセージングを選択→Lambda関数を選択またはLambda関数の作成→Lambdaトリガーを追加

		Lambda関数でengine/assets/aws_lambda/cognito_messaging.jsを登録する


	----------------------------------------------------------------------------
	ユーザ情報取得の例
	----------------------------------------------------------------------------
	$cognito = crow_auth::get_auth_instance();
	$cond = crow_cognito_cond::create_list_users_cond()
		->where("email", "test@example.com");
	$user = $cognito->get_user_from_cond($cond);

	//	返却される情報は次のような連想配列となる
	(
		[username] => 1
		[email_verified] => true
		[attributes] => Array
		(
			[sub] => 21e95c20-3a21-zzzz-yyyy-xxxxxxxxxxxx
			[email_verified] => true
			[email] => test@example.com
		)
		[enabled] => 1
		[status] => CONFIRMED
	)

*/
require_once(CROW_PATH."engine/vendor/autoload.php");
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CognitoIdentity\CognitoIdentityClient;

class crow_auth_cognito extends crow_auth_base
{
	//--------------------------------------------------------------------------
	//	override : インスタンス作成
	//--------------------------------------------------------------------------
	public static function create( $options_ = [] )
	{
		$target = isset($options_['target']) === true ? $options_['target'] : 'default';
		return new self($target);
	}

	//--------------------------------------------------------------------------
	//	override : ログイン済み？
	//--------------------------------------------------------------------------
	public function is_logined( $privilege_ = "" )
	{
		$privilege = $this->calc_privilege( $privilege_ );
		$sess = crow_session::get_instance();
		return $sess->has_privilege( $privilege );
	}

	//--------------------------------------------------------------------------
	//	override : ログイン処理
	//
	//	param1_はログイン名、param2_はパスワードとする
	//	ログイン名として使用できるパラメータ名はusername,email,phone_numberのいずれか
	//--------------------------------------------------------------------------
	public function login( $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		return $this->login_with_sqlext( false, $privilege_, $table_name_, $param1_, $param2_ );
	}
	public function login_with_sqlext( $ext_func_, $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get('auth.db.table');
		$login_name = $param1_!==false ? $param1_ : crow_config::get("auth.db.login_name");
		$login_pass = $param2_ !== false ? $param2_ : crow_config::get('auth.db.login_password');
		$login_name_value = crow_request::get($login_name);
		$login_pass_value = crow_request::get($login_pass);

		//	パスワードの入力チェック
		if( strlen( $login_pass_value ) <= 0 )
		{
			$err_key = crow_config::get('auth.error.lang.password');
			$this->add_error(crow_msg::get($err_key));
			return false;
		}

		//	sql作成
		$sql = $this->create_auth_sql( $ext_func_, $table_name, $param1_);
		if( $sql === false )
		{
			return false;
		}

		//	DBのユーザを特定
		$model = "model_".$table_name;
		$auth_row = $model::create_from_sql($sql);
		if( $auth_row === false )
		{
			$userinfo = "{".$login_name.":".$login_name_value."}";
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $userinfo, $err);
			$this->add_error($err);
			return false;
		}

		$design = crow::get_hdb()->get_design($table_name);
		$pk_column_name = $design->primary_key;
		$model_auth_provider = "model_".crow_config::get("auth.provider.table");

		$cognito_unique_id = strval($auth_row->{$pk_column_name});
		$auth_provider_row = $model_auth_provider::create_from_sql(
			$model_auth_provider::sql_select_all()
				->and_where($pk_column_name, $cognito_unique_id)
		);
		if( $auth_provider_row === false )
		{
			$userinfo = "{".$login_name.":".$login_name_value."}";
			$err = crow_msg::get("auth.err.none.user_auth_provider");
			$err = str_replace(":user", $userinfo, $err);
			$this->add_error($err);
			return false;
		}

		//	cognito認証
		return $this->exec_auth_cognito($cognito_unique_id, $login_name, $login_name_value, $login_pass_value, $auth_row, $auth_provider_row, $privilege_);
	}

	//--------------------------------------------------------------------------
	//	cognitoの認証部分
	//--------------------------------------------------------------------------
	private function exec_auth_cognito( $cognito_unique_id_, $login_name_, $login_name_value_, $login_pass_value_, $auth_row_, $auth_provider_row_, $privilege_ )
	{
		//	トークンが返却される
		$result = $this->m_cognito_core->auth_with_password( $cognito_unique_id_, $login_pass_value_ );
		if( $result === false )
		{
			$userinfo = "{".$login_name_.":".$login_name_value_."},{cognito:{username:".$cognito_unique_id_."}}";
			$err = crow_msg::get("auth.err.cognito.auth");
			$err = str_replace(":user", $userinfo, $err);
			$err = str_replace(":last_error", "", $err);
			$this->add_error($err);
			return false;
		}

		//	トークン取得
		$tokens = $this->m_cognito_core->format_tokens($result["AuthenticationResult"]);

		//	属性をチェック
		$cognito_user = $this->m_cognito_core->admin_get_user( $cognito_unique_id_ );
		$auth_providers = $this->m_cognito_core->get_auth_providers();

		/*	TODO: 登録済みのプロバイダーを消さなくてよい。消す理由があればコメント解除
		foreach( $auth_providers as $provider_code => $provider_name )
		{
			if( $provider_code === crow_cognito_core::CODE_COGNITO ) continue;

			$auth_provider_row_->{$provider_code."_sub"} = "";
		}
		*/
		if( isset( $cognito_user["attributes"]["identities"] ) === true )
		{
			foreach( $cognito_user["attributes"]["identities"] as $identity )
			{
				if( $identity["providerName"] === crow_cognito_core::CODE_COGNITO ) continue;

				$auth_provider_row_->{$provider_code."_sub"} = $identity["userId"];
			}
		}

		$hdb = crow::get_hdb();
		$hdb->begin();
		{
			$auth_row_->auth_provider_last_login = crow_cognito_core::CODE_COGNITO;
			$auth_row_->auth_cognito_access_token = $tokens["access_token"];
			$auth_row_->auth_cognito_id_token = $tokens["id_token"];
			$auth_row_->auth_cognito_refresh_token = $tokens["refresh_token"];
			$auth_row_->auth_cognito_users = array_to_json([$cognito_unique_id_ => $cognito_user]);
			if( $auth_row_->check_and_save() === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.update_user");
				$err = str_replace(":user", $cognito_unique_id_, $err);
				$err = str_replace(":last_error", $auth_row_->get_last_error(), $err);
				$this->add_error($err);
				return false;
			}

			if( $auth_provider_row_->check_and_save() === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.update_user");
				$err = str_replace(":user", $cognito_unique_id_, $err);
				$err = str_replace(":last_error", $auth_provider_row_->get_last_error(), $err);
				$this->add_error($err);
				return false;
			}
		}
		$hdb->commit();

		//	DBの行を返却
		return $this->login_force( $auth_row_->to_named_array(), $privilege_ );
	}

	//--------------------------------------------------------------------------
	//	override : ログアウト
	//--------------------------------------------------------------------------
	public function logout( $privilege_ = "" )
	{
		//	DBからcognitoの情報を削除
		$auth_row = $this->get_auth_row();

		//	アカウント削除などで
		//	認証情報が削除されている場合があるので
		//	存在する場合のみ空にする
		if( $auth_row !== false )
		{
			$auth_row->auth_cognito_access_token = "";
			$auth_row->auth_cognito_id_token = "";
			$auth_row->auth_cognito_refresh_token = "";
			$auth_row->auth_cognito_users = "";
			if( $auth_row->check_and_save() === false )
			{
				$err = crow_msg::get("auth.err.update_user");
				$err = str_replace(":user", $auth_row->user_id, $err);
				$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
				$this->add_error($err);
				return false;
			}
		}

		//	アプリ側ログアウト
		$sess = crow_session::get_instance();
		$sess->clear_properties($privilege_);
		$sess->remove_privilege( $this->calc_privilege($privilege_) );

		//	Cognito側ログアウト
		$url = $this->m_cognito_core->get_endpoint("logout")
			."?"
			.http_build_query(
			[
				"client_id" => $this->m_cognito_core->m_client_id,
				"logout_uri" => $this->m_cognito_core->m_signout_redirect_uri,
			]);

		crow::output_start();
		header("Location: ".$url);
		crow::output_end();
		exit;
	}

	//--------------------------------------------------------------------------
	//	サインアップ
	//--------------------------------------------------------------------------
	public function send_confirmation_code( $unique_id_, $password_, $to_mail_, $template_name_, $replace_map_, $attributes_ = [], $replace_params_ = [] )
	{
		$mail = crow_mail::create()->template($template_name_, $replace_map_);
		$mail->to($to_mail_);
		$is_mail_send = $this->is_mail_send();

		$attributes = $attributes_;
		$attributes[self::KEY_EMAIL] = $to_mail_;
		$attributes[self::KEY_ENABLE_MAIL_LOGIN] = "true";
		$attributes[self::KEY_IS_ORIGIN_EXTERNAL] = "false";
		$unique_id = strval($unique_id_);

		$verify_url = crow::make_url
		(
			crow_config::get("auth.mail.verify.module"),
			crow_config::get("auth.mail.verify.action"),
		);
		$resend_code_url = crow::make_url
		(
			crow_config::get("auth.mail.resend_code.module"),
			crow_config::get("auth.mail.resend_code.action"),
		);

		$lambda_params =
		[
			"unique_id" => $unique_id,
			"user_pool_id" => $this->m_cognito_core->m_user_pool_id,
			"client_id" => $this->m_cognito_core->m_client_id,
			"crypto_key" => $this->m_cognito_core->m_crypto_key,
			"verify_url" => $verify_url,
			"resend_code_url" => $resend_code_url,
			"password" => $password_,
			"mail_subject" => $mail->subject(),
			"mail_body" => $mail->body(),
		];

		//	メールログ出力
		crow_mail::output_log($mail);

		//	メール送らない場合にはcreate_userを利用してsignup利用時と同じ状態を作る
		if( $is_mail_send === false )
		{
			unset($attributes[self::KEY_EMAIL]);
			$result = $this->m_cognito_core->admin_create_user
			(
				$unique_id,
				$attributes,
				$password_,
				$lambda_params,
				$is_mail_send
			);
			if( $result === false )
			{
				$err = crow_msg::get("auth.err.cognito.create_user");
				$err = str_replace(":user", $unique_id, $err);
				$this->add_error($err);
				return false;
			}

			//	email&&email_verifiied=falseで登録するとトリガーが走るので、あとでfalseに変更する
			$result = $this->update_user_attrs
			(
				$unique_id,
				[
					self::KEY_EMAIL => $to_mail_,
					self::KEY_EMAIL_VERIFIED => "true",
				]
			);
			if( $result === false )
			{
				return false;
			}
			$result = $this->update_user_attrs($unique_id, [self::KEY_EMAIL_VERIFIED => "false"]);
			if( $result === false )
			{
				return false;
			}

			//	パスワード変更
			$result = $this->m_cognito_core->admin_set_user_password
			(
				$unique_id, $password_, true
			);
			if( $result === false )
			{
				$this->add_error(crow_msg::get("auth.err.cognito.set_user_password", $unique_id_));
				return false;
			}

			return $result;
		}

		$result = $this->m_cognito_core->signup
		(
			strval($unique_id_), $password_,
			$attributes,
			$lambda_params
		);

		if( $result === false )
		{
			$err = crow_msg::get("auth.err.send_confirmation_code");
			$err = str_replace(":mail", $to_mail_, $err);
			$this->add_error($err);
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : 認証コードチェック
	//--------------------------------------------------------------------------
	public function verify_confirmation_code( $hash_, $confirmation_code_ = "", $table_name_ = false )
	{
		$json_data = $this->decrypt($hash_);
		$data = json_decode($json_data, true);
		if( isset($data["unique_id"]) === false ) return false;

		$unique_id = $data["unique_id"];

		//	認証状態を更新
		$hdb = crow::get_hdb();
		$table_name = $table_name_ !== false ? $table_name_ :crow_config::get("auth.db.table");
		$model = "model_".$table_name;
		$table_design = $hdb->get_design($table_name);
		if( ! $table_design ) return false;

		$hdb->begin();
		{
			$auth_row = $model::create_from_sql(
				$model::sql_select_all()
					->and_where($table_design->primary_key, $unique_id)
					->for_update()
			);
			if( $auth_row === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.none.user");
				$err = str_replace(":user", $unique_id, $err);
				$this->add_error($err);
				crow_log::notice("failed not found auth_row");
				return false;
			}

			$result = false;
			$cognito_user = $this->m_cognito_core->admin_get_user($unique_id);

			//	既に認証済みになっていたらcognitoの情報を更新して終了
			if( $auth_row->auth_mail_addr_verified === true )
			{
				$update_attrs =
				[
					self::KEY_EMAIL => $auth_row->auth_mail_addr,
					self::KEY_EMAIL_VERIFIED => "true",
				];
				$result = $this->update_user_attrs($unique_id, $update_attrs);
				if( $result === false )
				{
					$hdb->rollback();
					$err = crow_msg::get("auth.err.verify_confirmation_code");
					$err = str_replace(":user", $unique_id, $err);
					$err = str_replace(":last_error", "", $err);
					$this->add_error($err);
					crow_log::notice("failed update_user_attrs verified");
					return false;
				}
				$hdb->commit();
				return true;
			}

			//	メールを認証済みにする
			$auth_row->verified_mail();

			if( $cognito_user["status"]==crow_cognito_core::STATUS_UNCONFIRMED )
			{
				$result = $this->m_cognito_core->confirm_signup($unique_id, $confirmation_code_);
			}
			else
			{
				$tokens = $this->tokens($table_name);
				$access_token = isset($tokens["access_token"]) === true ? $tokens["access_token"] : "";

				//	ログイン状態じゃなければ強制ログインして更新
				if( $access_token === "" )
				{
					$password = $this->decrypt($auth_row->auth_cognito_login_pw);
					$result = $this->m_cognito_core->auth_with_password($unique_id, $password);
					if( $result === false )
					{
						$hdb->rollback();
						$err = crow_msg::get("auth.err.cognito.auth");
						$err = str_replace(":user", $unique_id, $err);
						$err = str_replace(":last_error", "", $err);
						$this->add_error($err);
						crow_log::notice("failed auth_with_password");
						return false;
					}
					$tokens = $this->m_cognito_core->format_tokens($result["AuthenticationResult"]);
					$access_token = isset($tokens["access_token"]) === true ? $tokens["access_token"] : "";
					if( $access_token === "" )
					{
						$hdb->rollback();
						$err = crow_msg::get("auth.err.cognito.auth");
						$err = str_replace(":user", $unique_id, $err);
						$err = str_replace(":last_error", "", $err);
						$this->add_error($err);
						crow_log::notice("failed format_tokens");
						return false;
					}

					//	DBに保存
					$auth_row->auth_cognito_access_token = $tokens["access_token"];
					$auth_row->auth_cognito_id_token = $tokens["id_token"];
					$auth_row->auth_cognito_refresh_token = $tokens["refresh_token"];
				}
				$result = $this->m_cognito_core->verify_user_attribute($access_token, self::KEY_EMAIL, $confirmation_code_);
				if( $result === false )
				{
					$hdb->rollback();
					$err = crow_msg::get("auth.err.verify_confirmation_code");
					$err = str_replace(":user", $unique_id, $err);
					$err = str_replace(":last_error", "", $err);
					$this->add_error($err);
					crow_log::notice("failed verify_use_attributes");
					return false;
				}

				$update_attrs =
				[
					self::KEY_CURRENT_EMAIL => $cognito_user["attributes"][self::KEY_VERIFYING_EMAIL],
					self::KEY_VERIFYING_EMAIL => "",
				];
				$result = $this->update_user_attrs($unique_id, $update_attrs);
				if( $result === false )
				{
					$hdb->rollback();
					$err = crow_msg::get("auth.err.verify_confirmation_code");
					$err = str_replace(":user", $unique_id, $err);
					$err = str_replace(":last_error", "", $err);
					$this->add_error($err);
					crow_log::notice("failed update_user_attrs");
					return false;
				}

				//	セッションを更新
				foreach( $update_attrs as $k => $v )
					$congito_user["attributes"][$k] = $v;

				//	ログイン状態にする
				crow_auth::login_force($auth_row->to_named_array());
			}

			//	DBに保存
			$auth_row->auth_cognito_users = array_to_json([$unique_id => $cognito_user]);
			if( $auth_row->check_and_save() === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.verify_confirmation_code");
				$err = str_replace(":user", $unique_id, $err);
				$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
				$this->add_error($err);
				crow_log::notice("failed db error user");
				return false;
			}

			if( $result === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.verify_confirmation_code");
				$err = str_replace(":user", $unique_id, $err);
				$err = str_replace(":last_error", "", $err);
				$this->add_error($err);
				crow_log::notice("failed db error user");
				return false;
			}
		}
		$hdb->commit();

		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : 認証コード再送
	//--------------------------------------------------------------------------
	public function resend_confirmation_code( $to_mail_, $template_name_, $replace_map_, $table_name_ = false )
	{
		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get("auth.db.table");

		$model = "model_".$table_name;
		$auth_row = $model::create_from_sql(
			$model::sql_select_all()
				->and_where("auth_mail_addr_verify", $to_mail_)
		);
		if( $auth_row === false )
		{
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $to_mail_, $err);
			$this->add_error($err);
			return false;
		}

		$design = crow::get_hdb()->get_design($table_name);
		$pk_column_name = $design->primary_key;
		$unique_id = strval($auth_row->{$pk_column_name});

		$result = $this->m_cognito_core->admin_get_user($unique_id);
		if( $result === false )
		{
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		if( $result["status"] !== crow_cognito_core::STATUS_UNCONFIRMED )
		{
			$err = crow_msg::get("auth.err.verified_mail");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		$verify_url = crow::make_url
		(
			crow_config::get("auth.mail.verify.module"),
			crow_config::get("auth.mail.verify.action"),
		);

		$mail = crow_mail::create()->template($template_name_, $replace_map_);
		$mail->to($to_mail_);
		$is_mail_send = $this->is_mail_send();

		$lambda_params =
		[
			"user_pool_id" => $this->m_cognito_core->m_user_pool_id,
			"client_id" => $this->m_cognito_core->m_client_id,
			"crypto_key" => $this->m_cognito_core->m_crypto_key,
			"verify_url" => $verify_url,
			"unique_id" => $unique_id,
			"mail_subject" => $mail->subject(),
			"mail_body" => $mail->body(),
		];

		//	メールログ出力
		crow_mail::output_log($mail);

		//	メールを送らない場合にはcognitoで処理を行わない
		if( $is_mail_send === false ) return true;

		$result = $this->m_cognito_core->resend_confirmation_code($unique_id, $lambda_params);
		if( $result === false )
		{
			$err = crow_msg::get("auth.err.resend_confirmation_code");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : パスワード忘れ用のURLを発行し、ユーザにメールを送信する
	//--------------------------------------------------------------------------
	public function forgot_password_start( $to_mail_, $template_name_, $replace_map_, $more_params_ = [], $sql_ext_ = false, $table_name_ = false )
	{
		$mail = crow_mail::create()->template($template_name_, $replace_map_);
		$mail->to($to_mail_);
		$is_mail_send = $this->is_mail_send();

		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get("auth.db.table");

		$model = "model_".$table_name;
		$sql = $model::sql_select_all()
				->and_where("auth_mail_addr", $to_mail_)
		;
		if( $sql_ext_ !== false )
		{
			if( $sql_ext_($sql) === false )
			{
				return false;
			}
		}
		$auth_row = $model::create_from_sql($sql);
		if( $auth_row === false )
		{
			$err = crow_msg::get("auth.err.forgot_password_start");
			$err = str_replace(":user", $to_mail_, $err);
			$this->add_error($err);
			return false;
		}

		$design = crow::get_hdb()->get_design($table_name);
		$pk_column_name = $design->primary_key;
		$unique_id = strval($auth_row->{$pk_column_name});

		$reset_url = crow::make_url
		(
			crow_config::get("auth.mail.reset_password.module"),
			crow_config::get("auth.mail.reset_password.action"),
		);
		$reset_url = strrpos("/", $reset_url)===(mb_strlen($reset_url)-1) ? $reset_url : $reset_url."/";

		$lambda_params =
		[
			"user_pool_id" => $this->m_cognito_core->m_user_pool_id,
			"client_id" => $this->m_cognito_core->m_client_id,
			"crypto_key" => $this->m_cognito_core->m_crypto_key,
			"reset_url" => $reset_url,
			"unique_id" => $unique_id,
			"mail_subject" => $mail->subject(),
			"mail_body" => $mail->body(),
		];

		//	メールログ出力
		crow_mail::output_log($mail);

		//	メールを送らない場合にはcognitoで処理を行わない
		if( $is_mail_send === false ) return true;

		$result = $this->m_cognito_core->forgot_password($unique_id, $lambda_params);
		if( $result === false )
		{
			$err = crow_msg::get("auth.err.forgot_password_start");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : パスワード忘れ時の再設定処理
	//--------------------------------------------------------------------------
	public function forgot_password_exec( $new_pw_, $hash_, $confirmation_code_ )
	{
		//	ユーザ情報取得
		$params_for_hash = $this->decrypt($hash_);
		$data = json_decode($params_for_hash, true);
		if( isset($data["unique_id"]) === false ) return false;
		$unique_id = strval($data["unique_id"]);

		$result = $this->m_cognito_core->confirm_forgot_password($unique_id, $new_pw_, $confirmation_code_);
		if( $result === false )
		{
			$err = crow_msg::get("auth.err.change_password");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : パスワードの強制変更
	//--------------------------------------------------------------------------
	public function change_password( $new_pw_, $unique_id_ = false, $table_name_ = false )
	{
		$unique_id = $unique_id_ !== false ? $unique_id_ : crow_auth::get_logined_id();
		if( $unique_id <= 0 ) return false;

		$unique_id = strval($unique_id);
		$is_permanent = true;

		$hdb = crow::get_hdb_writer();
		$hdb->begin();
		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get("auth.db.table");
		$model = "model_".$table_name;
		$auth_row = $model::create_from_id($unique_id);
		$auth_row->set_login_pw($new_pw_);
		if( $auth_row->check_and_save() === false )
		{
			$hdb->rollback();
			$err = crow_msg::get("auth.err.change_password");
			$err = str_replace(":user", $unique_id, $err);
			$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}

		$result = $this->m_cognito_core->admin_set_user_password($unique_id, $new_pw_, $is_permanent);
		if( $result === false )
		{
			$hdb->rollback();
			$err = crow_msg::get("auth.err.change_password");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		$hdb->commit();
		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : ユーザ作成
	//--------------------------------------------------------------------------
	public function create_user( $unique_id_, $password_, $attrs_, $template_name_ = false, $replace_map_ = [] )
	{
		$unique_id = strval($unique_id_);
		$lambda_params =
		[
			"user_pool_id" => $this->m_cognito_core->m_user_pool_id,
			"client_id" => $this->m_cognito_core->m_client_id,
			"crypto_key" => $this->m_cognito_core->m_crypto_key,
			"unique_id" => $unique_id,
			"password" => $password_,
		];

		$mail = crow_mail::create();
		if( $template_name_ === true )
		{
			$mail->template($template_name_, $replace_map_);
			$lambda_params["mail_subject"] = $mail->subject();
			$lambda_params["mail_body"] = $mail->body();
		}

		//	メール送信フラグを渡す
		if( isset($attrs_[self::KEY_EMAIL]) === true ) $mail->to($attrs_[self::KEY_EMAIL]);
		$is_mail_send = $this->is_mail_send();

		//	メールログ出力
		crow_mail::output_log($mail);

		//	ユーザ作成
		$result = $this->m_cognito_core->admin_create_user
		(
			$unique_id,
			$attrs_,
			$password_,
			$lambda_params,
			$is_mail_send
		);
		if( $result === false )
		{
			$err = crow_msg::get("auth.err.cognito.create_user");
			$err = str_replace(":user", $unique_id_, $err);
			$this->add_error($err);
			return false;
		}

		//	パスワード変更
		$result = $this->m_cognito_core->admin_set_user_password
		(
			$unique_id, $password_, true
		);
		if( $result === false )
		{
			$this->add_error(crow_msg::get("auth.err.cognito.set_user_password", $unique_id_));
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	override : 外部プロバイダ認証ログイン
	//--------------------------------------------------------------------------
	public function login_with_provider( $provider_code_, $row_, $privilege_ = "" )
	{
		if( crow_config::get("auth.provider.enabled") !== "true" ) return false;

		$design = crow::get_hdb()->get_design($row_->m_table_name);
		$cognito_unique_id = strval($row_->{$design->primary_key});
		$hdb = crow::get_hdb_writer();

		//	外部認証拡張用カラムを更新
		$row_->auth_provider_last_login = $provider_code_;

		//	Cognitoも更新
		$password = $this->decrypt($row_->auth_cognito_login_pw);
		$result = $this->m_cognito_core->auth_with_password($cognito_unique_id, $password);
		if( $result === false )
		{
			$hdb->rollback();
			$err = crow_msg::get("auth.err.cognito.auth");
			$err = str_replace(":user", $cognito_unique_id, $err);
			$err = str_replace(":last_error", $this->m_cognito_core->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}

		//	トークン取得
		$tokens = $this->m_cognito_core->format_tokens($result["AuthenticationResult"]);

		//	属性をチェック
		$cognito_user = $this->m_cognito_core->admin_get_user( $cognito_unique_id );

		//	DBに保存
		$row_->auth_cognito_access_token = $tokens["access_token"];
		$row_->auth_cognito_id_token = $tokens["id_token"];
		$row_->auth_cognito_refresh_token = $tokens["refresh_token"];
		$row_->auth_cognito_users = array_to_json([$cognito_unique_id => $cognito_user]);
		if( $row_->check_and_save() === false )
		{
			$hdb->rollback();
			$err = crow_msg::get("auth.err.update_user");
			$err = str_replace(":user", $cognito_unique_id, $err);
			$err = str_replace(":last_error", $row_->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}
		$hdb->commit();

		//	DBの行を返却
		return $this->login_force($row_->to_named_array(), $privilege_);
	}

	//--------------------------------------------------------------------------
	//	CognitoID生成関数の登録
	//
	//	デフォルトではconfigの、auth.db.tableのauth.db.login_nameで指示した値となるが
	//	カスタムしたい場合にはここで関数を登録する
	//--------------------------------------------------------------------------
	public function set_unique_id_func( $func_ )
	{
		$this->m_uniqueid_func = $func_;
	}

	//--------------------------------------------------------------------------
	//	指定したユーザーを取得
	//--------------------------------------------------------------------------
	public function get_user( $unique_id_, $table_name_ = false )
	{
		//	DBから取得
		$auth_row = $this->get_auth_row($unique_id_, $table_name_);
		if( $auth_row === false ) return false;

		$users = json_decode($auth_row->auth_cognito_users, true);
		if( is_array($users) === true && isset($users[$unique_id_]) === true )
			return $users[$unique_id_];

		$result = $this->m_cognito_core->admin_get_user(strval($unique_id_));
		if( $result === false ) return false;

		$new_users = is_array($users) === true ? $users : [];
		$new_users[$unique_id_] = $result;
		$auth_row->auth_cognito_users = array_to_json($new_users);
		if( $auth_row->check_and_save() === false )
		{
			$err = crow_msg::get("auth.err.update_user");
			$err = str_replace(":user", $unique_id_, $err);
			$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	指定ユーザを連想配列で取得
	//--------------------------------------------------------------------------
	public function get_users( $unique_ids_ )
	{
		$ret = [];
		foreach( $unique_ids_ as $unique_id )
			$ret[$unique_id] = $this->get_user($unique_id);

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	指定したユーザの属性を連想配列で取得
	//--------------------------------------------------------------------------
	public function get_user_attrs( $unique_id_ )
	{
		$result = $this->get_user($unique_id_);
		if( $result === false ) return false;

		return $result["attributes"];
	}

	//--------------------------------------------------------------------------
	//	指定した複数ユーザの属性を連想配列で取得
	//
	//	例）
	//	get_extparams(["user1", "user2", "user3"]);
	///	echo $return_val["user2"]["ext1"];
	//--------------------------------------------------------------------------
	public function get_users_attrs( $unique_ids_ )
	{
		$ret = [];
		foreach( $unique_ids_ as $unique_id )
		{
			$result = $this->get_user_attrs($unique_id);
			$ret[$unique_id] = $result;
		}

		return $ret;
	}

	//--------------------------------------------------------------------------
	//	指定ユーザの属性を変更する
	//--------------------------------------------------------------------------
	public function update_user_attrs( $unique_id_, $attrs_, $lambda_params_ = [], $table_name_ = false, $is_ret_users_ = false )
	{
		$unique_id = strval($unique_id_);
		$lambda_params = array_merge
		(
			[
				"user_pool_id" => $this->m_cognito_core->m_user_pool_id,
				"client_id" => $this->m_cognito_core->m_client_id,
				"crypto_key" => $this->m_cognito_core->m_crypto_key,
				"unique_id" => $unique_id,
				"mail_subject" => "",
				"mail_body" => "",
			],
			$lambda_params_
		);

		//	DBからユーザ属性取得
		$auth_row = $this->get_auth_row($unique_id, $table_name_);
		if( $auth_row === false ) return false;

		$users = json_decode($auth_row->auth_cognito_users, true);
		$new_users = is_array($users) === true ? $users : [];
		$userinfo = isset($new_users[$unique_id]) ? $new_users[$unique_id] : false;

		//	属性にemailが含まれる場合にはcrowで利用するカスタム属性の更新も行う
		if( isset($attrs_[self::KEY_EMAIL]) === true )
		{
			if(
				$userinfo !== false &&
				isset($userinfo["attributes"]) === true &&
				isset($userinfo["attributes"][self::KEY_EMAIL]) === true &&
				$attrs_[self::KEY_EMAIL] != $userinfo["attributes"][self::KEY_EMAIL]
			){
				if( isset($attrs_[self::KEY_CURRENT_EMAIL]) === false )
					$attrs_[self::KEY_CURRENT_EMAIL] = $userinfo["attributes"][self::KEY_EMAIL];
				if( isset($attrs_[self::KEY_VERIFYING_EMAIL]) === false )
					$attrs_[self::KEY_VERIFYING_EMAIL] = $attrs_[self::KEY_EMAIL];
			}
			else
			{
				$attrs_[self::KEY_CURRENT_EMAIL] = "";
				if( isset($attrs_[self::KEY_VERIFYING_EMAIL]) === false )
					$attrs_[self::KEY_VERIFYING_EMAIL] = $attrs_[self::KEY_EMAIL];
			}
		}

		$result = $this->m_cognito_core->admin_update_user_attributes($unique_id, $attrs_, $lambda_params);
		if( $result === false ) return false;

		//	usersを取得してフラグあれば即返却
		$new_users[$unique_id] = $this->m_cognito_core->admin_get_user($unique_id);
		$new_users_json = array_to_json($new_users);
		if( $is_ret_users_ === true ) return $new_users_json;

		//	DBに保存
		$auth_row->auth_cognito_users = $new_users_json;
		if( $auth_row->check_and_save() === false )
		{
			$err = crow_msg::get("auth.err.update_user");
			$err = str_replace(":user", $unique_id, $err);
			$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	指定ユーザの属性を変更する
	//--------------------------------------------------------------------------
	public function delete_user_attrs( $unique_id_, $attrs_ )
	{
		return $this->m_cognito_core->admin_delete_user_attrigbutes($unique_id_, $attrs_);
	}

	//--------------------------------------------------------------------------
	//	指定ユーザのステータス有効・無効を変更する
	//--------------------------------------------------------------------------
	public function change_user_status( $unique_id_, $enabled_ )
	{
		return $this->m_cognito_core->admin_change_user_enabled($unique_id_, $enabled_);
	}

	//--------------------------------------------------------------------------
	//	指定ユーザの削除
	//--------------------------------------------------------------------------
	public function delete_user( $unique_id_ )
	{
		return $this->m_cognito_core->admin_delete_user($unique_id_);
	}

	//--------------------------------------------------------------------------
	//	指定ユーザのパスワード変更
	//--------------------------------------------------------------------------
	public function set_user_password( $unique_id_, $password_ )
	{
		return $this->m_cognito_core->admin_set_user_password($unique_id_, $password_, true);
	}

	//--------------------------------------------------------------------------
	//	指定ユーザの紐づけ
	//--------------------------------------------------------------------------
	public function link_user( $unique_id_, $provider_name_, $provider_user_id_ )
	{
		return $this->m_cognito_core->admin_link_provider_for_user
		(
			$unique_id_,
			$provider_name_,
			$provider_user_id_
		);
	}

	//--------------------------------------------------------------------------
	//	指定ユーザの紐づけ解除
	//--------------------------------------------------------------------------
	public function unlink_user( $provider_name_, $provider_user_id_ )
	{
		return $this->m_cognito_core->admin_disable_provider_for_user
		(
			$provider_name_, $provider_user_id_
		);
	}

	//--------------------------------------------------------------------------
	//	外部認証作成ユーザのメールログイン有効・無効化
	//--------------------------------------------------------------------------
	public function change_mail_login_enabled( $unique_id_, $enabled_, $template_name_, $replace_map_ = [], $password_ = false, $password_column_ = false )
	{
		$unique_id = strval($unique_id_);

		$hdb = crow::get_hdb();

		$table = crow_config::get("auth.db.table");
		$design = $hdb->get_design($table);
		$model_auth = "model_".$table;
		$auth_row = $model_auth::create_from_id($unique_id);
		if( $auth_row === false )
		{
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		if( $auth_row->auth_provider_is_origin_external == false )
		{
			$err = crow_msg::get("auth.err.is_provider_origin");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		//	外部接続ユーザでなければこの操作は無効
		$attrs = $this->get_user_attrs($unique_id);
		if( isset($attrs[self::KEY_IS_ORIGIN_EXTERNAL]) === false )
		{
			$err = crow_msg::get("auth.err.cognito.update_user_attr");
			$err = str_replace(":user", $unique_id, $err);
			$err = str_replace(":more", "no key:KEY_IS_ORIGIN_EXTERNAL", $err);
			$this->add_error($err);
			return false;
		}
		if( $attrs[self::KEY_IS_ORIGIN_EXTERNAL] === "false" )
		{
			$err = crow_msg::get("auth.err.cognito.update_user_attr");
			$err = str_replace(":user", $unique_id, $err);
			$err = str_replace(":more", "key:KEY_IS_ORIGIN_EXTERNAL is false", $err);
			$this->add_error($err);
			return false;
		}

		$password = "";
		if( $password_ === false )
		{
			$min = $auth_row->get_opt_pw_min();
			$max = $min;
			if( $enabled_ === true )
			{
				$min = self::PASSWORD_MAX_LENGTH;
				$max = self::PASSWORD_MAX_LENGTH;
			}
			$rule = $auth_row->get_opt_pw_rule();
			$must = $auth_row->get_opt_pw_must();
			$password = $auth_row->generate_password($min, $rule, $must, $max);
		}
		else
		{
			$password = $password_;
		}

		$hdb->begin();
		{
			$replace_map = array_merge
			(
				[
					"MAIL_ADDR" => $auth_row->auth_mail_addr,
					"PASSWORD" => $password,
				],
				$replace_map_
			);

			//	メールログイン有効化
			$set_status = $enabled_ === true ? "1" : "0";
			$update_attrs = [self::KEY_ENABLE_MAIL_LOGIN => $set_status];
			$cognito_users = $this->update_user_attrs($unique_id, $update_attrs, [], false, true);
			if( $cognito_users === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.cognito.update_user_attr");
				$err = str_replace(":user", $unique_id, $err);
				$err = str_replace(":more", "", $err);
				$this->add_error($err);
				return false;
			}

			//	DB更新
			$auth_row->set_login_pw($password, $password_column_);
			$auth_row->auth_provider_mail_login_enabled = $enabled_;
			$auth_row->auth_cognito_users = $cognito_users;
			if( $auth_row->check_and_save() === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.update_user");
				$err = str_replace(":user", $unique_id, $err);
				$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
				$this->add_error($err);
				return false;
			}

			//	パスワードは適度な長さに設定する
			$result = $this->set_user_password($unique_id, $password);
			if( $result === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.cognito.set_password");
				$err = str_replace(":user", $unique_id, $err);
				$this->add_error($err);
				return false;
			}

			//	有効化した場合にはメール送信
			if( $enabled_ === true )
			{
				$result = crow_mail::create()
					->name(crow_config::get("auth.mail.name"))
					->from(crow_config::get("auth.mail.from"))
					->to($auth_row->auth_mail_addr)
					->template($template_name_, $replace_map)
					->send()
					;
			}
		}
		$hdb->commit();

		return true;
	}

	//--------------------------------------------------------------------------
	//	Token取得
	//	{
	//		id_token : xxx
	//		access_token : xxx
	//		refresh_token : xxx
	//		token_type : Bearer
	//		expires_in : 3600(default)
	//	}
	//--------------------------------------------------------------------------
	public function tokens( $table_name_ = false )
	{
		if( crow_auth::is_logined() === false ) return false;

		//	DBから取得
		$auth_row = $this->get_auth_row(false, $table_name_);
		if( $auth_row === false ) return false;

		$tokens =
		[
			"access_token" => $auth_row->auth_cognito_access_token,
			"id_token" => $auth_row->auth_cognito_id_token,
			"refresh_token" => $auth_row->auth_cognito_refresh_token,
			"expires_in" => $auth_row->auth_cognito_expires_in,
		];
		return $tokens;
	}

	//--------------------------------------------------------------------------
	//	トークン期限チェック
	//	返却: true/false
	//--------------------------------------------------------------------------
	public function check_token_expiration( $table_name_ = false )
	{
		if( crow_auth::is_logined() === false ) return false;

		//	DBから取得
		$tokens = $this->tokens($table_name_);
		if( $tokens === false ) return false;

		$access_token = $tokens["access_token"];
		$payload = $this->m_cognito_core->get_payload_from_token($access_token);
		return $payload["exp"] > time();
	}

	//--------------------------------------------------------------------------
	//	トークン更新
	//	awsからはRefreshTokenは返却されない
	//	使用したRefreshTokenをそのまま返却する
	//--------------------------------------------------------------------------
	public function refresh_token( $privilege_ = "", $table_name_ = false )
	{
		if( crow_auth::is_logined() === false ) return false;

		//	リフレッシュ失敗時はprivilegeを削除
		$remove_privilege = function( $privilege_ )
		{
			$sess = crow_session::get_instance();
			$sess->clear_properties($privilege_);
			$sess->remove_privilege( $this->calc_privilege($privilege_) );
		};

		//	DBから取得
		$tokens = $this->tokens($table_name_);
		if( $tokens === false )
		{
			$remove_privilege($privilege_);
			return false;
		}

		$payload = $this->m_cognito_core->get_payload_from_token($tokens["access_token"]);
		if( isset($tokens["refresh_token"]) === false
			|| $tokens["refresh_token"] === ""
		){
			$remove_privilege($privilege_);
			return false;
		}

		$result = $this->m_cognito_core->auth_with_refresh_token
		(
			$payload["username"],
			$tokens["refresh_token"]
		);
		if( $result === false )
		{
			$remove_privilege($privilege_);
			return false;
		}

		$result_tokens = $result["AuthenticationResult"];
		$new_tokens =
		[
			"access_token" => $result_tokens["AccessToken"],
			"id_token" => $result_tokens["IdToken"],
			"refresh_token" => $tokens["refresh_token"],
			"expires_in" => $result_tokens["ExpiresIn"],
		];

		//	DBに保存
		$auth_row = $this->get_auth_row(false, $table_name_);
		$auth_row->auth_cognito_access_token = $result_tokens["AccessToken"];
		$auth_row->auth_cognito_id_token = $result_tokens["IdToken"];
		$auth_row->auth_cognito_refresh_token = $tokens["refresh_token"];
		$auth_row->auth_cognito_expires_in = $result_tokens["ExpiresIn"];
		if( $auth_row->check_and_save() === false )
		{
			$remove_privilege($privilege_);
			$err = crow_msg::get("auth.err.update_user");
			$err = str_replace(":user", $cognito_unique_id, $err);
			$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}
		return $new_tokens;
	}

	//--------------------------------------------------------------------------
	//	シークレットハッシュ生成
	//--------------------------------------------------------------------------
	public function create_secret_hash()
	{
		$this->m_cognito_core->create_secret_hash();
	}

	//--------------------------------------------------------------------------
	//	crow_cognito のインスタンス取得
	//--------------------------------------------------------------------------
	public function get_cognito_core()
	{
		return $this->m_cognito_core;
	}

	//--------------------------------------------------------------------------
	//	認証テーブルのレコードを取得
	//--------------------------------------------------------------------------
	private function get_auth_row( $unique_id_ = false, $table_name_ = false )
	{
		$unique_id = $unique_id_ !== false ? $unique_id_ : crow_auth::get_logined_id();
		if( array_key_exists($unique_id, $this->m_cache_auth_row_list) === true )
			return $this->m_cache_auth_row_list[$unique_id];

		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get("auth.db.table");
		$model_auth = "model_".$table_name;
		$auth_row = $model_auth::create_from_id($unique_id);
		if( $auth_row === false )
		{
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $unique_id_, $err);
			$this->add_error($err);
			return false;
		}
		$this->m_cache_auth_row_list[$unique_id] = $auth_row;
		return $auth_row;
	}

	//--------------------------------------------------------------------------
	//	コンストラクタ
	//--------------------------------------------------------------------------
	public function __construct($target_)
	{
		$this->m_cognito_core = crow_cognito_core::create($target_);
	}

	//--------------------------------------------------------------------------
	//	メール・SMS送信をするかどうか
	//--------------------------------------------------------------------------
	public function is_mail_send()
	{
		$is_system_mail_send = strtolower(crow_config::get("mail.send"))==="true";
		$is_cognito_mail_send = strtolower(crow_config::get("aws.".$this->m_cognito_core->m_target.".cognito.mail_send"))==="true";
		return ($is_system_mail_send === true) && ($is_cognito_mail_send === true);
	}

	//	private
	private $m_cognito_core = false;
	private $m_uniqueid_func = false;
	private $m_cache_auth_row_list = [];

	const KEY_EMAIL = "email";
	const KEY_EMAIL_VERIFIED = "email_verified";
	const KEY_CURRENT_EMAIL = "custom:current_email";
	const KEY_VERIFYING_EMAIL = "custom:verifying_email";
	const KEY_ENABLE_MAIL_LOGIN = "custom:enable_mail_login";
	const KEY_IS_ORIGIN_EXTERNAL = "custom:is_origin_external";

	//	cognitoの最大許容パスワード長
	const PASSWORD_MAX_LENGTH = 99;
}
?>
