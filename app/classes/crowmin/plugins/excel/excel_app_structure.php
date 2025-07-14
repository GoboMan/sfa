<?php
/*

	ファイル構成書出力

*/

trait	excel_app_structure
{
	private static $m_map_x = 0;
	private static $m_map_y = 0;
	private static $m_map = [];
	private static $m_col_map = [];

	//--------------------------------------------------------------------------
	//	ファイル構成ダウンロードボタン押下時処理
	//--------------------------------------------------------------------------
	public function action_download_app_structure()
	{
		file_put_contents(CROW_PATH."output/logs/system_".date("Ymd").".log","");
		$type = "app_structure";

		//	空シート作成
		self::add_sheet(self::APP_STRUCTURE_SHEET_NAME);

		//	初期ページの削除
		self::remove_sheet(0);

		//	アプリケーションファイル一覧取得
		$all_files = self::get_dir_files_recursive(CROW_PATH);
		$files = [];
		$ret = [];

		//	パス区切りn次元配列生成
		foreach( $all_files as $filepath )
		{
			//	チェックリストに含まれてマッチしていればスルー
			if( self::str_search_in_array($filepath, self::EXCLUDE_LIST) )
				continue;

			//	.から始まるファイルもスルー
			$fname = crow_storage::extract_filename($filepath);
			if( mb_strpos($fname, ".")===0 ) continue;

			$path = mb_substr($filepath, strlen(CROW_PATH));
			$path_row = explode("/", $path);
			$i=0;
			$ret = [];
			self::create_multi_dimension_array($path_row, $ret, $i);
			asort($ret);
			$files = array_merge_recursive($files, $ret);
		}

		//	初期設定
		self::$m_map = [ [self::APP_STRUCTURE_ROOT_NAME] ];
		self::$m_map_y++;

		//	エクセル用二次元配列生成
		self::to_excel_map($files);

		//	シートにデータ反映
		$sheet = self::get_sheet(self::APP_STRUCTURE_SHEET_NAME);
		$sheet->FromArray(self::$m_map, null, self::APP_STRUCTURE_ROOT_CELL);

		//	ファイル保存
		self::save_excel($type);

		//	ファイルダウンロード
		self::download_file($type);
	}

	//--------------------------------------------------------------------------
	//	配列内文字列検索
	//--------------------------------------------------------------------------
	private static function str_search_in_array( $haystack_, $needle_array_ )
	{
		$ret = false;
		foreach( $needle_array_ as $needle )
		{
			if( mb_strpos($haystack_, $needle)!==false )
			{
				$ret = true;
				break;
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	ファイル一覧のn次元配列への変換
	//--------------------------------------------------------------------------
	private static function create_multi_dimension_array( $row_, &$ret_, &$i_ )
	{
		$k = $row_[$i_];
		if( count($row_)==1 )
		{
			$ret_["/"][] = $k;
			return true;
		}
		if( count($row_)-1===$i_ )
		{
			$ret_["/"][] = $k;
			return true;
		}

		$ret_[$k] = [];
		$i_++;
		self::create_multi_dimension_array($row_, $ret_[$k], $i_);
	}

	//--------------------------------------------------------------------------
	//	行埋め
	//--------------------------------------------------------------------------
	private static function create_excel_map_row()
	{
		for( $i=0; $i<self::$m_map_x; $i++)
		{
			if( mb_strlen(self::$m_col_map[$i])>0 )
				self::add_to_map(self::$m_col_map[$i]);
			else
				self::add_to_map("");
		}
	}

	//--------------------------------------------------------------------------
	//	エクセル転写用二次元配列変換
	//--------------------------------------------------------------------------
	private static function to_excel_map( $files_ )
	{
		$index = 0;
		$cnt = count($files_);

		foreach( $files_ as $k => $v )
		{
			if( $k==="/" )
			{
				//	最終要素ではない場合
				if( $cnt-1!=$index )
				{
					foreach( $v as $i => $fname )
					{
						self::create_excel_map_row();

						$con_str = self::STR_TO_ITEM;
						self::add_to_map(array($con_str, $fname));
						self::$m_map_y++;
					}
				}
				else
				{
					$val_cnt = count($v);
					$val_index = 0;
					foreach( $v as $i => $fname )
					{
						self::create_excel_map_row();

						if( $val_index==$val_cnt-1 )
						{
							$con_str = self::STR_END_ITEM;
							self::$m_map_x--;
						}
						else
						{
							$con_str = self::STR_TO_ITEM;
						}

						self::add_to_map($con_str);
						self::add_to_map($fname);
						self::$m_map_y++;
						$val_index++;
					}
				}
				$index++;
				continue;
			}

			$con_str = ($cnt-1==$index) ? self::STR_END_ITEM : self::STR_TO_ITEM;

			if( $con_str===self::STR_TO_ITEM )
				self::$m_col_map[self::$m_map_x] = self::STR_NO_ITEM;
			else if( $con_str===self::STR_END_ITEM )
				self::$m_col_map[self::$m_map_x] = "";

			self::create_excel_map_row();
			self::add_to_map(array($con_str, $k));

			self::$m_map_y++;
			self::$m_map_x++;
			$index++;

			self::to_excel_map($v);

			if( $cnt==$index )
				self::$m_map_x--;
		}
	}

	//--------------------------------------------------------------------------
	//	エクセル転写用用二次元配列への追加
	//--------------------------------------------------------------------------
	private static function add_to_map( $value_ )
	{
		if( is_array($value_) )
		{
			foreach( $value_ as $v )
				self::$m_map[self::$m_map_y][]= $v;
		}
		else
			self::$m_map[self::$m_map_y][]= $value_;
	}
}


?>
