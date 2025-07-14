<?php
/*

	crow core


	run()に指定できるオプションは次の項目を指定できる。

	・distribution
	・memcached.enable
	・memcached.host
	・memcached.port
	・memcached.prefix

	・cleanup
		→ trueでキャッシュ都度クリア、falseでキャッシュ利用

	・components
		→ falseでコンポーネント無効、trueで有効、"force"で強制更新

*/
require_once('crow_mbext.php');
require_once('crow_utility.php');

class crow
{
	//--------------------------------------------------------------------------
	//	カーネルバージョン取得
	//--------------------------------------------------------------------------
	public static function version()
	{
		return "3.23.14";
	}

	//--------------------------------------------------------------------------
	//	バッチ向け初期化
	//--------------------------------------------------------------------------
	public static function init_for_batch( $role_, $opt_ = [] )
	{
		self::init_for_batch_with_module($role_, "", $opt_);
	}
	public static function init_for_batch_with_module( $role_, $module_, $opt_ = [] )
	{
		$crow = self::get_instance();
		$crow->m_opt = $opt_;
		$crow->m_initialized = false;
		$crow->m_is_batch = true;
		$bkup = ini_get('display_errors');
		ini_set('display_errors',0);
		{
			//	module指定があれば
			$_REQUEST['crow'] = $module_;

			//	HOSTを書き換え
			$_SERVER['HTTP_HOST'] = 'localhost';

			//	timezone、文字コードの設定
			date_default_timezone_set( 'Asia/Tokyo' );
			mb_language('uni');
			mb_detect_order( ['UTF-8'] );
			mb_internal_encoding( 'UTF-8' );
			mb_regex_encoding( 'UTF-8' );

			//	クリーンアップ
			if( isset($opt_['cleanup']) === true && $opt_['cleanup'] === true )
			{
				self::cleanup();
			}

			//	各種初期化
			$distribution = isset($opt_['distribution']) ? $opt_['distribution'] : '';
			crow_error::init();
			if( strlen($role_) <= 0 ) return crow_log::error( 'role is not specified' );

			crow_cache::init( $opt_ );
			crow_config::init( $role_, $distribution );
			crow_request::init( $role_, $distribution, [] );
		}
		ini_set('display_errors',$bkup);
		$crow->m_initialized = true;
	}

	//--------------------------------------------------------------------------
	//	実行
	//--------------------------------------------------------------------------
	public static function run( $role_, $opt_ = [] )
	{
		$crow = self::get_instance();
		$crow->m_opt = $opt_;
		$crow->m_initialized = false;
		$bkup = ini_get('display_errors');
		ini_set('display_errors',0);
		{
			//	timezone、文字コードの設定
			date_default_timezone_set( 'Asia/Tokyo' );
			mb_language('uni');
			mb_detect_order( ['UTF-8'] );
			mb_internal_encoding( 'UTF-8' );
			mb_regex_encoding( 'UTF-8' );

			//	クリーンアップ
			if( isset($opt_['cleanup']) === true && $opt_['cleanup'] === true )
			{
				self::cleanup();
			}

			//	必要ならコンポーネントの組み込み
			if( isset($opt_['components']) )
			{
				if( $opt_['components'] !== false )
				{
					crow_component::apply($opt_['components'] === 'force');
				}
			}

			//	各種初期化
			$distribution = isset($opt_['distribution']) ? $opt_['distribution'] : '';
			crow_error::init();
			if( strlen($role_) <= 0 )
				return crow_log::error( 'role is not specified' );

			//	ルート初期化
			$routes = isset($opt_['routes']) ? $opt_['routes'] : [];

			crow_cache::init( $opt_ );
			crow_config::init( $role_, $distribution );
			crow_request::init( $role_, $distribution, $routes );
		}
		ini_set('display_errors',$bkup);

		//	実行
		$crow->m_initialized = true;
		$crow->dispatch();
	}

	//--------------------------------------------------------------------------
	//	初期化済み？
	//--------------------------------------------------------------------------
	public static function initialized()
	{
		return self::get_instance()->m_initialized;
	}

	//--------------------------------------------------------------------------
	//	バッチ？
	//--------------------------------------------------------------------------
	public static function is_batch()
	{
		return self::get_instance()->m_is_batch;
	}

	//--------------------------------------------------------------------------
	//	キャッシュクリア
	//	通常は run() の $opt_ にcleanupを指定できるが、
	//	処理中にクリアしたい場合にはこちらを使う
	//--------------------------------------------------------------------------
	public static function cleanup()
	{
		$cache_dir = CROW_PATH."output/caches/";
		$hs = crow_storage::disk();
		$cache_files = $hs->get_files($cache_dir);
		foreach( $cache_files as $cache_file )
		{
			if( substr($cache_file, -8) == ".gitkeep" ) continue;
			$hs->remove($cache_file);
		}
	}

	//--------------------------------------------------------------------------
	//	キー名を指定して起動引数を取得
	//
	//	例）
	//	# php action.php key1=val1 key2="val2 detail" key3
	//
	//	のように指定された場合、
	//	$v1 = crow::get_argv("key1"); → "val1" が返却される
	//	$v2 = crow::get_argv("key2"); → "val2 detail" が返却される
	//	$v3 = crow::get_argv("key3"); → true が返却される
	//	$v3 = crow::get_argv("key4"); → false が返却される
	//
	//	スペースを含む文字列はシングルではなくダブルクォーテーションで括ること。上記の key2 が例となる。
	//	指定した名前の引数がなかった場合には $default_ で指定された値を返却する
	//--------------------------------------------------------------------------
	public static function get_argv( $key_, $default_ = false )
	{
		if( self::$m_parsed_argv === false ) self::parse_argv();
		return isset(self::$m_parsed_argv[$key_]) ?
			self::$m_parsed_argv[$key_] : $default_;
	}

	//--------------------------------------------------------------------------
	//	DBハンドルを取得する
	//--------------------------------------------------------------------------
	public static function get_hdb()
	{
		return self::get_hdb_writer();
	}

	//	reader取得、一度でもwriterを取得した後や、遅延対策機能が有効の場合はwriterが返却される
	public static function get_hdb_reader()
	{
		if( self::$m_hdb_writer !== false )
			return self::$m_hdb_writer;
		if( self::isin_writedelay() === true )
			return self::get_hdb_writer();

		if( self::$m_hdb_reader === false )
			self::$m_hdb_reader = self::create_hdb("reader");
		return self::$m_hdb_reader;
	}

	//	writer取得
	public static function get_hdb_writer()
	{
		if( self::$m_hdb_writer === false )
		{
			self::$m_hdb_writer = self::create_hdb("writer");
			self::set_writedelay();
		}
		return self::$m_hdb_writer;
	}

