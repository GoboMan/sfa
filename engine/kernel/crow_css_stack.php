<?php
/*

	crow css - stack

*/
class crow_css_stack
{
	//	値種別
	const type_void		= 0;	//	未初期化
	const type_arr		= 1;	//	配列
	const type_ref		= 2;	//	参照
	const type_raw		= 3;	//	生データ
	const type_num		= 4;	//	数値
	const type_str1		= 5;	//	シングルクォート文字列
	const type_str2		= 6;	//	ダブルクォート文字列
	const type_bool		= 7;	//	二値
	const type_str3		= 8;	//	バッククォート文字列

	//	値種別
	public $m_type = self::type_void;

	//	値
	public $m_data = false;

	//	値サブ
	public $m_data_sub = false;

	//	refを遡った実体の参照を取得
	public function &abstract_data()
	{
		if( $this->m_type == self::type_ref )
			return $this->m_data->abstract_data();
		return $this;
	}

	//	実体化。全階層の配列要素の ref を複製し、実体のみにする
	public static function materialize( $stack_ )
	{
		$stack = $stack_->abstract_data($stack_);
		if( $stack->m_type == self::type_arr )
		{
			$result = new crow_css_stack( self::type_arr );
			foreach( $stack->m_data as $key => $item )
			{
				$result->m_data[$key] = self::materialize($item);
			}
			return $result;
		}
		else {
			return new crow_css_stack($stack->m_type, $stack->m_data, $stack->m_data_sub);
		}
	}

	//	代入
	public function do_attach( &$right_stack_ )
	{
		$left = &$this->abstract_data();
		$right = &$right_stack_->abstract_data();

		if( $right->m_type == self::type_arr )
		{
			$left->m_type = self::type_arr;
			$left->m_data = [];
			$left->m_data_sub = false;
			foreach( $right->m_data as $key => $item )
			{
				$left->m_data[$key] = new crow_css_stack(self::type_void);
				$left->m_data[$key]->do_attach( $right->m_data[$key] );
			}
		}
		else
		{
			$left->m_type = $right->m_type;
			$left->m_data = $right->m_data;
			$left->m_data_sub = $right->m_data_sub;
		}
	}

	//	比較演算
	public function do_compare( $exp_, &$right_stack_ )
	{
		$left = &$this->abstract_data();
		$right = &$right_stack_->abstract_data();

		$result = false;

		//	==
		if( $exp_ == '==' )
		{
			if( $left->m_type == self::type_arr && $right->m_type == self::type_arr )
			{
				$result = true;

				if( is_array($left->m_data)==false ||
					is_array($right->m_data)==false
				){
					$result = false;
				}
				else if( count(array_diff_key($left->m_data, $right->m_data)) > 0 )
				{
					$result = false;
				}
				else
				{
					foreach( $left->m_data as $key => $val )
					{
						if( ! $left->m_data[$key]->do_compare($exp_, $right->m_data[$key]) )
						{
							$result = false;
							break;
						}
					}
				}
			}
			else
			{
				$result = ($left->m_data == $right->m_data);
			}
		}
		//	!=
		else if( $exp_ == '!=' )
		{
			if( $left->m_type == self::type_arr && $right->m_type == self::type_arr )
			{
				$result = false;

				if( is_array($left->m_data) == false ||
					is_array($right->m_data) == false
				){
					$result = true;
				}
				else if( count(array_diff_key($left->m_data, $right->m_data)) > 0 )
				{
					$result = true;
				}
				else
				{
					foreach( $left->m_data as $key => $val )
					{
						if( ! $left->m_data[$key]->do_compare($exp_, $right->m_data[$key]) )
						{
							$result = true;
							break;
						}
					}
				}
			}
			else
			{
				$result = ($left->m_data != $right->m_data);
			}
		}
		else if( $exp_ == '<' ) $result = ($left->m_data < $right->m_data);
		else if( $exp_ == '<=' ) $result = ($left->m_data <= $right->m_data);
		else if( $exp_ == '>' ) $result = ($left->m_data > $right->m_data);
		else if( $exp_ == '>=' ) $result = ($left->m_data >= $right->m_data);

		return $result;
	}

	//	論理演算
	public function do_logical( $exp_, &$right_stack_ )
	{
		$left = &$this->abstract_data();
		$right = &$right_stack_->abstract_data();

		$result = false;

		if( $exp_ == '&&' ) $result = ($left->m_data && $right->m_data);
		else if( $exp_ == '||' ) $result = ($left->m_data || $right->m_data);

		return $result;
	}

