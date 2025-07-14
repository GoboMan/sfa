<?php
/*

	基本となるバリデータ

	true  の返却でチェックOK
	false の返却でチェックNG
	とする。
*/
class crow_validation
{
	//--------------------------------------------------------------------------
	//	文字数が少なすぎないかチェック
	//
	//	指定より少ない場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_str_under( $value_, $min_ )
	{
		return mb_strlen($value_) < $min_;
	}

	//--------------------------------------------------------------------------
	//	文字数が多すぎないかチェック
	//
	//	指定より多い場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_str_over( $value_, $max_ )
	{
		return mb_strlen($value_) > $max_;
	}

	//--------------------------------------------------------------------------
	//	文字数が指定した範囲内かチェック
	//
	//	範囲内の場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_str_range( $value_, $min_, $max_ )
	{
		$len = mb_strlen($value_);
		return $len>=$min_ && $len<=$max_;
	}

	//--------------------------------------------------------------------------
	//	数値であるかチェックする
	//
	//	数値の場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_num( $value_ )
	{
		return preg_match("/^-?[0-9]+$/", $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	数値が小さすぎないかチェック
	//
	//	指定より小さい場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_num_under( $value_, $min_ )
	{
		if( strlen($value_)<=0 ) return false;
		if( ! preg_match("/^-?[0-9]+$/", $value_) ) return false;
		return $value_ < $min_;
	}

	//--------------------------------------------------------------------------
	//	数値が大きすぎないかチェック
	//
	//	指定より大きい場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_num_over( $value_, $max_ )
	{
		if( strlen($value_)<=0 ) return false;
		if( ! preg_match("/^-?[0-9]+$/", $value_) ) return false;
		return $value_ > $max_;
	}

	//--------------------------------------------------------------------------
	//	数値が指定した範囲内かチェック
	//
	//	範囲内の場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_num_range( $value_, $min_, $max_ )
	{
		if( strlen($value_)<=0 ) return false;
		if( ! preg_match("/^-?[0-9]+$/", $value_) ) return false;
		return $value_>=$min_ && $value_<=$max_;
	}

	//--------------------------------------------------------------------------
	//	少数であるかチェックする
	//
	//	少数の場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_dec( $value_ )
	{
		$index_to_decimal = function($str)
		{
			if( preg_match('@\.(\d+)E\-(\d+)@',$str,$matches) )
			{
				$digit = strlen($matches[1]) + $matches[2];
				$format  = "%.".$digit."f";
				$str     = sprintf($format,$str);
				return $str;
			}
			return $str;
		};
		return preg_match("/^-?[0-9]*\.?[0-9]+$/", $index_to_decimal($value_)) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	少数が小さすぎないかチェック
	//
	//	少数より小さい場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_dec_under( $value_, $min_ )
	{
		if( strlen($value_)<=0 ) return false;
		if( self::check_dec($value_) === false ) return false;
		return $value_ < $min_;
	}

	//--------------------------------------------------------------------------
	//	少数が大きすぎないかチェック
	//
	//	少数より大きい場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_dec_over( $value_, $max_ )
	{
		if( strlen($value_)<=0 ) return false;
		if( self::check_dec($value_) === false ) return false;
		return $value_ > $max_;
	}

	//--------------------------------------------------------------------------
	//	少数が指定した範囲内かチェック
	//
	//	範囲内の場合は true 返却
	//--------------------------------------------------------------------------
	public static function check_dec_range( $value_, $min_, $max_ )
	{
		if( strlen($value_)<=0 ) return false;
		if( self::check_dec($value_) === false ) return false;
		return $value_>=$min_ && $value_<=$max_;
	}

	//--------------------------------------------------------------------------
	//	メールアドレスかチェック
	//--------------------------------------------------------------------------
	public static function check_mail_addr( $value_ )
	{
		if( strlen($value_)<=0 ) return false;
		return preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._+-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	URLかチェック
	//--------------------------------------------------------------------------
	public static function check_url( $value_ )
	{
		if( strlen($value_)<=0 ) return false;
		return preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	電話番号かチェック
	//
	//	国内電話番号のみ通す
	//--------------------------------------------------------------------------
	public static function check_telno( $value_ )
	{
		if( strlen($value_) <= 0 ) return false;
		if( strlen($value_) > 16 ) return false;
		return preg_match("/^[0-9-]+$/", $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	国際電話番号であるかをチェック
	//
	//	例えば "+81" などが頭にない場合は国際電話番号ではないと判断される。
	//--------------------------------------------------------------------------
	public static function check_telno_global( $value_ )
	{
		if( strlen($value_) <= 0 ) return false;
		if( strlen($value_) > 16 ) return false;
		return preg_match('/^(?:\+81[-\s]?)?0\d{1,4}[-\s]?\d{1,4}[-\s]?\d{4}$/', $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	国内電話番号または国際電話番号かチェック
	//
	//	+81 999-999-9999
	//	国際電話番号は最大16文字となる
	//--------------------------------------------------------------------------
	public static function check_telno_all( $value_ )
	{
		if( strlen($value_) <= 0 ) return false;
		if( strlen($value_) > 16 ) return false;
		return preg_match('/^(?:\+81[-\s]?)?0\d{1,4}[-\s]?\d{1,4}[-\s]?\d{4}$/', $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	半角文字列かチェック
	//--------------------------------------------------------------------------
	public static function check_ascii( $value_ )
	{
		return strlen($value_) == mb_strlen($value_);
	}

	//--------------------------------------------------------------------------
	//	半角英数かチェック
	//--------------------------------------------------------------------------
	public static function check_alnum( $value_ )
	{
		return preg_match("/^[0-9a-zA-Z]*$/", $value_) ? true : false;
	}

	//--------------------------------------------------------------------------
	//	日付文字列かチェック
	//--------------------------------------------------------------------------
	public static function check_datetime( $value_, $format_ = "Y/m/d H:i:s" )
	{
		return $value_ == date($format_, strtotime($value_));
	}
}


?>