	//	定義名を指定してハンドル取得
	public static function create_hdb( $server_type_ )
	{
		$db_type = crow_config::get('db.type');
		$class_name = "crow_db_".$db_type;
		$path = CROW_PATH."engine/kernel/".$class_name.".php";
		if( is_file($path) === false )
		{
			crow_log::error( "not found db class file : ".$path );
			exit;
		}
		require_once($path);
		if( class_exists($class_name) === false )
		{
			crow_log::error( "not found db class : ".$class_name );
			exit;
		}

		//	ハンドルの初回作成
		$hdb = new $class_name($server_type_);
		$hdb->init();

		return $hdb;
	}

	//	writedelay機能 : writer使用をマークする
	private static function set_writedelay()
	{
		//	writedelayが有効であること
		if( crow_config::get('db.writedelay.enabled') !== "true" ) return;
		if( crow_config::get('session.type') === "db" )
		{
			crow_log::error( "db.writedelay.enabled is true, but 'db' is specified in session.type" );
			exit;
		}

		//	セッションのシステム領域を使う
		$sess = crow_session::get_instance();
		$sess->set_system_property('writedelay', time());
	}

	//	writedelay機能 : writer使用中？
	private static function isin_writedelay()
	{
		//	writedelayが有効であること
		if( crow_config::get('db.writedelay.enabled') !== "true" ) return false;
		if( crow_config::get('session.type') === "db" )
		{
			crow_log::error( "db.writedelay.enabled is true, but 'db' is specified in session.type" );
			exit;
		}

		$sess = crow_session::get_instance();
		$marktime = intval($sess->get_system_property('writedelay'));
		return $marktime + intval(crow_config::get('db.writedelay.sec')) >= time();
	}

	//--------------------------------------------------------------------------
	//	アクションへのリクエストURLを作成する
	//
	//	moduleとparamのみ指定したい場合は、actionにfalseを指定する。
	//	その場合、actionはデフォルトのものになる
	//--------------------------------------------------------------------------
	public static function make_url( $module_ = false, $action_ = false, $param_ = [] )
	{
		$def_module = crow_config::get('default.module', false);
		$def_action = crow_config::get('default.action', false);
		$m = $module_ === false ? $def_module : $module_;
		$a = $action_ === false ? $def_action : $action_;

		//	module/actionは可能なら省略する
		//	module名のみの省略は不可とする
		if( $a == $def_action )
		{
			if( $m == $def_module ) $m = false;
			$a = false;
		}

		//	URL作成
		$url = crow_request::get_entry_url();
		$url_dir = [];
		if( $m !== false ) $url_dir[] = urlencode($m);
		if( $a !== false ) $url_dir[] = urlencode($a);
		$url .= implode("/",$url_dir);

		//	パラメータ部作成
		$param = "";
		if( count($param_) > 0 )
		{
			foreach( $param_ as $key => $val )
			{
				if( strlen($key)<=0 ) continue;
				if( $key=="#" ) continue;
				if( $param=="" ) $param .= "?";
				else $param .= "&";
				$param .= urlencode($key)."=".urlencode($val);
			}
			if( isset($param_['#']) === true )
				$param .= "#".urlencode($param_['#']);
		}
		return $url.$param;
	}

	//	actionのみを指定してURL作成。moduleは現在のものとなる
	public static function make_url_action( $action_ = false, $param_ = [] )
	{
		return self::make_url
		(
			crow_request::get_module_name(),
			$action_,
			$param_
		);
	}

	//	現在のactionへのURLを作成する
	public static function make_url_self( $param_ = [] )
	{
		if( crow_request::get_route_path() !== false )
		{
			return self::make_url_path
			(
				crow_request::get_route_path(),
				$param_
			);
		}
		else
		{
			return self::make_url
			(
				crow_request::get_module_name(),
				crow_request::get_action_name(),
				$param_
			);
		}
	}

	//	indexからのパスを指定してURLを作成する
	//	例）
	//		htdocs/xxx/index.php
	//		が入口だとして、
	//		crow::make_url_path("/detail/abc.png")
	//		の結果は、
	//		"http://domain/xxx/detail/abc.png"
	//		となる
	public static function make_url_path( $path_, $param_ = [] )
	{
		$site_path = crow_request::get_entry_url();
		$site_path = substr( $site_path, 0, strlen($site_path) - 1 );

		$param = "";
		if( count($param_) > 0 )
		{
			foreach( $param_ as $key => $val )
			{
				if( strlen($key)<=0 ) continue;
				if( $key=="#" ) continue;
				if( $param=="" ) $param .= "?";
				else $param .= "&";
				$param .= urlencode($key)."=".urlencode($val);
			}
			if( isset($param_['#']) )
				$param .= "#".urlencode($param_['#']);
		}
		return $site_path.$path_.$param;
	}


	//--------------------------------------------------------------------------
	//	ページフォワード
	//
	//	$norewrite_method_ に true を設定すると、リクエストメソッドを書き換えない。
	//	デフォルトではGETに書き換わる
	//--------------------------------------------------------------------------
	public static function forward( $module_ = false, $action_ = false, $param_ = [], $norewrite_method_ = false )
	{
		//	リクエストパラメータを書き換える
		foreach( $param_ as $key => $val )
			$_REQUEST[$key] = $val;

		//	GETに書き換え
		if( $norewrite_method_ === false )
			$_SERVER['REQUEST_METHOD'] = "GET";

		//	再リクエスト
		$url_dir = [];
		if( $module_ !== false ) $url_dir[] = urlencode($module_);
		if( $action_ !== false ) $url_dir[] = urlencode($action_);
		$_REQUEST['crow'] = implode("/", $url_dir);
		self::run(crow_request::get_role_name(), self::get_instance()->m_opt);
	}

	//	actionのみを指定してフォワード
	public static function forward_action( $action_ = false, $param_ = [], $norewrite_method_ = false )
	{
		self::forward
		(
			crow_request::get_module_name(),
			$action_,
			$param_,
			$norewrite_method_
		);
	}

	//	現リクエストのアクションへフォワード
	public static function forward_self( $param_ = [], $norewrite_method_ = false )
	{
		self::forward
		(
			crow_request::get_module_name(),
			crow_request::get_action_name(),
			$param_,
			$norewrite_method_
		);
	}

	//	デフォルトページへフォワード
	public static function forward_default( $param_ = [], $norewrite_method_ = false )
	{
		$module	= crow_config::get('default.module', false);
		$action	= crow_config::get('default.action', false);
		self::forward( $module, $action, $param_, $norewrite_method_ );
	}

