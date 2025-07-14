<?php
/*

	crow request

*/
class crow_request
{
	//--------------------------------------------------------------------------
	//	リクエストURLをファイル名を含めず取得
	//--------------------------------------------------------------------------
	public static function get_entry_url()
	{
		//	全パーツ、手動でも指定できるようにする
		$index_path = crow_config::get_if_exists('request.index', crow_storage::extract_dirpath($_SERVER['SCRIPT_NAME']));
		$request_scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
		$protocol = crow_config::get_if_exists('request.protocol', $request_scheme);
		$host = crow_config::get_if_exists('request.host', $_SERVER['HTTP_HOST']);
		$port = crow_config::get_if_exists('request.port', ($_SERVER['SERVER_PORT']=="80" || $_SERVER['SERVER_PORT']=="443" ? "" : $_SERVER['SERVER_PORT']));

		if( $port != "" ) $port = ":".$port;

		$pos = strpos($host, ":");
		if( $pos !== false ) $host = substr($host, 0, $pos);
		if( substr($index_path,0,-1) != "/" ) $index_path .= "/";

		return ''
			.$protocol."://"
			.$host
			.$port
			.$index_path
			;
	}

	//--------------------------------------------------------------------------
	//	リクエストURLをファイル名まで含めて取得
	//--------------------------------------------------------------------------
	public static function get_entry_script()
	{
		$request_scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
		$protocol = crow_config::get_if_exists('request.protocol', $request_scheme);

		return ''
			.$protocol."://"
			.$_SERVER['HTTP_HOST']
			.($_SERVER['SERVER_PORT']=="80" || $_SERVER['SERVER_PORT']=="443" ? "" : (":".$_SERVER['SERVER_PORT']))
			.$_SERVER['SCRIPT_NAME']
			;
	}

	//--------------------------------------------------------------------------
	//	クエリを含めたリクエストのフルパスを取得
	//--------------------------------------------------------------------------
	public static function get_current_url()
	{
		//	全パーツ、手動でも指定できるようにする
		$request_scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
		$protocol = crow_config::get_if_exists('request.protocol', $request_scheme);
		$host = crow_config::get_if_exists('request.host', $_SERVER['HTTP_HOST']);
		$port = crow_config::get_if_exists('request.port', ($_SERVER['SERVER_PORT']=="80" || $_SERVER['SERVER_PORT']=="443" ? "" : $_SERVER['SERVER_PORT']));

		if( $port != "" ) $port = ":".$port;

		$pos = strpos($host, ":");
		if( $pos !== false ) $host = substr($host, 0, $pos);

		return ''
			.$protocol."://"
			.$host
			.$port
			.$_SERVER['REQUEST_URI']
			;
	}

