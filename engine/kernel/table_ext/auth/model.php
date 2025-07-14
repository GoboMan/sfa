/*
	db_designのテーブルオプションに、"auth"が付与されている場合に、
	crow_db.php により追加で読み込まれるコード
	db_designで、

		node, auth()
		{
			...カラム...
		}

	とすると追加される。
	上記例では各種オプションが指定されていないため、
	各ロールのベースクラスなどで、最初に設定を行う。

	model_node::set_opt_pw_min(8);										//	パスワード長最小
	model_node::set_opt_pw_rule(["NUM", "LOWER", "UPPER", "SYMBOL"]);	//	パスワード生成文字種別
	model_node::set_opt_pw_must(["NUM", "LOWER", "UPPER", "SYMBOL"]);	//	パスワード必須文字種別

	それらをdb_designで一括で指定する場合には、

		node, auth(8, NUM|LOWER|UPPER|SYMBOL, NUM|LOWER|UPPER|SYMBOL)
		{
			...カラム...
		}

	とすればよい。

	デフォルトの値はAws/Cognitoのデフォルトのパスワード要件を満たす
		パスワード長最小8
		パスワード長最大99
		少なくとも 1 つの数字を含む
		少なくとも 1 つの特殊文字 (^ $ * . [ ] { } ( ) ? - " ! @ # % & / \ , > < ' : ; | _ ~ ` + =) を含む
		少なくとも 1 つの大文字を含む
		少なくとも 1 つの小文字を含む

*/

//	db_designのオプションで指定した駆動条件
//	オプションを省略した場合や、自分で指定する場合には set_opt_xxx() で指定する
private static $m_opt_pw_min = false;
private static $m_opt_pw_rule = false;
private static $m_opt_pw_must = false;

//--------------------------------------------------------------------------
//	現在オプションの取得
//--------------------------------------------------------------------------
public static function get_opt_pw_min()
{
	return self::$m_opt_pw_min;
}
public static function get_opt_pw_rule()
{
	return self::$m_opt_pw_rule;
}
public static function get_opt_pw_must()
{
	return self::$m_opt_pw_must;
}

//--------------------------------------------------------------------------
//	オプションの手動指定
//--------------------------------------------------------------------------
//	パスワード長最小の指定
public static function set_opt_pw_min( $length_ )
{
	self::$m_opt_pw_min = intval($length_);
}

//	パスワードに含む文字種別の指定。すべてを含む場合には"*"を指定する
public static function set_opt_pw_rule( $rules_ )
{
	$wild = false;
	if( is_array($rules_) === false )
	{
		if( $rules_ !== "*" )
		{
			crow_log::error("rules not array but not a wildcard");
			return false;
		}
		$wild = true;
	}
	else
	{
		foreach( $rules_ as $rule )
		{
			if( $rule == "*" )
			{
				$wild = true;
				break;
			}
		}
	}
	self::$m_opt_pw_rule = $wild==true ? ["*"] : $rules_;
}

//	パスワードに含む必須文字種別の指定。すべてを必須にする場合には"*"を指定する
public static function set_opt_pw_must( $musts_ )
{
	$wild = false;
	if( is_array($musts_) === false )
	{
		if( $musts_ !== "*" )
		{
			crow_log::error("rules not array but not a wildcard");
			return false;
		}
		$wild = true;
	}
	else
	{
		foreach( $musts_ as $must )
		{
			if( $must == "*" )
			{
				$wild = true;
				break;
			}
		}
	}
	self::$m_opt_pw_must = $wild==true ? ["*"] : $musts_;
}

//--------------------------------------------------------------------------
//	各種値セット
//--------------------------------------------------------------------------
//	メールの会員登録情報をセット
//	パスワード指定が無い場合は自動生成
public function set_mail_signup( $mail_addr_, $pw_ = false, $pw_column_ = false )
{
	$this->auth_mail_addr = "";
	$this->set_verifing_mail($mail_addr_);

	$pw = $pw_;
	if( $pw === false )
		$pw = $this->generate_password();
	$this->set_login_pw($pw, $pw_column_);
	$this->auth_provider_mail_login_enabled = true;
	$this->auth_provider_is_origin_external = false;
	return $this;
}

//	プロバイダ連携の会員登録情報をセット
//	メールアドレス指定がある場合は認証済みとして、パスワード指定が無い場合は自動生成
public function set_provider_signup( $provider_, $mail_addr_ = "", $pw_ = false, $pw_column_ = false )
{
	$this->auth_provider_last_login = $provider_;

	if( strlen($mail_addr_) > 0 )
	{
		$this->auth_mail_addr = $mail_addr_;
		$this->auth_mail_addr_verified = true;
	}
	$this->auth_mail_addr_verify = "";

	$pw = $pw_;
	if( $pw === false )
		$pw = $this->generate_password();
	$this->set_login_pw($pw, $pw_column_);

	$this->auth_provider_mail_login_enabled = false;
	$this->auth_provider_is_origin_external = true;
	return $this;
}