	//	デフォルトエラーページへフォワード
	//	リクエストパラメータ"error"で、指定されたエラーが渡される
	public static function forward_default_error( $error_, $param_ = [], $norewrite_method_ = false )
	{
		if( crow_request::is_ajax() )
		{
			$module	= crow_config::get('default.error.module.ajax', false);
			$action	= crow_config::get('default.error.action.ajax', false);
			$param_['error'] = $error_;
			self::forward($module, $action, $param_, $norewrite_method_);
		}
		else
		{
			$module	= crow_config::get('default.error.module', false);
			$action	= crow_config::get('default.error.action', false);
			$param_['error'] = $error_;
			self::forward($module, $action, $param_, $norewrite_method_);
		}
	}

	//--------------------------------------------------------------------------
	//	ページリダイレクト
	//--------------------------------------------------------------------------
	private static function redirect_core( $url_ )
	{
		$crow = self::get_instance();
		if( isset($crow->m_opt['disable_redirect']) )
		{
			if( $crow->m_opt['disable_redirect'] === true )
			{
				echo "redirect to : ".$url_."\r\n";
				return;
			}
		}

		self::output_start();
		header("Location: ".$url_);
		self::output_end();
		exit;
	}

	public static function redirect( $module_ = false, $action_ = false, $param_ = [] )
	{
		$url = self::make_url($module_, $action_, $param_);
		self::redirect_core($url);
	}

	//	actionを指定して実行するリダイレクト（moduleは現リクエストのものとなる）
	public static function redirect_action( $action_ = false, $param_ = [] )
	{
		self::redirect
		(
			crow_request::get_module_name(),
			$action_,
			$param_
		);
	}

	//	現リクエストのアクションへリダイレクト
	public static function redirect_self( $param_ = [] )
	{
		$url = self::make_url_self($param_);
		self::redirect_core($url);
	}

	//	パスを指定して実行するリダイレクト
	public static function redirect_path( $path_ = false, $param_ = [] )
	{
		$url = self::make_url_path($path_, $param_);
		self::redirect_core($url);
	}

	//	デフォルトページへリダイレクト
	public static function redirect_default( $param_ = [] )
	{
		self::redirect
		(
			crow_config::get('default.module', false),
			crow_config::get('default.action', false),
			$param_
		);
	}

	//	デフォルトエラーページへリダイレクト
	//	リクエストパラメータ"error"で、指定されたエラーが渡される
	public static function redirect_default_error( $error_ = '', $param_ = [] )
	{
		$param_['error'] = $error_;
		$module = crow_request::is_ajax() ?
			crow_config::get('default.error.module.ajax', false) :
			crow_config::get('default.error.module', false)
			;
		$action = crow_request::is_ajax() ?
			crow_config::get('default.error.action.ajax', false) :
			crow_config::get('default.error.action', false)
			;
		self::redirect($module, $action, $param_);
	}

	//--------------------------------------------------------------------------
	//	セッションを使用して値を渡しながら、ページリダイレクト
	//
	//	リダイレクトされた側では、vars_で指定した内容が
	//	そのままリクエストパラメータで取得できる状態となる
	//--------------------------------------------------------------------------
	public static function redirect_with_vars( $module_ = false, $action_ = false, $vars_ = [], $param_ = [] )
	{
		//	varsをセッションのsystem領域に保持する
		$sess = crow_session::get_instance();
		$sess->set_system_property( 'vars', $vars_ );

		//	リダイレクト
		$url = self::make_url( $module_, $action_, $param_ );
		self::redirect_core($url);
	}

	//	actionを指定してvars付きリダイレクト（moduleは現リクエストのものとなる）
	public static function redirect_action_with_vars( $action_ = false, $vars_ = [], $param_ = [] )
	{
		self::redirect_with_vars
		(
			crow_request::get_module_name(),
			$action_,
			$vars_,
			$param_
		);
	}

	//	現リクエストのアクションへvars付きリダイレクト
	public static function redirect_self_with_vars( $vars_ = [], $param_ = [] )
	{
		//	varsをセッションのsystem領域に保持する
		$sess = crow_session::get_instance();
		$sess->set_system_property( 'vars', $vars_ );

		$url = self::make_url_self($param_);
		self::redirect_core($url);
	}

	//	パスを指定してvars付きリダイレクト
	public static function redirect_path_with_vars( $path_ = false, $vars_ = [], $param_ = [] )
	{
		//	varsをセッションのsystem領域に保持する
		$sess = crow_session::get_instance();
		$sess->set_system_property('vars', $vars_);

		//	リダイレクト
		$url = self::make_url_path($path_, $param_);
		self::redirect_core($url);
	}

	//	デフォルトページへvars付きリダイレクト
	public static function redirect_default_with_vars( $vars_ = [], $param_ = [] )
	{
		self::redirect_with_vars
		(
			crow_config::get('default.module', false),
			crow_config::get('default.action', false),
			$vars_,
			$param_
		);
	}

	//	デフォルトエラーページへvars付きリダイレクト
	public static function redirect_default_error_with_vars( $vars_ = [], $param_ = [] )
	{
		$module = crow_request::is_ajax() ?
			crow_config::get('default.error.module.ajax', false) :
			crow_config::get('default.error.module', false)
			;
		$action = crow_request::is_ajax() ?
			crow_config::get('default.error.action.ajax', false) :
			crow_config::get('default.error.action', false)
			;

		self::redirect_with_vars($module, $action, $vars_, $param_);
	}

	//--------------------------------------------------------------------------
	//	各リソースの参照ディレクトリを追加
	//--------------------------------------------------------------------------
	public static function add_view_dir( $path_ )
	{
		self::$m_ext_view_paths[] = substr($path_, -1) != "/" ?
			$path_."/" : $path_;
	}
	public static function add_js_dir( $path_ )
	{
		self::$m_ext_js_paths[] = substr($path_, -1) != "/" ?
			$path_."/" : $path_;
	}
	public static function add_css_dir( $path_ )
	{
		self::$m_ext_css_paths[] = substr($path_, -1) != "/" ?
			$path_."/" : $path_;
	}
	public static function add_query_dir( $path_ )
	{
		self::$m_ext_query_paths[] = substr($path_, -1) != "/" ?
			$path_."/" : $path_;
	}
	public static function get_query_dirs()
	{
		return self::$m_ext_query_paths;
	}

	//--------------------------------------------------------------------------
	//	CSRF値取得
	//
	//	jwt認証の場合は全てのアクションで同じ値となり、
	//	jwt認証以外においてはアクションが異なると異なる値となる
	//--------------------------------------------------------------------------
	public static function get_csrf_hidden( $module_=false, $action_=false )
	{
		if( $module_ !== false && $action_ === false )
			crow_log::notice("get_csrf_hidden() must specify module and action or neither");

		$key = self::get_csrf_key($module_, $action_);
		$val = self::get_csrf_val($module_, $action_);

		return '<input type="hidden" name="'.$key.'" value="'.$val.'">';
	}
	public static function get_csrf_hidden_action( $action_ )
	{
		return self::get_csrf_hidden(crow_request::get_module_name(), $action_);
	}