	//--------------------------------------------------------------------------
	//	リクエストメソッドを取得
	//--------------------------------------------------------------------------
	public static function method()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
	}

	//	POSTリクエスト？
	public static function is_post()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST";
	}

	//	GETリクエスト？
	public static function is_get()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "GET";
	}

	//--------------------------------------------------------------------------
	//  ajax リクエスト？
	//--------------------------------------------------------------------------
	public static function is_ajax()
	{
		return
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) === true &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
			;
	}

	//--------------------------------------------------------------------------
	//	実行中ポート名
	//--------------------------------------------------------------------------
	public static function get_role_name()
	{
		return self::$m_role;
	}

	//--------------------------------------------------------------------------
	//	実行中モジュール名
	//--------------------------------------------------------------------------
	public static function get_module_name()
	{
		return self::$m_module;
	}

	//--------------------------------------------------------------------------
	//	実行中アクション名
	//--------------------------------------------------------------------------
	public static function get_action_name()
	{
		return self::$m_action;
	}

	//--------------------------------------------------------------------------
	//	実行中拡張パラメータ
	//--------------------------------------------------------------------------
	public static function get_ext()
	{
		return self::$m_ext;
	}

	//--------------------------------------------------------------------------
	//	一致したカスタムルートのパス取得
	//	カスタムルートにヒットしていなければfalse返却
	//--------------------------------------------------------------------------
	public static function get_route()
	{
		return self::$m_route;
	}

	//	パラメータが埋め込まれた状態のパス取得
	//	カスタムルートにヒットしていなければfalse返却
	public static function get_route_path()
	{
		return self::$m_route_path;
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータ取得
	//--------------------------------------------------------------------------
	public static function get( $key_, $default_='' )
	{
		return isset($_REQUEST[$key_]) ? $_REQUEST[$key_] : $default_;
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータを数値で取得
	//--------------------------------------------------------------------------
	public static function get_int( $key_, $default_=0 )
	{
		if( isset($_REQUEST[$key_]) === false ) return $default_;
		if( crow_validation::check_num($_REQUEST[$key_]) === false ) return $default_;
		return intval($_REQUEST[$key_]);
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータを小数で取得
	//--------------------------------------------------------------------------
	public static function get_float( $key_, $default_=0 )
	{
		if( isset($_REQUEST[$key_]) === false ) return $default_;
		if( crow_validation::check_dec($_REQUEST[$key_]) === false ) return $default_;
		return floatval($_REQUEST[$key_]);
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータをそのままHTMLのアトリビュートに埋め込める形で取得
	//--------------------------------------------------------------------------
	public static function reflect( $key_, $default_='' )
	{
		$val = isset($_REQUEST[$key_]) ? $_REQUEST[$key_] : $default_;
		return crow_response::escape_html_string($val);
	}

	//--------------------------------------------------------------------------
	//	フォームで指定された名前のボタンが押されたかどうかチェックする
	//--------------------------------------------------------------------------
	public static function get_button( $btn_name_ )
	{
		return
			isset($_REQUEST[$btn_name_]) ||
			isset($_REQUEST[$btn_name_.'_x'])
			;
	}

	//--------------------------------------------------------------------------
	//  指定された文字列で始まるキーのリクエストパラメータをすべて取得する
	//--------------------------------------------------------------------------
	public static function get_starts_with( $key_ )
	{
		$rets = [];
		foreach( $_REQUEST as $key => $val )
		{
			if( strpos($key, $key_) === 0 )
				$rets[$key] = $val;
		}
		return $rets;
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータを書き換える
	//--------------------------------------------------------------------------
	public static function set( $key_, $val_ )
	{
		$_REQUEST[$key_] = $val_;
	}

	//--------------------------------------------------------------------------
	//	リクエストパラメータを削除する
	//--------------------------------------------------------------------------
	public static function unset( $key_ )
	{
		unset($_REQUEST[$key_]);
	}

	//--------------------------------------------------------------------------
	//	アクセス者のIPを取得
	//--------------------------------------------------------------------------
	public static function ip()
	{
		if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) )
		{
			$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			if( count($ips) > 0 ) return $ips[0];
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	//--------------------------------------------------------------------------
	//	端末判定
	//--------------------------------------------------------------------------
	public static function is_ios()
	{
		if( isset($_SERVER['HTTP_USER_AGENT']) === false ) return false;

		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		return
			strpos($ua, 'iphone') !== false ||
			strpos($ua, 'ipod') !== false ||
			strpos($ua, 'ipad') !== false
			;
	}
	public static function is_iphone()
	{
		if( isset($_SERVER['HTTP_USER_AGENT']) === false ) return false;

		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		return strpos($ua, 'iphone') !== false && strpos($ua, 'ipad') === false;
	}
	public static function is_ipod()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'ipod') !== false;
	}
	public static function is_ipad()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'ipad') !== false;
	}
	public static function is_android()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android') !== false;
	}
	public static function is_sp()
	{
		if( isset($_SERVER['HTTP_USER_AGENT']) === false ) return false;

		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		return
			(strpos($ua, 'iphone') !== false && strpos($ua,'ipad') === false) ||
			(strpos($ua, 'ipod') !== false) ||
			(strpos($ua,'android') !== false && strpos($ua,'mobile') !== false) ||
			(strpos($ua,'windows') !== false && strpos($ua,'phone') !== false) ||
			(strpos($ua,'firefox') !== false && strpos($ua,'mobile') !== false) ||
			(strpos($ua,'blackberry') !== false && strpos($ua,'bb') !== false)
			;
	}
	public static function is_tablet()
	{
		if( isset($_SERVER['HTTP_USER_AGENT']) === false ) return false;

		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		return
			(strpos($ua, 'ipad')!==false) ||
			(strpos($ua,'windows') !== false && strpos($ua,'touch') !== false && strpos($ua, 'tablet pc') == false) ||
			(strpos($ua,'android') !== false && strpos($ua,'mobile') === false) ||
			(strpos($ua,'firefox') !== false && strpos($ua,'tablet') !== false) ||
			(strpos($ua,'kindle') !== false) ||
			(strpos($ua,'silk') !== false && strpos($ua,'mobile') === false) ||
			(strpos($ua,'playbook') !== false)
			;
	}

	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	public static function init( $role_, $distribution_, $routes_ )
	{
		self::$m_distribution = $distribution_;
		self::$m_role = $role_;

		//	ヘッダのXSSチェック
		if( ! preg_match("/^[-|_|0-9|a-z|A-Z|.:]+$/", $_SERVER['HTTP_HOST'], $m) )
		{
			//	画面にエラーを出したくないので、crow_logへの出力は行わない
			self::$m_module = crow_config::get("notfound.module", false);
			self::$m_action = crow_config::get("notfound.action", false);
			self::$m_ext = false;
			if( self::$m_module === false || self::$m_action === false ) exit;
			return;
		}

		//	パーツ分解
		$action_path = isset($_REQUEST['crow']) === true ? trim($_REQUEST['crow']) : '';
		$parts_src = preg_split( "/[\/|?]/", $action_path );
		$parts = [];
		foreach( $parts_src as $part )
		{
			$part = trim($part);
			if( $part != "" ) $parts[] = $part;
		}
		$pnum = count($parts);

		//	最初のパートが_js_ならext
		if( $pnum > 0 )
		{
			if( $parts[0]=='_js_' )
			{
				self::$m_module = $parts[1];
				self::$m_action = $parts[2];
				self::$m_ext = "js";
				return;
			}
		}

		//	カスタムルートチェック
		foreach( $routes_ as $route )
		{
			$path = $route[0];
			$regex = count($route) > 3 ? $route[3] : [];

			if( substr($path,0,1) == "/" ) $path = substr($path, 1);
			if( substr($path,-1) == "/" ) $path = substr($path, 0, strlen($path) - 1);
			$route_parts = explode("/", $path);
			$route_pnum = count($route_parts);
			if( $route_pnum != $pnum ) continue;

			$args = [];
			$differ = false;
			for( $i=0; $i<$route_pnum; $i++ )
			{
				$p = $route_parts[$i];
				if( substr($p,0,1) == ":" )
				{
					$pname = substr($p,1);
					if( isset($regex[$pname]) )
					{
						//	正規表現が指定されていれば一致することをチェック
						if( preg_match($regex[$pname],$parts[$i],$m) )
						{
							$args[substr($p,1)] = $parts[$i];
						}
						else if( $p != $parts[$i] )
						{
							$differ = true;
							break;
						}
					}
					else
					{
						$args[substr($p,1)] = $parts[$i];
					}

					//	モジュール名やアクション名への置換チェック
					if( $p == $route[1] )
					{
						$route[1] = $parts[$i];
						if( strlen($route[1]) <= 0 )
							$route[1] = crow_config::get('default.module', '');
					}
					if( $p == $route[2] )
					{
						$route[2] = $parts[$i];
						if( strlen($route[2]) <= 0 )
							$route[2] = crow_config::get('default.action', '');
					}
				}
				else if( $p != $parts[$i] )
				{
					$differ = true;
					break;
				}
			}

			//	ルート一致
			if( $differ === false )
			{
				//	パスが省略されていて且つ":"開始の場合はデフォルトへ置換
				if( substr($route[1], 0, 1) == ":" )
					$route[1] = crow_config::get('default.module', '');
				if( substr($route[2], 0, 1) == ":" )
					$route[2] = crow_config::get('default.action', '');

				self::$m_module = $route[1];
				self::$m_action = $route[2];

				foreach( $args as $arg_key => $arg_val )
					self::set($arg_key, $arg_val);

				self::$m_route = $route[0];
				self::$m_route_path = "/".$action_path;
				return;
			}
		}

		//	通常ルート
		if( $pnum >= 2 )
		{
			self::$m_module = $parts[0];
			self::$m_action = $parts[1];
			self::$m_ext = false;
		}
		else if( $pnum >= 1 )
		{
			self::$m_module = $parts[0];
			self::$m_action = crow_config::get("default.action");
			self::$m_ext = false;
		}
		else
		{
			self::$m_module = crow_config::get("default.module");
			self::$m_action = crow_config::get("default.action");
			self::$m_ext = false;
		}
	}

	//--------------------------------------------------------------------------
	//	private
	//--------------------------------------------------------------------------
	private static $m_distribution = false;
	private static $m_role = false;
	private static $m_module = false;
	private static $m_action = false;
	private static $m_ext = false;
	private static $m_route = false;
	private static $m_route_path = false;
}
?>