	//	四則演算
	public function do_expression( $exp_, &$right_stack_ )
	{
		$left = &$this->abstract_data();
		$right = &$right_stack_->abstract_data();

		$result = new crow_css_stack( self::type_void );

		//	配列に対する演算なら
		if( $left->m_type == self::type_arr )
		{
			//	まずは左辺を実体化
			$result = self::materialize($left);

			//	右辺が配列の場合、一つずつ演算する
			if( $right->m_type == self::type_arr )
			{
				foreach( $result->m_data as $key => $item )
				{
					if( isset($right->m_data[$key]) )
					{
						$result->m_data[$key] =
							$item->do_expression($exp_, $right->m_data[$key]);
					}
				}
			}

			//	右辺が配列でない場合は一括演算
			else if( $right->m_type != self::type_arr )
			{
				foreach( $result->m_data as $key => $item )
				{
					$result->m_data[$key] = $item->do_expression($exp_, $right);
				}
			}
			return $result;
		}

		//	以降、配列でないものに対する演算
		if( $exp_ == '+' )
		{
			//	左辺が数値
			if( $left->m_type == self::type_num )
			{
				$result->m_data = $left->m_data + $right->m_data;
				$result->m_data_sub = $left->m_data_sub;
				$result->m_type = $left->m_type;
			}
			//	左辺が文字列
			else if( $left->m_type == self::type_str1 )
			{
				$result->m_data = $left->m_data.$right->m_data
					.($right->m_data_sub!==false ? $right->m_data_sub : '');
				$result->m_data_sub = false;
				$result->m_type = self::type_str1;
			}
			else if( $left->m_type == self::type_str2 )
			{
				$result->m_data = $left->m_data.$right->m_data
					.($right->m_data_sub!==false ? $right->m_data_sub : '');
				$result->m_data_sub = false;
				$result->m_type = self::type_str2;
			}
			else if( $left->m_type == self::type_str3 )
			{
				$result->m_data = $left->m_data.$right->m_data
					.($right->m_data_sub!==false ? $right->m_data_sub : '');
				$result->m_data_sub = false;
				$result->m_type = self::type_str3;
			}
			//	左辺が生データ
			else if( $left->m_type == self::type_raw )
			{
				$result->m_data = $left->m_data.$right->m_data
					.($right->m_data_sub!==false ? $right->m_data_sub : '');
				$result->m_data_sub = false;
				$result->m_type = self::type_raw;
			}
			//	それ以外は、右辺の値を結果の値とする
			else if( $left->m_type == self::type_bool )
			{
				$result->m_data = $right->m_data;
				$result->m_data_sub = $right->m_data_sub;
				$result->m_type = $right->m_type;
			}
		}
		else if(
			$exp_ == '-' ||
			$exp_ == '*' ||
			$exp_ == '/' ||
			$exp_ == '%'
		){
			//	左辺が数値
			if( $left->m_type == self::type_num )
			{
				if( $exp_=='-' ) $result->m_data = $left->m_data - $right->m_data;
				else if( $exp_=='*' ) $result->m_data = $left->m_data * $right->m_data;
				else if( $exp_=='/' ) $result->m_data = $left->m_data / $right->m_data;
				else if( $exp_=='%' ) $result->m_data = $left->m_data % $right->m_data;
				$result->m_data_sub = $left->m_data_sub;
				$result->m_type = $left->m_type;
			}
			//	それ以外は、右辺の値を結果の値とする
			else if( $left->m_type == self::type_bool )
			{
				$result->m_data = $right->m_data;
				$result->m_data_sub = $right->m_data_sub;
				$result->m_type = $right->m_type;
			}
		}

		return $result;
	}

	//	ビルド
	public function build()
	{
		$left = &$this->abstract_data();

		if( $left->m_type == self::type_arr )
		{
			$ret = '';
			foreach( $left->m_data as $key => $item )
			{
				if( preg_match('/^[0-9]+$/', $key) )
				{
					if( $ret != '' ) $ret .= ' ';
					$ret .= $item->build();
				}
				else {
					if( $ret != '' ) $ret .= ' ';
					$ret .= $key.":".$item->build().";";
				}
			}
			return $ret;
		}
		else if( $left->m_type == self::type_raw ) return $left->m_data;
		else if( $left->m_type == self::type_num ) return $left->m_data.$left->m_data_sub;
		else if( $left->m_type == self::type_str1 )
		{
			$str = $left->m_data;
			return "'".$str."'";
		}
		else if( $left->m_type == self::type_str2 )
		{
			$str = $left->m_data;
			return '"'.$str.'"';
		}
		else if( $left->m_type == self::type_str3 )
		{
			return $left->m_data;
		}
		else if( $left->m_type == self::type_bool )
		{
			return $left->m_data;
		}
	}

	//	トップレベルが配列であるものの内容を、連想配列でビルドする
	public function build_array()
	{
		$left = &$this->abstract_data();
		$arr = $this->build_array_core();

		//	多次元配列を一次元に詰めて返却
		$result = [];
		$this->build_array_pack($arr, $result);
		return $result;
	}
	private function build_array_pack( $arr_, &$result_ )
	{
		if( is_array($arr_)===true && count($arr_) > 0 )
		{
			foreach( $arr_ as $key => $val )
			{
				if( is_array($val) ) $this->build_array_pack($val, $result_);
				else $result_[$key] = $val;
			}
		}
	}
	private function build_array_core()
	{
		$left = &$this->abstract_data();

		if( $left->m_type == self::type_arr )
		{
			$result = [];
			foreach( $left->m_data as $key => $item )
			{
				$result[$key] = $item->build_array_core();
			}
			return $result;
		}
		else if( $left->m_type == self::type_raw ) return $left->m_data;
		else if( $left->m_type == self::type_num ) return $left->m_data.$left->m_data_sub;
		else if( $left->m_type == self::type_str1 )
		{
			$str = $left->m_data;
			//$str = mb_str_replace("\\","\\\\",$str);
			//$str = mb_str_replace("'","\\'",$str);
			return "'".$str."'";
		}
		else if( $left->m_type == self::type_str2 )
		{
			$str = $left->m_data;
			return '"'.$str.'"';
		}
		else if( $left->m_type == self::type_str3 )
		{
			return $left->m_data;
		}
		else if( $left->m_type == self::type_bool )
		{
			return $left->m_data;
		}
	}

	//	生成
	//
	//	ref 指定時は data は後から参照で初期化すること
	public function __construct( $type_, $data_=false, $data_sub_=false )
	{
		$this->m_type		= $type_;
		$this->m_data		= $data_;
		$this->m_data_sub	= $data_sub_;
		if( $type_ == self::type_arr ) $this->m_data = [];
	}
}
?>