	public static function get_csrf_key( $module_=false, $action_=false )
	{
		$sess_type = crow_config::get('session.type', '');
		if( $sess_type == 'jwt' ) return crow_request::get_role_name()."_token";

		$module = $module_ !== false ? $module_ : crow_request::get_module_name();
		$action = $action_ !== false ? $action_ : crow_request::get_action_name();
		return crow_request::get_role_name()."_".$module."_".$action."_token";
	}
	public static function get_csrf_key_action( $action_ )
	{
		return self::get_csrf_key(crow_request::get_module_name(), $action_);
	}

	public static function get_csrf_val( $module_ = false, $action_ = false )
	{
		$key = self::get_csrf_key($module_, $action_);
		if( isset(self::$m_csrf_tokens[$key])===false )
		{
			$sess = crow_session::get_instance();
			self::$m_csrf_tokens[$key] = crow_utility::random_str(32);
			$sess->set_system_property('csrf', self::$m_csrf_tokens);
		}
		return self::$m_csrf_tokens[$key];
	}
	public static function get_csrf_val_action( $action_ )
	{
		return self::get_csrf_val(crow_request::get_module_name(), $action_);
	}

	//--------------------------------------------------------------------------
	//	モジュールの全アクションのURLとCSRF値を合わせた配列を取得
	//
	//	$module_ にはモジュール名を指定し、省略時は現在のモジュールとして動作する
	//
	//	返却される配列）
	//	[
	//		アクション名 :
	//		[
	//			"url" : アクションのURL,
	//			"key" : CSRF対策のキー,
	//			"val" : CSRF対策の値,
	//		]
	//		...アクションの数だけ繰り返し...
	//	]
	//
	//--------------------------------------------------------------------------

	//	Jsonで取得
	public static function get_module_urls_as_json( $module_ = false )
	{
		return crow_utility::array_to_json(self::get_module_urls($module_));
	}

	//	PHPの配列で取得
	public static function get_module_urls( $module_ = false )
	{
		$module_name = $module_ === false ? crow_request::get_module_name() : $module_;
		$class_name = "module_".crow_request::get_role_name()."_".$module_name;
		if( class_exists($class_name) === false )
		{
			crow_log::notice("not found class name '".$class_name."' for get_module_urls");
			return;
		}

		$prefix = "action_";
		$prefix_len = strlen($prefix);
		$methods = get_class_methods($class_name);
		$urls = [];
		foreach( $methods as $method )
		{
			if( substr($method, 0, $prefix_len) != $prefix ) continue;
			$action_name = substr($method, $prefix_len);

			//	末尾_postを除外
			if( substr($action_name, -5) == "_post" )
				$action_name = substr($action_name, 0, strlen($action_name) - 5);
			if( isset($urls[$action_name]) === true ) continue;

			$urls[$action_name] =
			[
				"url" => self::make_url($module_name, $action_name),
				"key" => self::get_csrf_key($module_name, $action_name),
				"val" => self::get_csrf_val($module_name, $action_name),
			];
		}
		return $urls;
	}

	//--------------------------------------------------------------------------
	//	CSRF検証（configで、csrf.verifyが"false"の場合のみ実行する想定）
	//--------------------------------------------------------------------------
	public static function csrf_verify()
	{
		$csrf_key = self::get_csrf_key(crow_request::get_module_name(), crow_request::get_action_name());
		$csrf_val = crow_request::get($csrf_key, '');
		if( isset(self::$m_csrf_tokens[$csrf_key]) === false ||
			self::$m_csrf_tokens[$csrf_key] != $csrf_val
		){
			//	一度検証したトークンはクリア
			unset(self::$m_csrf_tokens[$csrf_key]);
			return false;
		}

		//	一度検証したトークンはクリア
		unset(self::$m_csrf_tokens[$csrf_key]);
		return true;
	}

	//--------------------------------------------------------------------------
	//	標準的な<head>内のタグを全て取得する
	//--------------------------------------------------------------------------
	public static function get_default_head_tag()
	{
		$noscript_tag = self::get_noscript_tag();
		return ''
			.self::get_base_tag()."\r\n"
			.'<meta http-equiv="content-type" content="text/html;charset=UTF-8">'."\r\n"
			.'<meta http-equiv="Pragma" content="no-cache">'."\r\n"
			.'<meta http-equiv="Cache-Control" content="no-cache">'."\r\n"
			.($noscript_tag=="" ? "" : $noscript_tag)
			.self::get_js_and_css_tag()
			;
	}

	//--------------------------------------------------------------------------
	//	<noscript>タグを取得する
	//--------------------------------------------------------------------------
	public static function get_noscript_tag()
	{
		$mod = crow_config::get('noscript.module', false);
		$act = crow_config::get('noscript.action', false);
		if( $mod === false || $act === false ) return '';

		//	現リクエストがnoscriptアクションの場合はタグなし
		if( crow_request::get_module_name() == $mod &&
			crow_request::get_action_name() == $act
		)	return '';

		return '<noscript><meta http-equiv="refresh" content="0;url='.self::make_url($mod, $act).'"></noscript>';
	}

	//--------------------------------------------------------------------------
	//	<base>タグを取得する
	//--------------------------------------------------------------------------
	public static function get_base_tag()
	{
		return '<base href="'.crow_request::get_entry_url().'">';
	}

	//--------------------------------------------------------------------------
	//	JS/CSSタグを取得する
	//--------------------------------------------------------------------------
	public static function get_js_and_css_tag()
	{
		return self::get_js_tag()."\r\n".self::get_css_tag();
	}

	//--------------------------------------------------------------------------
	//	JSタグを取得する
	//--------------------------------------------------------------------------
	public static function get_js_tag()
	{
		return '<script src="'
			.crow_request::get_entry_url()
			."_js_/"
			.crow_request::get_module_name()."/"
			.crow_request::get_action_name()
			.'" charset="utf-8" nonce="'.crow_response::nonce().'"></script>'
			;
	}

	//--------------------------------------------------------------------------
	//	CSSタグを取得する
	//--------------------------------------------------------------------------
	public static function get_css_tag()
	{
		return "<style type=\"text/css\">\r\n<!--\r\n".self::get_css_code()."-->\r\n</style>\r\n";
	}