//	認証中→認証済みとする
public function verified_mail()
{
	$this->auth_mail_addr = $this->auth_mail_addr_verify;
	$this->auth_mail_addr_verify = "";
	$this->auth_mail_addr_verified = true;
	return $this;
}

//	メールを未認証にする
//	既に認証済みのメールを未認証とする場合
public function unverified_mail()
{
	$this->auth_mail_addr_verify = $this->auth_mail_addr;
	$this->auth_mail_addr = "";
	$this->auth_mail_addr_verified = false;
	return $this;
}

//	認証前のメールアドレスをセット
//	メールアドレスに変更など認証済みのメールアドレスを変更せずに利用する
public function set_verifing_mail( $mail_addr_ )
{
	$this->auth_mail_addr_verify = $mail_addr_;
	$this->auth_mail_addr_verified = false;
	return $this;
}

//	認証前のメールアドレスを削除
public function remove_verifing_mail()
{
	$this->auth_mail_addr_verify = "";
	$this->auth_mail_addr_verified = strlen($this->auth_mail_addr) > 0;
	return $this;
}

//	パスワードをセット
public function set_login_pw( $pw_, $pw_column_ = false )
{
	$pw_column = $pw_column_ === false ? crow_config::get("auth.db.login_password") : $pw_column_;
	$this->{$pw_column} = $pw_;
	$this->auth_cognito_login_pw = crow_auth::get_auth_instance()->encrypt($pw_);
	return $this;
}

//--------------------------------------------------------------------------
//	パスワード設定
//	rule_,must_はNUM,LOWER,UPPER,SYMBOLの文字を指定
//--------------------------------------------------------------------------
public function generate_password( $min_ = false, $max_ = false, $rule_ = false, $must_ = false )
{
	list($d_pw_min, $d_pw_rule, $d_pw_must) = self::get_auth_defines();
	$min = $min_ === false ? $d_pw_min : $min_;
	$rule_row = $rule_ === false ? $d_pw_rule : explode(",", $rule_);
	$must_row = $must_ === false ? $d_pw_must : explode(",", $must_);

	$max = false;
	if( $max_ !== false )
	{
		$hdb = crow::get_hdb_reader();
		$design = $hdb->get_design($this->m_table_name);
		foreach( $design->fields as $field )
		{
			if( $field->type !== "password" ) continue;

			$size = $field->size;
			break;
		}
		$max = $max_ < $size ? $size : $max_;
	}
	else
	{
		$max = $min;
	}

	$length_row = [];
	for( $i=$min; $i<=$max; $i++ )
		$length_row[] = $i;

	$length = $length_row[array_rand($length_row)];

	$num_seed = "1234567890";
	$lower_seed = "abcdefghijklmnopqrstuvwxyz";
	$upper_seed = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$symbol_seed = self::$m_password_symbols;
	$num_row = str_split($num_seed);
	$lower_row = str_split($lower_seed);
	$upper_row = str_split($upper_seed);
	$symbol_row = str_split($symbol_seed);

	$pwd_row = [];

	//	ルールにのっとった文字列は最初に追加しておく
	if( array_search("NUM", $must_row) !== false )
		$pwd_row[] = $num_row[array_rand($num_row)];
	if( array_search("LOWER", $must_row) !== false )
		$pwd_row[] = $lower_row[array_rand($lower_row)];
	if( array_search("UPPER", $must_row) !== false )
		$pwd_row[] = $upper_row[array_rand($upper_row)];
	if( array_search("SYMBOL", $must_row) !== false )
		$pwd_row[] = $symbol_row[array_rand($symbol_row)];

	while(1)
	{
		$rule = $rule_row[array_rand($rule_row)];
		switch( $rule )
		{
			case "NUM":$str = $num_row[array_rand($num_row)];break;
			case "LOWER":$str = $lower_row[array_rand($lower_row)];break;
			case "UPPER":$str = $upper_row[array_rand($upper_row)];break;
			case "SYMBOL":$str = $symbol_row[array_rand($symbol_row)];break;
		}

		$pwd_row[] = $str;
		if( $length == count($pwd_row) ) break;
	}

	while(1)
	{
		shuffle($pwd_row);
		if( $pwd_row[0] === ":" ) continue;

		break;
	}

	return implode("", $pwd_row);
}

