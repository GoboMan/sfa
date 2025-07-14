<?php
/*

	マルチバイトライブラリに足りない関数を追加する

*/

if( ! function_exists('mb_split_multiple') )
{
	/*
		マルチバイト対応 複数区切り文字を使って、文字列を分割

		例）カンマとドットで分割したい場合
		$array = mb_split_multiple('(,|.)', "abc,def.fgh");
	*/
	function mb_split_multiple( $delimiters_, $str_ )
	{
		$bom = html_entity_decode("&#feff", ENT_NOQUOTES, "UTF-8");
		$str = mb_ereg_replace($delimiters_, $bom, $str_);
		return explode($bom, $str);
	}
}

if( ! function_exists('mb_str_replace') )
{
	/*
		マルチバイト対応 str_replace()

		$search		検索文字列（またはその配列）
		$replace	置換文字列（またはその配列）
		$subject	対象文字列（またはその配列）
		$encoding	文字列のエンコーディング(省略: 内部エンコーディング)
	*/
	function mb_str_replace( $search, $replace, $subject, $encoding = 'auto' )
	{
		if( ! is_array($search) ){
			$search = [$search];
		}
		if( ! is_array($replace) ){
			$replace = [$replace];
		}
		if( strtolower($encoding) === 'auto' ) {
			$encoding = mb_internal_encoding();
		}

		//	$subject が複数ならば各要素に繰り返し適用する
		if( is_array($subject) || $subject instanceof Traversable )
		{
			$result = [];
			foreach( $subject as $key => $val ) {
				$result[$key] = mb_str_replace( $search, $replace, $val, $encoding );
			}
			return( $result );
		}

		$currentpos = 0; // 現在の検索開始位置
		while( true )
		{
			//	$currentpos 以降で $search のいずれかが現れる位置を検索する
			$index = -1;	//	見つけた文字列（最も前にあるもの）の $search の index
			$minpos = -1;	//	見つけた文字列（最も前にあるもの）の位置
			foreach( $search as $key => $find )
			{
				if( $find == '' ) continue;
				$findpos = mb_strpos( $subject ? $subject : "", $find ? $find : "", $currentpos, $encoding );
				if( $findpos !== false )
				{
					if( $minpos < 0 || $findpos < $minpos ) {
						$minpos = $findpos;
						$index = $key;
					}
				}
			}

			//	$search のいずれも見つからなければ終了
			if( $minpos < 0 ) break;

			// 置換実行
			$r = array_key_exists($index, $replace) ? $replace[$index] : '';
			$subject =
				mb_substr($subject, 0, $minpos, $encoding)	//	置換開始位置より前
				.$r											//	置換後文字列
				.mb_substr(									//	置換終了位置より後ろ
					$subject,
					$minpos + mb_strlen($search[$index], $encoding),
					mb_strlen($subject, $encoding),
					$encoding
				);

			//	「現在位置」を $r の直後に設定
			$currentpos = $minpos + mb_strlen($r, $encoding);
		}
		return( $subject );
	}
}

?>