	//--------------------------------------------------------------------------
	//	CSSコード作成
	//--------------------------------------------------------------------------
	public static function get_css_code()
	{
		$role = crow_request::get_role_name();
		$module = crow_request::get_module_name();
		$action = crow_request::get_action_name();

		//	SPチェック
		$sp_name = "";
		if( crow_request::is_android() === true )		$sp_name = "_android";
		else if( crow_request::is_iphone() === true )	$sp_name = "_iphone";
		else if( crow_request::is_ipod() === true )		$sp_name = "_ipod";
		else if( crow_request::is_ipad() === true )		$sp_name = "_ipad";

		//	キャッシュにあればそれを返却
		$cache_name = 'css_'.$role."_".$module."_".$action."_".$sp_name;
		$source = crow_cache::load($cache_name);
		if( $source !== false ) return $source;
		$source = "";

		//	環境指定のCSSがあるなら結合対象にする
		$file_suffixs = [];
		if( crow_request::is_android() === true )
			$file_suffixs = ["_android.icss", "_android.css", "_sp.icss", "_sp.css", ".icss", ".css"];
		else if( crow_request::is_iphone() === true )
			$file_suffixs = ["_iphone.icss", "_iphone.css", "_ios.icss", "_ios.css", "_sp.icss", "_sp.css", ".icss", ".css"];
		else if( crow_request::is_ipod() === true )
			$file_suffixs = ["_ipod.icss", "_ipod.css", "_ios.icss", "_ios.css", "_sp.icss", "_sp.css", ".icss", ".css"];
		else if( crow_request::is_ipad() === true )
			$file_suffixs = ["_ipad.icss", "_ipad.css", "_ios.icss", "_ios.css", "_sp.icss", "_sp.css", ".icss", ".css"];
		else
			$file_suffixs = [".icss", ".css"];

		$sp_check =
		[
			"_sp.css", "_sp.icss", "_android.css", "_android.icss",
			"_ios.css", "_ios.icss", "_iphone.css", "_iphone.icss",
			"_ipod.css", "_ipod.icss", "_ipad.css", "_ipad.icss",
			".css", ".icss",
		];

		//	0. CROW_PATH/engine/assets/css 配下の(i)cssを再帰検索で全て取得
		//	1. CROW_PATH/app/assets/css/_common_ 配下の(i)cssを再帰検索で全て取得
		//	2. CROW_PATH/app/assets/css/ロール名/_common_ 配下の(i)cssを再帰検索で全て取得
		$fnames = [];
		$css_paths =
		[
			CROW_PATH."engine/assets/css",
			CROW_PATH."app/assets/css/_common_",
			CROW_PATH."app/assets/css/".$role."/_common_",
		];
		foreach( $css_paths as $path )
		{
			$files = crow_storage::disk()->get_files($path, false, ["css","icss"]);
			foreach( $files as $file )
			{
				foreach( $sp_check as $chk )
				{
					if( substr($file, -1 * strlen($chk)) != $chk ) continue;
					$fname = substr($file, 0, strlen($file) - strlen($chk));
					if( isset($fnames[$fname]) )
						$fnames[$fname][] = $file;
					else
						$fnames[$fname] = [$file];
				}
			}
		}

		//	3. CROW_PATH/app/assets/css/ロール名/ロール名.(i)css を取得
		//	4. CROW_PATH/app/assets/css/ロール名/ロール名_モジュール名.(i)css を取得
		//	5. CROW_PATH/app/assets/css/ロール名/ロール名_モジュール名_アクション名.(i)css を取得
		$css_paths =
		[
			CROW_PATH."app/assets/css/".$role."/".$role,
			CROW_PATH."app/assets/css/".$role."/".$role."_".$module,
			CROW_PATH."app/assets/css/".$role."/".$role."_".$module."_".$action,
		];
		foreach( $css_paths as $file )
		{
			foreach( $sp_check as $chk )
			{
				$fname = $file.$chk;
				if( is_file($fname) === false ) continue;

				if( isset($fnames[$file]) === true )
					$fnames[$file][] = $fname;
				else
					$fnames[$file] = [$fname];
			}
		}

		//	さらに拡張パスから拾う
		if( count(self::$m_ext_css_paths) > 0 )
		{
			foreach( self::$m_ext_css_paths as $path )
			{
				$comm_files = crow_storage::disk()->get_files($path."_common_", false, ["css","icss"]);
				foreach( $comm_files as $file )
				{
					foreach( $sp_check as $chk )
					{
						if( substr($file, -1 * strlen($chk)) != $chk ) continue;
						$fname = substr($file, 0, strlen($file) - strlen($chk));
						if( isset($fnames[$fname]) === true )
							$fnames[$fname][] = $file;
						else
							$fnames[$fname] = [$file];
					}
				}

				$css_paths =
				[
					$path.$role."_".$module,
					$path.$role."_".$module."_".$action,
				];
				foreach( $css_paths as $file )
				{
					foreach( $sp_check as $chk )
					{
						$fname = $file.$chk;
						if( is_file($fname) === false ) continue;

						if( isset($fnames[$file]) === true )
							$fnames[$file][] = $fname;
						else
							$fnames[$file] = [$fname];
					}
				}
			}
		}

		//	require パスを作成しておく、優先順で。
		$require_paths =
		[
			CROW_PATH."app/assets/css/".$role."/",
			CROW_PATH."app/assets/css/".$role."/_common_/",
			CROW_PATH."app/assets/css/_common_/",
			CROW_PATH."engine/assets/css/",
		];

		if( count(self::$m_ext_css_paths) > 0 )
		{
			foreach( self::$m_ext_css_paths as $path )
			{
				$require_paths[] = $path;
				$require_paths[] = $path."/_common_/";
			}
		}

		//	ここまでで列挙したファイルを全て結合する
		foreach( $fnames as $name => $list )
		{
			foreach( $list as $css_path )
			{
				$found = false;
				foreach( $file_suffixs as $file_sfx )
				{
					if( $name.$file_sfx == $css_path )
					{
						//	パーツがキャッシュ化されている場合はそちらを使用
						$part_name_hash = md5($role."@".$css_path);
						$part_src = crow_cache::load($part_name_hash);
						if( $part_src === false )
						{
							$part_src = crow_css::compress($css_path, $require_paths);
							crow_cache::save($part_name_hash, $part_src);
						}
						$source .= $part_src;
						$found = true;
						break;
					}
				}
				if( $found === true ) break;
			}
		}

		//	ビューパーツからの出力があれば加える
		$viewpart_icss = crow_viewpart::icss($role, $module, $action);
		if( strlen($viewpart_icss) > 0 )
			$source .= crow_css::compress_memory($viewpart_icss, $require_paths);

		//	最後に改行付与
		if( $source != "" ) $source .= "\n";

		//	キャッシュに保持しておく
		crow_cache::save($cache_name, $source);
		return $source;
	}

