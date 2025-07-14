<?php
/*

	crow js

*/
class crow_js
{
	//--------------------------------------------------------------------------
	//	圧縮実行
	//--------------------------------------------------------------------------
	public static function compress( $src_, $paths_ )
	{
		$dst = $src_;

		while(true)
		{
			$pos = strpos($dst, "@require");
			if( $pos === false ) break;

			//	終端を計算
			$endpos = strpos($dst, ";", $pos);
			if( $endpos === false )
			{
				crow_log::notice("syntax error of `require` not found semicolon");
				break;
			}
			$endpos++;

			//	ファイル名抽出
			$m = [];
			if( preg_match("/@require\\s*\\(['\"](.*)['\"]\\)\\s*;/isu", substr($dst, $pos, $endpos - $pos), $m) !== 1 )
			{
				crow_log::notice("syntax error of 'require', not found filename");
				break;
			}
			$fname = $m[1];

			//	ファイルを探す
			$full_path = false;
			foreach( $paths_ as $path )
			{
				if( is_file($path.$fname) === true )
				{
					$full_path = $path.$fname;
					break;
				}
			}
			if( $full_path === false )
			{
				$dst = substr($dst, 0, $pos).$require_src.substr($dst, $endpos);
				crow_log::notice("syntax error of `require` not found file, ".$fname);
				break;
			}

			//	見つけたので読み込んでソース文字列内に結合する
			$require_src = file_get_contents($full_path);
			$dst = substr($dst, 0, $pos).$require_src.substr($dst, $endpos);
		}

		//	\r\nは\nにする
		$dst = preg_replace("/\r\n/isu", "\n", $dst);

		//	コメント除去
		$chars = preg_split("//u", $dst, -1, PREG_SPLIT_NO_EMPTY);
		$len = count($chars);
		$dst = '';
		$in_str = 0; //	0:none, 1:single quat, 2:double quat
		$in_com = 0; //	0:none, 1:single, 2:multi
		for( $i = 0; $i < $len; $i++ )
		{
			$ch = $chars[$i];

			//	コメント中/文字列中でない場合
			if( $in_com === 0 && $in_str === 0 )
			{
				//	コメント開始チェック
				if( $ch === "/" )
				{
					if( $chars[$i+1] === "/" )
					{
						$in_com = 1;
						$i++;
						continue;
					}
					else if( $chars[$i+1] === "*" )
					{
						$in_com = 2;
						$i++;
						continue;
					}
				}
				//	文字列開始チェック
				else if( $ch === "'" )
				{
					$in_str = 1;
				}
				else if( $ch === "\"" )
				{
					$in_str = 2;
				}
			}
			//	コメント中の場合
			else if( $in_com !== 0 )
			{
				if( $in_com === 1 && $ch === "\n" )
				{
					$in_com = 0;
					continue;
				}
				else if( $in_com === 2 && $ch === "*" && $chars[$i+1] === "/" )
				{
					$in_com = 0;
					$i++;
					continue;
				}
			}
			//	文字列中の場合
			else if( $in_str !== 0 )
			{
				if( $ch==="\\" )
				{
					$dst .= $ch;
					$dst .= $chars[$i+1];
					$i++;
					continue;
				}
				else if(
					($in_str === 1 && $ch === "'") ||
					($in_str === 2 && $ch === "\"")
				){
					$in_str = 0;
				}
			}

			if( $in_com===0 )
			{
				//	文字列中でないなら改行とタブは除去
				if( $ch === "\n" && $in_str === 0 ) {}
				else if( $ch === "\t" && $in_str === 0 ) {$dst .= ' ';}
				else $dst .= $ch;
			}
		}

		//	最後セミコロン
		return substr($dst,0,-1) !== ";" ?
			$dst.";" : $dst;
	}
}
?>