//--------------------------------------------------------------------------
//	パスワード要件チェック
//--------------------------------------------------------------------------
public function check_password_rule( $password_, $min_ = false, $max_ = false, $rule_ = false, $must_ = false )
{
	list($d_pw_min, $d_pw_rule, $d_pw_must) = self::get_auth_defines();
	$min = $min_ === false ? $d_pw_min : $min_;
	$max = $min;
	$rule = $rule_ === false ? $d_pw_rule : $rule_;
	$must = $must_ === false ? $d_pw_must : $must_;

	if( $max_ !== false )
	{
		$max = $max_;
	}
	else
	{
		$hdb = crow::get_hdb_reader();
		$table_design = $hdb->get_design($this->m_table_name);
		foreach( $table_design->fields as $field )
		{
			if( $field->type !== "password" ) continue;

			$max = $field->size;
			break;
		}
	}

	$rule_exp = "";
	$rule_exp_must = "";
	if( array_search("NUM", $rule) !== false )
	{
		$rule_exp .= "0-9";
		if( array_search("NUM", $must) !== false )
		{
			$rule_exp_must .= "(?=.*[0-9])";
		}
	}

	if( array_search("LOWER", $rule) !== false )
	{
		$rule_exp .= "a-z";
		if( array_search("LOWER", $must) !== false )
		{
			$rule_exp_must .= "(?=.*[a-z])";
		}
	}

	if( array_search("UPPER", $rule) !== false )
	{
		$rule_exp .= "A-Z";
		if( array_search("UPPER", $must) !== false )
		{
			$rule_exp_must .= "(?=.*[A-Z])";
		}
	}

	if( array_search("SYMBOL", $rule) !== false )
	{
		$symbol_seed = preg_quote(self::$m_password_symbols, "/");
		$rule_exp .= $symbol_seed;
		if( array_search("SYMBOL", $must) !== false )
		{
			$rule_exp_must .= "(?=.*[".$symbol_seed."])";
		}
	}

	$regexp = "/^".$rule_exp_must."[".$rule_exp."]{".$min.",".$max."}$/";
	return preg_match($regexp, $password_) == 1;
}

//--------------------------------------------------------------------------
//	バリデーションの拡張
//--------------------------------------------------------------------------
public function validation_ext()
{
	//	自動/手動で指定した定義をマージして取得
	list($d_pw_min, $d_pw_rule, $d_pw_must) = self::get_auth_defines();
	$d_pw_max = $d_pw_min;

	$hdb = crow::get_hdb_reader();
	$table_design = $hdb->get_design($this->m_table_name);
	$password_column = "";
	foreach( $table_design->fields as $field )
	{
		if( $field->type !== "password" ) continue;

		$d_pw_max = $field->size;
		$password_column = $field->name;
		break;
	}

	$password = $this->{$password_column};
	if( $password !== "" )
	{
		if( $this->check_password_rule($password) === false )
		{
			$must_str_row = [];
			foreach( $d_pw_must as $must )
				$must_str_row[] = crow_msg::get('auth.err.pw_'.strtolower($must));

			$err = crow_msg::get('auth.err.pw_length_and_must');
			$err = str_replace(":min", $d_pw_min, $err);
			$err = str_replace(":max", $d_pw_max, $err);
			$err = str_replace(":must", implode(",",$must_str_row), $err);
			$this->push_validation_error("auth", $err);
			return false;
		}
	}

	return true;
}

//--------------------------------------------------------------------------
//	db_design 上でのオプション指定を読み込む
//	すでに手動で指定されていた場合は何もしない（手動指定が優先される）
//--------------------------------------------------------------------------
public static function get_auth_defines()
{
	$d_pw_min = self::$m_d_pw_min;
	$d_pw_rule = self::$m_d_pw_rule;
	$d_pw_must = self::$m_d_pw_must;

	$hdb = crow::get_hdb_reader();
	if( $hdb === false )
	{
		crow_log::error('failed to get db handle, auth::read_auth_defines');
		return [$d_pw_min, $d_pw_rule, $d_pw_must];
	}

	//	手動指定の分を適用
	if( self::$m_opt_pw_min !== false ) $d_pw_min = self::$m_opt_pw_min;
	if( self::$m_opt_pw_rule !== false ) $d_pw_rule = self::$m_opt_pw_rule;
	if( self::$m_opt_pw_must !== false ) $d_pw_must = self::$m_opt_pw_must;

	//	デフォルト定義を適用
	$table_design = $hdb->get_design(self::create()->m_table_name);
	foreach( $table_design->options as $option )
	{
		if( $option['name'] != "auth" ) continue;
		if( self::$m_opt_pw_min === false )
		{
			$opt = isset($option['args'][0]) ? trim($option['args'][0]) : '';
			$d_pw_min = crow_validation::check_num($opt) === true ? $opt : $d_pw_min;
		}
		if( self::$m_opt_pw_rule === false )
		{
			$opt = isset($option['args'][1]) ? trim($option['args'][1]) : '';
			$rule = $opt !== '' ? $opt : '';
			if( $rule !== "" && $rule !== "*" )
				$d_pw_rule = explode("|", $rule);
		}
		if( self::$m_opt_pw_must === false )
		{
			$opt = isset($option['args'][2]) ? trim($option['args'][2]) : '';
			$must = $opt !== '' ? $opt : '';
			if( $must !== "" && $must !== "*" )
				$d_pw_must = explode("|", $must);
		}

		break;
	}

	return [$d_pw_min, $d_pw_rule, $d_pw_must];
}

//	private
private static $m_d_pw_min = 8;
private static $m_d_pw_rule = ["NUM","LOWER","UPPER","SYMBOL"];
private static $m_d_pw_must = ["NUM","LOWER","UPPER","SYMBOL"];
private static $m_password_symbols = "^$*)?-!@#%&\/,><:;|_~`+=";

