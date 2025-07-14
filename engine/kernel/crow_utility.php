<?php
/*

	ユーティリティ

*/
class crow_utility
{
	//--------------------------------------------------------------------------
	//	指定長のランダム文字列作成、32文字だとほぼユニークだが短くすると重複の可能性あり
	//--------------------------------------------------------------------------
	public static function random_str( $length_ = 32 )
	{
		$str = md5(uniqid(rand(),true));
		while( strlen($str) < $length_ )
			$str .= md5(uniqid(rand(),true));
		return substr($str, 0, $length_);
	}

	//--------------------------------------------------------------------------
	//	数字と大小アルファベット(62進数)による短いユニーク文字列の生成。
	//	結果は7～9文字となる。
	//--------------------------------------------------------------------------
	public static function unique62()
	{
		$uniq = base_convert(uniqid(), 16, 10);
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$base = strlen($chars);
		$base62 = "";
		while( $uniq > 0 )
		{
			$index = $uniq % $base;
			$base62 = $chars[$index].$base62;
			$uniq = intdiv($uniq, $base);
		}
		return $base62;
	}

	//--------------------------------------------------------------------------
	//	タイムスタンプから年齢の計算
	//--------------------------------------------------------------------------
	public static function calc_age( $birth_timestamp_, $now_ = false )
	{
		$now = $now_ === false ? time() : $now_;
		$age = intval(date('Y',$now)) - intval(date('Y', $birth_timestamp_)) - 1;
		$b_md = intval(date('md', $birth_timestamp_));
		$c_md = intval(date('md', $now));
		if( $c_md >= $b_md ) $age ++;
		return $age;
	}

	//--------------------------------------------------------------------------
	//	タイムスタンプから和暦情報を取得
	//	[0] = 年号文字
	//	[1] = 和暦年
	//	の配列を返却する
	//--------------------------------------------------------------------------
	public static function calc_jpyear( $timestamp_ )
	{
		$date_val = intval(date('Ymd', $timestamp_));
		$year_val = intval(date('Y', $timestamp_));
		if( $date_val < 19120730 ) return["M", $year_val - 1868 + 1];
		else if( $date_val < 19261225 ) return ["T", $year_val - 1912 + 1];
		else if( $date_val < 19890108 ) return ["S", $year_val - 1926 + 1];
		else if( $date_val < 20190501 ) return ["H", $year_val - 1989 + 1];
		return ["R", $year_val - 2019 + 1];
	}

	//--------------------------------------------------------------------------
	//	うるう年判定
	//--------------------------------------------------------------------------
	public static function is_leap_year( $year_ )
	{
		return ($year_ % 4 == 0 && $year_ % 100 != 0) || $year_ % 400 == 0;
	}

