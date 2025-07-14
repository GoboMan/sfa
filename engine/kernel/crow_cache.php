<?php
/*

	crow cache

*/
class crow_cache
{
	//--------------------------------------------------------------------------
	//	初期化
	//--------------------------------------------------------------------------
	public static function init( $runopt_ )
	{
		$memcached_enable = isset($runopt_['memcached.enable']) ? boolval($runopt_['memcached.enable']) : false;
		$memcached_host = isset($runopt_['memcached.host']) ? $runopt_['memcached.host'] : '';
		$memcached_port = isset($runopt_['memcached.port']) ? intval($runopt_['memcached.port']) : 11211;
		$memcached_prefix = isset($runopt_['memcached.prefix']) ? $runopt_['memcached.prefix'] : 'crow.';

		if( $memcached_enable === true )
		{
			if( class_exists('memcached') === false )
				return crow_log::error( "not found memcached class" );

			self::$m_memcached_prefix = $memcached_prefix;
			self::$m_memcached = new Memcached();
			if( ! self::$m_memcached->addServer($memcached_host, $memcached_port) )
				return crow_log::error( "error of memcached host : ".$memcached_host );
		}
	}

	//--------------------------------------------------------------------------
	//	現在の設定値を取得
	//--------------------------------------------------------------------------
	public static function is_memcached_enable()
	{
		return self::$m_memcached !== false;
	}

	//--------------------------------------------------------------------------
	//	force_file_begin() ～ force_file_end()の間は、
	//	memcachedが有効な場合であっても、強制でファイルアクセスをするように
	//	crow_cacheの挙動を変更する。
	//--------------------------------------------------------------------------
	public static function force_file_begin()
	{
		self::$m_force_file = true;
	}
	public static function force_file_end()
	{
		self::$m_force_file = false;
	}

	//--------------------------------------------------------------------------
	//	指定したキーで、オブジェクトをキャッシュする
	//	第三引数には有効期限をunixtimestampで指定する。0で無期限とする。
	//
	//	※有効期限はmemcachedでのみ有効となる。
	//--------------------------------------------------------------------------
	public static function save( $key_, $object_, $expiration_=0 )
	{
		return self::save_as_text($key_, serialize($object_), $expiration_);
	}

	//--------------------------------------------------------------------------
	//	指定したキーで、テキストをキャッシュする。
	//	第二引数には文字列を指定すること。
	//	第三引数には有効期限をunixtimestampで指定する。0で無期限とする。
	//
	//	※有効期限はmemcachedでのみ有効となる。
	//--------------------------------------------------------------------------
	public static function save_as_text( $key_, $string_, $expiration_=0 )
	{
		if( self::$m_memcached && self::$m_force_file === false )
		{
			if( self::$m_memcached->set(self::$m_memcached_prefix.$key_, $string_, $expiration_) === false )
			{
				return crow_log::error( "failed for memcached set : "
					.self::$m_memcached->getResultMessage() );
			}
		}
		else
		{
			$path = self::get_fname( $key_ );
			file_put_contents( $path, $string_ );
		}
	}

	//--------------------------------------------------------------------------
	//	指定したキーで、オブジェクトをキャッシュからロードする
	//--------------------------------------------------------------------------
	public static function load( $key_ )
	{
		$var = self::load_as_text($key_);
		return ($var === false ? false : unserialize($var));
	}

	//--------------------------------------------------------------------------
	//	指定したキーで、テキストをキャッシュからロードする
	//--------------------------------------------------------------------------
	public static function load_as_text( $key_ )
	{
		if( self::$m_memcached && self::$m_force_file===false )
		{
			$var = self::$m_memcached->get(self::$m_memcached_prefix.$key_);
			return $var;
		}
		$path = self::get_fname($key_);
		if( is_file($path) === false ) return false;
		return file_get_contents($path);
	}

	//--------------------------------------------------------------------------
	//	指定したキーのオブジェクト/テキストを削除する
	//--------------------------------------------------------------------------
	public static function delete( $key_ )
	{
		if( self::$m_memcached && self::$m_force_file === false )
		{
			self::$m_memcached->delete(self::$m_memcached_prefix.$key_);
		}
		else
		{
			$path = self::get_fname($key_);
			if( is_file($path) === true )
 			{
				if( ! crow_storage::disk()->unlink($path) )
					crow_log::warning('failed delete cache file : '.$key_);
			}
		}
	}

	//--------------------------------------------------------------------------
	//	指定したキーの、キャッシュが存在するかチェックする
	//--------------------------------------------------------------------------
	public static function exists( $key_ )
	{
		if( self::$m_memcached && self::$m_force_file === false )
		{
			if( self::$m_memcached->get(self::$m_memcached_prefix.$key_)
				=== false
			){
				if( self::$m_memcached->getResultCode() == Memcached::RES_NOTFOUND )
					return false;
			}
			return true;
		}
		else
		{
			$path = self::get_fname($key_);
			return file_exists($path);
		}
	}

	//--------------------------------------------------------------------------
	//	指定したキーの、キャッシュファイル名を取得する
	//--------------------------------------------------------------------------
	public static function get_fname( $key_ )
	{
		return CROW_PATH."output/caches/".$key_;
	}

	//	private
	private static $m_memcached = false;
	private static $m_memcached_prefix = '';
	private static $m_force_file = false;
}
?>
