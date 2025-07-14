<?php
/*

	crow session


	crow_session::get_instance()
	で、インスタンスを取得して、操作すること。

	ログインしているユーザの権限を、セッションに追加する場合には、
	add_privilege() を使用する。
	ここで追加された権限を参照して、アクションが実行可能かどうかがチェックされる。

	ログアウト時には、remove_privilege()や、clear_privilege()を利用して
	権限を削除すること。
*/
class crow_session
{
	//--------------------------------------------------------------------------
	//	インスタンス取得
	//--------------------------------------------------------------------------
	public static function get_instance()
	{
		static $instance = false;
		if( $instance === false )
		{
			$instance = new crow_session();
			$instance->init($instance);
		}
		return $instance;
	}

	//--------------------------------------------------------------------------
	//	セッション種別取得
	//--------------------------------------------------------------------------
	public static function get_type()
	{
		$type = crow_config::get("session.type", "php");

		//	旧バージョンでは定義が異なる
		if(
			crow_config::exists("session.db") === true &&
			crow_config::get("session.db") == "true"
		)	$type = "db";

		return $type;
	}

	//--------------------------------------------------------------------------
	//	権限を追加する
	//--------------------------------------------------------------------------
	public function add_privilege( $privilege_ )
	{
		//	JWTの場合
		if( self::get_type() == "jwt" )
		{
			$items = $this->jwt_get_payload_item('privilege', []);
			if( in_array($privilege_, $items) === false )
			{
				$items[] = $privilege_;
				$this->jwt_set_payload_item('privilege', $items);
			}
			return $this;
		}

		//	JWT以外の場合
		if( isset($_SESSION['privilege']) === true )
		{
			$p = $_SESSION['privilege'];
			if( in_array($privilege_, $p) === false )
			{
				$p[] = $privilege_;
				$_SESSION['privilege'] = $p;
			}
		}
		else
		{
			$_SESSION['privilege'] = [$privilege_];
		}
		return $this;
	}

	//--------------------------------------------------------------------------
	//	権限を削除する
	//--------------------------------------------------------------------------
	public function remove_privilege( $privilege_ )
	{
		//	JWTの場合
		if( self::get_type() == "jwt" )
		{
			$items = $this->jwt_get_payload_item('privilege', []);
			$new_items = [];
			foreach( $items as $item )
			{
				if( $item == $privilege_ ) continue;
				$new_items[] = $item;
			}
			$this->jwt_set_payload_item('privilege', $new_items);
			return $this;
		}

		//	JWT以外の場合
		if( isset($_SESSION['privilege']) === true )
		{
			$p = $_SESSION['privilege'];
			$new_p = [];
			foreach( $p as $v )
			{
				if( $v == $privilege_ ) continue;
				$new_p[] = $v;
			}
			$_SESSION['privilege'] = $new_p;
		}
		return $this;
	}

	//--------------------------------------------------------------------------
	//	権限をクリアする
	//--------------------------------------------------------------------------
	public function clear_privileges()
	{
		if( self::get_type() == "jwt" )
			$this->jwt_remove_payload_item('privilege');
		else
			$_SESSION['privilege'] = [];

		return $this;
	}

	//--------------------------------------------------------------------------
	//	現在設定されている権限リストを取得する
	//--------------------------------------------------------------------------
	public function get_privileges()
	{
		return self::get_type() == "jwt" ?
			$this->jwt_get_payload_item('privilege', []) :
			(isset($_SESSION['privilege']) ? $_SESSION['privilege'] : [])
			;
	}

