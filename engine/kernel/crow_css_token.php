<?php
/*

	crow css - token

*/
class crow_css_token
{
	//	トークン種別
	const type_raw			= 1;	//	生データ
	const type_blank		= 2;	//	空白とコメント
	const type_reserve		= 3;	//	予約語
	const type_variable		= 4;	//	変数
	const type_num			= 5;	//	数値
	const type_str1			= 6;	//	文字列
	const type_str2			= 7;	//	文字列
	const type_str3			= 31;	//	埋め込みコード
	const type_semicolon	= 8;	//	セミコロン
	const type_colon		= 9;	//	コロン
	const type_comma		= 10;	//	カンマ
	const type_lsec_st		= 11;	//	[
	const type_lsec_ed		= 12;	//	]
	const type_msec_st		= 13;	//	{
	const type_msec_ed		= 14;	//	}
	const type_ssec_st		= 15;	//	(
	const type_ssec_ed		= 16;	//	)
	const type_inline_st	= 17;	//	#{  インライン開始
	const type_add			= 18;	//	+
	const type_sub			= 19;	//	-
	const type_mul			= 20;	//	*
	const type_div			= 21;	//	/
	const type_mod			= 22;	//	%
	const type_eqeq			= 23;	//	==
	const type_neq			= 24;	//	!=
	const type_sm			= 25;	//	<
	const type_smeq			= 26;	//	<=
	const type_lg			= 27;	//	>
	const type_lgeq			= 28;	//	>=
	const type_and			= 29;	//	&&
	const type_or			= 30;	//	||

	//	予約語コード
	const res_if			= 1;
	const res_elseif		= 2;
	const res_else			= 3;
	const res_for			= 4;
	const res_foreach		= 5;
	const res_while			= 6;
	const res_switch		= 7;
	const res_case			= 8;
	const res_default		= 9;
	const res_include		= 10;
	const res_media			= 11;
	const res_keyframes		= 12;

	//	予約語コードのマップ
	const codemap_res	=
	[
		"if"		=> self::res_if,
		"else if"	=> self::res_elseif,
		"elseif"	=> self::res_elseif,
		"else"		=> self::res_else,
		"for"		=> self::res_for,
		"foreach"	=> self::res_foreach,
		"while"		=> self::res_while,
		"sw"		=> self::res_switch,
		"switch"	=> self::res_switch,
		"case"		=> self::res_case,
		"def"		=> self::res_default,
		"default"	=> self::res_default,
		"include"	=> self::res_include,
		"media"		=> self::res_media,
		"keyframes"	=> self::res_keyframes,
	];

	//	行番号
	public $m_line = 0;

	//	種別
	public $m_type = self::type_raw;

	//	データ
	public $m_data = "";

	//	サブデータ
	public $m_data_sub = "";

	//	生成
	public function __construct( $line_, $type_, $data_ )
	{
		$this->m_line	= $line_;
		$this->m_type	= $type_;
		$this->m_data	= $data_;
	}
}
?>
