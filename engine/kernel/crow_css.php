<?php
/*

	crow css

*/
class crow_css
{
	//--------------------------------------------------------------------------
	//	ファイル指定で圧縮実行
	//--------------------------------------------------------------------------
	public static function compress( $path_, $require_paths_ )
	{
		$ext = crow_storage::extract_ext($path_);
		if( $ext == "css" )
		{
			//	圧縮のみ施して返却
			return self::compress_css(file_get_contents($path_));
		}
		if( $ext != "icss" ) return "";

		//	icss 圧縮
		$src = file_get_contents($path_);
		return self::parse_icss( $src, $path_, $require_paths_ );
	}

	//--------------------------------------------------------------------------
	//	メモリから圧縮実行
	//--------------------------------------------------------------------------
	public static function compress_memory( $data_, $require_paths_ )
	{
		return self::parse_icss( $data_, "", $require_paths_ );
	}

	//--------------------------------------------------------------------------
	//	通常のCSSを圧縮
	//--------------------------------------------------------------------------
	public static function compress_css( $css_ )
	{
		//	まずはコメント除去
		$pattern = '/\/\*.*\*\//';
		$src = preg_replace($pattern, "", $css_);

		//	改行とコロンの前後空白を除去
		$lines = explode("\n", $src);
		$ret = "";
		foreach( $lines as $line )
		{
			$ret .= preg_replace('/\s*:\s*/', ":", trim($line)).' ';
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	iCSSのパース＆圧縮
	//--------------------------------------------------------------------------
	private static function parse_icss( $src_, $path_, $require_paths_ )
	{
		$parser = new crow_css_parser();
		$dst = $parser->parse($src_, $path_, $require_paths_);
		$errors = $parser->get_errors();
		if( count($errors) > 0 )
		{
			$err = [];
			foreach( $errors as $error )
			{
				$err[] = $error->m_fname." #".$error->m_line." - ".$error->m_msg;
			}
			crow_log::error( 'icss parser error : '.implode(", ",$err) );
			return "";
		}

		return $dst;
	}

}
?>