	//--------------------------------------------------------------------------
	//	JSのコード取得
	//--------------------------------------------------------------------------
	public static function get_js_code()
	{
		$role = crow_request::get_role_name();
		$module = crow_request::get_module_name();
		$action = crow_request::get_action_name();

		//	キャッシュにあればそれを返却
		$cache_name = 'js_'.$role."_".$module."_".$action."_";
		$source = crow_cache::load($cache_name);
		if( $source !== false ) return $source;
		$source = "";

		//	require パスを作成しておく、優先順で。
		$require_paths =
		[
			CROW_PATH."app/assets/js/".$role."/",
			CROW_PATH."app/assets/js/".$role."/_common_/",
			CROW_PATH."app/assets/js/_common_/",
			CROW_PATH."engine/assets/js/",
		];

		//	0. CROW_PATH/engine/assets/js 配下のjsを再帰検索で全て取得
		//	1. CROW_PATH/app/assets/js/_common_ 配下のjsを再帰検索で全て取得
		//	2. CROW_PATH/app/assets/js/ロール名/_common_ 配下のjsを再帰検索で全て取得
		$js_paths =
		[
			CROW_PATH."engine/assets/js",
			CROW_PATH."app/assets/js/_common_",
			CROW_PATH."app/assets/js/".$role."/_common_",
		];
		foreach( $js_paths as $path )
		{
			$files = crow_storage::disk()->get_files($path, false, ["js"]);
			foreach( $files as $file )
			{
				$source .= substr($file, -7) == ".min.js" ?
					file_get_contents($file) :
					crow_js::compress(file_get_contents($file), $require_paths)
					;
				$source .= "\n";
			}
		}

		//	3. CROW_PATH/app/assets/js/ロール名/ロール名.js を取得
		//	4. CROW_PATH/app/assets/js/ロール名/ロール名_モジュール名.js を取得
		//	5. CROW_PATH/app/assets/js/ロール名/ロール名_モジュール名_アクション名.js を取得
		$js_paths =
		[
			CROW_PATH."app/assets/js/".$role."/".$role.".js",
			CROW_PATH."app/assets/js/".$role."/".$role."_".$module.".js",
			CROW_PATH."app/assets/js/".$role."/".$role."_".$module."_".$action.".js",
		];
		foreach( $js_paths as $file )
		{
			if( is_file($file) === true )
			{
				$source .= substr($file, -7) == ".min.js" ?
					file_get_contents($file) :
					crow_js::compress(file_get_contents($file), $require_paths)
					;
				$source .= "\n";
			}
		}

		//	さらに拡張パスから拾う
		if( count(self::$m_ext_js_paths) > 0 )
		{
			foreach( self::$m_ext_js_paths as $path )
			{
				$js_paths =
				[
					$path.$role.".js",
					$path.$role."_".$module.".js",
					$path.$role."_".$module."_".$action.".js",
				];
				foreach( $js_paths as $file )
				{
					if( is_file($file) === true )
					{
						$source .= substr($file, -7) == ".min.js" ?
							file_get_contents($file) :
							crow_js::compress(file_get_contents($file), $require_paths)
							;
						$source .= "\n";
					}
				}
			}
		}

		//	ビューパーツからの出力があれば加える
		$viewpart_js = crow_viewpart::js($role, $module, $action);
		if( strlen($viewpart_js) > 0 )
			$source .= crow_js::compress($viewpart_js, $require_paths);

		//	キャッシュに保存して返却
		crow_cache::save( $cache_name, $source );
		return $source;
	}

