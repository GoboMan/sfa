<?php
/*

	crow css - error

*/
class crow_css_error
{
	//	ファイル名
	public $m_fname = "";

	//	行番号
	public $m_line = "";

	//	エラーメッセージ
	public $m_msg = "";

	//	生成
	public function __construct( $fname_, $line_, $msg_ )
	{
		$this->m_fname	= $fname_;
		$this->m_line	= $line_;
		$this->m_msg	= $msg_;
	}
}
?>
