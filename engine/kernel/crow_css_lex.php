<?php
/*

	crow css - lexical analyser

*/
class crow_css_lex
{
	//--------------------------------------------------------------------------
	//	パース実行
	//--------------------------------------------------------------------------
	public function parse( $src_, $len_=false )
	{
		if( $len_===false ) $len_ = strlen($src_);

		//	\r\nは\nにする
		$src_ = preg_replace("/\r\n/isu", "\n", $src_);

		//	最後が\nでなければ\nを付与
		if( substr($src_,-1) != "\n" ) $src_ .= "\n";

		//	行の位置を計算
		$this->m_lines = [];
		$pat_line = '/[^\n]*\n/isu';
		if( preg_match_all($pat_line, $src_, $m, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE) )
		{
			foreach( $m[0] as $found ) $this->m_lines[] = [$found[1], strlen($found[0])];
		}

		//	コメント除去と位置の再計算
//		$pat_comment = '/(\/\*.*?\*\/|\A\/\/[^\n]*|([\{|\}|:|;|\'|"|,])\s*?\/\/[^\n]*)/isu';
//		$pat_comment = '/(\/\*.*?\*\/|\A\/\/[^\n]*|([\{|\}|;|\'|"|,])\s*?\/\/[^\n]*)/isu';
		$pat_comment = '/(\/\*.*?\*\/|\A\/\/[^\n]*|\/\/[^\n]*)/isu';
		while(true)
		{
			if( preg_match($pat_comment, $src_, $m, PREG_OFFSET_CAPTURE) )
			{
				$com_pos = $m[0][1];
				$com_len = strlen($m[0][0]);
				$com_lines = mb_split( '\n', $m[0][0] );
				//$com_lfnum = count($com_lines);

				$dec = 0;
				foreach( $this->m_lines as $index => $line )
				{
					//	完結か始まりか
					if( $line[0] <= $com_pos && $line[0]+$line[1] > $com_pos )
					{
						if( $line[0]+$line[1] >= $com_pos + $com_len )
						{
							$dec += $com_len;
							$this->m_lines[$index][1] -= $com_len;
						}
						else {
							$dec += strlen($com_lines[0]) + 1;
							$this->m_lines[$index][1] -= strlen($com_lines[0]) + 1;
							if( $this->m_lines[$index][1] <= 0 ) unset($this->m_lines[$index]);
						}
					}
					//	継続か
					else if( $line[0] > $com_pos && $line[0] + $line[1] < $com_pos + $com_len )
					{
						$dec += $this->m_lines[$index][1];
						unset($this->m_lines[$index]);
					}
					//	終端か
					else if( $line[0] > $com_pos && $line[0] < $com_pos + $com_len && $line[0] + $line[1] >= $com_pos + $com_len )
					{
						$this->m_lines[$index][0] -= $dec;
						$this->m_lines[$index][1] -= strlen(end($com_lines));
						$dec += strlen(end($com_lines));
					}
					//	範囲外か
					else
					{
						$this->m_lines[$index][0] -= $dec;
					}
				}
				if( $src_[$com_pos] != "/" )
				{
					$src_ = preg_replace( $pat_comment, $src_[$com_pos], $src_, 1 );
				}
				else{
					$src_ = preg_replace( $pat_comment, "", $src_, 1 );
				}
			}
			else break;
		}
		foreach( $this->m_lines as $index => $line )
			$this->m_lines[$index][1] = $line[0] + $line[1] - 1;


		//	パターン定義
		$pat_blank = '/\s+/isuA';
		$pat_inline = '/#\\{/isuA';
		$pat_variable = '/\\$([a-zA-Z_]+[a-zA-Z0-9_]*)/isuA';
		$pat_color = '/#[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]|#[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]/isuA';
		$pat_numeric = '/-?(\.[0-9]+|[0-9]*\.[0-9]+|[0-9]+)/isuA';
		$pat_str_s = "/'([^\\\\]|\\\\.)*?'/isuA";
		$pat_str_w = '/"([^\\\\]|\\\\.)*?"/isuA';
		$pat_str_b = '/`([^\\\\]|\\\\.)*?`/isuA';
		$pat_reserve = '/@(if|for[^e]|foreach|else[^ i]|else if|elseif|while|sw|case|def|include|media|keyframes|import)/isuA';
		$pat_mark = '/[;:{}()+-\\/*%\\[\\],]|==|!=|<=|<|>=|>|&&|\|\|/isuA';
		$pat_cssfunc = "/(linear\\-gradient|calc|rgba|matrix|matrix3d|translate|translate3d|translateX|translateY|translateZ|scale|scale3d|scaleX|scaleY|scaleZ|rotate|rotate3d|rotateX|rotateY|rotateZ|skew|skewX|skewY|perspective)\\(.+?\\)/isuA";
		$pat_dbl_colon = '/(::)/isuA';

		//	初期化
		$this->m_tokens = [];
		$this->m_pos = 0;

		//	トークン分割
		$raw_pos = $this->m_pos;
		while( $this->m_pos < $len_ )
		{
			//	ブランクパターン
			if( preg_match($pat_blank, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$this->add( $this->m_pos, crow_css_token::type_blank, $m[0] );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	インライン開始パターン
			else if( preg_match($pat_inline, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$this->add( $this->m_pos, crow_css_token::type_inline_st, $m[0] );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	変数パターン
			else if( preg_match($pat_variable, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$this->add( $this->m_pos, crow_css_token::type_variable, $m[1] );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	色コードパターン
			else if( preg_match($pat_color, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				//	rawデータとして追加する
				$this->add( $this->m_pos, crow_css_token::type_raw, $m[0] );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	数値パターン
			else if( preg_match($pat_numeric, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				//	ドットから始まる数値なら0を付与する
				$result_val = $m[0];
				if( substr($m[0],0,1)=="." ) $result_val = "0".$m[0];
				if( substr($m[0],0,2)=="-." ) $result_val = "-0".substr($m[0],1);

				$this->add( $this->m_pos, crow_css_token::type_num, 0 + $result_val );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	文字列パターン：シングルクォート
			else if( preg_match($pat_str_s, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$data = substr($m[0],1,strlen($m[0])-2);
				//$data = preg_replace("/\\\\'/isu", "'", $data);
				//$data = preg_replace("/\\\\\\\\/isu", "\\\\", $data);
				$this->add( $this->m_pos, crow_css_token::type_str1, $data );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	文字列パターン：ダブルクォート
			else if( preg_match($pat_str_w, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$data = substr($m[0],1,strlen($m[0])-2);
				//$data = preg_replace("/\\\\\"/isu", "\"", $data);
				//$data = preg_replace("/\\\\\\\\/isu", "\\\\", $data);

				$this->add( $this->m_pos, crow_css_token::type_str2, $data );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	文字列パターン：バッククォート
			else if( preg_match($pat_str_b, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$data = substr($m[0],1,strlen($m[0])-2);
				//$data = preg_replace("/\\\\`/isu", "`", $data);
				//$data = preg_replace("/\\\\\\\\/isu", "\\\\", $data);

				$this->add( $this->m_pos, crow_css_token::type_str3, $data );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	CSS関数パターン
			else if( preg_match($pat_cssfunc, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				$func_name = $m[1];
				$brk_pos = $this->m_pos + strlen($func_name) + 1;
				$brk_cnt = 1;
				while($brk_cnt > 0)
				{
					$brk_ch = substr($src_, $brk_pos, 1);
					$brk_len = strlen($brk_ch);
					if( $brk_len <= 0 ) break;
					if( $brk_ch == "(" ) $brk_cnt++;
					else if( $brk_ch == ")" ) $brk_cnt--;
					$brk_pos += $brk_len;
				}
				$this->add( $this->m_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $brk_pos - $raw_pos) );
				$this->m_pos = $brk_pos;
				$raw_pos = $this->m_pos;
			}

			//	予約語パターン
			else if( preg_match($pat_reserve, $src_, $m, 0, $this->m_pos) )
			{
				if( $raw_pos != $this->m_pos )
				{
					$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
					$raw_pos = $this->m_pos;
				}

				if( substr($m[1],0,3)=="for" && strlen($m[1])==4 ) $m[1] = "for";
				if( substr($m[1],0,4)=="else" && strlen($m[1])==5 ) $m[1] = "else";

				$this->add(
					$this->m_pos,
					crow_css_token::type_reserve,
					crow_css_token::codemap_res[$m[1]]
				);
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	コロン連続の検出
			else if( preg_match($pat_dbl_colon, $src_, $m, 0, $this->m_pos) )
			{
				$this->m_pos += strlen($m[0]);
			}
			//	記号
			else if( preg_match($pat_mark, $src_, $m, 0, $this->m_pos) )
			{
				//	正規表現 [\/] で、スラッシュだけでなくドットやカンマもヒットするので、
				//	ドットの場合はrawに回す
				if( $m[0] == "." )
				{
					$this->m_pos += 1;
					continue;
				}

				if( $raw_pos != $this->m_pos )
				{
					//	ハイフンの場合、rawを継続する（ここに来るということは前回がブランクではない）
					if( $m[0] == "-" )
					{
						$this->m_pos += 1;
						continue;
					}
					else {
						$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
						$raw_pos = $this->m_pos;
					}
				}

				if( $m[0]==";" ) $this->add( $this->m_pos, crow_css_token::type_semicolon, $m[0] );
				else if( $m[0]==":" ) $this->add( $this->m_pos, crow_css_token::type_colon, $m[0] );
				else if( $m[0]=="[" ) $this->add( $this->m_pos, crow_css_token::type_lsec_st, $m[0] );
				else if( $m[0]=="]" ) $this->add( $this->m_pos, crow_css_token::type_lsec_ed, $m[0] );
				else if( $m[0]=="{" ) $this->add( $this->m_pos, crow_css_token::type_msec_st, $m[0] );
				else if( $m[0]=="}" ) $this->add( $this->m_pos, crow_css_token::type_msec_ed, $m[0] );
				else if( $m[0]=="(" ) $this->add( $this->m_pos, crow_css_token::type_ssec_st, $m[0] );
				else if( $m[0]==")" ) $this->add( $this->m_pos, crow_css_token::type_ssec_ed, $m[0] );
				else if( $m[0]=="+" ) $this->add( $this->m_pos, crow_css_token::type_add, $m[0] );
				else if( $m[0]=="-" ) $this->add( $this->m_pos, crow_css_token::type_sub, $m[0] );
				else if( $m[0]=="*" ) $this->add( $this->m_pos, crow_css_token::type_mul, $m[0] );
				else if( $m[0]=="/" ) $this->add( $this->m_pos, crow_css_token::type_div, $m[0] );
				else if( $m[0]=="%" ) $this->add( $this->m_pos, crow_css_token::type_mod, $m[0] );
				else if( $m[0]=="==" ) $this->add( $this->m_pos, crow_css_token::type_eqeq, $m[0] );
				else if( $m[0]=="!=" ) $this->add( $this->m_pos, crow_css_token::type_neq, $m[0] );
				else if( $m[0]=="<" ) $this->add( $this->m_pos, crow_css_token::type_sm, $m[0] );
				else if( $m[0]=="<=" ) $this->add( $this->m_pos, crow_css_token::type_smeq, $m[0] );
				else if( $m[0]==">" ) $this->add( $this->m_pos, crow_css_token::type_lg, $m[0] );
				else if( $m[0]==">=" ) $this->add( $this->m_pos, crow_css_token::type_lgeq, $m[0] );
				else if( $m[0]=="&&" ) $this->add( $this->m_pos, crow_css_token::type_and, $m[0] );
				else if( $m[0]=="||" ) $this->add( $this->m_pos, crow_css_token::type_or, $m[0] );
				else if( $m[0]=="," ) $this->add( $this->m_pos, crow_css_token::type_comma, $m[0] );
				$this->m_pos += strlen($m[0]);
				$raw_pos = $this->m_pos;
			}

			//	出力パターン
			else
			{
				if( preg_match('/./isuA', $src_, $m, 0, $this->m_pos) )
				{
					$this->m_pos += strlen($m[0]);
				}
				else
				{
					break;
				}
			}
		}

		//	最後のトークン
		if( $raw_pos != $this->m_pos )
		{
			$this->add( $raw_pos, crow_css_token::type_raw, substr($src_, $raw_pos, $this->m_pos - $raw_pos) );
			$raw_pos = $this->m_pos;
		}

		//	rawに続けてスペース無しにハイフンなら、rawにつなげる
		$pre = -1;
		foreach( $this->m_tokens as $index => $token )
		{
			if(
				$pre == -1 &&
				$token->m_type == crow_css_token::type_raw
			){
				$pre = $index;
				continue;
			}
			else if
			(
				$pre != -1 &&
				$token->m_type == crow_css_token::type_sub
			){
				$this->m_tokens[$pre]->m_data .= $token->m_data;
				unset($this->m_tokens[$index]);
			}
			else
			{
				$pre = -1;
			}
		}

		//	数字と単位をまとめる
		$pre = -1;
		$units = ['px','pt','em','ex','%','mm','cm','in','pc','s','ms','vw','vh','vmin','vmax','svw','svh','svmin','svmax','lvw','lvh','lvmin','lvmax','dvw','dvh','dvmin','dvmax'];
		foreach( $this->m_tokens as $index => $token )
		{
			if( $token->m_type == crow_css_token::type_num )
			{
				$pre = $index;
				continue;
			}
			else if
			(
				$pre != -1 &&
				$token->m_type == crow_css_token::type_raw &&
				in_array($token->m_data, $units)
			){
				$this->m_tokens[$pre]->m_data_sub = $token->m_data;
				unset($this->m_tokens[$index]);
				$pre = -1;
			}
			else if
			(
				$pre != -1 &&
				$token->m_type == crow_css_token::type_mod
			){
				$this->m_tokens[$pre]->m_data_sub = '%';
				unset($this->m_tokens[$index]);
				$pre = -1;
			}
			else
			{
				$pre = -1;
			}
		}

		//	トークン整理
		$this->m_tokens = array_merge($this->m_tokens);
	}

	//	トークン追加
	public function add( $pos_, $type_, $data_ )
	{
		$new_token = new crow_css_token($this->calc_line($pos_), $type_, $data_);
		$this->m_tokens[] = $new_token;
		return $new_token;
	}

	//	ライン番号とカラム番号の計算
	private function calc_line($pos_)
	{
		foreach( $this->m_lines as $no => $line )
		{
			if( $pos_ >= $line[0] && $pos_ <= $line[1] )
				return $no+1;
		}
		return 0;
	}

	//	private
	private $m_pos = 0;
	private $m_lines = [];
	//private $m_comments = [];
	public  $m_tokens = [];
}
?>
