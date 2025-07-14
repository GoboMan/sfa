<?php
/*

	crow config

*/
class crow_config
{
	//--------------------------------------------------------------------------
	//	指定したキーが存在するかチェックする
	//--------------------------------------------------------------------------
	public static function exists( $key_ )
	{
		return isset(self::$m_items[$key_]);
	}

	//--------------------------------------------------------------------------
	//	指定したキーに対する値を取得する
	//
	//	見つからなかった場合、$default_に指定した値を返却する
	//--------------------------------------------------------------------------
	public static function get( $key_, $default_ = "" )
	{
		if( self::$m_items === false )
		{
			echo "not found config key : ".$key_;
			exit;
		}
		if( isset(self::$m_items[$key_]) === false )
		{
			if( $default_ == "" )
				crow_log::notice_without_auth("not found config key : ".$key_);
			return $default_;
		}
		return self::$m_items[$key_];
	}

	//	上記のログ出力を行わないバージョン
	public static function get_if_exists( $key_, $default_ = "" )
	{
		if( self::$m_items === false ) return $default_;
		return isset(self::$m_items[$key_]) === true ?
			self::$m_items[$key_] : $default_;
	}

	//--------------------------------------------------------------------------
	//	指定したキーの値を上書きする
	//--------------------------------------------------------------------------
	public static function set( $key_, $value_ )
	{
		if( self::$m_items === false )
		{
			echo "crow_config::set() executed before initialization : ".$key_;
			exit;
		}
		self::$m_items[$key_] = $value_;
	}

	//--------------------------------------------------------------------------
	//	指定したキーで始まる項目一覧を取得する
	//--------------------------------------------------------------------------
	public static function get_starts_with( $prefix_ )
	{
		if( self::$m_items === false )
		{
			echo "crow_config::get_starts_with() executed before initialization : ".$prefix_;
			exit;
		}

		$results = [];
		if( count(self::$m_items) <= 0 )
		{
			crow_log::notice_without_auth("not found config key starts with : ".$prefix_);
		}
		else
		{
			foreach( self::$m_items as $key => $val )
			{
				if( strpos($key, $prefix_) === 0 )
					$results[$key] = $val;
			}
		}
		return $results;
	}

	//--------------------------------------------------------------------------
	//	指定したファイルを読み込み、現在のリストにマージする
	//--------------------------------------------------------------------------
	private static function merge( $fname_ )
	{
		if( is_file($fname_) === false ) return false;
		if( is_readable($fname_) === false ) return false;

		//	読込み
		$lines = file( $fname_ );
		foreach( $lines as $line )
		{
			//	コメントアウト
			if( substr($line,0,1) == ";" ) continue;
			if( substr($line,0,2) == "//" ) continue;

			//	項目抽出
			$pos = mb_strpos( $line, '=' );
			if( $pos !== false )
			{
				$item_key = trim( mb_substr($line, 0, $pos) );
				$item_val = trim( mb_substr($line, $pos + 1) );

				//	crow_pathの置換
				$item_val = str_replace("[CROW_PATH]", CROW_PATH, $item_val);

				self::$m_items[$item_key] = $item_val;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	public static function init( $role_, $distribution_='' )
	{
		//	キャッシュから取得
		$key = "crow_config_".$role_;
		self::$m_items = crow_cache::load($key);
		if( self::$m_items !== false ) return;

		//	キャッシュになければ構築する
		self::$m_items = [];
		self::merge(CROW_PATH."app/config/config.txt");
		if( $distribution_ != '' )
		{
			self::merge(CROW_PATH."app/config/config_".$distribution_.".txt");
			self::merge(CROW_PATH."app/config/".$distribution_."/config.txt");
		}

		self::merge(CROW_PATH."app/config/config_".$role_.".txt");
		if( $distribution_ != '' )
		{
			self::merge(CROW_PATH."app/config/config_".$role_."_".$distribution_.".txt");
			self::merge(CROW_PATH."app/config/".$distribution_."/config_".$role_.".txt");
		}

		//	キャッシュに格納しておく
		crow_cache::save($key, self::$m_items);
	}

	//--------------------------------------------------------------------------
	//	private
	//--------------------------------------------------------------------------
	private static $m_items = false;
}

?>
