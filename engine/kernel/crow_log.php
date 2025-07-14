<?php
/*

	crow log

*/
class crow_log
{
	//--------------------------------------------------------------------------
	//	ログ
	//--------------------------------------------------------------------------
	public static function notice( /* msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return;
		$msg = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		$auth = crow_config::get('log.auth', 'false') == 'true' ?
			crow_auth::get_logined_id() : false;
		$auth = $auth === false ? "" : ("[auth:".$auth."] ");
		self::write( "[notice] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$auth.$msg, "system", $args );
	}
	public static function notice_without_auth( /* msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return;
		$msg = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		self::write( "[notice] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$msg, "system", $args );
	}
	public static function warning( /* msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return;
		$msg = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		$auth = crow_config::get('log.auth', 'false') == 'true' ?
			crow_auth::get_logined_id() : false;
		$auth = $auth === false ? "" : ("[auth:".$auth."] ");
		self::write( "[warning] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$auth.$msg, "system", $args );
	}
	public static function error( /* msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return;
		$msg = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		$auth = crow_config::get('log.auth', 'false') == 'true' ?
			crow_auth::get_logined_id() : false;
		$auth = $auth === false ? "" : ("[auth:".$auth."] ");
		self::write( "[error] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$auth.$msg, "system", $args );

		if( self::$abort_on_error === true ) exit;
	}
	public static function log( /* msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 0 ) return;
		$msg = func_get_arg(0);
		$args = [];
		for( $i = 1; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		self::write( $msg, "system", $args );
	}

	public static function notice_with_name( /* name_, msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 1 ) return;
		$name = func_get_arg(0);
		$msg = func_get_arg(1);
		$args = [];
		for( $i = 2; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		self::write( "[notice] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$msg, $name, $args );
	}
	public static function warning_with_name( /* name_, msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 1 ) return;
		$name = func_get_arg(0);
		$msg = func_get_arg(1);
		$args = [];
		for( $i = 2; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		self::write( "[warning] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$msg, $name, $args );
	}
	public static function error_with_name( /* name_, msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 1 ) return;
		$name = func_get_arg(0);
		$msg = func_get_arg(1);
		$args = [];
		for( $i = 2; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		self::write( "[error] [".crow_request::get_module_name()."/".crow_request::get_action_name()."] ".$msg, $name, $args );
	}
	public static function log_with_name( /* name_, msg_, ... */ )
	{
		$arg_num = func_num_args();
		if( $arg_num <= 1 ) return;
		$name = func_get_arg(0);
		$msg = func_get_arg(1);
		$args = [];
		for( $i = 2; $i < $arg_num; $i++ )
		{
			$a = func_get_arg($i);
			$args[] = (is_array($a) || is_object($a)) ? print_r($a,true) : $a;
		}
		if( is_array($msg) || is_object($msg) ) $msg = print_r($msg,true);

		self::write( $msg, $name, $args );
	}

	//--------------------------------------------------------------------------
	//	致命エラー時にスクリプト実行を強制停止するかどうかの設定
	//
	//	デフォルトは強制停止となりその挙動が好ましいため、
	//	例外としてのみ本機能を利用し、ここで無効にした後は用が済み次第すぐに元にもどすこと。
	//
	//	例）
	//	crow_log::set_abort_on_error(false); // 一時的に強制終了停止
	//	{
	//		...エラーをスルーしたい処理...
	//	}
	//	crow_log::set_abort_on_error(true); // 速やかにもとに戻す
	//
	//--------------------------------------------------------------------------
	public static function set_abort_on_error($enabled_ = true)
	{
		self::$abort_on_error = $enabled_;
	}

	//--------------------------------------------------------------------------
	//	出力処理
	//--------------------------------------------------------------------------
	public static function write( $msg_, $name_, $replaces_ = [] )
	{
		//	コールスタック出力が指定されていれば、メッセージに追加する
		if( crow_config::exists('log.trace') && crow_config::get('log.trace', '') == "true" )
		{
			$stack_lines = [];
			foreach(debug_backtrace() as $no => $line)
				$stack_lines[] = "\t".($no+1).". ".$line["file"].": #".$line["line"];
			$msg_ .= "\n".implode("\n",$stack_lines);
		}

		//	systemログの場合は、出力フラグをチェックする
		if( $name_ == "system" )
		{
			if( crow_config::exists("log.system") === false ) return;
			if( crow_config::get("log.system", "true") != "true" ) return;
		}

		//	初期化中のエラーの場合、画面に出力する。
		if( ! crow::initialized() )
		{
			echo count($replaces_) > 0 ? vsprintf($msg_,$replaces_) : $msg_;
		}
		else
		{
			if( crow_config::exists('log.dir') === false ) return;

			$path = crow_config::get('log.dir', CROW_PATH."output/logs/");
			$path = str_replace("[CROW_PATH]", CROW_PATH, $path);
			$path .= $name_."_".date('Ymd').'.log';
			if(
				substr($path,0,1) != "/" &&
				strpos($path,":") === false
			){
				//	必要なら相対パスから絶対パスへ変換する(warning防止)
				$path = dirname($_SERVER['SCRIPT_FILENAME'])."/".$path;
			}

			$fp = fopen( $path, "a" );
			if( $fp )
			{
				$m = count($replaces_) > 0 ? vsprintf($msg_,$replaces_) : $msg_;
				flock($fp, LOCK_EX);
				fputs($fp, date("Y/m/d H:i:s ").$m."\n");
				fflush($fp);
				flock($fp, LOCK_UN);
				fclose($fp);
				chmod($path, 0644);
			}
		}
	}

	//	内部スイッチ
	private static $abort_on_error = true;
}
?>