	//--------------------------------------------------------------------------
	//	指定した年月の日数を取得
	//--------------------------------------------------------------------------
	public static function get_days_in_month( $month_, $year_ )
	{
		$days_in_month = [31, self::is_leap_year($year_) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		return $days_in_month[$month_ - 1];
	}

	//--------------------------------------------------------------------------
	//	連想配列レコードを、オブジェクトレコードに変換する
	//
	//		$arr = array( "field1"=>"value1", "field2"=>"value2" );
	//		$obj = crow_utility::array_to_obj($arr);
	//
	//		とすると、$obj->field1 というようにアクセス可能になる
	//
	//--------------------------------------------------------------------------
	public static function array_to_obj( $arr_ )
	{
		return $arr_ ? json_decode(json_encode($arr_)) : $arr_;
	}

	//--------------------------------------------------------------------------
	//	array_column のオブジェクト版
	//--------------------------------------------------------------------------
	public static function object_column( $list_, $col_name_, $index_name_ = null )
	{
		$result = array();
		if( count($list_) > 0 )
		{
			if( $index_name_ === null )
			{
				foreach( $list_ as $row )
					$result[] = $row->{$col_name_};
			}
			else
			{
				foreach( $list_ as $row )
					$result[$row->{$index_name_}] = $row->{$col_name_};
			}
		}
		return $result;
	}

	//--------------------------------------------------------------------------
	//	連想配列の配列を、指定したカラムをキーにした配列に変換して返却する
	//--------------------------------------------------------------------------
	public static function array_replace_key( $arr_, $col_name_ )
	{
		$new_list = array();
		if( count($arr_) > 0 )
		{
			foreach( $arr_ as $row )
				$new_list[$row[$col_name_]] = $row;
		}
		return $new_list;
	}

	//--------------------------------------------------------------------------
	//	オブジェクトの配列を、指定したカラムをキーにした配列に変換して返却する
	//--------------------------------------------------------------------------
	public static function object_replace_key( $obj_list_, $col_name_ )
	{
		$new_list = array();
		if( count($obj_list_) > 0 )
		{
			foreach( $obj_list_ as $obj )
				$new_list[$obj->{$col_name_}] = $obj;
		}
		return $new_list;
	}

	//--------------------------------------------------------------------------
	//	多階層の連想配列を xml に出力する
	//
	//
	//	$data = array
	//	(
	//		"result" => "true",
	//		"row" => array
	//		(
	//			"col" => array( "dataA", "dataB" ),
	//		),
	//		"table name='tmp'" => array
	//		(
	//			"row" => array(
	//				"user_id" => "32",
	//				"name" => "taro"
	//			)
	//		)
	//	);
	//	$xml = crow_utility::array_to_xml($data);
	//
	//	とすると、$xmlは次のように構築される。
	//
	//	<result>true</result>
	//	<row>
	//		<col>dataA</col>
	//		<col>dataB</col>
	//	</row>
	//	<table name='tmp'>
	//		<row>
	//			<user_id>32</user_id>
	//			<name>taro</name>
	//		</row>
	//	</table>
	//
	//	となる。
	//
	//--------------------------------------------------------------------------
	public static function array_to_xml( $array_, $parent_key_ = "" )
	{
		if( is_array($array_) )
		{
			$msg = "";
			$is_number = false;
			$index = 0;
			foreach( $array_ as $key => $val )
			{
				if( $index == 0 && $parent_key_!="" && crow_validation::check_num($key) )
				{
					$is_number = true;
				}
				if( $is_number )
				{
					if( $index == 0 )
					{
						$pos = strpos( $parent_key_, " " );
						if( $pos === false ){
							$msg .= array_to_xml($val,$key);
							if( count($array_) > 1 ) $msg .= "</".$parent_key_.">\n";
						}else{
							$msg .= array_to_xml($val,$key);
							if( count($array_) > 1 ) $msg .= "</".substr($parent_key_, 0, $pos).">\n";
						}
					}
					else if( $index == count($array_)-1 )
					{
						$msg .= "<".$parent_key_.">".array_to_xml($val,$key);
					}
					else
					{
						$pos = strpos( $parent_key_, " " );
						if( $pos === false )
						{
							$msg .= "<".$parent_key_.">".array_to_xml($val,$key)."</".$parent_key_.">\n";
						}
						else
						{
							$msg .= "<".$parent_key_.">".array_to_xml($val,$key)."</".substr($parent_key_, 0, $pos).">\n";
						}
					}
				}
				else
				{
					$pos = strpos( $key, " " );
					if( $pos === false )
					{
						$msg .= "<".$key.">".array_to_xml($val,$key)."</".$key.">\n";
					}
					else
					{
						$msg .= "<".$key.">".array_to_xml($val,$key)."</".substr($key, 0, $pos).">\n";
					}
				}
				$index++;
			}
			return $msg;
		}
		return htmlspecialchars($array_);
	}

	//--------------------------------------------------------------------------
	//	多階層の連想配列を json に出力する
	//
	//
	//	$data = array
	//	(
	//		"result" => "true",
	//		"col-list" => array
	//		(
	//			"col" => "dataA",
	//			"col" => "dataB",
	//		)
	//		"table name='tmp'" => array
	//		(
	//			"row" => "tmpA",
	//			"row" => "tmpB",
	//		)
	//	);
	//	$json = crow_utility::array_to_json($data);
	//
	//	とすると、$jsonは次のように構築される。
	//
	//	{
	//		"result" : "true",
	//		"col-list" : {
	//			"col" : "dataA",
	//			"col" : "dataB"
	//		},
	//		"table name='tmp'" : {
	//			"row" : "tmpA",
	//			"row" : "tmpB"
	//		}
	//	}
	//
	//	$int_str_ に、falseを指定すると、数値データは数値として出力されるようになる。
	//	$bool_str_ に、falseを指定すると、二値データはtrue/falseとして出力されるようになる。
	//	これらをtrueに指定した場合には、すべて文字列として出力される。
	//
	//--------------------------------------------------------------------------
	public static function array_to_json( $array_, $int_str_ = true, $bool_str_ = true )
	{
		if( is_array($array_) )
		{
			$ret = '';

			$key_is_index = true;
			$index = 0;
			foreach( $array_ as $key => $val )
			{
				if( $key !== $index )
				{
					$key_is_index = false;
					break;
				}
				$index++;
			}

			if( $key_is_index )
			{
				foreach( $array_ as $key => $val )
				{
					if( strlen($ret) > 0 ) $ret .= ',';
					$ret .= self::array_to_json($val, $int_str_, $bool_str_);
				}
				return '['.$ret.']';
			}
			else
			{
				foreach( $array_ as $key => $val )
				{
					if( strlen($ret) > 0 ) $ret .= ',';
					$ret .= '"'.$key.'":'.self::array_to_json($val, $int_str_, $bool_str_);
				}
				return '{'.$ret.'}';
			}
		}

		if( is_bool($array_) )
		{
			if( $bool_str_ === true )
				return $array_ ? '"true"' : '"false"';
			else
				return $array_ ? 'true' : 'false';
		}
		if( is_int($array_) )
		{
			if( $int_str_ === true )
				return '"'.$array_.'"';
			else
				return $array_;
		}
		if( is_null($array_) )
			return '""';

		$dat = $array_;
		$dat = str_replace( "\\", "\\\\", $dat );
		$dat = str_replace( "\r", "", $dat );
		$dat = str_replace( "\n", "\\n", $dat );
		$dat = str_replace( "\t", "\\t", $dat );
		$dat = str_replace( "\"", "\\\"", $dat );
		return '"'.$dat.'"';
	}

	//--------------------------------------------------------------------------
	//	二次元配列を CSV に出力する
	//
	//
	//	$data = array
	//	(
	//		array("red", "orange", "yellow"),
	//		array("left", "center", "right"),
	//		array("one", "two", "three"),
	//	);
	//	$csv = crow_utility::array_to_csv($data);
	//
	//	とすると、$csvは次のように構築される。
	//
	//		"red","orange","yellow"
	//		"left","center","right"
	//		"one","two","three"
	//
	//	各行は、改行コード"\r\n"で区切られる。
	//	エンコードやエスケープはExcel仕様に合わせる。
	//
	//--------------------------------------------------------------------------
	public static function array_to_csv( $array_, $encode_ = "SJIS-win" )
	{
		$ret = "";
		if( is_array($array_) === true )
		{
			foreach( $array_ as $line )
			{
				if( is_array($line) === true )
				{
					$row = "";
					foreach( $line as $col )
					{
						if( $row != "" ) $row .= ",";
						$col = mb_str_replace('"', '""', $col);
						$col = mb_convert_encoding($col, $encode_, "auto");
						$row .= '"'.$col.'"';
					}
					$ret .= $row."\r\n";
				}
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	数値を指定して、ビットを数値配列に分解する。
	//	下位ビットから巣内にするため、0b00001011 を指定した場合は、
	//	[0] 1
	//	[1] 10
	//	[2] 1000
	//	を返却する。0を指定した場合は全ビットが0のため、空配列が返却される。
	//--------------------------------------------------------------------------
	public static function unpack_bits($num_)
	{
		if( $num_ <= 0 ) return [];

		$bits = [];
		$pos = 0;
		while( $num_ > 0 )
		{
			if( $num_ & 1  == 1 ) $bits[] = 1 << $pos;
			$num_ = $num_ >> 1;
			$pos++;
		}
		return $bits;
	}
}

//	グローバル化
function random_str( $length_ = 32 )
	{return crow_utility::random_str($length_);}

function unique62()
	{return crow_utility::unique62();}

function calc_age( $birth_timestamp_, $now_ = false )
	{return crow_utility::calc_age($birth_timestamp_, $now_);}

function calc_jpyear( $timestamp_ )
	{return crow_utility::calc_jpyear($timestamp_);}

function is_leap_year( $year_ )
	{return crow_utility::is_leap_year($year_);}

function get_days_in_month( $month_, $year_ )
	{return crow_utility::get_days_in_month($month_, $year_);}

function array_to_obj( $arr_ )
	{return crow_utility::array_to_obj($arr_);}

function object_column( $list_, $col_name_, $index_name_ = null )
	{return crow_utility::object_column($list_, $col_name_, $index_name_);}

function array_replace_key( $arr_, $col_name_ )
	{return crow_utility::array_replace_key($arr_, $col_name_);}

function object_replace_key( $obj_list_, $col_name_ )
	{return crow_utility::object_replace_key($obj_list_, $col_name_);}

function array_to_xml( $array_, $parent_key_="" )
	{return crow_utility::array_to_xml($array_, $parent_key_);}

function array_to_json( $array_, $int_str_=true, $bool_str_=true )
	{return crow_utility::array_to_json($array_, $int_str_, $bool_str_);}

function array_to_csv( $array_, $encode_="SJIS-win" )
	{return crow_utility::array_to_csv($array_, $encode_);}

function unpack_bits( $num_ )
	{return crow_utility::unpack_bits($num_);}

?>
