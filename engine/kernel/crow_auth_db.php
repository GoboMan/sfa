<?php
/*

	crow auth 認証モジュール DB向け


	----------------------------------------------------------------------------
	config
	----------------------------------------------------------------------------

		auth.db.table
			本テーブルに存在するカラムで認証を行う。
			認証が完了すると、ここで指定したテーブルのモデルインスタンスを取得する。
			省略した場合、login()時に指定する必要がある。

		auth.db.login_name
			ログイン名を表すフィールドの名前。
			省略した場合、login()時に指定する必要がある。

			複数フィールドのうちどれか一つが一致すればよい場合、
			field1|field2のように、"|"でor条件を指定できる。
			複数フィールドの全てに一致する必要がある場合、
			field1&field2のように、"&"でand条件を指定できる。
			"|"と"&"の同時使用はできない。

		auth.db.login_password
			ログインパスワードを表すフィールドの名前
			省略した場合、login()時に指定する必要がある。

		auth.db.auto_update
			trueを指定すると、auth.db.tableのモデルクラスを通した保存時に、
			自動でset_logined_row()による更新を行う。

	----------------------------------------------------------------------------
	ログインクエリをカスタマイズしたい場合
	----------------------------------------------------------------------------
	「login_name=入力値 and login_pass=入力値」

	上記以上の条件を施したクエリ、またはまったく別のクエリを以て認証を行いたい場合には、
	ログイン時のメソッドに、login()ではなく、login_with_sqlext()を使える。

	login_with_sqlext()はlogin()と異なり、インスタンスのメソッドとなる
	そのため、crow_auth::get_auth_instance() で取得したインスタンスに対してコールすること。

	login_with_sqlext()の第一引数にSQLオブジェクトを引数にとるクロージャメソッドを指定できる。

	例）
		crow_auth::login_with_sqlext( function( &$sql_ )
		{
			//	通常のログインルールに加え、ユーザグループが3である条件を追加する
			$sql_->and_where('user_group', '3);
		});

	また、上記のクロージャメソッド内でログイン失敗が判明した場合には、
	false を返却するとログイン失敗となる。

	例）
		crow_auth::login_with_sqlext( function( &$sql_ )
		{
			//	リクエストパラメータで client_id が指定されていない場合はエラーとする
			if( crow_request::get('client_id',false) === false )
				return false;

			//	通常のログインルールに加え、ユーザグループが3である条件を追加する
			$sql_->and_where('user_group', '3);
		});
*/
class crow_auth_db extends crow_auth_base
{
	//--------------------------------------------------------------------------
	//	override : インスタンス作成
	//--------------------------------------------------------------------------
	public static function create( $options_ = [] )
	{
		return new self();
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
	//--------------------------------------------------------------------------
	public function login( $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		return $this->login_with_sqlext( false, $privilege_, $table_name_, $param1_, $param2_ );
	}
	public function login_with_sqlext( $ext_func_, $privilege_ = "", $table_name_ = false, $param1_ = false, $param2_ = false )
	{
		//	privilegeの計算
		$privilege = $this->calc_privilege( $privilege_ );

		//	コンフィグ定義取得
		$table_name = $table_name_ !== false ? $table_name_ : crow_config::get('auth.db.table');
		$login_pass = $param2_ !== false ? $param2_ : crow_config::get('auth.db.login_password');
		$login_pass_value = crow_request::get($login_pass);

		//	パスワードの入力チェック
		if( strlen( $login_pass_value ) <= 0 )
		{
			$err_key = crow_config::get('auth.error.lang.password');
			$this->add_error(crow_msg::get($err_key));
			return false;
		}

		//	テーブル定義から、パスワードがハッシュタイプかどうかをチェックする
		$model_class = "model_".$table_name;
		$is_hash = false;
		$hdb = crow::get_hdb_reader();
		$design = $hdb->get_design($table_name);
		if( $design !== false )
		{
			if( isset($design->fields[$login_pass]) === true )
			{
				if( $design->fields[$login_pass]->type == 'password' )
					$is_hash = true;
			}
		}

		//	sql作成
		$sql = $this->create_auth_sql($ext_func_, $table_name_, $param1_);
		if( $sql === false )
		{
			return false;
		}

		$sql = $sql->build();
		$hdb = crow::get_hdb_reader();
		$rid = $hdb->query($sql);
		$row = false;

		if( $rid === false || $rid->num_rows() <= 0 )
		{
			$err_key = crow_config::get('auth.error.lang.nohit');
			$this->add_error(crow_msg::get($err_key));
			return false;
		}
		$row = $rid->get_row();

		//	パスワードハッシュならここでベリファイ
		if( $is_hash === true )
		{
			if( password_verify($login_pass_value, $row[$login_pass]) === false )
			{
				$err_key = crow_config::get('auth.error.lang.nohit');
				$this->add_error(crow_msg::get($err_key));
				return false;
			}
		}

		//	ハッシュ以外なら比較
		else
		{
			if( $row[$login_pass] != $login_pass_value )
			{
				$err_key = crow_config::get('auth.error.lang.nohit');
				$this->add_error(crow_msg::get($err_key));
				return false;
			}
		}

		//	ログイン成功したので、取得した情報をセッションに保存しておく
		$table_design = crow::get_hdb_reader()->get_design( $table_name );
		$logined_row = [];
		foreach( $table_design->fields as $field )
		{
			$logined_row[$field->name] = $field->type == "datetime" ?
				strtotime($row[$field->name]) : $row[$field->name];
		}
		$this->ignore_fields($logined_row);
		$sess = crow_session::get_instance();
		$sess->set_property('auth_row', $logined_row, $privilege);
		$sess->add_privilege($privilege);

		//	取得した情報を返却
		return $row;
	}

	//--------------------------------------------------------------------------
	//	override : ログアウト
	//--------------------------------------------------------------------------
	public function logout( $privilege_ = "" )
	{
		$sess = crow_session::get_instance();
		$sess->clear_properties($privilege_);
		$sess->remove_privilege($this->calc_privilege($privilege_));
		return true;
	}

	//--------------------------------------------------------------------------
	//	override : ユーザ登録時のメールアドレス認証コードの送信
	//--------------------------------------------------------------------------
	public function send_confirmation_code( $unique_id_, $password_, $to_mail_, $template_name_, $replace_map_, $attributes_ = [] )
	{
		//	認証コードを生成
		$code = random_str();

		$params_for_hash = json_encode(
		[
			"email" => $to_mail_,
			"unique_id" => $unique_id_,
		]);
		$hash = $this->encrypt($params_for_hash);
		$verify_url = crow::make_url
		(
			crow_config::get("auth.mail.verify.module"),
			crow_config::get("auth.mail.verify.action"),
			["hash" => $hash]
		);
		$resend_code_url = crow::make_url
		(
			crow_config::get("auth.mail.resend_code.module"),
			crow_config::get("auth.mail.resend_code.action")
		);

		$replace_map = array_merge
		(
			$replace_map_,
			[
				"PASSWORD" => $password_,
				"VERIFY_URL" => $verify_url,
				"RESEND_CODE_URL" => $resend_code_url,
				"CONFIRMATION_CODE" => $code,
			]
		);

		//	メール送信
		$result = crow_mail::create()
			->name(crow_config::get("auth.mail.name"))
			->from(crow_config::get("auth.mail.from"))
			->to($to_mail_)
			->template($template_name_, $replace_map)
			->send()
			;

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
	public function verify_confirmation_code( $hash_, $confirmation_code_ = "" )
	{
		$json_data = $this->decrypt($hash_);
		$data = json_decode($json_data, true);
		if( isset($data["unique_id"]) === false ) return false;

		$unique_id = $data["unique_id"];

		//	認証状態を更新
		$table_name = crow_config::get("auth.db.table");

		$model = "model_".$table_name;
		$auth_row = $model::create_from_id($unique_id);
		if( $auth_row === false )
		{
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		//	メール系カラムを更新
		$auth_row->auth_mail_addr = $auth_row->auth_mail_addr_verify;
		$auth_row->auth_mail_addr_verify = "";
		$auth_row->auth_mail_addr_verified = true;
		if( $auth_row->check_and_save() === false )
		{
			$err = crow_msg::get("auth.err.verify_confirmation_code");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	override : ユーザー登録時のメールアドレス認証コード再送
	//--------------------------------------------------------------------------
	public function resend_confirmation_code( $to_mail_, $template_name_, $replace_map_ )
	{
		$table_name = crow_config::get("auth.db.table");

		$model = "model_".$table_name;
		$auth_row = $model::create_from_sql
		(
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

		if( $auth_row->email_verified == true )
		{
			$err = crow_msg::get("auth.err.verified_mail");
			$err = str_replace(":user", $to_mail_, $err);
			$this->add_error($err);
			return false;
		}

		$design = crow::get_hdb()->get_design($table_name);
		$pk_column_name = $design->primary_key;
		$unique_id = strval($auth_row->{$pk_column_name});

		//	認証コードを生成
		$code = crow_utility::random_str();

		$params_for_hash = json_encode(
		[
			"email" => $to_mail_,
			"unique_id" => $unique_id,
		]);
		$hash = $this->encrypt($params_for_hash);
		$verify_url = crow::make_url
		(
			crow_config::get("auth.mail.verify.module"),
			crow_config::get("auth.mail.verify.action"),
			["hash" => $hash]
		);

		$replace_map = array_merge
		(
			$replace_map_,
			[
				"VERIFY_URL" => $verify_url,
				"CONFIRMATION_CODE" => $code,
			]
		);

		//	メール送信
		$result = crow_mail::create()
			->name(crow_config::get("auth.mail.name"))
			->from(crow_config::get("auth.mail.from"))
			->to($to_mail_)
			->template($template_name_, $replace_map)
			->send()
			;

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
	public function forgot_password_start( $to_mail_, $template_name_, $replace_map_, $more_params_ = [], $sql_ext_ = false )
	{
		//	URLを作成
		$table_name = crow_config::get("auth.db.table");

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
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $to_mail_, $err);
			$this->add_error($err);
			return false;
		}

		$design = crow::get_hdb()->get_design($table_name);
		$pk_column_name = $design->primary_key;
		$unique_id = strval($auth_row->{$pk_column_name});

		$params_for_hash = json_encode(
		[
			"unique_id" => $unique_id,
		]);
		$hash = $this->encrypt($params_for_hash);

		$code = random_str();
		$params = array_merge(["hash"=>$hash, "code" => $code], $more_params_);
		$reset_url = crow::make_url
		(
			crow_config::get("auth.mail.reset_password.module"),
			crow_config::get("auth.mail.reset_password.action"),
			$params
		);

		$replace_map = array_merge
		(
			$replace_map_,
			[
				"RESET_URL" => $reset_url,
			]
		);

		//	メール送信
		$result = crow_mail::create()
			->name(crow_config::get("auth.mail.name"))
			->from(crow_config::get("auth.mail.from"))
			->to($to_mail_)
			->template($template_name_, $replace_map)
			->send()
			;
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
	//	override : パスワード忘れ時の再発行処理
	//--------------------------------------------------------------------------
	public function forgot_password_exec( $new_pw_, $hash_, $confirmation_code_ )
	{
		//	ユーザ情報取得
		$params_for_hash = $this->decrypt($hash_);
		$data = json_decode($params_for_hash, true);
		if( isset($data["unique_id"]) === false ) return false;
		$cid = $data["unique_id"];

		$result = $this->change_password($new_pw_, $cid);
		if( $result === false )
		{
			$err = crow_msg::get("auth.err.change_password");
			$err = str_replace(":user", $cid, $err);
			$err = str_replace(":last_error", "", $err);
			$this->add_error($err);
			return false;
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	override : パスワードの強制変更
	//--------------------------------------------------------------------------
	public function change_password( $new_pw_, $unique_id_ = false )
	{
		$unique_id = $unique_id_ !== false ? $unique_id_ : crow_auth::get_logined_id();
		if( $unique_id <= 0 ) return false;

		$table_name = crow_config::get("auth.db.table");
		$model = "model_".$table_name;
		$auth_row = $model::create_from_id($unique_id);
		if( $auth_row === false )
		{
			$err = crow_msg::get("auth.err.none.user");
			$err = str_replace(":user", $unique_id, $err);
			$this->add_error($err);
			return false;
		}

		$pw_field = crow_config::get('auth.db.login_password');
		$auth_row->{$pw_field} = $new_pw_;
		if( $auth_row->check_and_save() === false )
		{
			$err = crow_msg::get("auth.err.change_password");
			$err = str_replace(":user", $unique_id, $err);
			$err = str_replace(":last_error", "", $err);
			$this->add_error($err);
			return false;
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	外部プロバイダ認証ユーザのメールログインの有効・無効
	//--------------------------------------------------------------------------
	public function change_mail_login_enabled( $unique_id_, $enabled_, $template_name_, $replace_map_ = [], $password_ = false, $password_column_ = false )
	{
		$enabled = $enabled_==="true" ? true : false;

		$hdb = crow::get_hdb();

		$table = crow_config::get("auth.db.table");
		$design = $hdb->get_design($table);
		$model_auth = "model_".$table;
		$auth_row = $model_auth::create_from_id($unique_id_);
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

		$min = $auth_row->get_opt_pw_min();
		$max = $min;
		foreach( $design->fields as $field )
		{
			if( $field->type !== "password" ) continue;

			$max = $field->size;
			break;
		}

		//	DB指定のカラムの最大長とする
		if( $enabled_ == true )
		{
			$min = $max;
			$max = $max;
		}

		$password = $password_ === false ? $auth_row->generate_password($min) : $password_;
		$password_column = $password_column_ === false ? crow_config::get("auth.db.login_password") : $password_column_;

		$hdb->begin();
		{
			//	DB更新
			$auth_row->{$password_column} = $password;
			$auth_row->auth_cognito_login_pw = $this->encrypt($password);
			$auth_row->auth_provider_mail_login_enabled = $enabled;
			if( $auth_row->check_and_save() === false )
			{
				$hdb->rollback();
				$err = crow_msg::get("auth.err.update_user");
				$err = str_replace(":user", $unique_id_, $err);
				$err = str_replace(":last_error", $auth_row->get_last_error(), $err);
				$this->add_error($err);
				return false;
			}

			$replace_map = array_merge
			(
				[
					"MAIL_ADDR" => $auth_row->auth_mail_addr,
					"PASSWORD" => $password,
				],
				$replace_map_
			);

			//	有効化した場合にはメール送信
			if( $enabled === true )
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
	//	override : 外部プロバイダ認証ログイン
	//--------------------------------------------------------------------------
	public function login_with_provider( $provider_code_, $row_, $privilege_ = "" )
	{
		if( crow_config::get("auth.provider.enabled") !== "true" ) return false;

		//	外部認証拡張用カラムを更新
		$row_->auth_provider_last_login = $provider_code_;
		if( $row_->check_and_save() === false )
		{
			$design = crow::get_hdb()->get_design(crow_config::get("auth.db.table"));
			$pk_colname = $design->primary_key;

			$err = crow_msg::get("auth.err.update_user");
			$err = str_replace($err, ":user", $row_->{$pk_colname}, $err);
			$err = str_replace($err, ":last_error", $row_->get_last_error(), $err);
			$this->add_error($err);
			return false;
		}

		return $this->login_force($row_->to_named_array(), $privilege_);
	}

}
?>