	//--------------------------------------------------------------------------
	//	dispatch action
	//--------------------------------------------------------------------------
	private function dispatch()
	{
		//	初期化必須
		if( $this->m_initialized !== true )
			return crow_log::error( 'engine has not been initialized' );

		//	アクセスログを記録
		if( crow_config::get('log.access') == 'true' )
			self::output_accesslog();

		//	IP制限のチェック
		$allow_ips = trim(crow_config::get('allow.ip', ''));
		if( $allow_ips != "" )
		{
			$ips = explode(",", $allow_ips);
			$allow_ips = [];
			foreach( $ips as $ip ) $allow_ips[] = trim($ip);
			if( in_array(crow_request::ip(), $allow_ips) === false )
				return crow_log::error('illegal access : '.crow_request::ip());
		}

		//	設定のデフォルトヘッダを適用する
		$default_headers = crow_config::get_starts_with("default.header.");
		if( count($default_headers) > 0 )
		{
			foreach( $default_headers as $key => $val )
				crow_response::set_header( trim(substr($key, strlen("default.header."))), trim($val) );
		}

		//	拡張URL指定があった場合、jsならそれを出力する。
		$ext_url = crow_request::get_ext();
		if( $ext_url === "js" )
		{
			crow_response::set_header("Content-Type", "text/javascript");
			self::output_start();
			echo self::get_js_code();
			self::output_end();
			return;
		}

		//	クッキーが発行されていた場合は、vars引継ぎの処理を行う
		$need_del_vars = false;
		$sess = false;

		//	"crow_session_"で始まるcookieの値があるなら、jwt情報を含むものとする
		$has_jwt = false;
		$cookie_keys = array_keys($_COOKIE);
		$cookie_keyword = 'crow_session_';
		$cookie_keyword_len = strlen($cookie_keyword);
		foreach( $cookie_keys as $cookie_key )
		{
			if( substr($cookie_key, 0, $cookie_keyword_len) == $cookie_keyword )
			{
				$has_jwt = true;
				break;
			}
		}
		if( $has_jwt === true )
		{
			//	セッションからのvars引き継ぎ処理
			$sess = crow_session::get_instance();
			$redirect_vars = $sess->get_system_property('vars');
			$need_del_vars = false;
			if( is_array($redirect_vars) === true )
			{
				if( count($redirect_vars) > 0 )
				{
					//	リクエストとレスポンスに渡す。クリアについては別途
					foreach( $redirect_vars as $rkey => $rval )
					{
						crow_request::set( $rkey, $rval );
						crow_response::set( $rkey, $rval );
					}

					//	URLがファイル名終端でないなら、vars削除予約
					$crow_param = crow_request::get('crow', '');
					$rp = strrpos("/",$crow_param);
					if( $rp !== false )
						$need_del_vars = strpos(substr($crow_param, $rp+1), ".") === false;
					else
						$need_del_vars = true;
				}
			}
		}

		//	CSRF無視のアクションかどうかを判断しておく
		$ignore_csrf = false;
		if( isset($this->m_opt['csrf_ignores']) === true && count($this->m_opt['csrf_ignores']) > 0 )
		{
			foreach( $this->m_opt['csrf_ignores'] as $ignore_item )
			{
				if( count($ignore_item) < 2 ) continue;
				if( ($ignore_item[0] == "*" || $ignore_item[0] == crow_request::get_module_name()) &&
					($ignore_item[1] == "*" || $ignore_item[1] == crow_request::get_action_name())
				){
					$ignore_csrf = true;
					break;
				}
			}
		}

		//	CSRF情報をセッションから復帰、POSTだった場合の検証
		self::$m_csrf_tokens = $sess !== false ? $sess->get_system_property("csrf") : [];
		if( self::$m_csrf_tokens === false ) self::$m_csrf_tokens = [];
		if( crow_request::is_post() && crow_config::get("csrf.verify","") == "true" && $ignore_csrf !== true )
		{
			$csrf_key = self::get_csrf_key(crow_request::get_module_name(), crow_request::get_action_name());
			$csrf_val = crow_request::get($csrf_key, '');
			if( isset(self::$m_csrf_tokens[$csrf_key]) === false ||
				self::$m_csrf_tokens[$csrf_key] != $csrf_val
			){
				//	一度検証したトークンはクリア
				if( isset(self::$m_csrf_tokens[$csrf_key]) === true )
					unset(self::$m_csrf_tokens[$csrf_key]);

				if( crow_request::is_ajax() === true )
					return self::redirect
					(
						crow_config::get('csrf.module.ajax', crow_config::get('default.error.module.ajax', false)),
						crow_config::get('csrf.action.ajax', crow_config::get('default.error.action.ajax', false))
					);
				else
					return self::redirect
					(
						crow_config::get('csrf.module', crow_config::get('default.error.module', false)),
						crow_config::get('csrf.action', crow_config::get('default.error.action', false))
					);
			}

			//	一度検証したトークンはクリア
			unset(self::$m_csrf_tokens[$csrf_key]);
		}

		//	モジュール作成
		$role_name		= crow_request::get_role_name();
		$module_name	= crow_request::get_module_name();
		$action_name	= crow_request::get_action_name();
		$class_name		= "module_".$role_name."_".$module_name;

		//	クラスが存在するならロジック実行
		$done = false;
		if( class_exists($class_name) === true )
		{
			//	先にvarsクリア
			if( $need_del_vars === true )
			{
				if( $sess !== false ) $sess->clear_system_property('vars');
				$need_del_vars = false;
			}

			$module = new $class_name();

			//	必要であればプリロード実行
			if( method_exists($module, 'preload') === true )
			{
				if( $module->preload() === false ) return;
			}

			//	リクエストメソッドにより実行する関数が異なる
			if( crow_request::is_post() === true )
			{
				if( method_exists($module, "action_".$action_name."_post") === true )
				{
					$module->{"action_".$action_name."_post"}();
					$done = true;
				}
				else if( method_exists($module, "action_".$action_name) === true )
				{
					$module->{"action_".$action_name}();
					$done = true;
				}
			}
			else if( crow_request::is_get() === true )
			{
				if( method_exists($module, "action_".$action_name) === true )
				{
					$module->{"action_".$action_name}();
					$done = true;
				}
			}
			else
			{
				crow_log::error( 'unknown request method ['.crow_request::method().']' );
				return;
			}
		}
		//	クラスが存在しないなら、ロール名までのクラスを探して、
		//	プリロードだけ確認する
		else
		{
			$role_class_name = "module_".$role_name;
			if( class_exists($role_class_name) === true )
			{
				$role_inst = new $role_class_name();
				if( method_exists($role_inst, 'preload') === true )
				{
					//	varsクリア
					if( $need_del_vars === true )
					{
						if( $sess !== false ) $sess->clear_system_property('vars');
						$need_del_vars = false;
					}

					if( $role_inst->preload() === false ) return;
				}
			}
		}

		//	リクエストのアクションが、インスタンスと異なる場合、
		//	フォワードされたと判断してビュー表示を行わない。
		if( $role_name != crow_request::get_role_name() ||
			$module_name != crow_request::get_module_name() ||
			$action_name != crow_request::get_action_name()
		)	return;

		//	actionに対するビューを探す
		$view_path = CROW_PATH."app/views/".$role_name."/".$module_name."/".$action_name;

		//	キャリア指定のビューがあるならそれを利用するため、
		//	ファイル名のプリフィクスに対して優先度を決める
		$suffixs = [];
		if( crow_request::is_android() === true )
		{
			$suffixs[] = "_sp.php";
			$suffixs[] = "_android.php";
		}
		else if( crow_request::is_iphone() === true )
		{
			$suffixs[] = "_sp.php";
			$suffixs[] = "_ios.php";
			$suffixs[] = "_iphone.php";
		}
		else if( crow_request::is_ipod() === true )
		{
			$suffixs[] = "_sp.php";
			$suffixs[] = "_ios.php";
			$suffixs[] = "_ipod.php";
		}
		else if( crow_request::is_ipad() === true )
		{
			$suffixs[] = "_sp.php";
			$suffixs[] = "_ios.php";
			$suffixs[] = "_ipad.php";
		}
		else {}
		$suffixs[] = ".php";

		//	上で計算した優先度に従い、最初に見つかったファイルをビューとして表示する
		$found_view = false;
		foreach( $suffixs as $suffix )
		{
			if( is_file($view_path.$suffix) === true )
			{
				//	varsクリア
				if( $need_del_vars === true )
				{
					if( $sess !== false ) $sess->clear_system_property('vars');
					$need_del_vars = false;
				}

				self::output_start();
				self::display($view_path.$suffix);
				self::output_end();
				$done = true;
				$found_view = true;
				break;
			}
		}

		//	ビューが見つからない場合、拡張パスの中から検索
		if( $found_view === false && count(self::$m_ext_view_paths) > 0 )
		{
			foreach( self::$m_ext_view_paths as $ext_path )
			{
				foreach( $suffixs as $suffix )
				{
					if( is_file($ext_path.$action_name.$suffix) === true )
					{
						//	varsクリア
						if( $need_del_vars === true )
						{
							if( $sess !== false ) $sess->clear_system_property('vars');
							$need_del_vars = false;
						}

						self::output_start();
						self::display($ext_path.$action_name.$suffix);
						self::output_end();
						$done = true;
						$found_view = true;
						break;
					}
				}
				if( $done === true ) break;
			}
		}

		//	ロジックもビューもなければ、configで設定した404用のビューへ飛ばす。
		//	それすらもないなら、実装漏れでエラーとする
		if( $done === false )
		{
			$module_404 = crow_config::get('notfound.module', false);
			$action_404 = crow_config::get('notfound.action', false);
			if( $module_404 !== false && $action_404 !== false )
			{
				if( $module_name != $module_404 || $action_name != $action_404 )
				{
					return self::forward($module_404, $action_404);
				}
			}

			//	どうやっても表示できないなら、404画面をデザインなしで表示する
			crow_log::error( 'not found action and view : '
				.$role_name.'/'.$module_name.'/'.$action_name );
			self::output_start();
			echo '<html><head></head><body><div style="font-size:40px;text-align:center;margin:20px;">404 not found</div></body></html>';
			self::output_end();
		}
	}

