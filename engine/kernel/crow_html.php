<?php
/*

	HTML用ユーティリティ

*/
class crow_html
{
	//--------------------------------------------------------------------------
	//	<select>内の<option>タグを作成する
	//
	//		$arr_		: 指定した連想配列のキーがoptionのvalueになり、値がoptionのテキストになる。
	//		$selected_	: 現在選択されているoptionのvalueを指定
	//
	//	先頭に未選択の項目を追加するためには、make_option_tag_with_empty を利用すること。
	//--------------------------------------------------------------------------
	public static function make_option_tag( $arr_, $selected_ = false )
	{
		$ret = '';
		if( count($arr_) > 0 )
		{
			foreach( $arr_ as $key => $val )
			{
				$ret .= ($selected_ !== false && $selected_ == $key) ?
					'<option value="'.$key.'" selected>'.$val.'</option>' :
					'<option value="'.$key.'">'.$val.'</option>'
					;
			}
		}
		return $ret;
	}
	public static function make_option_tag_with_empty( $arr_, $selected_ = false, $empty_msg_ = "", $empty_val_ = '0' )
	{
		$ret = '<option value="'.$empty_val_.'">'.$empty_msg_.'</option>';
		if( count($arr_) > 0 )
		{
			foreach( $arr_ as $key => $val )
			{
				$ret .= ($selected_ !== false && $selected_ == $key) ?
					'<option value="'.$key.'" selected>'.$val.'</option>' :
					'<option value="'.$key.'">'.$val.'</option>'
					;
			}
		}
		return $ret;
	}

	//------------------------------------------------------------------------------
	//	<select>内の、<option>タグを作成する
	//
	//	$array_		: KEY=[Object]の形でアイテムの一覧を指定
	//	$member_	: $array_の[Object]のどのメンバを表示対象にするかを指定
	//	$selected_	: 現在選択されている項目のKEYを指定
	//
	//	例）userというクラスがnameというメンバをもってる場合。
	//
	//		$user[] = new user('yamada');
	//		$user[] = new user('taro');
	//		$user[] = new user('hanako');
	//		echo make_option_tag_with_obj( $user, "name", 2 );
	//
	//	とすると、
	//		<option value="1">yamada</option>
	//		<option value="2" selected>taro</option>
	//		<option value="3">hanako</option>
	//
	//	のように出力される。
	//
	//	先頭に未選択の項目を追加するためには、make_option_tag_with_obj_and_empty を利用すること。
	//
	//------------------------------------------------------------------------------
	public static function make_option_tag_with_obj( $array_, $member_, $selected_ = false )
	{
		$ret = '';
		if( count($array_) > 0 )
		{
			foreach( $array_ as $key => $val )
			{
				$ret .= ($selected_ !== false && $selected_ == $key) ?
					'<option value="'.$key.'" selected>'.$val->{$member_}.'</option>' :
					'<option value="'.$key.'">'.$val->{$member_}.'</option>'
					;
			}
		}
		return $ret;
	}
	public static function make_option_tag_with_obj_with_empty( $array_, $member_, $selected_ = false, $empty_msg_ = '', $empty_val_ = '0' )
	{
		$ret = '<option value="'.$empty_val_.'">'.$empty_msg_.'</option>';
		if( count($array_) > 0 )
		{
			foreach( $array_ as $key => $val )
			{
				$ret .= ($selected_ !== false && $selected_ == $key) ?
					'<option value="'.$key.'" selected>'.$val->{$member_}.'</option>' :
					'<option value="'.$key.'">'.$val->{$member_}.'</option>'
					;
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	<select>内の<option>タグを作成する
	//
	//		指定した範囲の数値でoptionのvalueと表示値を作成する。
	//
	//		$from_		: 開始の数値
	//		$to_		: 終了の数値（この数値を含む）
	//		$selected_	: 現在選択されているoptionのvalueを指定
	//		$format_	: 表示文字列のフォーマット。デフォルトでは数値がそのまま出力されるが、
	//					  「%VAL%]年」のように指定すると、%VAL%の部分を数値に置き換えた文字列として出力する
	//		$span_		: インクリメンタル/デクリメンタル値を指定する
	//--------------------------------------------------------------------------
	public static function make_option_tag_range( $from_, $to_, $selected_=false, $format_="%VAL%", $span_ = 1 )
	{
		$ret = '';
		$from = $from_ <= $to_ ? $from_ : $to_;
		$to = $from_ <= $to_ ? $to_ : $from_;
		for( $val = $from; $val <= $to; $val += $span_ )
		{
			$ret .= ($selected_ !== false && $selected_ == $val) ?
				'<option value="'.$val.'" selected>'.str_replace("%VAL%",$val,$format_).'</option>' :
				'<option value="'.$val.'">'.str_replace("%VAL%",$val,$format_).'</option>'
				;
		}
		return $ret;
	}

	//	逆順にする場合
	public static function make_option_tag_range_reverse( $from_, $to_, $selected_=false, $format_="%VAL%", $span_ = 1 )
	{
		$ret = '';
		$from = $from_ <= $to_ ? $from_ : $to_;
		$to = $from_ <= $to_ ? $to_ : $from_;
		for( $val = $to; $val >= $from; $val -= $span_ )
		{
			$ret .= ($selected_ !== false && $selected_ == $val) ?
				'<option value="'.$val.'" selected>'.str_replace("%VAL%",$val,$format_).'</option>' :
				'<option value="'.$val.'">'.str_replace("%VAL%",$val,$format_).'</option>'
				;
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	JSに埋め込める形に文字列をエスケープする
	//
	//	配列が指定された場合、リカーシブにエスケープされる
	//	シングル/ダブルクォートの両方で文字列が括られることを想定している。
	//
	//	例）
	//		＜?php
	//			$data = "key='lne1\nline2'";
	//			$esc = crow_html::escape_js($data);
	//		?＞
	//		<script>
	//		alert( '＜?= $esc ?＞' );
	//		</script>
	//
	//--------------------------------------------------------------------------
	public static function escape_js( $data_ )
	{
		$data = $data_;
		if( is_array($data) === true )
		{
			foreach( $data as $k => $v )
				$data[$k] = self::escape_js($v);
		}
		else
		{
			$data = mb_str_replace( "\\", "\\\\", $data );
			$data = mb_str_replace( "\r", "", $data );
			$data = mb_str_replace( "\n", "\\n", $data );
			$data = mb_str_replace( "\"", "\\\"", $data );
			$data = mb_str_replace( "'", "\\'", $data );
		}
		return $data;
	}
}

?>
