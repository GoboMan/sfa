<?php
/*

	crow error handler

*/

//	crow内のエラーの場合 autoload が効かないことがあるので、
//	crow_logは先読みしておく
require_once "crow_log.php";


class crow_error
{
	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	public static function init()
	{
		$version = explode('.', PHP_VERSION);
		if( intval($version[0]) >= 8 )
			set_error_handler("_crow_error_handler_core_");
		else
			set_error_handler("_crow_error_handler_old_", E_ALL | E_STRICT);

		register_shutdown_function("_crow_shutdown_handler_");
	}
}


//	エラーハンドラ : php8 以前
function _crow_error_handler_old_( $error_level_, $error_msg_, $error_file_, $error_line_, $error_context_ )
{
	_crow_error_handler_core_($error_level_, $error_msg_, $error_file_, $error_line_);
}

//	エラーハンドラ実体
//	crow_error::init()でアタッチされる。
function _crow_error_handler_core_( $error_level_, $error_msg_, $error_file_, $error_line_ )
{
	static $level_names =
	[
		E_ERROR				=> 'E_ERROR',
		E_WARNING			=> 'E_WARNING',
		E_PARSE				=> 'E_PARSE',
		E_NOTICE			=> 'E_NOTICE',
		E_CORE_ERROR		=> 'E_CORE_ERROR',
		E_CORE_WARNING		=> 'E_CORE_WARNING',
		E_COMPILE_ERROR		=> 'E_COMPILE_ERROR',
		E_COMPILE_WARNING	=> 'E_COMPILE_WARNING',
		E_USER_ERROR		=> 'E_USER_ERROR',
		E_USER_WARNING		=> 'E_USER_WARNING',
		E_USER_NOTICE		=> 'E_USER_NOTICE',
		E_STRICT			=> 'E_STRICT',
		E_RECOVERABLE_ERROR	=> 'E_RECOVERABLE_ERROR',
		E_DEPRECATED		=> 'E_DEPRECATED',
		E_USER_DEPRECATED	=> 'E_USER_DEPRECATED',
		E_ALL				=> 'E_ALL',
	];
	$level_str = "";
	if( array_key_exists($error_level_, $level_names) )
		$level_str = $level_names[$error_level_];

	//	ログ出力
	if( $error_level_ != E_NOTICE && $error_level_ != E_STRICT )
	{
		$msg = $level_str." : ".$error_file_." (".$error_line_.") ".$error_msg_;
		switch( $error_level_ )
		{
			case E_USER_NOTICE:
				crow_log::notice( $msg );
				break;
			case E_WARNING:
			case E_USER_WARNING:
				crow_log::warning( $msg );
				break;
			default:
				crow_log::error( $msg );
				break;
		}
	}

	//	致命的エラーの場合は、スクリプトの実行を中断する。
	if( $error_level_ == E_ERROR ||
		$error_level_ == E_USER_ERROR ||
		$error_level_ == E_RECOVERABLE_ERROR
	)	exit;
}
function _crow_shutdown_handler_()
{
	$err = error_get_last();
	if( ! $err ) return;

	static $level_names =
	[
		E_ERROR				=> 'E_ERROR',
		E_WARNING			=> 'E_WARNING',
		E_PARSE				=> 'E_PARSE',
		E_NOTICE			=> 'E_NOTICE',
		E_CORE_ERROR		=> 'E_CORE_ERROR',
		E_CORE_WARNING		=> 'E_CORE_WARNING',
		E_COMPILE_ERROR		=> 'E_COMPILE_ERROR',
		E_COMPILE_WARNING	=> 'E_COMPILE_WARNING',
		E_USER_ERROR		=> 'E_USER_ERROR',
		E_USER_WARNING		=> 'E_USER_WARNING',
		E_USER_NOTICE		=> 'E_USER_NOTICE',
		E_STRICT			=> 'E_STRICT',
		E_RECOVERABLE_ERROR	=> 'E_RECOVERABLE_ERROR',
		E_DEPRECATED		=> 'E_DEPRECATED',
		E_USER_DEPRECATED	=> 'E_USER_DEPRECATED',
		E_ALL				=> 'E_ALL',
	];
	$level_str = "";
	if( array_key_exists($err['type'], $level_names) )
		$level_str = $level_names[$err['type']];

	//	ログ出力
	if( $err['type'] != E_NOTICE && $err['type'] != E_STRICT )
	{
		$msg = $level_str." : ".$err['file']." (".$err['line'].") ".$err['message'];
		switch( $err['type'] )
		{
			case E_USER_NOTICE:
				crow_log::notice( $msg );
				break;
			case E_WARNING:
			case E_USER_WARNING:
				crow_log::warning( $msg );
				break;
			default:
				crow_log::error( $msg );
				break;
		}
	}
}
?>