	//--------------------------------------------------------------------------
	//	権限がある？
	//--------------------------------------------------------------------------
	public function has_privilege( $privilege_ )
	{
		$p = self::get_type() == "jwt" ?
			$this->jwt_get_payload_item('privilege', false) :
			(isset($_SESSION['privilege']) ? $_SESSION['privilege'] : false)
			;

		if( $p === false ) return false;

		if( is_array($privilege_) === true )
		{
			foreach( $privilege_ as $_p )
			{
				if( in_array($_p, $p) )
					return true;
			}
		}
		else
		{
			if( in_array($privilege_, $p) )
				return true;
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//	セッション変数に値をセットする
	//--------------------------------------------------------------------------
	public function set_property( $key_, $val_, $privilege_ = false )
	{
		$privilege = strlen($privilege_) <= 0 ? crow_config::get('auth.privilege', 'auth') : $privilege_;
		$this->set_property_with_section('properties.'.$privilege, $key_, $val_);
	}

	//	連想配列指定版
	public function set_properties( $items_, $privilege_ = false )
	{
		$privilege = strlen($privilege_) <= 0 ? crow_config::get('auth.privilege', 'auth') : $privilege_;
		foreach( $items_ as $k => $v )
			$this->set_property_with_section('properties.'.$privilege, $k, $v);
	}

	//--------------------------------------------------------------------------
	//	セッション変数の値をクリアする
	//--------------------------------------------------------------------------
	public function clear_property( $key_, $privilege_ = false )
	{
		$privilege = strlen($privilege_) <= 0 ? crow_config::get('auth.privilege', 'auth') : $privilege_;
		if( is_array($key_) === true )
		{
			foreach( $key_ as $k )
				$this->clear_property_with_section('properties.'.$privilege, $k);
		}
		else
			$this->clear_property_with_section('properties.'.$privilege, $key_);
	}

	//--------------------------------------------------------------------------
	//	セッション変数から値を取得する
	//--------------------------------------------------------------------------
	public function get_property( $key_, $privilege_ = false )
	{
		$privilege = strlen($privilege_) <= 0 ? crow_config::get('auth.privilege', 'auth') : $privilege_;
		return $this->get_property_with_section('properties.'.$privilege, $key_);
	}

	//--------------------------------------------------------------------------
	//	セッション変数から全ての値を取得する
	//--------------------------------------------------------------------------
	public function get_properties( $privilege_ = false )
	{
		$privilege = strlen($privilege_) <= 0 ? crow_config::get('auth.privilege', 'auth') : $privilege_;
		return $this->get_properties_with_section('properties.'.$privilege);
	}

	//--------------------------------------------------------------------------
	//	セッション変数内の値をクリアする
	//--------------------------------------------------------------------------
	public function clear_properties( $privilege_ = false )
	{
		$privilege = strlen($privilege_) <= 0 ? crow_config::get('auth.privilege', 'auth') : $privilege_;
		$this->clear_properties_with_section('properties.'.$privilege);
	}

	//--------------------------------------------------------------------------
	//	システム用：値をセットする
	//--------------------------------------------------------------------------
	public function set_system_property( $key_, $val_ )
	{
		$this->set_property_with_section('system', $key_, $val_);
	}

	//--------------------------------------------------------------------------
	//	システム用：値をクリアする
	//--------------------------------------------------------------------------
	public function clear_system_property( $key_ )
	{
		$this->clear_property_with_section('system', $key_);
	}

	//--------------------------------------------------------------------------
	//	システム用：値を取得する
	//--------------------------------------------------------------------------
	public function get_system_property( $key_ )
	{
		return $this->get_property_with_section('system', $key_);
	}

	//--------------------------------------------------------------------------
	//	システム用：全ての値を取得する
	//--------------------------------------------------------------------------
	public function get_system_properties()
	{
		return $this->get_properties_with_section('system');
	}

	//--------------------------------------------------------------------------
	//	システム用：値をクリアする
	//--------------------------------------------------------------------------
	public function clear_system_properties()
	{
		$this->clear_properties_with_section('system');
	}

	//--------------------------------------------------------------------------
	//	指定したセクションに値をセットする
	//--------------------------------------------------------------------------
	private function set_property_with_section( $section_, $key_, $val_ )
	{
		if( self::get_type() == "jwt" )
		{
			$props = $this->jwt_get_payload_item($section_, []);
			$props[$key_] = $val_;
			$this->jwt_set_payload_item($section_, $props);
		}
		else
		{
			if( isset($_SESSION[$section_]) === true )
			{
				$props = $_SESSION[$section_];
				$props[$key_] = $val_;
				$_SESSION[$section_] = $props;
			}
			else
			{
				$_SESSION[$section_] = [ $key_ => $val_ ];
			}
		}
	}

	//--------------------------------------------------------------------------
	//	指定されたセクションの値をクリアする
	//--------------------------------------------------------------------------
	private function clear_property_with_section( $section_, $key_ )
	{
		if( self::get_type() == "jwt" )
		{
			$props = $this->jwt_get_payload_item($section_, []);
			if( isset($props[$key_]) === true ) unset($props[$key_]);
			$this->jwt_set_payload_item($section_, $props);
		}
		else
		{
			if( isset($_SESSION[$section_]) === true )
			{
				$props = $_SESSION[$section_];
				if( isset($props[$key_]) === true ) unset($props[$key_]);
				$_SESSION[$section_] = $props;
			}
		}
	}

	//--------------------------------------------------------------------------
	//	指定したセクションから値を取得する
	//--------------------------------------------------------------------------
	private function get_property_with_section( $section_, $key_ )
	{
		if( self::get_type() == "jwt" )
		{
			$props = $this->jwt_get_payload_item($section_, false);
			if( isset($props[$key_]) === true )
				return $props[$key_];
		}
		else
		{
			if( isset($_SESSION[$section_]) === true )
			{
				$props = $_SESSION[$section_];
				if( isset($props[$key_]) === true )
					return $props[$key_];
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//	指定したセクションから全ての値を取得する
	//--------------------------------------------------------------------------
	private function get_properties_with_section( $section_ )
	{
		if( self::get_type() == "jwt" )
		{
			return $this->jwt_get_payload_item($section_, []);
		}
		else
		{
			if( isset($_SESSION[$section_]) === true )
			{
				return $_SESSION[$section_];
			}
		}
		return [];
	}

	//--------------------------------------------------------------------------
	//	指定したセクション内の値をクリアする
	//--------------------------------------------------------------------------
	private function clear_properties_with_section( $section_ )
	{
		if( self::get_type() == "jwt" )
		{
			$this->jwt_remove_payload_item($section_);
		}
		else
		{
			$_SESSION[$section_] = [];
		}
	}

	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	private function init( $instance_ )
	{
		//	設定のセッション種別ごとに挙動が異なる
		$type = self::get_type();

		//	DBセッションの場合
		if( $type == "db" )
		{
			session_set_save_handler
			(
				[$instance_, "open"],
				[$instance_, "close"],
				[$instance_, "read"],
				[$instance_, "write"],
				[$instance_, "destroy"],
				[$instance_, "gc"]
			);
			register_shutdown_function("session_write_close");
		}

		//	キャッシュの場合
		else if( $type == "memcached" || $type == "redis" )
		{
			$path = crow_config::get("session.save_path", false);
			if( $path === false )
			{
				crow_log::warning("session.save_path is not defined, required to start ".$type." session");
				return;
			}
			ini_set("session.save_handler", $type);
			ini_set("session.save_path", $path);
		}

		//	phpセッションの場合
		else if( $type == "php" )
		{
		}

		//	jwtの場合
		else if( $type == "jwt" )
		{
			//	クッキーから取得
			$jwt = false;
			if( isset($_COOKIE['crow_session_'.crow_request::get_role_name()]) === true )
			{
				$jwt = $_COOKIE['crow_session_'.crow_request::get_role_name()];
				$payload_data = self::jwt_verify($jwt);
				$this->m_jwt_payload_data = $payload_data;
			}

			//	start_sessionは行わずに返却
			return;
		}

		//	それ以外
		else
		{
			crow_log::warning("unknown session type : ".$type);
			return;
		}

		//	セッション開始
		session_start();
	}

	//--------------------------------------------------------------------------
	//	DBセッションハンドラ
	//--------------------------------------------------------------------------
	private $session_hdb = false;
	public function open( $path_, $name_ )
	{
		$hdb = false;

		//	autoconn でないなら、hdbのインスタンスは自分で管理する
		if( crow_config::get('db.autoconn','') !== 'true' )
		{
			$this->session_hdb = crow::create_hdb();
			$hdb = $this->session_hdb;
			$hdb->connect();
		}
		else
		{
			$hdb = crow::get_hdb_writer();
		}

		if( ! $hdb )
		{
			crow_log::warning("no db connection in db session");
			return false;
		}
		return true;
	}
	public function close()
	{
		//	autoconnでないなら自分で切断
		if( $this->session_hdb !== false )
		{
			$this->session_hdb->disconnect();
			$this->session_hdb = false;
		}
		return true;
	}
	public function read( $sid_ )
	{
		$sid = md5($sid_);
		$hdb = $this->session_hdb ? $this->session_hdb : crow::get_hdb_writer();
		$hdb->change_log_temp(false);
		$rset = $hdb->query
		(
			sprintf
			(
				"select %s from %s where %s='%s'",
				$hdb->escape_symbol(crow_config::get('session.db.field.data')),
				$hdb->escape_symbol(crow_config::get('session.db.table')),
				$hdb->escape_symbol(crow_config::get('session.db.field.id')),
				$hdb->encode($sid)
			)
		);
		$hdb->recovery_log_temp();
		if( $rset->num_rows() > 0 )
		{
			$arr = $rset->get_row();
			return $arr[crow_config::get('session.db.field.data')];
		}
		return '';
	}
	public function write( $sid_, $data_ )
	{
		$sid = md5($sid_);
		$hdb = $this->session_hdb ? $this->session_hdb : crow::get_hdb_writer();
		$hdb->change_log_temp(false);
		if( crow_config::get('db.type')==='mysqli' )
		{
			$hdb->query
			(
				sprintf
				(
					"replace into %s (%s,%s,%s) values ('%s','%s', '%s')",
					$hdb->escape_symbol(crow_config::get('session.db.table')),
					$hdb->escape_symbol(crow_config::get('session.db.field.id')),
					$hdb->escape_symbol(crow_config::get('session.db.field.data')),
					$hdb->escape_symbol(crow_config::get('session.db.field.created')),
					$hdb->encode($sid),
					$hdb->encode($data_),
					date('Y-m-d H:i:s')
				)
			);
		}
		else if( crow_config::get('db.type')==='postgres' )
		{
			$hdb->query
			(
				sprintf
				(
					"insert into %s (%s,%s,%s) values ('%s','%s','%s') "
					."on conflict (%s) do update set %s='%s'",
					$hdb->escape_symbol(crow_config::get('session.db.table')),
					$hdb->escape_symbol(crow_config::get('session.db.field.id')),
					$hdb->escape_symbol(crow_config::get('session.db.field.data')),
					$hdb->escape_symbol(crow_config::get('session.db.field.created')),
					$hdb->encode($sid),
					$hdb->encode($data_),
					date('Y-m-d H:i:s'),
					$hdb->escape_symbol(crow_config::get('session.db.field.id')),
					$hdb->escape_symbol(crow_config::get('session.db.field.data')),
					$hdb->encode($data_)
				)
			);
		}
		$hdb->recovery_log_temp();
		return true;
	}
	public function destroy( $sid_ )
	{
		$sid = md5($sid_);
		$hdb = $this->session_hdb ? $this->session_hdb : crow::get_hdb_writer();
		$hdb->change_log_temp(false);
		$hdb->query
		(
			sprintf
			(
				"delete from %s where %s='%s'",
				$hdb->escape_symbol(crow_config::get('session.db.table')),
				$hdb->escape_symbol(crow_config::get('session.db.field.id')),
				$hdb->encode($sid)
			)
		);
		$hdb->recovery_log_temp();
		return true;
	}
	public function gc( $max_life_time_ )
	{
		$max_life_time = preg_replace('/[^0-9]/','',$max_life_time_);
		$max_life_time = date('Y-m-d H:i:s', time() - $max_life_time);
		$hdb = $this->session_hdb ? $this->session_hdb : crow::get_hdb_writer();

		$hdb->change_log_temp(false);
		$hdb->query
		(
			sprintf
			(
				"delete from %s where %s < '%s'",
				$hdb->escape_symbol(crow_config::get('session.db.table')),
				$hdb->escape_symbol(crow_config::get('session.db.field.created')),
				$max_life_time
			)
		);
		$hdb->recovery_log_temp();
		return true;
	}

	//--------------------------------------------------------------------------
	//	JWTクッキー出力
	//--------------------------------------------------------------------------
	public static function jwt_output_cookie()
	{
		if( self::get_type() != "jwt" ) return;

		//	有効期間とcookieオプション
		$config_expires = intval(crow_config::get('session.jwt.expires', 3600));
		$expires = $config_expires > 0 ? (time() + $config_expires) : 0;
		$cookie_opts =
		[
			'path' => '/',
			'domain' => '',
			'secure' => crow_config::get('session.jwt.secure', 'true') == 'true',
			'httponly' => true,
			'samesite' => crow_config::get('auth.type') == 'cognito' ? 'Lax' : crow_config::get('session.jwt.samesite', 'Strict'),
		];
		if( $expires > 0 ) $cookie_opts['expires'] = $expires;

		//	cookie出力
		$inst = self::get_instance();
		setcookie
		(
			'crow_session_'.crow_request::get_role_name(),
			self::jwt_create($inst->m_jwt_payload_data),
			$cookie_opts
		);
	}

	//--------------------------------------------------------------------------
	//	JWT作成
	//
	//	必要なキーは、
	//
	//	$ ssh-keygen -t rsa -b 4096 -f private_key.pem
	//		→これで出来たprivate_key.pemを秘密鍵とする。
	//
	//	さらに、
	//	$ ssh-keygen -f private_key.pem -e -m pem > public_key.pem
	//	$ chmod 644 public_key.pem
	//	$ openssl rsa -pubin -in public_key.pem -RSAPublicKey_in -outform PEM -out new_public_key.pem
	//		→ これで出来たnew_public_key.pemを公開鍵とする。
	//
	//--------------------------------------------------------------------------
	public static function jwt_create($data_)
	{
		//	対称鍵の生成 (256ビットの対称鍵)
		$symmetric_key = openssl_random_pseudo_bytes(32);

		//	ペイロードの暗号化
		$cipher = "aes-256-cbc";
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
		$encrypted_payload = openssl_encrypt(json_encode(['iat' => time(), 'data' => $data_]), $cipher, $symmetric_key, 0, $iv);

		//	ペイロードをエンコード
		$enc_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted_payload));
		$enc_iv = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($iv));

		//	暗号化用の公開鍵取得
		$encrypt_public_key = str_replace("[CROW_PATH]", CROW_PATH, crow_config::get('session.jwt.encrypt_public_key'));
		if( is_file($encrypt_public_key) === false )
		{
			crow_log::notice('not found encrypt public key for jwt');
			return false;
		}
		$encrypt_public_key = file_get_contents($encrypt_public_key);

		//	対称鍵を公開鍵で暗号化
		$encrypted_key = '';
		openssl_public_encrypt($symmetric_key, $encrypted_key, $encrypt_public_key, OPENSSL_RAW_DATA);
		$enc_key = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted_key));

		//	ヘッダーを生成
		$header = json_encode(
		[
			'alg' => 'RSA-OAEP',
			'enc' => 'A256CBC-HS512',
			'typ' => 'JWT'
		]);

		//	ヘッダをエンコード
		$enc_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

		//	署名用の秘密鍵取得
		$private_key = str_replace("[CROW_PATH]", CROW_PATH, crow_config::get('session.jwt.private_key'));
		if( is_file($private_key) === false )
		{
			crow_log::notice('not found private key for jwt');
			return false;
		}
		$private_key = file_get_contents($private_key);
		$private_key = openssl_pkey_get_private($private_key);
		if( $private_key === false )
		{
			crow_log::notice('failed to get private key with OpenSSL');
			return false;
		}

		//	署名作成
		$msg = $enc_header.".".$enc_key.".".$enc_iv.".".$enc_payload;
		$signature = '';
		if( openssl_sign($msg, $signature, $private_key, OPENSSL_ALGO_SHA256) === false )
		{
			crow_log::notice('Failed to sign jwt');
			return false;
		}
		$enc_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

		//	jwt作成
		$jwt = $enc_header.".".$enc_key.".".$enc_iv.".".$enc_payload.".".$enc_signature;
		return $jwt;
	}

