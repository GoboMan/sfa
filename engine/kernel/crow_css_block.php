<?php
/*

	crow css - block

*/
class crow_css_block
{
	//	親 ref
	public $m_parent = false;

	//	子 instance
	public $m_child = false;

	//	開始位置
	public $m_index = 0;

	//	親変数 ref
	public $m_parent_vars = [];

	//	ローカル変数 instance (crow_css_stack)
	public $m_local_vars = [];

	//	セレクタクリア
	public function clear_selector()
	{
		$this->m_selector = '';
	}

	//	データ追加
	public function append( $str_ )
	{
		if( $str_ == ";" )
		{
			$pos = strpos($this->m_selector,":");
			$key = ($pos === false) ?
				trim($this->m_selector) :
				trim(substr($this->m_selector,0,$pos))
				;
			$val = ($pos === false) ? '' :
				trim(substr($this->m_selector,$pos+1));

			$this->append_body([$key=>$val]);

			$this->m_selector = "";
		}
		else if( $str_ == ":" )
		{
			$this->m_selector =
				rtrim($this->m_selector).$str_;
		}
		else
		{
			$this->m_selector .= $str_;
		}
	}

	//	ボディを連想配列で追加
	public function append_body( $arr_ )
	{
		foreach( $arr_ as $k => $v )
		{
			$this->m_body[] = ["key"=>$k, "val"=>$v];
		}
	}

	//	変数の参照取得、なければローカル変数として追加
	public function &getvar( $name_ )
	{
		if( isset($this->m_parent_vars[$name_]) )
			return $this->m_parent_vars[$name_];

		if( isset($this->m_local_vars[$name_]) )
			return $this->m_local_vars[$name_];

		//	ないので、voidとして追加
		$this->m_local_vars[$name_] =
			new crow_css_stack(crow_css_stack::type_void);
		return $this->m_local_vars[$name_];
	}

	//	ビルド
	public function build()
	{
		//	ルートブロックは出力なしとする
		if( $this->m_parent === false ) return '';

		//	属性が一つもないなら出力なしとする
		if( count($this->m_body) <= 0 ) return '';

		//	親に向かってセレクタを辿る
		$sels = [];
		$p = &$this->m_parent;
		while( $p !== false )
		{
			$newsels = [];

			$psels = $p->selector_to_array();
			if( count($sels) <= 0 )
			{
				foreach( $psels as $psel )
				{
					$newsels[trim($psel)] = 1;
				}
			}
			else
			{
				foreach( $psels as $psel )
				{
					foreach( $sels as $sel )
					{
						if( substr($sel,0,1) == '&' )
							$newsels[trim($psel).substr($sel,1)] = 1;
						else
							$newsels[trim($psel).' '.$sel] = 1;
					}
				}
			}
			$sels = array_keys($newsels);
			$p = &$p->m_parent;
		}
		$selector = implode(',', $sels);

		//	内容の構築
		$body_line = '';
		foreach( $this->m_body as $item )
		{
			if( $item['key'] == "" ) continue;
			$body_line .= $item['key'].":".$item['val'].";";
		}

		if( strlen($selector)<=0 && strlen($body_line)<=0 ) return '';
		return trim($selector)."{".$body_line."}";
	}

	//	selectorを配列化する
	private function selector_to_array()
	{
		$selectors = explode(",",$this->m_selector);
		return $selectors;
	}

	//	生成
	public function __construct( &$parent_ )
	{
		$this->m_parent	= &$parent_;
	}

	//	private
	private $m_selector = "";
	private $m_body = [];
}
?>
