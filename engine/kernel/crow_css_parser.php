<?php
/*

	crow css - parser

*/
class crow_css_parser
{
	//--------------------------------------------------------------------------
	//	実行
	//--------------------------------------------------------------------------
	public function parse( $src_, $fname_ = '', $paths_ = [] )
	{
		//	字句解析はexe指定があればそれを実行する
		$lexer = trim(crow_config::get('icss.lexer', ''));
		if( $lexer != "" )
		{
			$this->lex_with_bin($lexer, $src_, $fname_, $paths_);
		}
		else
		{
			//	requireを辿り、ソースを構築する
			$src = $this->build_src($src_, $paths_);

			//	字句解析
			$this->m_lex = new crow_css_lex();
			$this->m_lex->parse($src);
		}

		//	パース初期化
		$this->m_fname = $fname_;
		$this->m_paths = $paths_;
		$this->m_root_block = false;
		$this->m_block = false;
		$this->m_index = -1;

		//	ルートブロックパース開始
		return $this->parse_block();
	}

	//--------------------------------------------------------------------------
	//	binを使って字句解析
	//--------------------------------------------------------------------------
	private function lex_with_bin( $lexer_, $src_, $fname_ = '', $paths_ = [] )
	{
		$separator = "/";
		if( strtolower(substr($lexer_, -4)) == ".exe" ) $separator = "\\";
		$lexer = str_replace("[CROW_PATH]", CROW_PATH, $lexer_);

		//	fnameの指定がなければメモリからなので、一旦ソースをファイルに吐き出す
		$src_fname = $fname_;
		if( $src_fname == "" )
		{
			$src_fname = CROW_PATH."output".$separator."temp".$separator.md5(uniqid(rand(),1));
			file_put_contents($src_fname, $src_);
		}

		//	ファイルパスはwindowsならエスケープする
		$crow_path = CROW_PATH;
		$tmp_fname = CROW_PATH."output".$separator."temp".$separator.md5(uniqid(rand(),1));
		$crow_path_esc = $crow_path;
		$tmp_fname_esc = $tmp_fname;
		$src_fname_esc = $src_fname;
		$paths = [];
		if( $separator == "\\" )
		{
			$crow_path_esc = str_replace("\\", "\\\\", str_replace("/", "\\", $crow_path_esc));
			$tmp_fname_esc = str_replace("\\", "\\\\", str_replace("/", "\\", $tmp_fname_esc));
			$src_fname_esc = str_replace("\\", "\\\\", str_replace("/", "\\", $src_fname_esc));

			foreach( $paths_ as $path)
				$paths[] = str_replace("\\", "\\\\", str_replace("/", "\\", $path));
		}
		else
		{
			$paths = $paths_;
		}

		//	実行
		exec(
			$lexer." \"".$crow_path_esc."\" \"".$src_fname_esc."\" \"".implode(",", $paths)."\" \"".$tmp_fname_esc."\""
		);

		//	結果読み込み
		$dst = file_get_contents($tmp_fname);
		$lines = explode("\n", $dst);
		$this->m_lex = new crow_css_lex();
		$cnt = count($lines);
		for( $i = 0; $i + 2 < $cnt; $i+=3 )
		{
			$new_token = $this->m_lex->add
			(
				0,
				$lines[$i + 0],
				$lines[$i + 1]
			);
			$new_token->m_data_sub = $lines[$i + 2];
		}

		//	ゴミファイル削除
		if( $fname_ == "" ) unlink($src_fname);
		unlink($tmp_fname);
	}

	//--------------------------------------------------------------------------
	//	エラー取得
	//--------------------------------------------------------------------------
	public function get_errors()
	{
		return $this->m_errors;
	}

	//--------------------------------------------------------------------------
	//	ソースを収集して結合
	//--------------------------------------------------------------------------
	private function build_src( $src_, $paths_ )
	{
		$src = $src_;
		while(true)
		{
			$pos = strpos($src, "@require");
			if( $pos === false ) break;

			//	終端を計算
			$endpos = strpos($src, ";", $pos);
			if( $endpos === false )
			{
				$this->add_error("syntax error of `require` not found semicolon, ".$fname);
				break;
			}
			$endpos++;

			//	ファイル名抽出
			$m = [];
			if( preg_match("/@require\\s*\\(['\"](.*)['\"]\\)\\s*;/isu", substr($src, $pos, $endpos - $pos), $m) !== 1 )
			{
				$this->add_error("syntax error of 'require', not found filename");
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
				$this->add_error("syntax error of `require` not found file, ".$fname);
				break;
			}

			//	見つけたので読み込んでソース文字列内に結合する
			$require_src = file_get_contents($full_path);
			$src = substr($src, 0, $pos).$require_src.substr($src, $endpos);
		}
		return $src;
	}