	//--------------------------------------------------------------------------
	//	JWT署名検証
	//
	//	検証失敗時はfalseを返却、成功時はペイロード返却
	//--------------------------------------------------------------------------
	public static function jwt_verify($jwt_)
	{
		//	jwtを分解
		$parts = explode(".", $jwt_);
		if( count($parts) != 5 )
		{
			crow_log::notice('invalid jwt structure : '.$jwt_);
			return false;
		}

		$enc_header = $parts[0];
		$enc_key = $parts[1];
		$enc_iv = $parts[2];
		$enc_payload = $parts[3];
		$enc_signature = $parts[4];

		//	ヘッダのデコード
		$json_header = base64_decode(str_replace(['-', '_'], ['+', '/'], $enc_header));
		$header = json_decode($json_header, true);

		if( $header === false )
		{
			crow_log::notice('failed to decode jwt header');
			return false;
		}

		if( $header['alg'] !== 'RSA-OAEP' || $header['enc'] !== 'A256CBC-HS512' || $header['typ'] !== 'JWT' )
		{
			crow_log::notice('invalid jwt header');
			return false;
		}

		//	署名用の公開鍵取得
		$public_key = str_replace("[CROW_PATH]", CROW_PATH, crow_config::get('session.jwt.public_key'));
		if( is_file($public_key) === false )
		{
			crow_log::notice('not found public key for jwt');
			return false;
		}
		$public_key = file_get_contents($public_key);
		$public_key = openssl_pkey_get_public($public_key);
		if( $public_key === false )
		{
			crow_log::notice('failed to get public key with OpenSSL');
			return false;
		}

		//	署名の検証
		$msg = $enc_header.".".$enc_key.".".$enc_iv.".".$enc_payload;
		$signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $enc_signature));
		$valid = openssl_verify($msg, $signature, $public_key, OPENSSL_ALGO_SHA256);
		if( $valid !== 1 )
		{
			crow_log::notice('invalid jwt signature');
			return false;
		}

		//	復号用の秘密鍵取得
		$decrypt_private_key = str_replace("[CROW_PATH]", CROW_PATH, crow_config::get('session.jwt.decrypt_private_key'));
		if( is_file($decrypt_private_key) === false )
		{
			crow_log::notice('not found decrypt private key for jwt');
			return false;
		}
		$decrypt_private_key = file_get_contents($decrypt_private_key);

		//	対称鍵の復号化
		$decrypt_private_key = openssl_pkey_get_private($decrypt_private_key);
		if( $decrypt_private_key === false )
		{
			crow_log::notice('failed to get decrypt private key with OpenSSL');
			return false;
		}
		$encrypted_key = base64_decode(str_replace(['-', '_'], ['+', '/'], $enc_key));
		$symmetric_key = '';
		if( openssl_private_decrypt($encrypted_key, $symmetric_key, $decrypt_private_key, OPENSSL_RAW_DATA) === false )
		{
			crow_log::notice('failed to decrypt symmetric key');
			return false;
		}

		// ペイロードの復号化
		$iv = base64_decode(str_replace(['-', '_'], ['+', '/'], $enc_iv));
		$encrypted_payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $enc_payload));

		$cipher = "aes-256-cbc";
		$payload = openssl_decrypt($encrypted_payload, $cipher, $symmetric_key, 0, $iv);

		if( $payload === false )
		{
			crow_log::notice('failed to decrypt payload');
			return false;
		}

		$payload = json_decode($payload, true);
		if( $payload === false )
		{
			crow_log::notice('failed to decode payload');
			return false;
		}

		//	有効期間のチェック
		//
		//	configで有効期間 0 指定の場合はブラウザを閉じるまでの指定となるので、
		//	本来はタブを閉じたらcookieが消去され、同時にjwtも失われる。
		//	この時に万一jwtのみ残していた場合でも、最大で一週間で期限切れになるように保険をかけておく。
		if( isset($payload['iat']) === false )
		{
			crow_log::notice('not found iat in payload');
			return false;
		}
		$config_expires = intval(crow_config::get('session.jwt.expires', 3600));
		if( $config_expires <= 0 ) $config_expires = 60 * 60 * 24 * 7;

		if( intval($payload['iat']) + $config_expires < time() )
		{
			crow_log::notice('jwt iat expired, payload:'.$payload['iat'].', expire:'.$config_expires);
			return false;
		}

		//	有効な場合、ペイロードを返却
		return isset($payload['data']) ? $payload['data'] : [];
	}

	//--------------------------------------------------------------------------
	//	JWTペイロードへの読み書き
	//--------------------------------------------------------------------------
	private function jwt_get_payload_item($key_, $default_ = false)
	{
		return	$this->m_jwt_payload_data === false ||
				isset($this->m_jwt_payload_data[$key_]) === false ?
				$default_ : $this->m_jwt_payload_data[$key_]
				;
	}
	private function jwt_set_payload_item($key_, $val_)
	{
		if( $this->m_jwt_payload_data === false ) $this->m_jwt_payload_data = [];
		$this->m_jwt_payload_data[$key_] = $val_;
		return $this;
	}
	private function jwt_remove_payload_item($key_)
	{
		if( $this->m_jwt_payload_data === false ) return $this;
		if( isset($this->m_jwt_payload_data[$key_]) === false ) return $this;

		unset($this->m_jwt_payload_data[$key_]);
		return $this;
	}

	//	JWT
	private $m_jwt_payload_data = false;
}
?>
