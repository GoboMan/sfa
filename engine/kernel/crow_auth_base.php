<?php
/*

	crow auth 各認証モジュールのベース

*/
class crow_auth_base
{
	//--------------------------------------------------------------------------
	//	required : インスタンス作成
	//--------------------------------------------------------------------------
	public static function create( $options_ = [] )
	{
		//	生成したインスタンスを返却する
		crow_log::error( "not implemented crow_auth_base::create" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : ログイン済み？
	//--------------------------------------------------------------------------
	public function is_logined( $privilege_ = "" )
	{
		crow_log::error( "not implemented crow_auth_base::is_logined" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : ログイン処理
	//
	//	param1 と param2 については継承先で自由に仕様の定義を可能とする
	//--------------------------------------------------------------------------
	public function login( $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		crow_log::error( "not implemented crow_auth_base::login" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : ログアウト
	//--------------------------------------------------------------------------
	public function logout( $privilege_ = "" )
	{
		crow_log::error( "not implemented crow_auth_base::logout" );
		return true;
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードのモデルインスタンスを取得
	//--------------------------------------------------------------------------
	public function get_logined_row( $privilege_ = "", $table_name_ = false )
	{
		//	privilegeの計算
		$privilege = $this->calc_privilege( $privilege_ );
		if( $this->is_logined($privilege) === false ) return false;

		//	セッションから情報取得
		$sess = crow_session::get_instance();
		$row = $sess->get_property( 'auth_row', $privilege );
		if( $row === false ) return false;

		//	モデルインスタンスにする
		$table_name = $table_name_ ? $table_name_ : crow_config::get('auth.db.table');
		$table_design = crow::get_hdb_reader()->get_design( $table_name );
		$model_class = "model_".$table_name;

		//	モデルクラスが存在しなければ失敗とする
		if( class_exists($model_class) === false ) return false;

		//	インスタンスで返却
		$logined_row = $model_class::create();
		foreach( $table_design->fields as $field )
		{
			if( array_key_exists($field->name, $row) === true )
				$logined_row->{$field->name} = $row[$field->name];
		}
		return $logined_row;
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードのプライマリキー値を取得
	//--------------------------------------------------------------------------
	public function get_logined_id( $privilege_ = "", $table_name_ = false )
	{
		//	privilegeの計算
		$privilege = $this->calc_privilege( $privilege_ );
		if( $this->is_logined($privilege) === false ) return false;

		//	セッションから情報取得
		$sess = crow_session::get_instance();
		$row = $sess->get_property( 'auth_row', $privilege );
		if( $row === false ) return false;

		//	モデルインスタンスにする
		$table_name = $table_name_ ? $table_name_ : crow_config::get('auth.db.table');
		$table_design = crow::get_hdb_reader()->get_design( $table_name );
		$model_class = "model_".$table_name;

		//	モデルクラスが存在しなければ失敗とする
		if( class_exists($model_class) === false ) return false;

		//	ID値返却
		if( is_array($table_design->primary_key) === true ) return false;
		if( isset($row[$table_design->primary_key]) === false ) return false;
		return $row[$table_design->primary_key];
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードをDBから直接取得
	//--------------------------------------------------------------------------
	public function get_logined_full_row( $privilege_ = "", $table_name_ = false )
	{
		$primary_id = $this->get_logined_id($privilege_, $table_name_);
		if( $primary_id === false ) return false;

		//	DBから取得
		$table_name = $table_name_ ? $table_name_ : crow_config::get('auth.db.table');
		$model_class = "model_".$table_name;
		return $model_class::create_from_id($primary_id);
	}

	//--------------------------------------------------------------------------
	//	認証済みDBレコードのモデルインスタンスを更新する
	//--------------------------------------------------------------------------
	public function set_logined_row( $row_, $privilege_ = "" )
	{
		$privilege = $this->calc_privilege( $privilege_ );

		$logined_row  = $row_->to_named_array();
		$this->ignore_fields($logined_row);

		$sess = crow_session::get_instance();
		$sess->set_property( 'auth_row', $logined_row, $privilege );
	}

	//--------------------------------------------------------------------------
	//	DB認証なしに強制でログイン成功とする
	//--------------------------------------------------------------------------
	public function login_force( $row_, $privilege_ = "" )
	{
		$privilege = $this->calc_privilege( $privilege_ );

		$this->ignore_fields($row_);

		$sess = crow_session::get_instance();
		$sess->set_property( 'auth_row', $row_, $privilege );
		$sess->add_privilege( $privilege );
		return $row_;
	}

	//--------------------------------------------------------------------------
	//	セッションに入れないカラムを取り除く
	//--------------------------------------------------------------------------
	protected function ignore_fields( &$row_ )
	{
		$ignores = crow_config::get("auth.db.ignore_fields");
		if( strlen($ignores) > 0 )
		{
			$ymdhis = ["y", "m", "d", "h", "i", "s"];
			foreach( explode(",", $ignores) as $col )
			{
				$col = trim($col);
				if( array_key_exists($col, $row_) === true )
					unset($row_[$col]);

				foreach( $ymdhis as $s )
				{
					if( array_key_exists($col."_".$s, $row_) === true )
						unset($row_[$col."_".$s]);
				}
			}
		}
	}

	//--------------------------------------------------------------------------
	//	required : メールアドレス認証URLを発行し、ユーザにメールを送信する
	//--------------------------------------------------------------------------
	public function send_confirmation_code( $unique_id_, $password_, $to_mail_, $template_name_, $replace_map_, $attributes_ )
	{
		crow_log::error( "not implemented crow_auth_base::send_confirmation_code" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : 送信メールに記載されたURLでメールアドレスを認証する
	//--------------------------------------------------------------------------
	public function verify_confirmation_code( $hash_, $confirmation_code_ = "" )
	{
		crow_log::error( "not implemented crow_auth_base::verify_confirmation_code" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : メールアドレス認証URL再送
	//--------------------------------------------------------------------------
	public function resend_confirmation_code( $to_mail_, $template_name_, $replace_map_ )
	{
		crow_log::error( "not implemented crow_auth_base::resend_confirmation_code" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : パスワード忘れ用のURLを発行し、ユーザにメールを送信する
	//--------------------------------------------------------------------------
	public function forgot_password_start( $to_mail_, $template_name_, $replace_map_, $more_params_ = [] )
	{
		crow_log::error( "not implemented crow_auth_base::forgot_password_start" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : パスワード忘れ時の再発行処理
	//--------------------------------------------------------------------------
	public function forgot_password_exec( $new_pw_, $hash_, $confirmation_code_ )
	{
		crow_log::error( "not implemented crow_auth_base::forgot_password_exec" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : パスワードの強制変更
	//--------------------------------------------------------------------------
	public function change_password( $new_pw_, $unique_id_ = false )
	{
		crow_log::error( "not implemented crow_auth_base::change_password" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : 外部プロバイダ認証ユーザのメールログインの有効・無効
	//--------------------------------------------------------------------------
	public function change_mail_login_enabled( $unique_id_, $enabled_, $template_name_, $replace_map_ = [], $password_ = false, $password_column = false )
	{
		crow_log::error( "not implemented crow_auth_base::change_mail_login_enabled" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : クエリ指定でログイン処理
	//--------------------------------------------------------------------------
	public function login_with_sqlext( $ext_func_, $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		crow_log::error( "not implemented crow_auth_base::change_mail_login_enabled" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : 外部プロバイダ認証ログイン
	//--------------------------------------------------------------------------
	public function login_with_provider( $provider_, $row_, $privilege_ = "" )
	{
		crow_log::error( "not implemented crow_auth_base::login_with_provider" );
		return false;
	}

	//--------------------------------------------------------------------------
	//	required : 外部プロバイダユーザ作成・ログイン
	//--------------------------------------------------------------------------
	public function check_and_login_provider( $provider_code_, $func_create_user_, $privilege_ = "", $table_name_ = false, $param1_ = false )
	{
		return $this->login_with_provider_with_sqlext( $provider_code_, $func_create_user_, false, $privilege_, $table_name_, $param1_ );
	}

	public function login_with_provider_with_sqlext( $provider_code_, $func_create_user_, $ext_func_ = false, $privilege_ = "", $table_name_ = false, $param1_ = false )
	{
		//	許可されてないproviderならエラー
		$providers = crow_auth_provider::get_enable_providers();
		if( in_array($provider_code_, $providers) === false )
		{
			crow_log::warning("illegal provider");
			$this->add_error(crow_msg::get("auth.err.message"));
			return false;
		}

		$inst = crow_auth_provider::create($provider_code_);

		$provider_user_id = $inst->get_id();
		$provider_email = $inst->get_email();
		$login_name = $param1_ !== false ? $param1_ : crow_config::get('auth.db.login_name');
		$login_name_arr = explode("|", $login_name);
		if( count($login_name_arr) == 1 )
			$login_name_arr = explode("&",$login_name);

		//	user_idが取れない場合にはエラーとする
		if( strlen($provider_user_id) <= 0 || $provider_user_id == "0" )
		{
			crow_log::warning("failed to get user id");
			$this->add_error(crow_msg::get("auth.err.message"));
			return false;
		}

		//	プロバイダ紐づけテーブルに存在するか
		$provider_sub_colname = $provider_code_."_sub";
		$provider_email_colname = $provider_code_."_email";

		//	認証テーブル名
		$auth_table_name = crow_config::get("auth.db.table");
		$provider_table_name = crow_config::get("auth.provider.table");

		$model_auth = "model_".$auth_table_name;
		$model_auth_provider = "model_".$provider_table_name;

		$auth_provider_row = $model_auth_provider::create_from_sql(
			$model_auth_provider::sql_select_all()
				->and_where($provider_sub_colname, $provider_user_id)
		);

		$design_auth = crow::get_hdb()->get_design($auth_table_name);
		$auth_pk_colname = $design_auth->primary_key;

		$auth_row = false;

		//	現在ログイン状態なら紐づける
		if( crow_auth::is_logined() === true )
		{
			//	他に紐づけある？なければ紐づけ
			if( $auth_provider_row === false )
			{
				$auth_provider_row = $model_auth_provider::create_from_sql(
					$model_auth_provider::sql_select_all()
						->and_where($auth_pk_colname, self::get_logined_id())
				);
				if( $auth_provider_row === false )
				{
					$auth_provider_row = $model_auth_provider::create();
					$auth_provider_row->{$auth_pk_colname} = self::get_logined_id();
				}
				$auth_provider_row->{$provider_sub_colname} = $provider_user_id;
				if( $auth_provider_row->check_and_save() === false )
				{
					crow_log::warning("failed to update user_auth_provider - ".$auth_provider_row->get_last_error());
					$this->add_error($auth_provider_row->get_last_error());
					return false;
				}
			}
			//	あれば同一ユーザかチェック
			else
			{
				if( $auth_provider_row->{$auth_pk_colname} != strval(self::get_logined_id()) )
				{
					crow_log::warning("different user - target_column:".$auth_pk_colname.", login_id:".self::get_logined_id()." email:".$provider_email);
					$err = crow_msg::get("auth.err.already_connected_user");
					$err = str_replace(":mail", $provider_email, $err);
					$this->add_error($err);
					return false;
				}
			}

			$auth_row = $model_auth::create_from_id(self::get_logined_id());
			if( $auth_row === false )
			{
				crow_log::warning("failed to get user - target_column:".$auth_pk_colname.", login_id:".self::get_logined_id());
				$err = crow_msg::get("auth.err.none.user");
				$err = str_replace(":user", "", $err);
				$this->add_error($err);
				return false;
			}

			if( crow_config::get("auth.provider.enabled") === "true" )
			{
				$auth_row->auth_provider_last_login = $provider_code_;
				if( $auth_row->check_and_save() === false )
				{
					crow_log::warning("login via external auth_provider - ".$auth_row->get_last_error());
					$this->add_error($auth_row->get_last_error());
					return false;
				}
			}

			//	ログインして終了
			if( $this->login_with_provider($provider_code_, $auth_row, $privilege_) === false )
			{
				crow_log::warning("failed to login - target_column:".$auth_pk_colname.", login_id:".self::get_logined_id());
				return false;
			}
			self::set_logined_row($auth_row);

			return true;
		}
		//	未ログイン
		else
		{
			while(1)
			{
				//	紐づけあればログイン
				if( $auth_provider_row !== false )
				{
					$auth_row = $model_auth::create_from_id($auth_provider_row->{$auth_pk_colname});
					break;
				}

				//	メールアドレスは任意なので存在すればチェック
				//	ユーザのメール→紐づけのメールでチェックして該当があればそのユーザでログイン
				if( $provider_email === "" ) break;

				//	認証カラムが複数あった場合は先頭のmailもしくはmailcryptカラムをemailの検証カラムとする
				$params = [$login_name => $provider_email];
				if( count( $login_name_arr ) > 0 )
				{
					foreach( $login_name_arr as $login_column_name )
					{
						if( isset( $design_auth->fields[$login_column_name] ) )
						{
							if( $design_auth->fields[$login_column_name]->type == "mail" || $design_auth->fields[$login_column_name]->type == "mailcrypt" )
							{
								$params = [$login_column_name => $provider_email];
								break;
							}
						}
					}
				}

				$sql = $this->create_auth_sql( $ext_func_, $table_name_, $param1_ , $params);
				if( $sql === false )
				{
					return false;
				}

				$auth_row = $model_auth::create_from_sql($sql);
				if( $auth_row === false )
				{
					foreach( $providers as $provider )
					{
						$same_mail_provider = $model_auth_provider::create_from_sql(
							$model_auth_provider::sql_select_all()
								->and_where($provider."_email", $provider_email)
						);
						if( $same_mail_provider !== false )
						{
							$auth_row = $model_auth::create_from_sql(
								$model_auth::sql_select_all()
									->and_where($auth_pk_colname, $same_mail_provider->{$auth_pk_colname})
							);
							break;
						}
					}
				}
				//	同一メールのユーザがいればそちらに紐づけ
				if( $auth_row !== false )
				{
					$auth_provider_row = $model_auth_provider::create_from_sql(
						$model_auth_provider::sql_select_all()
							->and_where($auth_pk_colname, $auth_row->{$auth_pk_colname})
					);
					$auth_provider_row->{$provider_sub_colname} = $provider_user_id;
					$auth_provider_row->{$provider_email_colname} = $provider_email;
					if( $auth_provider_row->check_and_save() === false )
					{
						crow_log::warning("login via external auth_provider - ".$auth_row->get_last_error());
						$this->add_error($auth_row->get_last_error());
						return false;
					}

				}
				break;
			}

			//	ユーザ特定できればログインして終了
			if( $auth_row !== false )
			{
				if( $this->login_with_provider($provider_code_, $auth_row, $privilege_) === false )
				{
					crow_log::warning("failed to login - target_column:".$auth_pk_colname.", logined_id:".$auth_row->{$auth_pk_colname});
					return false;
				}

				return true;
			}

			//	ユーザ作成を実行
			return $func_create_user_();
		}
	}

	//--------------------------------------------------------------------------
	//	DBの該当レコードを取得するSQLを発行
	//
	//	$ext_func_ : 拡張関数
	//	$table_name : 認証テーブル名（未指定でconfigの値が使用される）
	//	$param1 : 認証カラム名（未指定でconfigの値が使用される）
	//	$param1_vals : 認証カラム名に対する入力値があれば連想配列で指定（キーは認証カラム名、なければリクエストから取得する）
	//--------------------------------------------------------------------------
	protected function create_auth_sql( $ext_func_, $table_name_ = false, $param1_ = false, $param1_vals_ = [] )
	{
		//	コンフィグ定義取得
		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get('auth.db.table');
		$login_name = $param1_ !== false ? $param1_ : crow_config::get('auth.db.login_name');
		$design = crow::get_hdb_reader()->get_design($table_name);

		//	'|' か '&' で複数指定可能
		$multi_opt = '';
		$login_name_arr = explode("|", $login_name);
		if( count($login_name_arr) == 1 )
		{
			$login_name_arr = explode("&",$login_name);
			if( count($login_name_arr) > 1 )
				$multi_opt = 'and';
		}
		else
		{
			$multi_opt = 'or';
		}

		//	入力値のチェック
		$model_class = "model_".$table_name;

		//	認証
		$sql = false;
		if( count($login_name_arr) == 1 )
		{
			//	ログイン名の入力チェック
			$login_name_value = isset( $param1_vals_[$login_name] ) ? $param1_vals_[$login_name] : crow_request::get($login_name);
			if( strlen( $login_name_value ) <= 0 )
			{
				$err_key = crow_config::get('auth.error.lang.name');
				$this->add_error(crow_msg::get($err_key));
				return false;
			}

			//	暗号カラムかどうかでwhere句が異なる
			$ftype = $design->fields[$login_name]->type;

			$sql = in_array($ftype, ["tinycrypt", "varcrypt", "crypt", "bigcrypt", "mailcrypt"]) ?
				$model_class::sql_select_all()->and_where($login_name, "=", $login_name_value, true) :
				$model_class::sql_select_all()->and_where($login_name, $login_name_value)
				;
			if( $ext_func_ !== false )
			{
				if( $ext_func_($sql) === false )
				{
					$err_key = crow_config::get('auth.error.lang.nohit');
					$this->add_error(crow_msg::get($err_key));
					return false;
				}
			}
		}
		else
		{
			$sql = $model_class::sql_select_all();
			$sql->and_where_open();

			//	orの場合、リクエストパラメータ名は"login_name"固定とする
			//	andの場合は、config記載のカラム名=リクエストパラメータ名とする
			if( $multi_opt == "or" )
			{
				$login_name_value = crow_request::get("login_name", "");
				if( strlen( $login_name_value ) <= 0 )
				{
					$err_key = crow_config::get('auth.error.lang.name');
					$this->add_error(crow_msg::get($err_key));
					return false;
				}
			}

			for( $i = 0; $i < count($login_name_arr); $i++ )
			{
				$name = $login_name_arr[$i];

				if( $multi_opt == "and" )
				{
					$login_name_value = isset( $param1_vals_[$name] ) ? $param1_vals[$name] : crow_request::get($name, "");
				}

				$ftype = $design->fields[$name]->type;
				$is_crypt = in_array($ftype, ["tinycrypt", "varcrypt", "crypt", "bigcrypt", "mailcrypt"]);

				if( $i == 0 )
				{
					if( $is_crypt ) $sql->where( $name, "=", $login_name_value, true );
					else  $sql->where( $name, $login_name_value );
				}
				else
				{
					if( $is_crypt )
					{
						if( $multi_opt == 'or' ) $sql->or_where( $name, "=", $login_name_value, true );
						else if( $multi_opt == 'and' ) $sql->and_where( $name, "=", $login_name_value, true );
					}
					else
					{
						if( $multi_opt == 'or' ) $sql->or_where( $name, $login_name_value );
						else if( $multi_opt == 'and' ) $sql->and_where( $name, $login_name_value );
					}
				}
			}
			$sql->where_close();
			if( $ext_func_ !== false )
			{
				if( $ext_func_($sql) === false )
				{
					$err_key = crow_config::get('auth.error.lang.nohit');
					$this->add_error(crow_msg::get($err_key));
					return false;
				}
			}
		}

		//	例外
		if( $sql === false )
		{
			$err = "fatal error no sql";
			$this->add_error($err);
			return false;
		}
		return $sql;
	}

	//--------------------------------------------------------------------------
	//	最後のエラー取得
	//--------------------------------------------------------------------------
	public function get_last_error()
	{
		return count($this->m_errors) > 0 ? end($this->m_errors) : '';
	}

	//--------------------------------------------------------------------------
	//	全てのエラー取得
	//--------------------------------------------------------------------------
	public function get_errors()
	{
		return $this->m_errors;
	}

	//--------------------------------------------------------------------------
	//	内部用
	//--------------------------------------------------------------------------

	//--------------------------------------------------------------------------
	//	暗号化
	//	認証URLにユーザ情報埋め込む用
	//	暗号化・復号化メソッドはCognitoのメッセージトリガーで動作するLambdaの内の
	//	encrypt関数と整合性とれるようにする
	//--------------------------------------------------------------------------
	public function encrypt( $data_ )
	{
		$key = crow_config::get("auth.mail.cryptkey");
		$iv_size = openssl_cipher_iv_length($this->m_crypto_method);
		$iv = openssl_random_pseudo_bytes($iv_size);
		$cipher_text = openssl_encrypt($data_, $this->m_crypto_method, $key, OPENSSL_RAW_DATA, $iv);
		$cipher_text_hex = bin2hex($cipher_text);
		$iv_hex = bin2hex($iv);
		return $iv_hex.":".$cipher_text_hex;
	}

	//--------------------------------------------------------------------------
	//	復号化
	//--------------------------------------------------------------------------
	public function decrypt( $data_ )
	{
		$key = crow_config::get("auth.mail.cryptkey");
		$iv_size = openssl_cipher_iv_length($this->m_crypto_method);
		$data = explode(":", $data_);
		$iv = hex2bin($data[0]);
		$cipher_text = hex2bin($data[1]);
		return openssl_decrypt($cipher_text, $this->m_crypto_method, $key, OPENSSL_RAW_DATA, $iv);
	}

	//	privilegeの計算
	protected function calc_privilege( $privilege_ )
	{
		$privilege = $privilege_;
		if( strlen($privilege) <= 0 )
		{
			$privilege = crow_config::get('auth.privilege');
			if( strlen($privilege) <= 0 ) $privilege = "auth";
		}
		return $privilege;
	}

	//	エラー追加
	protected function add_error( $msg_ )
	{
		$this->m_errors[] = $msg_;
	}

	//	private
	private $m_errors = [];

	private $m_crypto_method = "aes-256-cbc";

}
?>