	//--------------------------------------------------------------------------
	//	パース：ブロック
	//--------------------------------------------------------------------------
	private function parse_block()
	{
		//	push block
		$this->push_block();

		//	制御ブロック
		$control_stack = [];

		//	特殊クエリ
		$is_media = false;
		$is_keyframes = false;

		$keyframes_begin_pos = 0;

		$result = '';
		while(true)
		{
			//	次トークン（ファイル終端チェック）
			if( ! $this->token_next() ) break;

			//	ブロック終端チェック
			if( $this->m_token->m_type == crow_css_token::type_msec_ed )
			{
				//	制御ブロックのネストが残っているなら、制御を終了させる。
				if( count($control_stack) > 0 )
				{
					//	@ifブロックの中ならば、後続のelseif/elseをスキップする
					$cur_control = &$control_stack[count($control_stack)-1];
					if( $cur_control['type'] == 'if' )
					{
						while(true)
						{
							if( ! $this->token_next_exp() ) break;

							if( $this->m_token->m_type == crow_css_token::type_reserve &&
								(
									$this->m_token->m_data == crow_css_token::res_elseif ||
									$this->m_token->m_data == crow_css_token::res_else
								)
							){
								$this->skip_token_to( crow_css_token::type_msec_st );
								$this->skip_block();
							}
							else
							{
								$this->token_back();
								break;
							}
						}

						//	制御スタック pop
						$this->pop_control( $control_stack );
					}

					//	@forブロックならリミットチェック
					else if( $cur_control['type'] == 'for' )
					{
						$prop = $cur_control['data'];

						//	カウンタを参照取得
						$counter = &$this->m_block->getvar( $prop['var'] );

						//	インクリメント演算
						$counter->do_attach
						(
							$counter->do_expression
							(
								"+",
								new crow_css_stack(
									crow_css_stack::type_num,
									$prop['inc_val']
								)
							)
						);

						$break = false;

						//	to（未満）limitは拒否
						if( $prop['type']=='to' )
						{
							if(
								($prop['inc_vec']=='inc' && $counter->m_data >= $prop['limit']) ||
								($prop['inc_vec']=='dec' && $counter->m_data <= $prop['limit'])
							){
								$break = true;
							}
						}

						//	through（以下）limitまでは許容
						else
						{
							if(
								($prop['inc_vec']=='inc' && $counter->m_data > $prop['limit']) ||
								($prop['inc_vec']=='dec' && $counter->m_data < $prop['limit'])
							){
								$break = true;
							}
						}

						//	ループ終了なら
						if( $break )
						{
							//	制御スタック pop
							$this->pop_control( $control_stack );
						}

						//	ループ継続なら
						else
						{
							//	ループの先頭へジャンプ
							$this->jump_token_to($prop['begin']);
						}
					}

					//	@foreachブロックならイテレーションチェック
					else if( $cur_control['type']=='foreach' )
					{
						$prop = $cur_control['data'];

						$target_keys = array_keys($prop['target']->m_data);
						$new_index = $prop['index'] + 1;

						//	foreach終了
						if( isset($target_keys[$new_index]) === false )
						{
							$this->pop_control( $control_stack );
						}

						//	foreach継続
						else
						{
							$new_key = $target_keys[$new_index];

							//	新しいkeyとvalueをセット
							if( $prop['key_name'] != '' )
							{
								$key_stack = new crow_css_stack(crow_css_stack::type_raw, $new_key);
								$ite_key = &$this->m_block->getvar($prop['key_name']);
								$ite_key->do_attach($key_stack);
							}
							$ite_val = &$this->m_block->getvar($prop['val_name']);
							$ite_val->do_attach( $prop['target']->m_data[$new_key] );

							//	index更新
							$cur_control['data']['index'] = $new_index;

							//	ブロック開始位置までジャンプ
							$this->jump_token_to($prop['begin']);
						}
					}
				}

				//	特殊クエリの途中なら
				else if( $is_media || $is_keyframes )
				{
					$result .= '}';

					//	keyframes の場合は、-webkit版を複製する
					if( $is_keyframes )
					{
						$orgcode = substr($result, $keyframes_begin_pos);
						$result .= "@-webkit-keyframes ".$orgcode;
					}

					$is_media = false;
					$is_keyframes = false;
				}

				//	そうでないなら、CSSブロックの終了
				else{
					break;
				}
			}

			//	ブロックネストチェック
			else if( $this->m_token->m_type == crow_css_token::type_msec_st )
			{
				$result .= $this->parse_block();

				//	現ブロックのセレクタをクリア
				$this->m_block->clear_selector();
			}

			//	ブランク
			else if( $this->m_token->m_type == crow_css_token::type_blank )
			{
				$this->m_block->append(' ');
			}

			//	インライン開始
			else if( $this->m_token->m_type == crow_css_token::type_inline_st )
			{
				//	計算用スタックを用意して因子パース "}" 終端
				$stack = [];
				$this->parse_exp( $stack, crow_css_token::type_msec_ed );
				if( count($stack) <= 0 ) break;

				//	"}" はスキップ
				if( ! $this->token_next_exp() ) return true;

				//	結果を出力
				$this->m_block->append( end($stack)->build() );
			}

			//	変数
			else if( $this->m_token->m_type == crow_css_token::type_variable )
			{
				//	一旦戻して
				$this->token_back();

				//	計算用スタックを用意して因子パース
				$left_stack = [];
				$this->parse_exp( $left_stack, crow_css_token::type_colon, false );
				if( count($left_stack) <= 0 ) break;

				//	この時点で一番上のスタックは参照であることが前提
				if( end($left_stack)->m_type != crow_css_stack::type_ref ) break;
				$target_addr = count($left_stack) - 1;

				//	コロンはスキップ
				$this->token_next_exp();

				//	右辺計算
				$right_stack = [];
				$this->parse_exp( $right_stack, crow_css_token::type_semicolon );

				//	右辺一番上のスタックを左辺に代入
				$left_stack[$target_addr]->do_attach( end($right_stack) );
			}

			//	数値
			else if( $this->m_token->m_type == crow_css_token::type_num )
			{
				$this->m_block->append( $this->m_token->m_data.$this->m_token->m_data_sub );
			}

			//	文字列
			else if( $this->m_token->m_type == crow_css_token::type_str1 )
			{
				$str = $this->m_token->m_data;
//				$str = mb_str_replace("\\","\\\\",$str);
//				$str = mb_str_replace("'","\\'",$str);
			}
			else if( $this->m_token->m_type == crow_css_token::type_str2 )
			{
				$str = $this->m_token->m_data;
//				$str = mb_str_replace("\\","\\\\",$str);
//				$str = mb_str_replace("'","\\'",$str);
				$this->m_block->append( '"'.$str.'"' );
			}
			else if( $this->m_token->m_type == crow_css_token::type_str3 )
			{
				$str = $this->m_token->m_data;
				$this->m_block->append( $str );
			}

			//	コロン
			else if( $this->m_token->m_type == crow_css_token::type_colon )
			{
				$this->m_block->append( $this->m_token->m_data );

				//	右辺は演算式（セミコロン終端）
				$stack = [];
				$this->parse_exp( $stack, [crow_css_token::type_semicolon, crow_css_token::type_colon, crow_css_token::type_msec_st] );

				//	結果を出力
				$this->m_block->append( end($stack)->build() );
			}

			//	@media
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_media
			){
				//	"{" まで何も考えず取得
				$query = '';
				while(true)
				{
					if( ! $this->token_next() )
					{
						$this->add_error("syntax error of media query");
						break;
					}
					if( $this->m_token->m_type == crow_css_token::type_msec_st ) break;

					//	コロンの後は演算式
					if( $this->m_token->m_type == crow_css_token::type_colon )
					{
						$query .= ":";

						//	演算式 ";" or ")" 終端
						$stack = [];
						$this->parse_exp( $stack, [crow_css_token::type_semicolon, crow_css_token::type_ssec_ed, crow_css_token::type_msec_st] );

						//	結果を結合
						$query .= end($stack)->build();
					}
					else
					{
						$query .= $this->m_token->m_data.$this->m_token->m_data_sub;
					}
				}
				$result .= "@media ".trim($query)."{";
				$is_media = true;
			}

			//	@keyframes
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_keyframes
			){
				//	"{" まで何も考えず取得
				$query = '';
				while(true)
				{
					if( ! $this->token_next() )
					{
						$this->add_error("syntax error of keyframes query");
						break;
					}
					if( $this->m_token->m_type == crow_css_token::type_msec_st ) break;
					$query .= $this->m_token->m_data.$this->m_token->m_data_sub;
				}
				$result .= "@keyframes ";
				$keyframes_begin_pos = strlen($result);
				$result .= trim($query)."{";
				$is_keyframes = true;
			}

