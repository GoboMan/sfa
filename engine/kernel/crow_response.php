<?php
/*

	crow response

*/
class crow_response
{
	//--------------------------------------------------------------------------
	//	ヘッダレスポンス設定
	//--------------------------------------------------------------------------
	public static function set_header( $key_, $value_ )
	{
		if( strlen($value_) <= 0 )
			unset(self::$m_headers[$key_]);
		else
		{
			//	[NONCE]を置き換え
			$val = str_replace("[NONCE]", self::nonce(), $value_);
			self::$m_headers[$key_] = $val;
		}
	}

	//--------------------------------------------------------------------------
	//	ヘッダレスポンス取得
	//--------------------------------------------------------------------------
	public static function get_headers()
	{
		return self::$m_headers;
	}
	public static function get_header( $key_ )
	{
		return isset(self::$m_headers[$key_]) ? self::$m_headers[$key_] : false;
	}

	//--------------------------------------------------------------------------
	//	今回レスポンスでのnonce取得
	//--------------------------------------------------------------------------
	public static function nonce()
	{
		if( self::$m_nonce === false )
			self::$m_nonce = crow_utility::random_str();
		return self::$m_nonce;
	}

	//--------------------------------------------------------------------------
	//	ビューに渡す値をセットする
	//--------------------------------------------------------------------------
	public static function set( $key_, $value_, $html_escape_=true )
	{
		if( $html_escape_ )
		{
			if( is_object($value_) ) $escape_value = clone $value_;
			else $escape_value = $value_;
			self::$vars[$key_] = self::escape_html_string($escape_value);
		}
		else{
			self::$vars[$key_] = $value_;
		}
	}

	//--------------------------------------------------------------------------
	//	ビューに渡す値を削除する
	//--------------------------------------------------------------------------
	public static function reset( $key_ )
	{
		if( isset(self::$vars[$key_]) )
			unset(self::$vars[$key_]);
	}

	//--------------------------------------------------------------------------
	//	ビューに渡す値を連想配列でセットする
	//--------------------------------------------------------------------------
	public static function sets( $items_, $html_escape_=true )
	{
		foreach( $items_ as $k => $v ) self::set($k, $v, $html_escape_);
	}

	//--------------------------------------------------------------------------
	//	ビューに渡す値を取得する
	//--------------------------------------------------------------------------
	public static function get( $key_ )
	{
		return isset(self::$vars[$key_]) ? self::$vars[$key_] : false;
	}

	//--------------------------------------------------------------------------
	//	ビューに渡す値を全て取得
	//--------------------------------------------------------------------------
	public static function get_all()
	{
		return self::$vars;
	}
	private static $vars = [];

	//--------------------------------------------------------------------------
	//	HTML文字列エスケープ
	//--------------------------------------------------------------------------
	public static function escape_html_string($value_)
	{
		if( is_array($value_) )
		{
			foreach( $value_ as $key => $val )
			{
				$value_[$key] = self::escape_html_string($val);
			}
			return $value_;
		}
		else if( is_object($value_) )
		{
			foreach( $value_ as $key => $val )
			{
				if( is_array($val) || is_string($val) )
				{
					$value_->{$key} = self::escape_html_string($val);
				}
				else if( is_object($val) )
				{
					foreach( $val as $model_key => $model_val )
					{
						$val->{$model_key} = self::escape_html_string($model_val);
					}
					$value_->{$key} = $val;
				}
				else
				{
					$value_->{$key} = $val;
				}
			}
			return $value_;
		}
		else if( is_string($value_) )
		{
			return htmlspecialchars($value_);
		}
		return $value_;
	}

	//--------------------------------------------------------------------------
	//	private
	//--------------------------------------------------------------------------
	private static $m_headers = [];
	private static $m_nonce = false;
}
?>
