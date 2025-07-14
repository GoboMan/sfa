<?php
/*

	メッセージ

*/
class crow_msg
{
	//--------------------------------------------------------------------------
	//	指定したキーが存在するかチェックする
	//--------------------------------------------------------------------------
	public static function exists( $key_ )
	{
		$instance = self::get_instance();
		return isset($instance->m_msgs[$key_]);
	}

	//--------------------------------------------------------------------------
	//	取得
	//
	//	第一引数に キー名を指定する。
	//	第二引数以降に、パラメータを可変引数で渡す。
	//
	//	例）
	//	言語ファイルに次の定義があった場合。
	//
	//		validation.err.num.range = %sは、%d～%dの範囲で指定してください
	//
	//	取得するためのコードは次のようになる。
	//
	//		crow_msg::get( 'validation.err.num.range', 'ユーザ名', 1, 128 );
	//
	//--------------------------------------------------------------------------
	public static function get( /* key_, .... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return "";

		$key  = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ ) $args[] = func_get_arg($i);

		$def = "?";
		if( count($args) > 0 ) $def = $args[0];

		$instance = self::get_instance();
		if( isset($instance->m_msgs[$key]) === false )
		{
			crow_log::notice("not found message key : ".$key);
			return $def;
		}
		return vsprintf($instance->m_msgs[$key], $args);
	}

	//	上記のログ出力を行わないバージョン
	public static function get_if_exists( /* key_, .... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return "";

		$key  = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ ) $args[] = func_get_arg($i);

		$def = "?";
		if( count($args) > 0 ) $def = $args[0];

		$instance = self::get_instance();
		if( isset($instance->m_msgs[$key]) === false )
		{
			return $def;
		}
		return vsprintf($instance->m_msgs[$key], $args);
	}

	//--------------------------------------------------------------------------
	//	指定したキーで始まる一覧を取得する
	//	ここ取得できる値の一式は、%による置換が行われないものとする
	//--------------------------------------------------------------------------
	public static function get_starts_with( $prefix_ )
	{
		$instance = self::get_instance();
		$results = [];
		foreach( $instance->m_msgs as $msg_key => $msg_val )
		{
			if( strpos($msg_key, $prefix_) === 0 )
				$results[$msg_key] = $msg_val;
		}
		return $results;
	}

	//--------------------------------------------------------------------------
	//	指定したキーで始まる一覧を取得する
	//	ここ取得できる値の一式は、%による置換が行われないものとする
	//	返却される連想配列のキーからプレフィクスに指定した部分を除外する
	//--------------------------------------------------------------------------
	public static function get_starts_with_and_extract_prefix( $prefix_ )
	{
		$len = strlen($prefix_);
		$instance = self::get_instance();
		$results = [];
		foreach( $instance->m_msgs as $msg_key => $msg_val )
		{
			if( strpos($msg_key, $prefix_) === 0 )
				$results[substr($msg_key, $len)] = $msg_val;
		}
		return $results;
	}

	//--------------------------------------------------------------------------
	//	言語取得
	//--------------------------------------------------------------------------
	public static function lang()
	{
		$instance = self::get_instance();
		return $instance->m_lang_code;
	}

	//--------------------------------------------------------------------------
	//	言語変更
	//--------------------------------------------------------------------------
	public static function change_lang( $lang_code_ )
	{
		crow_config::set("lang.code", $lang_code_);
		self::get_instance(true);
	}

	//--------------------------------------------------------------------------
	//	インスタンス取得
	//--------------------------------------------------------------------------
	public static function get_instance( $reload_ = false )
	{
		if( $reload_ !== false )
		{
			self::$m_instance = null;
		}

		if( ! isset(self::$m_instance) )
		{
			//	設定で指定されている言語を取得する
			$req_lang = crow_config::get("lang.code", "auto");
			if( $req_lang == "auto" )
			{
				//	auto指定の場合、リクエストから最適な言語を計算する
				if( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) === true )
				{
					$req_langs = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
					$req_langs = array_reverse($req_langs);
					foreach( $req_langs as $lang )
					{
						if( preg_match('/^ja/i', $lang) ){
							$req_lang = "ja";
						}
						else if( preg_match('/^en/i', $lang) ){
							$req_lang = "en";
						}
						else if( preg_match('/^zh/i', $lang) ){
							$req_lang = "zh";
						}
					}
				}

				//	決まらなければenとする
				if( $req_lang == "auto" ) $req_lang = "en";
			}

			//	言語ファイルを読み込む
			$role = crow_request::get_role_name();
			$module = crow_request::get_module_name();
			$action = crow_request::get_action_name();
			$cache_name = 'lang_'.$role."_".$module."_".$action."_".$req_lang;

			//	キャッシュにあるならそれを利用する
			self::$m_instance = crow_cache::load($cache_name);
			if( self::$m_instance === false )
			{
				self::$m_instance = new crow_msg();
				self::$m_instance->m_lang_code = $req_lang;

				//	engine/assets/lang/<LANG>.txt
				self::$m_instance->append_load(CROW_PATH."engine/assets/lang/".$req_lang.".txt");

				//	app/assets/lang/_common_/*.txt
				$dir = CROW_PATH."app/assets/lang/_common_/";
				$paths = crow_storage::disk()->get_files($dir, "txt");
				foreach( $paths as $path ) self::$m_instance->append_load($path);

				//	app/assets/lang/<LANG>/_common_/*.txt
				$dir = CROW_PATH."app/assets/lang/".$req_lang."/_common_/";
				$paths = crow_storage::disk()->get_files($dir, "txt");
				foreach( $paths as $path ) self::$m_instance->append_load($path);

				//	app/assets/lang/<LANG>/<ROLE>/<ROLE>.txt
				self::$m_instance->append_load(CROW_PATH."app/assets/lang/".$req_lang."/".$role."/".$role.".txt");

				//	app/assets/lang/<LANG>/<ROLE>/<ROLE>_<MODULE>.txt
				self::$m_instance->append_load(CROW_PATH."app/assets/lang/".$req_lang."/".$role."/".$role."_".$module.".txt");

				//	app/assets/lang/<LANG>/<ROLE>/<ROLE>_<MODULE>_<ACTION>.txt
				self::$m_instance->append_load(CROW_PATH."app/assets/lang/".$req_lang."/".$role."/".$role."_".$module."_".$action.".txt");

				//	キャッシュ更新
				crow_cache::save( $cache_name, self::$m_instance );
			}
		}
		return self::$m_instance;
	}

	//--------------------------------------------------------------------------
	//	インスタンスのリロード
	//--------------------------------------------------------------------------
	public static function reload()
	{
		return self::get_instance(true);
	}

	//--------------------------------------------------------------------------
	//	追加読込み（同一キーは上書きされる）
	//--------------------------------------------------------------------------
	private function append_load( $fname_ )
	{
		if( is_file($fname_) === false ) return false;
		if( is_readable($fname_) === false ) return false;

		//	読込み
		$lines = file( $fname_ );
		foreach( $lines as $line )
		{
			//	コメントアウト
			if( mb_substr($line, 0, 2) == "//" ) continue;

			//	項目抽出
			$pos = mb_strpos( $line, '=' );
			if( $pos !== false )
			{
				$item_key = trim( mb_substr($line, 0, $pos) );
				$item_val = trim( mb_substr($line, $pos+1) );
				$this->m_msgs[$item_key] = $item_val;
			}
		}
		return true;
	}


	//	メッセージデータ
	private $m_msgs = [];

	//	言語コード
	private $m_lang_code = false;

	//	唯一のインスタンス
	private static $m_instance = null;
}


?>
