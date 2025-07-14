<?php
/*

	アプリ共通

*/
class	app
{
	//--------------------------------------------------------------------------
	//	ajax 返却コード
	//--------------------------------------------------------------------------
	const CODE_OK			= 100;
	const CODE_NG			= 200;
	const CODE_NG_LOGIN		= 202;		//	ログインしていない
	const CODE_NG_CSRF		= 203;		//	CSRFエラー
	const CODE_NG_NOTFOUND	= 204;		//	ページが見つからない
	const CODE_NG_NOSCRIPT	= 205;		//	Scriptが有効ではない

	//--------------------------------------------------------------------------
	//	json形式レスポンス出力終了コア
	//--------------------------------------------------------------------------
	public static function exit_core( $code_, $data_ )
	{
		crow_response::set_header("Content-Type", "application/json; charset=utf-8");
		crow::output_start();
		echo crow_utility::array_to_json(
		[
			'r'	=> $code_,
			'd'	=> $data_
		]);
		crow::output_end();
		exit;
	}

	//--------------------------------------------------------------------------
	//	正常出力終了
	//--------------------------------------------------------------------------
	public static function exit_ok( $data_='' )
	{
		self::exit_core(self::CODE_OK, $data_);
	}
	public static function exit_ok_with_code( $code_, $data_='' )
	{
		self::exit_core($code_, $data_);
	}

	//--------------------------------------------------------------------------
	//	異常出力終了
	//--------------------------------------------------------------------------
	public static function exit_ng( $data_='' )
	{
		self::exit_core(self::CODE_NG, $data_);
	}
	public static function exit_ng_with_code( $code_, $data_='' )
	{
		self::exit_core($code_, $data_);
	}
}

?>