			//	@include
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_include
			){
				//	展開する式
				$stack_target = [];
				$this->parse_exp( $stack_target, crow_css_token::type_semicolon );
				if( count($stack_target) <= 0 )
				{
					$this->add_error("syntax error of 'include'");
					break;
				}

				//	セミコロンをスキップ
				if( ! $this->token_next_exp() )
				{
					$this->add_error("syntax error of 'include', not found semicolon");
					break;
				}
				if( $this->m_token->m_type != crow_css_token::type_semicolon )
				{
					$this->add_error("syntax error of 'include', not found semicolon");
					break;
				}

				//	内容に直接出力する
				$this->m_block->append_body(
					end($stack_target)->build_array() );
			}
/*
			//	@require
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_require
			){
				//	取り込むファイル
				$stack_target = [];
				$this->parse_exp( $stack_target, crow_css_token::type_semicolon );
				if( count($stack_target) <= 0 )
				{
					$this->add_error("syntax error of 'require'");
					break;
				}

				//	セミコロンをスキップ
				if( ! $this->token_next_exp() )
				{
					$this->add_error("syntax error of 'require', not found semicolon");
					break;
				}
				if( $this->m_token->m_type != crow_css_token::type_semicolon )
				{
					$this->add_error("syntax error of 'require', not found semicolon");
					break;
				}

				//	ファイル取り込み
				$require_fname = end($stack_target)->abstract_data()->m_data;
				foreach( $this->m_paths as $path )
				{
					if( is_file($path.$require_fname) === false ) continue;

					$path.$require_fname

					break;
				}
				crow_log::notice("file=[".end($stack_target)->abstract_data()->m_data."]");
			}
*/
			//	@if
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_if
			){
				//	条件式部（"{"終端）
				$backup_index = $this->m_index;
				$stack_cond = [];
				$this->parse_exp( $stack_cond, crow_css_token::type_msec_st );

				$start_index = $this->m_index;

				//	評価結果がtrueならば中に入る
				$cond = crow_css_stack::materialize($stack_cond[count($stack_cond)-1]);
				$this->token_next();
				if( $cond->m_type == crow_css_stack::type_bool &&
					$cond->m_data === true
				){
					$this->push_control( $control_stack, "if", $start_index );
				}

				//	評価結果がfalseならばブロックスキップ
				else
				{
					$this->jump_token_to($backup_index);
					$this->skip_block();

					//	@else if / @elseが継続するなら再評価
					while(true)
					{
						if( ! $this->token_next_exp() ) break;

						if( $this->m_token->m_type == crow_css_token::type_reserve &&
							(
								$this->m_token->m_data == crow_css_token::res_elseif ||
								$this->m_token->m_data == crow_css_token::res_else
							)
						){
							$backup_index = $this->m_index;
							$logicin = false;
							$start_index = $this->m_index;

							//	@else if なら条件式部（"{"終端）
							if( $this->m_token->m_data == crow_css_token::res_elseif )
							{
								$stack_cond = [];
								$this->parse_exp( $stack_cond, crow_css_token::type_msec_st );
								$start_index = $this->m_index;

								//	評価結果がtrueならば中に入る
								$cond = crow_css_stack::materialize($stack_cond[count($stack_cond)-1]);
								$this->token_next();
								if( $cond->m_type == crow_css_stack::type_bool &&
									$cond->m_data === true
								){
									$logicin = true;
								}
							}
							//	@else なら "{" の次までスキップ
							else {
								$this->skip_token_to( crow_css_token::type_msec_st );
								$start_index = $this->m_index;
								$this->token_next();
								$logicin = true;
							}

							//	中に入るなら制御スタックに積む
							if( $logicin == true )
							{
								$this->push_control( $control_stack, "if", $start_index );
								break;
							}

							//	中に入らないならブロックスキップ
							else
							{
								$this->jump_token_to($backup_index);
								$this->skip_block();
							}
						}
						else
						{
							$this->token_back();
							break;
						}
					}
				}
			}

			//	@for
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_for
			){
				//	変数
				if( ! $this->token_next_exp() )
				{
					$this->add_error("syntax error of 'for'");
					break;
				}
				if( $this->m_token->m_type != crow_css_token::type_variable )
				{
					$this->add_error("syntax error of 'for'");
					break;
				}

				//	カウンタ作成
				$varname = $this->m_token->m_data;
				$counter = &$this->m_block->getvar( $varname );

				//	from
				$this->token_next_exp();
				if( $this->m_token->m_type != crow_css_token::type_raw ||
					$this->m_token->m_data != "from"
				){
					$this->add_error("not found 'from' keyword for 'for'");
					break;
				}

				//	初期式
				$stack = [];
				$this->parse_exp( $stack, crow_css_token::type_raw );
				if( count($stack)<=0 || end($stack)->m_type != crow_css_stack::type_num )
				{
					$this->add_error("invalid initial expression for 'for'");
					break;
				}
				$counter->do_attach( end($stack) );

				//	to / through
				$this->token_next_exp();
				if( $this->m_token->m_type != crow_css_token::type_raw ||
					(
						$this->m_token->m_data != "to" &&
						$this->m_token->m_data != "through"
					)
				){
					$this->add_error("not found 'to'/'through' keyword for 'for'");
					break;
				}
				$limit_type = $this->m_token->m_data;

				//	目的式
				$stack = [];
				$this->parse_exp( $stack, crow_css_token::type_msec_st );
				if( count($stack)<=0 || end($stack)->m_type != crow_css_stack::type_num )
				{
					$this->add_error("invalid initial expression for 'for'");
					break;
				}
				$limit = end($stack)->m_data;

				//	inc / dec のデフォルト
				$inc_vec = 'inc';
				$inc_val = 1;
				if( $counter->m_data > $limit )
				{
					$inc_vec = 'dec';
					$inc_val = -1;
				}

				//	inc / dec の指定があるなら
				$this->token_next_exp();
				if( $this->m_token->m_type == crow_css_token::type_raw &&
					(
						$this->m_token->m_data == "inc" ||
						$this->m_token->m_data == "dec"
					)
				){
					$inc_vec = $this->m_token->m_data;

					//	inc値 / dec値
					$this->token_next_exp();
					if( $this->m_token->m_type != crow_css_token::type_num )
					{
						$this->add_error("invalid inc/dec value, must be numeric (with unit) value");
						break;
					}
					$inc_val = end($stack)->m_data;
					if( $inc_vec == 'dec' ) $inc_val *= -1;
				}
				else {
					$this->token_back();
				}

				//	{
				if( ! $this->token_next_exp() )
				{
					$this->add_error("'for' block must start with '{'");
					break;
				}
				if( $this->m_token->m_type != crow_css_token::type_msec_st )
				{
					$this->add_error("'for' block must start with '{'");
					break;
				}
				$start_index = $this->m_index;

				//	{ の次をループ先頭とする
				if( ! $this->token_next() )
				{
					$this->add_error("syntax error of 'for'");
					break;
				}
				$loop_begin = $this->m_index;
				$this->push_control
				(
					$control_stack, "for", $start_index,
					[
						'begin'		=> $loop_begin,
						'inc_vec'	=> $inc_vec,
						'inc_val'	=> $inc_val,
						'var'		=> $varname,
						'type'		=> $limit_type,
						'limit'		=> $limit,
					]
				);
			}

			//	@foreach
			else if(
				$this->m_token->m_type == crow_css_token::type_reserve &&
				$this->m_token->m_data == crow_css_token::res_foreach
			){
				//	ターゲット
				$target_stack = [];
				if( ! $this->parse_exp($target_stack, crow_css_token::type_raw) )
				{
					$this->add_error("specify an array for 'foreach'");
					break;
				}
				$target_stack = crow_css_stack::materialize(end($target_stack));

				//	as
				if( ! $this->token_next_exp() )
				{
					$this->add_error("syntax error of 'foreach'");
					break;
				}
				if( $this->m_token->m_type != crow_css_token::type_raw ||
					$this->m_token->m_data != 'as'
				){
					$this->add_error("syntax error of 'foreach'");
					break;
				}

				//	value( or key)
				if( ! $this->token_next_exp() )
				{
					$this->add_error("syntax error of 'foreach'");
					break;
				}
				if( $this->m_token->m_type != crow_css_token::type_variable )
				{
					$this->add_error("syntax error of 'foreach'");
					break;
				}

				//	格納用変数作成
				$key_name = '';
				$val_name = $this->m_token->m_data;

				//	カンマがあるとキー指定となる
				if( ! $this->token_next_exp() )
				{
					$this->add_error("syntax error of 'foreach'");
					break;
				}
				if( $this->m_token->m_type == crow_css_token::type_comma )
				{
					//	カンマがあったので、キーと値の指定となる
					if( ! $this->token_next_exp() )
					{
						$this->add_error("syntax error of 'foreach'");
						break;
					}
					if( $this->m_token->m_type != crow_css_token::type_variable )
					{
						$this->add_error("syntax error of 'foreach'");
						break;
					}
					$key_name = $val_name;
					$val_name = $this->m_token->m_data;
				}
				else
				{
					$this->token_back();
				}

				//	ターゲットの配列が要素ゼロならブロックをスキップする
				if(
					$target_stack->m_type != crow_css_stack::type_arr ||
					count($target_stack->m_data) <= 0
				){
					$this->skip_block();
				}
				//	それ以外なら foreach 開始
				else
				{
					//	{
					if( ! $this->token_next_exp() )
					{
						$this->add_error("'foreach' block must start with '{'");
						break;
					}
					if( $this->m_token->m_type != crow_css_token::type_msec_st )
					{
						$this->add_error("'foreach' block must start with '{'");
						break;
					}
					$start_index = $this->m_index;

					//	キーと値用の変数を作成する
					$ite_key = null;
					$ite_val = null;
					if( $key_name != '' ) $ite_key = &$this->m_block->getvar($key_name);
					$ite_val = &$this->m_block->getvar($val_name);

					//	最初のキーと値を格納する
					$target_keys = array_keys($target_stack->m_data);
					$first_key = $target_keys[0];
					if( $key_name != '' )
					{
						$key_stack = new crow_css_stack(crow_css_stack::type_raw, $first_key);
						$ite_key->do_attach($key_stack);
					}
					$ite_val->do_attach( $target_stack->m_data[$first_key] );

					//	{ の次をループ先頭とする
					if( ! $this->token_next() )
					{
						$this->add_error("syntax error of 'foreach'");
						break;
					}
					$loop_begin = $this->m_index;
					$this->push_control
					(
						$control_stack, "foreach", $start_index,
						[
							'begin'		=> $loop_begin,
							'target'	=> $target_stack,
							'index'		=> 0,
							'key_name'	=> $key_name,
							'val_name'	=> $val_name,
						]
					);
				}
			}

			//	その他コード
			else
			{
				$this->m_block->append( $this->m_token->m_data );
			}
		}

		$result = $this->m_block->build().$result;

		//	pop block
		$this->pop_block();

		return $result;
	}

	//--------------------------------------------------------------------------
	//	パース：連想配列式
	//--------------------------------------------------------------------------
	private function parse_array( &$stack_, $term_type_ )
	{
		//	ここに来た段階で最初の "{" は読み込み済み

		//	{key1:exp1; key2:exp2}
		//	{key1:exp1; key2:exp2;}

		$addr = count($stack_);
		$stack_[$addr] = new crow_css_stack( crow_css_stack::type_arr );

		while(true)
		{
			//	key (or value)
			$key_stack = [];
			$this->parse_exp( $key_stack,
			[
				crow_css_token::type_msec_ed,
				crow_css_token::type_semicolon,
				crow_css_token::type_colon,
				crow_css_token::type_comma,
			] );

			//	":", "blank", "}"
			$backup = $this->m_index;
			if( ! $this->token_next_exp() ) break;

			//	連想データ
			if( $this->m_token->m_type == crow_css_token::type_colon )
			{
				//	連想データの場合はさらに値をパース
				$val_stack = [];
				$this->parse_exp( $val_stack,
				[
					crow_css_token::type_msec_ed,
					crow_css_token::type_semicolon,
					crow_css_token::type_comma,
				] );

				$key = $key_stack[count($key_stack)-1]->build();
				$stack_[$addr]->m_data[$key] = crow_css_stack::materialize(
					$val_stack[count($val_stack)-1] );
			}
			//	単一データ
			else
			{
				$this->jump_token_to($backup);
				$stack_[$addr]->m_data[] = crow_css_stack::materialize(
					$key_stack[count($key_stack)-1] );
			}

			//	"}"なら定義終了
			$backup = $this->m_index;
			$this->token_next_exp();
			if( $this->m_token->m_type == crow_css_token::type_msec_ed )
			{
				break;
			}

			//	";" "," の場合は、継続か終了
			if(
				$this->m_token->m_type == crow_css_token::type_semicolon ||
				$this->m_token->m_type == crow_css_token::type_comma
			){
				//	さらに読む
				$backup = $this->m_index;
				$this->token_next_exp();
				if( $this->m_token->m_type == crow_css_token::type_msec_ed )
				{
					//	"}"なら終わり
					break;
				}
			}

			$this->jump_token_to($backup);
		}

	}

	//--------------------------------------------------------------------------
	//	パース：演算式
	//--------------------------------------------------------------------------
	private function parse_exp( &$stack_, $term_type_, $materialize_=true )
	{
		//	最初のトークンが "{" なら配列定義開始
		if( ! $this->token_next_exp() ) return false;
		if( $this->m_token->m_type == crow_css_token::type_msec_st )
		{
			if( ! $this->parse_array($stack_, crow_css_token::type_msec_ed) ) return false;
			return true;
		}
		else {
			$this->token_back();
		}

		//	最初の論理式
		if( ! $this->parse_exp_logical($stack_, $term_type_) ) return false;

		//	コード先読み
		$backup = $this->m_index;
		if( ! $this->token_next_exp() ) return false;

		//	指定された終端であればここで終わり
		if(
			(is_array($term_type_) && in_array($this->m_token->m_type, $term_type_)) ||
			(is_array($term_type_) == false && $this->m_token->m_type == $term_type_)
		){
			$this->token_back();

			//	必要なら実体化したものを積んで返却
			if( $materialize_ == true )
				$stack_[count($stack_)] = crow_css_stack::materialize($stack_[count($stack_) - 1]);
			return true;
		}

		//	トークン戻す（スペースも復元したいので、位置指定とする）
		$this->jump_token_to($backup);

		//	終端でなかったなら配列となる
		$result = new crow_css_stack( crow_css_stack::type_arr );
		$result->m_data[count($result->m_data)] =
			crow_css_stack::materialize($stack_[count($stack_) - 1]);

		//	配列式を継続
		while(true)
		{
			if( ! $this->parse_exp_logical($stack_, $term_type_) ) return false;

			//	結果を追加
			$result->m_data[count($result->m_data)] =
				crow_css_stack::materialize($stack_[count($stack_) - 1]);

			//	先読み
			if( ! $this->token_next_exp() ) return false;

			//	指定終端なら終了
			if(
				(is_array($term_type_) && in_array($this->m_token->m_type, $term_type_)) ||
				(is_array($term_type_)==false && $this->m_token->m_type==$term_type_)
			){
				$this->token_back();
				break;
			}

			//	トークン戻す
			$this->token_back();
		}

		//	必要なら実体化したものを積んで返却
		if( $materialize_==true )
			$stack_[count($stack_)] = crow_css_stack::materialize($result);
		else
			$stack_[count($stack_)] = $result;
		return true;
	}

	//--------------------------------------------------------------------------
	//	演算：論理式
	//--------------------------------------------------------------------------
	private function parse_exp_logical( &$stack_, $term_type_ )
	{
		//	左辺
		if( ! $this->parse_exp_compare($stack_, $term_type_) ) return false;
		$left_addr = count($stack_) - 1;

		//	継続
		while(1)
		{
			//	トークン取得
			if( ! $this->token_next_exp() ) break;

			//	論理演算子
			if(
				$this->m_token->m_type == crow_css_token::type_and ||
				$this->m_token->m_type == crow_css_token::type_or
			){
				$exp = $this->m_token->m_data;

				//	右辺
				if( ! $this->parse_exp_compare($stack_, $term_type_) ) return false;

				//	スタック数に変化がないならエラー
				if( $left_addr == count($stack_) - 1 )
				{
					$this->add_error("syntax error of expression");
					return false;
				}
				$right_addr = count($stack_) - 1;

				//	比較
				$newval = $stack_[$left_addr]->do_logical($exp, $stack_[$right_addr]);

				//	右辺のスタックを戻す
				for( $i=$left_addr+1; $i<=$right_addr; $i++ ) unset($stack_[$i]);

				//	結果として新しいスタックを積み、新たな左辺とする
				$stack_[count($stack_)] = new crow_css_stack(
					crow_css_stack::type_bool, $newval ? true : false
				);
				$left_addr = count($stack_) - 1;
			}
			//	それ以外はトークンを戻して終わり
			else
			{
				$this->token_back();
				break;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	演算：比較式
	//--------------------------------------------------------------------------
	private function parse_exp_compare( &$stack_, $term_type_ )
	{
		//	左辺
		if( ! $this->parse_term_low($stack_, $term_type_) ) return false;
		$left_addr = count($stack_) - 1;

		//	継続
		while(true)
		{
			//	トークン取得
			if( ! $this->token_next_exp() ) break;

			//	比較演算子
			if(
				$this->m_token->m_type == crow_css_token::type_eqeq ||
				$this->m_token->m_type == crow_css_token::type_neq ||
				$this->m_token->m_type == crow_css_token::type_sm ||
				$this->m_token->m_type == crow_css_token::type_smeq ||
				$this->m_token->m_type == crow_css_token::type_lg ||
				$this->m_token->m_type == crow_css_token::type_lgeq
			){
				$exp = $this->m_token->m_data;

				//	右辺
				if( ! $this->parse_term_low($stack_, $term_type_) ) return false;

				//	スタック数に変化がないならエラー
				if( $left_addr == count($stack_) - 1 )
				{
					$this->add_error("syntax error of expression");
					return false;
				}
				$right_addr = count($stack_) - 1;

				//	比較
				$newval = $stack_[$left_addr]->do_compare($exp, $stack_[$right_addr]);

				//	右辺のスタックを戻す
				for( $i=$left_addr+1; $i<=$right_addr; $i++ ) unset($stack_[$i]);

				//	結果として新しいスタックを積み、新たな左辺とする
				$stack_[count($stack_)] = new crow_css_stack(
					crow_css_stack::type_bool, $newval ? true : false
				);
				$left_addr = count($stack_) - 1;
			}
			//	それ以外はトークンを戻して終わり
			else
			{
				$this->token_back();
				break;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	演算：項（低優先）
	//--------------------------------------------------------------------------
	private function parse_term_low( &$stack_, $term_type_ )
	{
		//	左辺
		if( ! $this->parse_term_high($stack_, $term_type_) ) return false;
		$left_addr = count($stack_) - 1;

		//	継続
		while(true)
		{
			//	トークン取得
			if( ! $this->token_next_exp() ) break;

			//	add, sub
			if(
				$this->m_token->m_type == crow_css_token::type_add ||
				$this->m_token->m_type == crow_css_token::type_sub
			){
				$exp = $this->m_token->m_data;

				//	右辺
				if( ! $this->parse_term_high($stack_, $term_type_) ) return false;

				//	スタック数に変化がないならエラー
				if( $left_addr == count($stack_) - 1 )
				{
					$this->add_error("syntax error of expression");
					return false;
				}
				$right_addr = count($stack_) - 1;

				//	演算
				$newstack = $stack_[$left_addr]->do_expression($exp, $stack_[$right_addr]);

				//	右辺のスタックを戻す
				for( $i = $left_addr + 1; $i <= $right_addr; $i++ ) unset($stack_[$i]);

				//	結果として新しいスタックを積み、新たな左辺とする
				$stack_[count($stack_)] = $newstack;
				$left_addr = count($stack_) - 1;
			}
			//	それ以外はトークンを戻して終わり
			else
			{
				$this->token_back();
				break;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	演算：項（高優先）
	//--------------------------------------------------------------------------
	private function parse_term_high( &$stack_, $term_type_ )
	{
		//	左辺
		if( ! $this->parse_factor_high($stack_, $term_type_) ) return false;
		$left_addr = count($stack_) - 1;

		//	継続
		while(1)
		{
			//	トークン取得
			if( ! $this->token_next_exp() ) break;

			//	mul, div, mod
			if(
				$this->m_token->m_type == crow_css_token::type_mul ||
				$this->m_token->m_type == crow_css_token::type_div ||
				$this->m_token->m_type == crow_css_token::type_mod
			){
				$exp = $this->m_token->m_data;

				//	右辺
				if( ! $this->parse_factor_high($stack_, $term_type_) ) return false;

				//	スタック数に変化がないならエラー
				if( $left_addr == count($stack_) - 1 )
				{
					$this->add_error("syntax error of expression");
					return false;
				}
				$right_addr = count($stack_) - 1;

				//	演算
				$newstack = $stack_[$left_addr]->do_expression($exp, $stack_[$right_addr]);

				//	右辺のスタックを戻す
				for( $i = $left_addr+1; $i <= $right_addr; $i++ ) unset($stack_[$i]);

				//	結果として新しいスタックを積み、新たな左辺とする
				$stack_[count($stack_)] = $newstack;
				$left_addr = count($stack_) - 1;
			}
			//	それ以外はトークンを戻して終わり
			else
			{
				$this->token_back();
				break;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	演算：因子（高優先）
	//--------------------------------------------------------------------------
	private function parse_factor_high( &$stack_, $term_type_ )
	{
		//	左辺
		if( ! $this->parse_factor_low($stack_, $term_type_) ) return false;
		$left_addr = count($stack_) - 1;

		//	継続
		while(1)
		{
			//	トークン取得
			if( ! $this->token_next() ) break;

			//	次が配列の区切りや終端でないなら、rawとして連結する
			if(
				$this->m_token->m_type == crow_css_token::type_raw ||
				$this->m_token->m_type == crow_css_token::type_num ||
				$this->m_token->m_type == crow_css_token::type_str1 ||
				$this->m_token->m_type == crow_css_token::type_str2 ||
				$this->m_token->m_type == crow_css_token::type_str3 ||
				$this->m_token->m_type == crow_css_token::type_ssec_st ||
				$this->m_token->m_type == crow_css_token::type_ssec_ed ||
				$this->m_token->m_type == crow_css_token::type_inline_st ||
				$this->m_token->m_type == crow_css_token::type_sub ||
				$this->m_token->m_type == crow_css_token::type_variable
			){
				$this->token_back();

				$stack_sub = [];
				if( ! $this->parse_factor_low($stack_sub, $term_type_) ) return false;
				$right_addr = count($stack_sub) - 1;

				//	継続因子はrawとするため、新しくスタックを作る
				$tmp_stack = new crow_css_stack
				(
					crow_css_stack::type_raw,
					$stack_sub[$right_addr]->build()
				);

				//	rawとして左辺にappendする
				$stack_[$left_addr] = $stack_[$left_addr]->do_expression("+", $tmp_stack);
			}
			else
			{
				$this->token_back();
				break;
			}
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	演算：因子（低優先）
	//--------------------------------------------------------------------------
	private function parse_factor_low( &$stack_, $term_type_ )
	{
		if( ! $this->token_next_exp() ) return true;

		//	指定終端なら戻して終了
		if(
			(is_array($term_type_) && in_array($this->m_token->m_type, $term_type_)) ||
			(is_array($term_type_) == false && $this->m_token->m_type == $term_type_)
		){
			$this->token_back();
			return false;
		}

		//	数値
		else if( $this->m_token->m_type == crow_css_token::type_num )
		{
			$stack_[count($stack_)] = new crow_css_stack
			(
				crow_css_stack::type_num,
				$this->m_token->m_data,
				$this->m_token->m_data_sub
			);
		}

		//	文字列
		else if( $this->m_token->m_type == crow_css_token::type_str1 )
		{
			$stack_[count($stack_)] = new crow_css_stack(
				crow_css_stack::type_str1,
				$this->m_token->m_data
			);
		}
		else if( $this->m_token->m_type == crow_css_token::type_str2 )
		{
			$stack_[count($stack_)] = new crow_css_stack(
				crow_css_stack::type_str2,
				$this->m_token->m_data
			);
		}
		else if( $this->m_token->m_type == crow_css_token::type_str3 )
		{
			$stack_[count($stack_)] = new crow_css_stack(
				crow_css_stack::type_str3,
				$this->m_token->m_data
			);
		}

		//	変数
		else if( $this->m_token->m_type == crow_css_token::type_variable )
		{
			$idx = count($stack_);
			$stack_[$idx] = new crow_css_stack(crow_css_stack::type_ref);
			$stack_[$idx]->m_data =
				&$this->m_block->getvar($this->m_token->m_data);

			while(true)
			{
				//	"["が続くなら配列アクセス
				if( ! $this->token_next() ) break;
				if( $this->m_token->m_type == crow_css_token::type_lsec_st )
				{
					$key_stack = [];
					if( ! $this->parse_exp($key_stack, crow_css_token::type_lsec_ed) ) return false;
					if( count($key_stack) <= 0 ) return false;

					//	"]" はスキップ
					if( ! $this->token_next_exp() ) return true;
					if( $this->m_token->m_type != crow_css_token::type_lsec_ed )
					{
						return false;
					}

					//	配列要素への参照を積む
					$key = $key_stack[count($key_stack)-1]->build();
					if( ! isset($stack_[$idx]->m_data->m_data[$key]) )
					{
						//	初めてアクセスされる要素ならvoidで作成
						$stack_[$idx]->m_data->m_type = crow_css_stack::type_arr;
						$stack_[$idx]->m_data->m_data[$key] = new crow_css_stack(crow_css_stack::type_void);
					}

					//	参照を入れ替える
					$new_idx = count($stack_);
					$stack_[$new_idx] = new crow_css_stack(crow_css_stack::type_ref);
					$stack_[$new_idx]->m_data = &$stack_[$idx]->m_data->m_data[$key];
					$idx = $new_idx;
				}
				else {
					$this->token_back();
					break;
				}
			}
		}

		//	インライン
		else if( $this->m_token->m_type == crow_css_token::type_inline_st )
		{
			//	計算用スタックを用意して因子パース "}" 終端
			$stack = [];
			$this->parse_exp($stack, crow_css_token::type_msec_ed);
			if( count($stack) <= 0 ) return;

			//	"}" はスキップ
			if( ! $this->token_next_exp() ) return true;
			if( $this->m_token->m_type != crow_css_token::type_msec_ed )
			{
				return false;
			}

			//	結果はrawとする
			$stack_[count($stack_)] = new crow_css_stack(
				crow_css_stack::type_raw,
				end($stack)->build()
			);
		}

		//	それ以外はraw
		else
		{
			$stack_[count($stack_)] = new crow_css_stack(
				crow_css_stack::type_raw,
				$this->m_token->m_data
			);
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//	ブロックをプッシュ
	//--------------------------------------------------------------------------
	private function push_block()
	{
		if( $this->m_block === false )
		{
			$this->m_root_block = new crow_css_block($this->m_falseval);
			$this->m_block = &$this->m_root_block;
		}
		else
		{
			//	子ブロックの実体作成
			$this->m_block->m_child = new crow_css_block($this->m_block);

			//	現ブロックを作成したものに置き換える
			$this->m_block = &$this->m_block->m_child;
			$this->m_block->m_index = $this->m_index;

			//	var引き継ぎ
			$current = &$this->m_block;
			$parent  = &$this->m_block->m_parent;

			//	parent vars -> local vars の順で引き継ぐ。
			//	同一キーでアクセスした際にネストレベルが近いものを参照。
			if( $parent !== false )
			{
				foreach( $parent->m_parent_vars as $key => $dummy )
					$current->m_parent_vars[$key] = &$parent->m_parent_vars[$key];
				foreach( $parent->m_local_vars as $key => $dummy )
					$current->m_parent_vars[$key] = &$parent->m_local_vars[$key];
			}
		}
	}

	//--------------------------------------------------------------------------
	//	ブロックをポップ
	//--------------------------------------------------------------------------
	private function pop_block()
	{
		if( $this->m_block === false ) return;
		$this->m_block = &$this->m_block->m_parent;
		if( $this->m_block !== false ) $this->m_block->m_child = false;
	}

	//--------------------------------------------------------------------------
	//	エラー追加
	//--------------------------------------------------------------------------
	private function add_error( $msg_ )
	{
		$this->m_errors[] = new crow_css_error
		(
			$this->m_fname,
			$this->m_token ? $this->m_token->m_line : 0,
			$msg_
		);
	}

	//--------------------------------------------------------------------------
	//	トークンを進める
	//--------------------------------------------------------------------------
	private function token_next()
	{
		if( $this->m_index + 1 >= count($this->m_lex->m_tokens) )
			return false;

		$this->m_index++;
		$this->m_token = &$this->m_lex->m_tokens[$this->m_index];
		return true;
	}

	//	演算式用（ブランクをスキップ）
	private function token_next_exp()
	{
		if( ! $this->token_next() ) return false;
		while( $this->m_token->m_type == crow_css_token::type_blank )
		{
			if( ! $this->token_next() ) return false;
		}
		return true;
	}

	//--------------------------------------------------------------------------
	//	トークンを戻す
	//--------------------------------------------------------------------------
	private function token_back()
	{
		if( $this->m_index >= 0 ) $this->m_index--;
		if( $this->m_index >= 0 )
		{
			$this->m_token = &$this->m_lex->m_tokens[$this->m_index];
		}
		else
		{
			$this->m_index = -1;
			$this->m_token = &$this->m_nullval;
		}
	}

	//--------------------------------------------------------------------------
	//	特定のインデックスまでトークンを飛ばす
	//--------------------------------------------------------------------------
	private function jump_token_to( $index_ )
	{
		if( $index_ < 0 || $index_ >= count($this->m_lex->m_tokens) ) return;

		$this->m_index = $index_;
		$this->m_token = &$this->m_lex->m_tokens[$this->m_index];
	}

	//--------------------------------------------------------------------------
	//	特定トークンが出現するまでスキップする
	//--------------------------------------------------------------------------
	private function skip_token_to( $type_ )
	{
		while( $this->m_token->m_type != $type_ )
		{
			if( ! $this->token_next() ) return false;
		}
		$this->token_back();
		return true;
	}

	//--------------------------------------------------------------------------
	//	ブロックを丸々スキップする（"{"の前から開始すること）
	//--------------------------------------------------------------------------
	private function skip_block()
	{
		$this->skip_token_to( crow_css_token::type_msec_st );

		if( ! $this->token_next_exp() ) return;
		if( $this->m_token->m_type != crow_css_token::type_msec_st )
		{
			$this->token_back();
			return;
		}

		$depth = 1;
		while( $depth > 0 )
		{
			if( ! $this->token_next_exp() ) return;
			if( $this->m_token->m_type == crow_css_token::type_msec_st )
				$depth++;
			else if( $this->m_token->m_type == crow_css_token::type_msec_ed )
				$depth--;
		}
	}

	//--------------------------------------------------------------------------
	//	ロジック階層 push
	//--------------------------------------------------------------------------
	public function push_control( &$control_stack_, $control_type_, $start_index_, $data_=array() )
	{
		$control_stack_[count($control_stack_)] =
		[
			'type'	=> $control_type_,
			'index'	=> $start_index_,
			'data'	=> $data_,
		];
	}

	//--------------------------------------------------------------------------
	//	ロジック階層 pop
	//--------------------------------------------------------------------------
	public function pop_control( &$control_stack_ )
	{
		if( count($control_stack_) > 0 )
			unset($control_stack_[count($control_stack_) - 1]);
	}

	//--------------------------------------------------------------------------
	//	ロジック階層 break
	//--------------------------------------------------------------------------
	public function control_break( &$control_stack_ )
	{
		$cur = count($control_stack_) - 1;
		for( $i=$cur; $i>=0; $i-- )
		{
			if(
				$control_stack_[$i]['type'] == 'for' ||
				$control_stack_[$i]['type'] == 'foreach' ||
				$control_stack_[$i]['type'] == 'while' ||
				$control_stack_[$i]['type'] == 'sw'
			){
				$this->jump_token_to( $control_stack_[$i]['index'] );
				$this->skip_block();
				unset($control_stack_[$i]);
				break;
			}
			else {
				unset($control_stack_[$i]);
			}
		}
	}

	//--------------------------------------------------------------------------
	//	private
	//--------------------------------------------------------------------------
	private $m_fname = '';
	private $m_paths = [];
	private $m_errors = [];
	private $m_root_block = false;
	private $m_block = false;
	private $m_lex = false;
	private $m_index = -1;
	private $m_token = null;
	private $m_nullval = null;
	private $m_falseval = false;
}
?>