	//--------------------------------------------------------------------------
	//	出力
	//--------------------------------------------------------------------------
	public static function output_start()
	{
		//	ヘッダ出力
		$headers = crow_response::get_headers();
		if( count($headers) > 0 )
		{
			foreach( $headers as $key => $val )
				header( $key.": ".$val );
		}

		//	出力バッファへの蓄積開始
		ob_start();
	}
	public static function output_end()
	{
		//	jwtトークン出力
		crow_session::jwt_output_cookie();

		//	出力バッファのフラッシュ
		echo ob_get_clean();
	}
	private static function display( $view_path_, $view_args_ = [] )
	{
		//	ビューパーツのinclude用
		$include = function( $path_, $args_ = [], $after_clear_ = false )
		{
			foreach( $args_ as $key => $val ) crow_response::set($key, $val, false);
			$role_name		= crow_request::get_role_name();
			$module_name	= crow_request::get_module_name();

			//	自フォルダ > モジュール単位共通 > 全モジュール共通
			//	の優先度でビューパーツを探しに行く
			$view_path1 = CROW_PATH."app/views/".$role_name."/".$module_name."/";
			$view_path2 = CROW_PATH."app/views/".$role_name."/_common_/";
			$view_path3 = CROW_PATH."app/views/_common_/";
			if( is_file($view_path1.$path_) === true ) self::display($view_path1.$path_, $args_);
			else if( is_file($view_path2.$path_) === true ) self::display($view_path2.$path_, $args_);
			else if( is_file($view_path3.$path_) === true ) self::display($view_path3.$path_, $args_);
			else
			{
				//	viewパス拡張がある場合はさらに検索
				if( count(self::$m_ext_view_paths) > 0 )
				{
					$found_ext = false;
					foreach( self::$m_ext_view_paths as $ext_path )
					{
						if( is_file($ext_path."_common_/".$path_) === true )
						{
							self::display($ext_path."_common_/".$path_, $args_);
							$found_ext = true;
							break;
						}
					}
					if( $found_ext === false )
						return crow_log::error('not found view file : '.$path_);
				}
				else
					return crow_log::error('not found view file : '.$path_);
			}

			if( $after_clear_ === true )
			{
				foreach( $args_ as $key => $val ) crow_response::reset($key);
			}
		};

		//	action から view 内変数への展開
		foreach( crow_response::get_all() as $___key___ => $___val___ ) ${$___key___} = $___val___;

		//	ビューへの引数を変数へ展開する
		foreach( $view_args_ as $___key___ => $___val___ ) ${$___key___} = $___val___;

		//	表示実行
		include( $view_path_ );
	}

	//--------------------------------------------------------------------------
	//	argvのパース
	//--------------------------------------------------------------------------
	private static function parse_argv()
	{
		if( self::$m_parsed_argv !== false ) return;

		//	$argv の時点でダブルクォーテーションとエスケープはphpが認識している
		global $argv;
		$result = [];
		foreach($argv as $val)
		{
			$eq_pos = strpos($val, "=");
			$quot_pos_1 = strpos($val, "'");
			$quot_pos_2 = strpos($val, "\"");

			//	クォーテーションが"="より先に出現していれば、キーのみのオプションとなる
			if( $eq_pos !== false &&
				(
					($quot_pos_1 !== false && $eq_pos > $quot_pos_1) ||
					($quot_pos_2 !== false && $eq_pos > $quot_pos_2)
				)
			){
				$result[$val] = true;
			}

			//	"="があればkey=valueの形
			else if($eq_pos !== false )
			{
				$result[substr($val, 0, $eq_pos)] = substr($val, $eq_pos + 1);
			}

			//	それ以外はキーのみのオプション
			else
			{
				$result[$val] = true;
			}
		}
		self::$m_parsed_argv = $result;
	}

	//--------------------------------------------------------------------------
	//	output access log
	//--------------------------------------------------------------------------
	private function output_accesslog()
	{
		//	認証情報は、オプションでtrueの場合のみ出力する。
		//	→ セッションへのアクセスを軽減できるようにする目的でon/offできるようにしている。
		$auth = "";
		if( crow_config::get('log.auth', 'false') == 'true' )
		{
			$auth = crow_auth::get_logined_id();
			$auth = $auth === false ? "" : (" auth[".$auth."]");
		}

		$indent = "\t";
		$msg = sprintf
		(
			"method[%s] crow[%s/%s/%s]%s",
			isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : '',
			crow_request::get_role_name(),
			crow_request::get_module_name(),
			crow_request::get_action_name(),
			$auth
		);
		$msg .= "\n"
			. $indent."UA:".(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."\n"
			. $indent."REFERER:".(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '')."\n"
			;

		foreach( $_REQUEST as $key => $val )
		{
			//	隠しパラメータが指定されている場合は、
			//	それにヒットするパラメータを出力しない
			if( isset($this->m_opt['hidden']) === true )
			{
				$hit_hidden = false;
				foreach( $this->m_opt['hidden'] as $hidden )
				{
					//	"/"で括られていれば正規表現比較とする。正規表現は大小文字を区別する
					if( substr($hidden,0,1) == "/" && substr($hidden,-1) == "/" )
					{
						//	preg_matchが 1 or 0 or false を返却するので1かどうかで判断する
						if( preg_match($hidden, $key) === 1 )
						{
							$hit_hidden = true;
							break;
						}
					}
					else if( strtolower($hidden) == strtolower($key) )
					{
						$hit_hidden = true;
						break;
					}
				}
				if( $hit_hidden ) continue;
				$msg .= is_array($val) ?
					("\n".$indent.$key.":".print_r($val,true)) :
					("\n".$indent.$key.":".$val)
					;
			}

			//	隠しパラメータが指定されていない場合は、
			//	パスワードで使われそうなパラメータを出力しない
			else
			{
				$k = strtolower($key);
				if( $k=="admin_pw" ||
					$k=="login_pass" ||
					$k=="login_pw" ||
					$k=="pw" ||
					$k=="pass" ||
					$k=="password"
				) continue;
				$msg .= is_array($val) ?
					("\n".$indent.$key.":".print_r($val,true)) :
					("\n".$indent.$key.":".$val)
					;
			}
		}
		$msg .= "\n";

		crow_log::log_with_name( 'access', $msg );
	}

	//--------------------------------------------------------------------------
	//	singleton instance
	//--------------------------------------------------------------------------
	public static function get_instance()
	{
		static $instance;
		if( isset($instance) === false ) $instance = new self();
		return $instance;
	}

	//--------------------------------------------------------------------------
	//	private
	//--------------------------------------------------------------------------
	private $m_initialized = false;
	private $m_is_batch = false;
	private $m_opt = [];
	private static $m_csrf_tokens = [];
	private static $m_hdb_reader = false;
	private static $m_hdb_writer = false;
	private static $m_ext_view_paths = [];
	private static $m_ext_js_paths = [];
	private static $m_ext_css_paths = [];
	private static $m_ext_query_paths = [];
	private static $m_parsed_argv = false;
}


?>
