<?php
/*

	DB定義書出力

*/

trait	excel_db
{
	private static $m_config_data = false;
	private static $m_config_user = false;
	private static $m_config_default = false;
	private static $m_history_data = false;
	private static $m_db_design = false;
	private static $m_tables = false;
	private static $m_check_tables = false;
	private static $m_scale = 85;
	private static $m_start_fields = 7;
	private static $m_end_line = false;

	//	リクエスト情報
	private $m_req_page_header = [];
	private $m_req_excel_prop = [];
	private $m_req_history = [];
	private $m_req_tables = [];
	private $m_output_sheets = [];

	//--------------------------------------------------------------------------
	//	初期表示
	//--------------------------------------------------------------------------
	public function action_index()
	{
		//	DB定義書初期化
		self::init_db_defines();

		$config = self::get_config_data();
		$history = self::get_history();
		$tables = self::get_tables();

		//	プロパティは画面に一部だけ表示とする
		$property_rows = [];
		$skip_props =
		[
			"subject",
			"keywords",
			"creator",
			"category",
			"company",
			"revize",
			"version",
			"manager",
		];
		foreach( $config["property"] as $key => $val )
		{
			if( in_array($key, $skip_props, true) ) continue;
			$property_rows[$key] = $val;
		}

		//	ユーザーの設定ファイルに記述があった場合にはデフォルトよりも優先する
		crow_response::set( "page_header_rows", $config["page_header"] );
		crow_response::set( "property_rows", $property_rows );
		crow_response::set( "history_rows", $history );
		crow_response::set( "table_rows", $tables );
	}

	//--------------------------------------------------------------------------
	//	ダウンロードボタン押下時処理
	//--------------------------------------------------------------------------
	public function action_download()
	{
		//	DB定義書初期化
		self::init_db_defines();

		$instance = self::get_self_instance();

		//	POST情報処理
		$instance->m_req_page_header = self::get_request_all("page_header");
		$instance->m_req_excel_prop = self::get_request_all("excel_prop");
		$instance->m_req_history = self::get_request_all("history");
		$instance->m_req_tables = self::get_request_all("tables");

		//	ファイルの作成と更新
		self::make_spreadsheet();

		//	ファイルダウンロード
		self::download_file("database", crow_request::get("document"));
	}

	//--------------------------------------------------------------------------
	//	DB定義書初期化
	//--------------------------------------------------------------------------
	private static function init_db_defines()
	{
		self::load_db_design();
		self::load_config_user();
		self::load_config_default();
		self::load_config_data();
		self::load_history();
	}

	//--------------------------------------------------------------------------
	//	ファイルロード
	//--------------------------------------------------------------------------
	private static function file_load( $path_ )
	{
		if( file_exists($path_) === false ) return [];
		$json = file_get_contents($path_);
		$json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
		$data = json_decode($json, true);
		if( ! $data ) throw new Exception("JSON 構文エラー");
		return $data;
	}

	//--------------------------------------------------------------------------
	//	DBデザインデータロード
	//--------------------------------------------------------------------------
	private static function load_db_design()
	{
		if( ! self::$m_db_design )
		{
			$hdb = crow::get_hdb();
			self::$m_db_design = $hdb->get_design_all();
		}
		return self::$m_db_design;
	}

	//--------------------------------------------------------------------------
	//	設定ファイルの値取得
	//--------------------------------------------------------------------------
	private static function load_config_user()
	{
		//	通常設定ファイル
		$config = self::PATH_CONF.self::NAME_CONF;
		if( file_exists($config) === false )
		{
			touch($config);
		}
		self::$m_config_user = self::file_load($config);

		//	exec用の一時フォルダ作成
		if( file_exists(self::PATH_TMP) === false )
		{
			mkdir(self::PATH_TMP);
		}
		return self::$m_config_user;
	}
//	private static function get_config_user()
//	{
//		return self::$m_config_user;
//	}

	//--------------------------------------------------------------------------
	//	設定ファイルの基本値取得
	//--------------------------------------------------------------------------
	private static function load_config_default()
	{
		$config = self::PATH_CONF.self::NAME_CONF_DEF;
		self::$m_config_default = self::file_load($config);
	}
	private static function get_config_default()
	{
		return self::$m_config_default;
	}

	//--------------------------------------------------------------------------
	//	履歴ファイルの取得
	//--------------------------------------------------------------------------
	private static function load_history()
	{
		if( self::$m_history_data === false )
		{
			$config_history = self::PATH_OUTPUT.self::NAME_HISTORY;
			if( file_exists($config_history) === false )
			{
				if( file_exists(self::PATH_OUTPUT) === false )
				{
					mkdir(self::PATH_OUTPUT);
				}
				touch($config_history);
			}
			$fp = fopen($config_history, "r");
			if( ! $fp ) self::exit("ファイルが読み込めません");
			$history_rows = [];
			while( $row = fgetcsv($fp) )
				$history_rows[$row[0]] = mb_convert_encoding($row, 'UTF-8', 'SJIS-win');

			fclose($fp);
			self::$m_history_data = $history_rows;
		}
	}
	private static function get_history()
	{
		return self::$m_history_data;
	}
	private static function update_history()
	{
		$hdb = crow::get_hdb();
		$config_history = self::PATH_OUTPUT.self::NAME_HISTORY;
		file_put_contents($config_history, crow_utility::array_to_csv(self::$m_history_data));
	}

	//--------------------------------------------------------------------------
	//	テーブル一覧の取得
	//--------------------------------------------------------------------------
	private static function get_tables()
	{
		if( ! self::$m_tables )
		{
			$ret = [];
			foreach( self::$m_db_design as $table => $content )
			{
				$ret[$table] =
				[
					"physical" => $content->name,
					"logical" => $content->logical_name
				];
			}
			self::$m_tables = $ret;
		}
		return self::$m_tables;
	}

	//----------------------------------------------------------------
	//	必要なファイルチェック
	//----------------------------------------------------------------
	private static function check_zip_archiver()
	{
		return class_exists("zipArchive");
	}

	//----------------------------------------------------------------
	//	改行コード取得
	//----------------------------------------------------------------
	private static function get_line_code()
	{
		return self::check_zip_archiver() ? "\r\n" : "\n";
	}
	//----------------------------------------------------------------
	//	改行コード変更 excel内改行のため
	//----------------------------------------------------------------
	private static function change_line_code( $val_ )
	{
		return preg_replace("/\r\n/", "\n", $val_);
	}
	//----------------------------------------------------------------
	//	改行コード数取得 excel内改行のため
	//----------------------------------------------------------------
	private static function get_line_code_num( $val_ )
	{
		return substr_count($val_, "\n");
	}

	//----------------------------------------------------------------
	//	リクエスト一括処理
	//----------------------------------------------------------------
	private static function get_request_all( $key_ )
	{
		$ret = [];
		if( $key_ === "page_header" )
		{
			$req_names = array_keys(self::get_config_default()["page_header"]);
			foreach( $req_names as $name )
			{
				$ret[$name] = crow_request::get($name);
			}
		}
		elseif( $key_==="excel_prop" )
		{
			//	load_config_dataでなぜかm_config_defaultが上書きされるので強制更新
			self::load_config_default();
			$req_names = array_keys(self::get_config_default()["property"]);
			foreach( $req_names as $name )
			{
				$default_prop = self::get_config_default()["property"][$name]["val"];
				$ret[$name] = crow_request::get($name, $default_prop);
			}
		}
		elseif( $key_==="history" )
		{
			$req_history = [];
			$new_date = date("Y/m/d");
			$new_message = crow_request::get("new_message", "");

			//	内容編集があれば上書きして返却
			$history = self::get_history();
			$history_no_row = array_column($history, 0);
			$current_no = count($history_no_row) > 0 ? intval($history_no_row[count($history_no_row) - 1]) : 1;
			$new_history = [];
			for( $i = 0; $i <= $current_no; $i++ )
			{
				if( isset($history[$i]) === false ) continue;
				$msg = crow_request::get("update_msg_".$i, $history[$i][2]);
				$new_history[] = array($history[$i][0], $history[$i][1], $msg);
			}

			if( strlen($new_message) <= 0 )
			{
				self::$m_history_data = $new_history;
				return $new_history;
			}

			//	新しい内容があれば追加して返却
			$next_no = $current_no + 1;
			$req_history = array($next_no, $new_date, $new_message);
			array_unshift($new_history, array_values($req_history));
			self::$m_history_data = $new_history;
			return $new_history;
		}
		elseif( $key_ === "tables" )
		{
			//	array( physical => array(physical, logical) )
			$table_names = array_keys(self::get_tables());
			self::$m_check_tables = [];
			foreach( $table_names as $name )
			{
				if( crow_request::get("checked_".$name, false) )
				{
					self::$m_check_tables[$name] = self::$m_db_design[$name];
				}
			}
			$ret = self::$m_check_tables;
		}
		return $ret;
	}

	//----------------------------------------------------------------
	//	設定値の上書き
	//----------------------------------------------------------------
	private static function load_config_data()
	{
		if( self::$m_config_user === false ) return false;

		self::$m_config_data = [];
		$overwrite_skip_list =
		[
			"#comment", "system_info", "history",
			"basics", "page_table", "layout"
		];

		foreach( self::$m_config_default as $key => $row )
		{
			self::$m_config_data[$key] = $row;
			if( in_array($key, $overwrite_skip_list) ) continue;
			//	スタイルはそのまま上書き
			if( $key==="style" )
			{
				self::$m_config_data[$key] = self::$m_config_user[$key];
				self::$m_config_data["border"] = self::get_convert_border_style();
				continue;
			}
			else
			{
				//	ヘッダとプロパティ設定
				$row_keys = array_keys((array)$row);
				foreach( $row_keys as $k )
				{
					if( isset(self::$m_config_data[$key]) === true && isset(self::$m_config_data[$key][$k]) === true && isset(self::$m_config_data[$key][$k]["val"]) === true )
					{
						if( isset(self::$m_config_user[$key]) === true && isset(self::$m_config_user[$key][$k]) === true )
						{
							//	ここでなぜかm_config_defaultが上書きされる...
							self::$m_config_data[$key][$k]["val"] = self::$m_config_user[$key][$k];
						}
					}
				}
			}
		}
	}
	private static function get_config_data()
	{
		return self::$m_config_data;
	}

	//--------------------------------------------------------------------------
	//	出力シートの取得
	//--------------------------------------------------------------------------
	private static function get_output_sheets()
	{
		$instance = self::get_self_instance();
		if( ! $instance->m_output_sheets )
		{
			$sheets = [];
			//	基本の3シート(表紙、改版履歴、テーブル一覧)の追加
			foreach( self::$m_config_data["basics"] as $v ) $sheets[] = $v;

			//	各テーブルのシートのシート名の設定
			if( isset($instance->m_req_tables) === true && is_array($instance->m_req_tables) === true )
			{
				foreach( $instance->m_req_tables as $data )
				{
					$sheets[] = strlen($data->logical_name) > 0
						? $data->logical_name
						: $data->name
						;
				}
			}
			else
			{
				crow_log::notice("not array req_tables");
			}
			$instance->m_output_sheets = $sheets;
		}
		return $instance->m_output_sheets;
	}

	//----------------------------------------------------------------
	//	テーブル定義書の作成基底
	//----------------------------------------------------------------
	private static function make_spreadsheet()
	{
		$type = "database";

		//	エクセルファイルのプロパティの設定
		self::add_file_property();

		//	空シート作成
		self::add_empty_sheets();

		//	シートの基本スタイルの設定
		self::set_basic_sheet_style();

		//	表紙作成
		self::make_cover_sheet();

		//	改版履歴のシート作成
		self::make_history_sheet();

		//	テーブル一覧のシート作成
		self::make_table_list_sheet();

		//	各テーブルのシート作成
		self::make_table_sheets();

		//	初期ページの削除
		self::remove_sheet(0);

		//	固定パスへと出力
		self::save_excel($type);

		//	改版履歴ファイル更新
		self::update_history();

	}

	//--------------------------------------------------------------------------
	//	空シート作成
	//--------------------------------------------------------------------------
	private static function add_empty_sheets()
	{
		//	各シートの追加
		self::add_sheets( self::get_output_sheets() );
	}

	//--------------------------------------------------------------------------
	//	表紙作成
	//	(D10,AJ12)=>クライアント名, (D13,AJ15)=>タイトル, (D16,AJ18)=>ドキュメント
	//	(D35,AJ37)=>作成者
	//--------------------------------------------------------------------------
	private static function make_cover_sheet()
	{
		$instance = self::get_self_instance();
		$sheet_name = self::$m_config_data["basics"]["cover_page"];
		$sheet = self::get_sheet($sheet_name);
		$focus_cell = self::$m_config_data["layout"]["focus"];
		$printarea = self::$m_config_data["layout"]["cover_page"]["printarea"];

		//	マージ
		$title_col = self::$m_config_data["layout"]["cover_page"]["title"]["col"];
		$title_row = self::$m_config_data["layout"]["cover_page"]["title"]["row"];
		$merge_rows = self::convert_row_of_fixed_col($title_col, $title_row);
		self::merge_from_array( $sheet, $merge_rows );

		//	値セット
		$input_cells = self::get_inputables_from_merged( $merge_rows );

		$input_vals =
		[
			$instance->m_req_page_header["client"],
			$instance->m_req_page_header["project"],
			$instance->m_req_page_header["document"],
			$instance->m_req_page_header["entrustment"],
		];
		$input_rows = self::get_convert_input_row($input_cells, $input_vals);
		self::set_vals( $sheet, $input_rows );

		//	boldで中央寄せとする
		$set_style =
		[
			"alignment" => self::get_style_alignment("center_middle"),
			"font" => self::get_style_font( ["size" => 18, "bold" => true] )
		];
		foreach( $input_cells as $cell )
		{
			$sheet->getStyle( $cell )->applyFromArray($set_style);
		}

		self::set_focus( $sheet, $focus_cell );
		self::set_scale($sheet_name, self::$m_scale);

		//	改ページプレビューを設定
		$print_range = $printarea;
		self::set_printarea($print_range);
	}

	//--------------------------------------------------------------------------
	//	改版履歴シート作成
	//--------------------------------------------------------------------------
	private static function make_history_sheet()
	{
		$instance = self::get_self_instance();
		$history_name = self::$m_config_data["basics"]["history_page"];
		$focus_cell = self::$m_config_data["layout"]["focus"];
		$layout = self::$m_config_data["layout"]["history_page"];
		$style = self::$m_config_data["style"];
		$printarea = $layout["printarea"];
		$sheet = self::get_sheet($history_name);

		//	スケール変更
		self::set_scale($history_name, self::$m_scale);

		//	ヘッダー作成
		self::make_header($sheet);

		//	改版履歴の文字列セット(A4)
		$title_cell = $layout["title"];
		self::set_val($sheet, [$title_cell => $history_name] );
		$set_style = ["font" => ["bold" => true]];
		$sheet->getStyle($title_cell)->applyFromArray($set_style);

		//	6から
		$data = $instance->m_req_history;
		$cnt = count( (array) $data );
		$begin = $layout["content"]["begin"];
		$no = $begin;
		$merge_cells = $layout["content"]["cols"];
		$merge_layout = [];
		for( $i=0; $i<$cnt+1; $i++ )
		{
			$merge_layout[$no] = $merge_cells;
			$no++;
		}
		$merge_rows = self::convert_to_merge_cells( $merge_layout );
		self::merge_from_array( $sheet, $merge_rows );

		//	値セット,更新日時と内容
		$input_cells = self::get_inputables_from_merged( $merge_rows );
		$input_vals = $layout["content"]["header"];

		for( $i = 0; $i < $cnt + 1; $i++ )
		{
			if( isset($data[$i]) === true )
			{
				$input_vals[] = $data[$i][1];
				$input_vals[] = $data[$i][2];
			}
		}
		$input_rows = self::get_convert_input_row($input_cells, $input_vals);
		self::set_vals( $sheet, $input_rows, ["br" => true] );

		//	タイトルの背景色設定
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
		];
		foreach( $merge_rows as $cell )
			$sheet->getStyle( $cell )->applyFromArray($set_style);

		//	ヘッダーの背景色設定
		$fill_color = substr($style["fill"]["color"], 1);
		$set_style =
		[
			"fill" => self::get_style_fill( ["color" => $fill_color, "type" => "solid"] ),
		];

		//	色塗り対象のセルの取得
		$fill_row_key = array_keys($merge_layout)[0];
		$fill_layout = [$fill_row_key => $merge_layout[$fill_row_key]];
		$fill_rows = self::convert_to_merge_cells( $fill_layout );
		foreach( $fill_rows as $cell )
			$sheet->getStyle( $cell )->applyFromArray($set_style);

		self::set_focus( $sheet, $focus_cell );

		//	改ページプレビューを設定
		$print_range = str_replace("%end_row%", $sheet->getHighestDataRow() + 1, $printarea);
		self::set_printarea($print_range);
	}

	//--------------------------------------------------------------------------
	//	テーブル一覧シート作成
	//--------------------------------------------------------------------------
	private static function make_table_list_sheet()
	{
		$instance = self::get_self_instance();
		$tables_name = self::$m_config_data["basics"]["table_list_page"];
		$focus_cell = self::$m_config_data["layout"]["focus"];
		$layout = self::$m_config_data["layout"]["table_list_page"];
		$style = self::$m_config_data["style"];
		$printarea = $layout["printarea"];
		$sheet = self::get_sheet($tables_name);

		//	スケール変更
		self::set_scale($tables_name, self::$m_scale);

		//	ヘッダー作成
		self::make_header($sheet);

		//	テーブル一覧見出し設定 4
		$tables_start_line = $layout["title"]["begin"];
		$target_cell = explode(",", $layout["title"]["col"]);

		//	(A%d:BG%d)
		$target_cell = $target_cell[0]."%d:".$target_cell[1]."%d";
		$title_cell = sprintf($target_cell, $tables_start_line, $tables_start_line);
		$fill_color = substr($style["fill"]["color"], 1);
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
			"fill" => self::get_style_fill( ["color" => $fill_color, "type" => "solid"] ),
		];
		//	テーブル一覧の見出し作成
		self::merge_and_input_value( $sheet, $title_cell, $tables_name, $set_style);

		//	テーブルの一覧
		$tables_data = $instance->m_req_tables;
		$start_line = $tables_start_line + 2;
		$no = $start_line;
		//	("B,C","D,M","N,W")
		$definition_row = $layout["definition"]["cols"];
		$definition_cols = [$start_line => $definition_row];
		$definition_cells = self::convert_to_merge_cells( $definition_cols );

		$set_style_center = ["alignment"=> self::get_style_alignment("center_middle")];
		//	("No.","論理名","物理名")
		$input_values = $layout["definition"]["header"];
		$index_center = [0];
		foreach( $definition_cells as $i => $cell )
		{
			$val = $input_values[$i];
			self::merge_and_input_value( $sheet, $cell, $val, $set_style );
			if( in_array($i, $index_center) )
			{
				$sheet->getStyle( $cell )->applyFromArray( $set_style_center );
			}
		}

		//	一覧のスタイル設定
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"font" => self::get_style_font( ["size" => 10] ),
		];
		$set_style_thin =
		[
			"borders" => self::get_style_border( ["border_type" => "bottom", "type" => "thin"] ),
		];
		$set_style_left =
		[
			"borders" => self::get_style_border( ["border_type" => "left", "type" => "thin"] ),
		];
		$set_style_right =
		[
			"borders" => self::get_style_border( ["border_type" => "right", "type" => "thin"] ),
		];
		$set_style_bottom =
		[
			"borders" => self::get_style_border( ["border_type" => "bottom", "type" => "dotted"] ),
		];
		$set_style_align_right = ["alignment" => self::get_style_alignment("right_middle")];

		$line_no = $start_line;
		$no = 1;
		$cnt = count( $tables_data );
		$output_sheets = self::get_output_sheets();

		foreach( $tables_data as $table )
		{
			$line_no++;

			//	入力値
			$logical = $table->logical_name;
			$physical = $table->name;

			//	設定
			$definition_cols = [$line_no => $definition_row];
			$definition_cells = self::convert_to_merge_cells( $definition_cols );
			$definition_values = [$no, $logical, $physical];
			foreach( $definition_cells as $j => $cell )
			{
				$val = $definition_values[$j];
				self::merge_and_input_value( $sheet, $cell, $val, $set_style );

				$sheet->getStyle( $cell )->applyFromArray( $set_style_bottom );
				$sheet->getStyle( $cell )->applyFromArray( $set_style_left );
				$sheet->getStyle( $cell )->applyFromArray( $set_style_right );

				if( $no===$cnt ) $sheet->getStyle( $cell )->applyFromArray( $set_style_thin );

				if( $j===0 )
				{
					$sheet->getStyle( $cell )->applyFromArray( $set_style_align_right );

					//	ハイパーリンク設定
					$sheet_name = in_array($table->name, $output_sheets) ? $table->name : $table->logical_name;
					$input_cell = self::get_inputable_from_merged( $cell );
					self::set_sheet_url( $input_cell, $sheet_name, $sheet );
				}
			}
			$no++;
		}

		//	フィールド数+1の外枠にラインを付ける
		$outline_range = sprintf($target_cell,$tables_start_line, $line_no+1);
		$set_style_outline = ["borders" => self::get_style_border( ["border_type" => "outline", "type" => "thin"])];
		$sheet->getStyle( $outline_range )->applyFromArray( $set_style_outline );

		self::set_focus( $sheet, $focus_cell );

		//	改ページプレビューを設定
		$print_range = str_replace("%end_row%", $sheet->getHighestDataRow() + 1, $printarea);
		self::set_printarea($print_range);
	}

	//--------------------------------------------------------------------------
	//	各テーブルのシート作成
	//--------------------------------------------------------------------------
	private static function make_table_sheets()
	{
		$instance = self::get_self_instance();
		foreach( $instance->m_req_tables as $table_obj )
			self::make_table_sheet( $table_obj );
	}

	//--------------------------------------------------------------------------
	//	各テーブルのページ作成
	//--------------------------------------------------------------------------
	private static function make_table_sheet( $table_obj_ )
	{
		$sheet_name = in_array($table_obj_->name, self::get_output_sheets())
			? $table_obj_->name
			: $table_obj_->logical_name
			;
		$sheet = self::get_sheet( $sheet_name );
		$focus_cell = self::$m_config_data["layout"]["focus"];

		//	スケール変更
		self::set_scale($sheet_name, self::$m_scale);

		//	テーブル名からシート取得
		self::make_header( $sheet, $table_obj_ );

		//	概要作成
		self::make_overview( $sheet, $table_obj_->name, $sheet_name );

		//	フィールド定義作成
		self::make_fields( $sheet, $table_obj_->name, $sheet_name );

		//	インデックス作成
		self::make_index( $sheet, $table_obj_->name );

		self::set_focus( $sheet, $focus_cell );
	}

	//--------------------------------------------------------------------------
	//	ヘッダ作成
	//--------------------------------------------------------------------------
	private static function make_header( $sheet_, $table_obj_ = false )
	{
		$instance = self::get_self_instance();
		$header = self::$m_config_data["page_header"];
		$tables_sheet_name = self::$m_config_data["basics"]["table_list_page"];
		$layout = self::$m_config_data["layout"]["common"]["header"];
		$style = self::$m_config_data["style"];

		//	マージ 1 => array("A,H","I,P","Q,AB","AC,AN","AO,BG"), 2=>[]
		$merge_layout =
		[
			$layout["rows"][0] => $layout["cols"],
			$layout["rows"][1] => $layout["cols"]
		];

		//	論理名、物理名
		$header_logical = $layout["vals"][0];
		$header_physical = $layout["vals"][1];
		$logical_name = $table_obj_ !== false ? $table_obj_->logical_name : "";
		$physical_name = $table_obj_ !== false ? $table_obj_->name : "";

		$merge_rows = self::convert_to_merge_cells( $merge_layout );
		self::merge_from_array( $sheet_, $merge_rows );

		//	値セット
		$input_cells = self::get_inputables_from_merged( $merge_rows );

		$input_vals =
		[
			$header["document"]["name"], $header["project"]["name"], $header_logical, $header_physical, "",
			$instance->m_req_page_header["document"], $instance->m_req_page_header["project"], $logical_name, $physical_name, "",
		];
		$input_rows = self::get_convert_input_row($input_cells, $input_vals);
		self::set_vals( $sheet_, $input_rows );

		//	ハイパーリンク設定
		self::set_sheet_url( $input_cells[5], $tables_sheet_name, $sheet_ );

		//	ヘッダーのボーダー設定
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
		];
		foreach( $merge_rows as $cell )
			$sheet_->getStyle( $cell )->applyFromArray($set_style);

		//	ヘッダーの背景色設定
		$fill_color = substr($style["fill"]["color"], 1);
		$set_style =
		[
		//	"borders" => self::get_style_border(array("type"=>"thin")),
			"fill" => self::get_style_fill( ["color" => $fill_color, "type" => "solid"] ),
		];
		//	色塗り対象のセルの取得
		$fill_row_key = array_keys($merge_layout)[0];
		$fill_layout = [$fill_row_key, $merge_layout[$fill_row_key]];

		$fill_rows = self::convert_to_merge_cells( $fill_layout );
		foreach( $fill_rows as $cell )
			$sheet_->getStyle( $cell )->applyFromArray($set_style);
	}

	//--------------------------------------------------------------------------
	//	概要作成
	//--------------------------------------------------------------------------
	private static function make_overview( $sheet_, $physical_name_, $sheet_name_, $overview_="" )
	{
		$instance = self::get_self_instance();
		$layout = self::$m_config_data["layout"]["table_page"]["overview"];

		//	概要見出し設定"A4:BG4"
		$title_cell = $layout["title"]["cell"];
		$title = self::$m_config_data["page_table"]["overview"];
		$title_set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
			"fill" => self::get_style_fill( ["color" => "d0cece", "type" => "solid"] ),
		];

		//	概要の見出し作成
		self::merge_and_input_value( $sheet_, $title_cell, $title, $title_set_style);

		//	概要の内容部分設定"A5:BG5";60,5
		$overview_cell = $layout["content"]["cell"];
		$row_height = $layout["row_height"];
		$line = $layout["line"];
		$remark = $instance->m_req_tables[$physical_name_]->remark;
		$set_style =
		[
			"alignment" => self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
		];

		self::merge_and_input_value( $sheet_, $overview_cell, $remark, $set_style );
		self::set_row_height( $sheet_name_, $row_height, $line);
	}

	//--------------------------------------------------------------------------
	//	フィールド定義作成
	//--------------------------------------------------------------------------
	private static function make_fields( $sheet_, $physical_name_, $sheet_name_ )
	{
		$instance = self::get_self_instance();
		$table_obj = $instance->m_req_tables[$physical_name_];
		$layout = self::$m_config_data["layout"]["table_page"]["fields"];
		$style = self::$m_config_data["style"];

		//	フィールド定義見出し設定"A7:BG7";
		$field_start_line = self::$m_start_fields;
		$target_cell = explode(",", $layout["title"]["col"]);
		//	(A%d:BG%d)
		$target_cell = $target_cell[0]."%d:".$target_cell[1]."%d";
		$title_cell = sprintf($target_cell,$field_start_line,$field_start_line);
		$title = self::$m_config_data["page_table"]["fields_title"];

		$fill_color = substr($style["fill"]["color"], 1);
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
			"fill" => self::get_style_fill( ["color" => $fill_color, "type" => "solid"] ),
		];
		//	フィールド定義の見出し作成
		self::merge_and_input_value( $sheet_, $title_cell, $title, $set_style);

		//	フィールド定義の見出し部分設定
		$start_line = $field_start_line + 2;
		$definition_row = $layout["content"]["cols"];
	//	$definition_row = ["B,C","D,M","N,W","X,AB","AC,AE","AF,AG","AH,AI","AJ,AK","AL,AO","AP,BF"];
		$definition_cols = [$start_line => $definition_row];
		$definition_cells = self::convert_to_merge_cells( $definition_cols );

		$set_style_center = ["alignment" => self::get_style_alignment("center_middle")];
		$field_values = self::$m_config_data["page_table"]["fields"];
		$index_center = [0, 4, 5, 6];
		foreach( $definition_cells as $i => $cell )
		{
			$val = $field_values[$i];
			self::merge_and_input_value( $sheet_, $cell, $val, $set_style );
			if( in_array($i, $index_center) )
			{
				$sheet_->getStyle( $cell )->applyFromArray( $set_style_center );
			}
		}

		//	フィールド定義の内容部分設定
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"font" => self::get_style_font( ["size" => 10] ),
		];
		$set_style_thin =
		[
			"borders" => self::get_style_border( ["border_type" => "bottom", "type" => "thin"] ),
		];
		$set_style_left =
		[
			"borders" => self::get_style_border( ["border_type" => "left", "type" => "thin"] ),
		];
		$set_style_right =
		[
			"borders" => self::get_style_border( ["border_type" => "right", "type" => "thin"] ),
		];
		$set_style_bottom =
		[
			"borders" => self::get_style_border( ["border_type" => "bottom", "type" => "dotted"] ),
		];
		$set_style_align_right = ["alignment" => self::get_style_alignment("right_middle")];
		$set_style_align_center = ["alignment" => self::get_style_alignment("center_middle")];

		$line_no = $start_line;
		$fields = $table_obj->fields;
		$table_name = $table_obj->name;
		$must_is_notnull = "";
		$no = 1;
		$mark_ok = "○";
		$mark_ng = "×";
		$centering_index = [4, 5, 6, 7, 8];
		$field_array = (array) $fields;
		$field_num = count($field_array);
		$remark_max_line_len = 50;
		foreach( $fields as $field )
		{
			$line_no++;

			$func = "model_".$table_name."::get_".$field->name."_map";
			$const_map = is_callable($func) === true ? call_user_func($func) : [];
			$default_value = $field->default_value;
			if( is_string($field->default_value) === true )
				$default_value = str_replace("self", "model_".$table_name, $field->default_value);

			//	入力値
			$logical = $field->logical_name;
			$physical = $field->name;
			$type = self::get_column_type($field->type);
			$size = self::get_column_size($field->type, $field->size);
			$pk = $field->primary_key == 1 ? $mark_ok : "";
			$ai = $field->auto_increment == 1 ? $mark_ok : "";
			$null = $field->nullable == 1 ? $mark_ok : $mark_ng;
			$default = self::get_column_default($field->type, $default_value);
			$remark = self::get_column_remark($field->remark, $const_map);
			$remark_len = mb_strwidth($remark);
			$line_cnt = $remark_len > 0 ? ceil($remark_len / $remark_max_line_len) : 1;
			$row_height = $layout["row_height"] * $line_cnt;
			self::set_row_height( $sheet_name_, $row_height, $line_no);

			//	設定
			$definition_cols = [$line_no => $definition_row];
			$definition_cells = self::convert_to_merge_cells( $definition_cols );
			$definition_values =
			[
				$no, $logical, $physical, $type, $size,
				$pk, $ai, $null, $default, $remark
			];
			foreach( $definition_cells as $j => $cell )
			{
				$val = $definition_values[$j];
				self::merge_and_input_value( $sheet_, $cell, $val, $set_style );

				$sheet_->getStyle( $cell )->applyFromArray( $set_style_bottom );
				$sheet_->getStyle( $cell )->applyFromArray( $set_style_left );
				$sheet_->getStyle( $cell )->applyFromArray( $set_style_right );
				$sheet_->getStyle( $cell )->getAlignment()->setWrapText(true);

				if( $no === $field_num ) $sheet_->getStyle( $cell )->applyFromArray( $set_style_thin );

				if( in_array($j, $centering_index) ) $sheet_->getStyle( $cell )->applyFromArray( $set_style_align_center );
				elseif( $j===0 ) $sheet_->getStyle( $cell )->applyFromArray( $set_style_align_right );
			}
			$no++;
		}

		//	フィールド数+1の外枠にラインを付ける
		$outline_range = sprintf($target_cell,$field_start_line, $line_no+1);
		$set_style_outline = ["borders" => self::get_style_border( ["border_type" => "outline", "type" => "thin"] )];
		$sheet_->getStyle( $outline_range )->applyFromArray( $set_style_outline );

		//	最終行の更新
		self::$m_end_line = $line_no+1;
	}

	//--------------------------------------------------------------------------
	//	indexの行生成
	//--------------------------------------------------------------------------
	private static function make_index( $sheet_, $physical_name_ )
	{
		$instance = self::get_self_instance();
		$table_obj = $instance->m_req_tables[$physical_name_];
		$layout = self::$m_config_data["layout"]["table_page"]["index"];
		$printarea = self::$m_config_data["layout"]["table_page"]["printarea"];
		$style = self::$m_config_data["style"];

		//	フィールド定義見出し設定
		$index_start_line = self::$m_end_line+2;
		$target_cell = explode(",", $layout["title"]["col"]);
		//	(A%d:BG%d)
		$target_cell = $target_cell[0]."%d:".$target_cell[1]."%d";
		$title_cell = sprintf($target_cell,$index_start_line,$index_start_line);
		$title = self::$m_config_data["page_table"]["index_title"];

		$fill_color = substr($style["fill"]["color"], 1);
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"borders" => self::get_style_border( ["type" => "thin"] ),
			"font" => self::get_style_font( ["size" => 10] ),
			"fill" => self::get_style_fill( ["color" => $fill_color, "type" => "solid"] ),
		];
		//	インデックス定義の見出し作成
		self::merge_and_input_value( $sheet_, $title_cell, $title, $set_style);

		//	インデックス定義の見出し部分設定
		$start_line = $index_start_line+2;
		$definition_row = $layout["content"]["cols"];
	//	$definition_row = array("B,C","D,M","N,R","S,AB","AC,AL","AM,AV","AW,BF");
		$definition_cols = [$start_line => $definition_row];
		$definition_cells = self::convert_to_merge_cells( $definition_cols );

		$set_style_center = ["alignment" => self::get_style_alignment("center_middle")];
		$field_values = self::$m_config_data["page_table"]["indexes"];
		$index_center = [0];
		foreach( $definition_cells as $i => $cell )
		{
			$val = $field_values[$i];
			self::merge_and_input_value( $sheet_, $cell, $val, $set_style );
			if( in_array($i, $index_center) )
			{
				$sheet_->getStyle( $cell )->applyFromArray( $set_style_center );
			}
		}

		//	インデックス定義の内容部分設定
		$set_style =
		[
			"alignment"=> self::get_style_alignment("left_middle"),
			"font" => self::get_style_font( ["size" => 10] ),
		];
		$set_style_thin =
		[
			"borders" => self::get_style_border( ["border_type" => "bottom", "type" => "thin"] ),
		];
		$set_style_left =
		[
			"borders" => self::get_style_border( ["border_type" => "left", "type" => "thin"] ),
		];
		$set_style_right =
		[
			"borders" => self::get_style_border( ["border_type" => "right", "type" => "thin"] ),
		];
		$set_style_bottom =
		[
			"borders" => self::get_style_border( ["border_type" => "bottom", "type" => "dotted"] ),
		];
		$set_style_align_right = ["alignment" => self::get_style_alignment("right_middle")];
	//	$set_style_align_center = ["alignment" => self::get_style_alignment("center_middle")];

		//	各種別のインデックス
		$pk = is_array($table_obj->primary_key) ? $table_obj->primary_key : [$table_obj->primary_key];
		$indexes = $table_obj->indexes;
		$indexes_unq = $table_obj->indexes_with_unique;
		$index_all = array_merge($indexes, $indexes_unq);
		ksort($index_all);
		array_unshift($index_all, $pk);
		//	インデックス数
	//	$cnt_pk = count($pk);
	//	$cnt_indexes = count($indexes);
	//	$cnt_indexes_unq = count($indexes_unq);
		$cnt_all = count($index_all);

		$line_no = $start_line;
		$no = 1;
		$i = 0;
	//	$align_right_index = array(0);
		foreach( $index_all as $index_name => $index_row )
		{
			$line_no++;
			if( (intval($no) - 1) === 0 )
			{
				$index_name = "primary_key";
				$index_type = "primary_key";
			}
			elseif( array_key_exists($index_name, $indexes_unq) )
			{
				$index_type = count($index_row) <= 1 ? "unique single" : "unique multi";
			}
			else
			{
				$index_type = count($index_row) <= 1 ? "single" : "multi";
			}
			array_unshift($index_row, $index_type);
			array_unshift($index_row, $index_name);
			array_unshift($index_row, $no);

			//	設定
			$definition_cols = [$line_no => $definition_row];
			$definition_cells = self::convert_to_merge_cells( $definition_cols );
			$definition_values = $index_row;
			foreach( $definition_cells as $j => $cell )
			{
				$val = isset($definition_values[$j]) === true ? $definition_values[$j] : "";
				self::merge_and_input_value( $sheet_, $cell, $val, $set_style );

				$sheet_->getStyle( $cell )->applyFromArray( $set_style_bottom );
				$sheet_->getStyle( $cell )->applyFromArray( $set_style_left );
				$sheet_->getStyle( $cell )->applyFromArray( $set_style_right );

				if( $no === $cnt_all ) $sheet_->getStyle( $cell )->applyFromArray( $set_style_thin );

				if( $j === 0 ) $sheet_->getStyle( $cell )->applyFromArray( $set_style_align_right );
			}
			$no++;
		}

		//	フィールド数+1の外枠にラインを付ける
		$outline_range = sprintf($target_cell, $index_start_line, $line_no+1);
		$set_style_outline = ["borders" => self::get_style_border( ["border_type" => "outline", "type" => "thin"] )];
		$sheet_->getStyle( $outline_range )->applyFromArray( $set_style_outline );

		//	インデックスで終わりなので改ページプレビューを設定
		$print_range = str_replace("%end_row%", $sheet_->getHighestDataRow() + 1, $printarea);
		self::set_printarea($print_range);
	}

	//--------------------------------------------------------------------------
	//	シートの追加
	//--------------------------------------------------------------------------
	private static function add_sheet( $title_=false )
	{
		$excel = self::get_spreadsheet();
		if( $title_ )
			$excel->createSheet()->setTitle($title_);
		else
			$excel->createSheet();
	}
	private static function add_sheets( $rows_ )
	{
		foreach( $rows_ as $title ) self::add_sheet( $title );
	}

	//--------------------------------------------------------------------------
	//	シートの取得
	//--------------------------------------------------------------------------
	private static function get_sheet( $key_ )
	{
		$excel = self::get_spreadsheet();
		$sheet = false;
		if( is_int($key_) )
			$sheet = $excel->getSheetByIndex($key_);
		elseif( is_string($key_) )
			$sheet = $excel->getSheetByName($key_);
		return $sheet;
	}

	//--------------------------------------------------------------------------
	//	シートの削除
	//--------------------------------------------------------------------------
	private static function remove_sheet( $key_ )
	{
		$excel = self::get_spreadsheet();
		$sheet = false;
		if( is_int($key_) )
			$sheet = $excel->removeSheetByIndex($key_);
		elseif( is_string($key_) )
			$sheet = $excel->removeSheetByName($key_);
		return $sheet;
	}

	//--------------------------------------------------------------------------
	//	セルにフォーカス
	//--------------------------------------------------------------------------
	private static function set_focus( $sheet_, $cell_ )
	{
		$sheet_->getStyle( $cell_ );
	}

	//--------------------------------------------------------------------------
	//	セルに値のセット ["A3" => "value"];
	//--------------------------------------------------------------------------
	private static function set_val( $sheet_, $row_, $args_=[] )
	{
		foreach( $row_ as $k => $v )
		{
			if( isset($args_["br"]) )
			{
				$v = self::change_line_code($v);
				self::set_height_with_lines($sheet_, $k, $v);
			}
			$sheet_->setCellValue( $k, $v );
		}
	}
	private static function set_vals( $sheet_, $rows_, $args_=[] )
	{
		foreach( $rows_ as $k => $v )
			self::set_val( $sheet_, [$k => $v], $args_ );
	}

	//--------------------------------------------------------------------------
	//	セル内改行スタイル調整
	//--------------------------------------------------------------------------
	private static function set_height_with_lines($sheet_, $cell_, $val_ )
	{
		$line_num = self::get_line_code_num($val_);
		$sheet_->getStyle($cell_)->getAlignment()->setWrapText(true);
		$line = preg_replace("/[^0-9]/", '', $cell_);
		$line_height = self::$m_config_data["style"]["cell"]["height"];
		$set_height = $line_height * ($line_num+1);
		$sheet_->getRowDimension($line)->setRowHeight($set_height);
	}

	//--------------------------------------------------------------------------
	//	セルのマージ ["A1:B1", "C1:E3"]
	//--------------------------------------------------------------------------
	private static function merge( $sheet_, $row_ )
	{
		$sheet_->mergeCells( $row_ );
	}
	private static function merge_from_array( $sheet_, $rows_ )
	{
		foreach( $rows_ as $row ) self::merge( $sheet_, $row );
	}

	//--------------------------------------------------------------------------
	//	結合範囲の入力セルの取得
	//--------------------------------------------------------------------------
	private static function get_inputable_from_merged( $range_ )
	{
		$cells = explode(":", $range_);
		return $cells[0];
	}
	private static function get_inputables_from_merged( $rows_ )
	{
		$ret = [];
		foreach( $rows_ as $row )
		{
			$ret[] = self::get_inputable_from_merged($row);
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	配列からの変換（行番号=>結合セル配列）1=>["a,c","d,f"]
	//--------------------------------------------------------------------------
	private static function convert_to_merge_cells( $rows_ )
	{
		$ret = [];
		foreach( $rows_ as $line_no => $cells )
		{
			if( ! is_array($cells) ) continue;
			foreach( $cells as $cell )
			{
				$cols = explode(",", $cell);
				$ret[] = $cols[0].$line_no.":".$cols[1].$line_no;
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	配列でのセルへの入力
	//--------------------------------------------------------------------------
	private static function get_convert_input_row( $cell_row_, $val_row_ )
	{
		$cnt = count($cell_row_);
		$ret = [];
		for( $i = 0; $i < $cnt; $i++ )
		{
			$ret[$cell_row_[$i]] = $val_row_[$i];
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	セルのマージ,値,スタイルのセット
	//--------------------------------------------------------------------------
	private static function merge_and_input_value( $sheet_, $cell_, $val_, $style_=false)
	{
		self::merge( $sheet_, $cell_ );
		$input_cell = self::get_inputable_from_merged( $cell_ );
		self::set_val( $sheet_, array($input_cell => $val_) );

		$sheet_->getStyle( $cell_ )->applyFromArray($style_);
	}

	//--------------------------------------------------------------------------
	//	セル変換：列固定の複数行
	//--------------------------------------------------------------------------
	private static function convert_row_of_fixed_col( $col_, $rows_ )
	{
		$ret = [];
		$chars = explode(",", $col_);
		foreach( $rows_ as $row )
		{
			$row_no = explode(",", $row);
			$ret[] = sprintf("%s%d:%s%d",$chars[0],$row_no[0],$chars[1],$row_no[1]);
		}
		return $ret;
	}


	//--------------------------------------------------------------------------
	//	プロパティ情報作成
	//	{
	//		タイトル				=> Title
	//		件名					=> Subject
	//		タグ					=> Keywords
	//		分類項目				=> Category
	//		コメント				=> Description
	//		作成者					=> Creator
	//		前回保存者				=> LastModifiedBy
	//		コンテンツの作成日時	=> Created
	//		前回保存日時			=> Modified
	//		バージョン番号			=> ??
	//		会社					=> Company
	//		マネージャー			=> Manager
	//	}
	//--------------------------------------------------------------------------
	private static function add_file_property()
	{
		$excel = self::get_spreadsheet();
		$instance = self::get_self_instance();
	//	$reader = self::get_reader();
	//	$last_modified_time = $reader->get_Properties()->getModified();
	//	$last_modified_by = $reader->get_Properties()->getLastModifiedBy();

		$props = $instance->m_req_excel_prop;
		$now = isset($instance->m_req_history["date"]) === true ? $instance->m_req_history["date"] : time();
		$excel->getProperties()
			->setTitle($props["title"])
			->setSubject($props["subject"])
		//	->setKeywords($props["keywords"])
			->setCategory($props["category"])
			->setDescription($props["description"])
			->setCreator($props["creator"])
		//	->setLastModifiedBy($last_modified_by)
			->setCreated($now)
			->setModified($now)
			->setCompany($props["company"])
		//	->setManager($props["manager"])
			;
	}

	//--------------------------------------------------------------------------
	//	シートの基本スタイル設定
	//--------------------------------------------------------------------------
	private static function set_basic_sheet_style()
	{
		//	スタイル設定の取得
		$style = self::get_style();

		//	シート全体のフォント設定
		self::set_default_font($style["font"]);
		self::set_default_font_size($style["font_size"]);

		//	セルの大きさ設定
		foreach( self::get_output_sheets() as $name )
		{
			$w = 2.5;
			$h = 20;
			if( isset($style["cell"]) === true )
			{
				if( isset($style["cell"]["width"]) ===  true ) $h = $style["cell"]["width"];
				if( isset($style["cell"]["height"]) ===  true ) $h = $style["cell"]["height"];
			}
			self::set_default_row_height($name, $h);
			self::set_default_col_width($name, $w);
		}
	}

	//--------------------------------------------------------------------------
	//	スタイル：設定取得
	//--------------------------------------------------------------------------
	private static function get_style()
	{
		return self::$m_config_data["style"];
	}

	//--------------------------------------------------------------------------
	//	スタイル：フォント設定
	//--------------------------------------------------------------------------
	private static function set_default_font( $font_ )
	{
		$excel = self::get_spreadsheet();
		$excel->getDefaultStyle()->getFont()->setName($font_);
	}
	private static function set_default_font_size( $size_ )
	{
		$excel = self::get_spreadsheet();
		$excel->getDefaultStyle()->getFont()->setSize($size_);
	}
//	private static function set_font_color( $sheet_, $cell_, $color_ )
//	{
//		$sheet_->getStyle( $cell_ )->getFont()->getColor()->setARGB( $color_ );
//	}
//	private static function set_font_underline( $sheet_, $cell_, $line_type_ )
//	{
//		$sheet_->getStyle( $cell_ )->getFont()->setUnderline( $line_type_ );
//	}

	//--------------------------------------------------------------------------
	//	スタイル：セルの大きさ設定
	//--------------------------------------------------------------------------
	private static function set_default_row_height( $sheet_name_, $height_ )
	{
		$excel = self::get_spreadsheet();
		$excel->getSheetByName($sheet_name_)
			->getDefaultRowDimension()
			->setRowHeight($height_)
			;
	}
	private static function set_default_col_width( $sheet_name_, $width_ )
	{
		$excel = self::get_spreadsheet();
		$excel->getSheetByName($sheet_name_)
			->getDefaultColumnDimension()
			->setWidth($width_)
			;
	}
	private static function set_row_height( $sheet_name_, $height_, $line_ )
	{
		$excel = self::get_spreadsheet();
		$excel->getSheetByName($sheet_name_)
			->getRowDimension($line_)
			->setRowHeight($height_)
			;
	}

	//--------------------------------------------------------------------------
	//	スタイル：ボーダーの設定データ形式変換
	//--------------------------------------------------------------------------
	private static function get_convert_border_style()
	{
		$borders = [];
		if( isset(self::$m_config_user["border"]) === true && isset(self::$m_config_user["border"]["type"]) === true )
		{
			$borders[self::$m_config_user["border"]["type"]] =
			[
				"borderStyle" => self::$m_config_user["border"]["style"],
				"color" => [ "rgb" => self::$m_config_user["border"]["color"] ],
			];
		}
		return ["borders" => $borders];
	}

//	//--------------------------------------------------------------------------
//	//	図、画像の追加
//	//--------------------------------------------------------------------------
//	private static function add_drawing( $sheet_name_, $opt_=[] )
//	{
//		$drawing = self::get_instance("Drawing");
//
//		if( array_key_exists("name", $opt_) ) $drawing->setName($opt_["name"]);
//		if( array_key_exists("desc", $opt_) ) $drawing->setDescription($opt_["desc"]);
//		//	読み込む画像パス
//		if( array_key_exists("image", $opt_) ) $drawing->setPath($opt_["image"]);
//		//	画像の起点位置(セル番号で指定 A1)
//		if( array_key_exists("pos", $opt_) ) $drawing->setCoordinates($opt_["pos"]);
//		//	左からの距離
//		if( array_key_exists("offset_left", $opt_) ) $drawing->setOffsetX($opt_["offset_left"]);
//		//	画像の回転
//		if( array_key_exists("rotate", $opt_) ) $drawing->setRotation($opt_["rotate"]);
//		//	画像の高さ指定
//		if( array_key_exists("height", $opt_) ) $drawing->setHeight($opt_["height"]);
//
//		$drawing->setWorksheet($sheet_name_);
//	}

	//--------------------------------------------------------------------------
	//	スタイル: 文字寄せ
	//--------------------------------------------------------------------------
	private static function get_style_alignment( $key_ )
	{
		$style = self::get_instance("StyleAlignment");
		$props = explode("_", $key_);
		foreach( $props as $i => $prop )
		{
			if( $i===0 )
			{
				if( $prop==="center" ) $ret["horizontal"] = $style::HORIZONTAL_CENTER;
				elseif( $prop==="left" ) $ret["horizontal"] = $style::HORIZONTAL_LEFT;
				elseif( $prop==="right" ) $ret["horizontal"] = $style::HORIZONTAL_RIGHT;
			}
			elseif( $i===1 )
			{
				if( $prop==="bottom" ) $ret["vertical"] = $style::VERTICAL_BOTTOM;
				elseif( $prop==="top" ) $ret["vertical"] = $style::VERTICAL_TOP;
				elseif( $prop==="middle" ) $ret["vertical"] = $style::VERTICAL_CENTER;
				elseif( $prop==="justify" ) $ret["vertical"] = $style::VERTICAL_JUSTIFY;
				elseif( $prop==="distributed" ) $ret["vertical"] = $style::VERTICAL_DISTRIBUTED;
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	塗色スタイル
	//--------------------------------------------------------------------------
	private static function get_style_fill( $arg_ )
	{
		$style = self::get_instance("StyleFill");
		if( $arg_["type"]==="solid" ) $fill_type = $style::FILL_SOLID;
		$ret =
		[
			"color" => ["rgb" => $arg_["color"]],
			"fillType"	=> $fill_type
		];
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	フォントスタイル
	//--------------------------------------------------------------------------
	private static function get_style_font( $arg_ )
	{
		$keys =
		[
			"name","size","bold","underline","color","italic",
			"superscript","subscript","strikethrought"
		];
		foreach( $keys as $key )
		{
			if( isset($arg_[$key]) === true )
			{
				$ret[$key] = $arg_[$key];
			}
		}
		return $ret;
	}

	//--------------------------------------------------------------------------
	//	ボーダースタイル
	//--------------------------------------------------------------------------
	private static function get_style_border( $args_ )
	{
		$border_type = isset($args_["border_type"]) ? $args_["border_type"] : "allBorders";

		foreach( $args_ as $prop => $val )
		{
			if( $prop==="color" )
			{
				$ret[$border_type]["color"] = $val;
			}
			if( $prop==="type" )
			{
				$ret[$border_type]["borderStyle"] = $val;
			}
		}
		return $ret;

	}

	//--------------------------------------------------------------------------
	//	db_designから型を変換
	//--------------------------------------------------------------------------
	private static function get_column_type( $type_ )
	{
		$db = crow_config::get("db.type");
		$ret = '';
		if( $db==='mysqli' || $db==='mysql' )
		{
			if( strpos($type_, 'int') > 0 ){ $ret = (substr($type_, 0, 1) === 'u') ? substr($type_, 1).' unsigned' : $type_; }
			else if( $type_==='bigtext' ){ $ret = 'longtext'; }
			else if( $type_==='varcrypt' ){ $ret = 'tinyblob'; }
			else if( $type_==='crypt' ){ $ret = 'blob'; }
			else if( $type_==='mailcrypt' ){ $ret = 'blob'; }
			else if( $type_==='password' ){ $ret = 'varchar'; }
			else if( $type_==='unixtime' ){ $ret = 'bigint unsigned'; }
			else if( $type_==='boolean' ){ $ret = 'tinyint'; }
			else if( $type_==='url' ){ $ret = 'text'; }
			else if( $type_==='mail' ){ $ret = 'varchar'; }
			else if( $type_==='telno' ){ $ret = 'varchar'; }
			else if( strpos($type_,'[')===0 && strpos($type_, ']') === (strlen($type_) - 1) ){ $ret = substr($type_, 1, strlen($type_) - 2); }
			else{ $ret = $type_; }
		}
		else if( $db==='postgres' )
		{
			if( $type_==='tinyint' || $type_==='utinyint' ){ $ret = 'smallint'; }
			else if( $type_==='int' || $type_==='uint' ){ $ret = 'integer'; }
			else if( $type_==='bigint' || $type_==='ubigint' ){ $ret = 'bigint'; }
			else if( $type_==='bigtext' ){ $ret = 'text'; }
			else if( $type_==='varcrypt' ){ $ret = 'bytea'; }
			else if( $type_==='crypt' ){ $ret = 'bytea'; }
			else if( $type_==='mailcrypt' ){ $ret = 'bytea'; }
			else if( $type_==='password' ){ $ret = 'varchar'; }
			else if( $type_==='unixtime' ){ $ret = 'bigint'; }
			else if( $type_==='datetime' ){ $ret = 'timestamp'; }
			else if( $type_==='boolean' ){ $ret = 'smallint'; }
			else if( $type_==='url' ){ $ret = 'text'; }
			else if( $type_==='mail' ){ $ret = 'varchar'; }
			else if( $type_==='telno' ){ $ret = 'varchar'; }
			else if( strpos($type_,'[')===0 && strpos($type_,']')===(strlen($type_)-1) ){ $ret = substr($type_,1,strlen($type_)-2); }
			else{ $ret = $type_; }
		}
		return $ret;
	}

	//----------------------------------------------------------------
	//	db_designからサイズを変換
	//----------------------------------------------------------------
	private static function get_column_size( $type_, $size_ )
	{
		$ret = '';
		if( $type_==='password' ) $ret = '255';
		else if( $type_==='unixtime' ) $ret = '20';
		else if( $type_==='boolean' ) $ret = '1';
		else if( $type_==='mail' ) $ret = '255';
		else if( $type_==='telno' ) $ret = '20';
		else $ret = $size_;
		return $ret;
	}

	//----------------------------------------------------------------
	//	db_designからデフォルトを変換
	//----------------------------------------------------------------
	private static function get_column_default( $type_, $default_ )
	{
		//	一部は日本語にして返却する
		if( $default_ === "time()" ) return "現在時刻";

		//	table_model用にPHPコードになってるので変換
		$def = eval("return ".$default_.";");
		if( $type_ === "datetime" && $default_ == 0 )
		{
			$def = "";
		}
		else if( $type_ === "boolean" )
		{
			$def = $default_ !== false ? 1 : 0;
		}
		return $def;
	}

	//----------------------------------------------------------------
	//	db_designから備考の内容を返却(const値があれば追記)
	//----------------------------------------------------------------
	private static function get_remark_const( $const_array_ )
	{
		$cnt = count($const_array_);
		if( $cnt <= 0 ) return "";
		$const = '(';
		$i = 0;
		foreach( $const_array_ as $key => $value )
		{
			$const .= $key.': '.$value;
			$i++;
			if( $i < $cnt ) $const .= ', ';
		}
		$const .= ')';
		return $const;
	}
	private static function get_column_remark( $remark_, $const_array_ )
	{
		$ret = $remark_;
		$ret .= self::get_remark_const($const_array_);
		return $ret;
	}

//	//--------------------------------------------------------------------------
//	//	外部リンク設定
//	//--------------------------------------------------------------------------
//	private static function set_url( $cell_, $url_, $sheet_=false )
//	{
//		$sheet = $sheet_ ? self::get_sheet( $sheet_ ) : self::get_active_sheet();
//		$sheet->getCell( $cell_ )->getHyperlink()->setUrl($url_);
//	}

	//--------------------------------------------------------------------------
	//	エクセルシート内のリンク設定
	//--------------------------------------------------------------------------
	private static function set_sheet_url( $cell_, $to_sheet_, $sheet_=false )
	{
		$sheet = $sheet_ ? $sheet_ : self::get_active_sheet();
		$sheet_url = sprintf("sheet://'%s'!%s", $to_sheet_, "A1");
		$sheet->getCell( $cell_ )->getHyperlink()->setUrl($sheet_url);
		//	リンク色を青とする
		$set_style =
		[
			"font" =>
			[
				"color" => ["argb" => "4444FF"],
				"underline" => "single"
			]
		];
		$sheet->getStyle( $cell_ )->applyFromArray($set_style);
	//	self::set_font_color($sheet, $cell_, "4444FF");
	//	self::set_font_underline($sheet, $cell_, "single");
	}

	//--------------------------------------------------------------------------
	//	ページオプション
	//--------------------------------------------------------------------------
	private static function set_scale( $sheet_name_, $percentage_ )
	{
		$sheet = self::get_sheet($sheet_name_);
		$sheet->getSheetView()->setZoomScale($percentage_);
	}

	//--------------------------------------------------------------------------
	//	改ページプレビュー設定
	//--------------------------------------------------------------------------
	private static function set_printarea( $range_ )
	{
		$excel = self::get_spreadsheet();
		$excel->getActiveSheet()->getPageSetUp()->setPrintArea($range_);
		$excel->getActiveSheet()->getPageSetUp()->setFitToHeight(1);
		$excel->getActiveSheet()->getPageSetUp()->setFitToWidth(1);
	}

	//--------------------------------------------------------------------------
	//	指定したディレクトリ以下を再帰検索し、ファイルの相対パス一覧を取得する
	//
	//	第二引数にカンマ区切りで絞りたい拡張子を指定できる。
	//	ディレクトリを含めたファイル名の配列が返却される
	//--------------------------------------------------------------------------
	private static function get_dir_files_recursive( $dir_, $ext_="" )
	{
		if( substr($dir_, -1) != "/" ) $dir_ .= "/";
		if( ! is_dir($dir_) ) return [];
		$files = [];

		//	まず直下のファイルを処理
		$exts = strlen($ext_) > 0 ? explode(",",$ext_) : [];
		$hdir = dir($dir_);
		while( $file = $hdir->read() )
		{
			if( $file=="." || $file==".." ) continue;
			if( ! is_file($dir_.$file) ) continue;

			if( count($exts) > 0 )
			{
				if( in_array(self::extract_ext($file), $exts) )
					$files[] = $dir_.$file;
			}
			else $files[] = $dir_.$file;
		}
		$hdir->close();

		//	名前で並び替える
		sort($files);

		//	サブフォルダを処理
		$hdir = dir($dir_);
		while( $file = $hdir->read() )
		{
			if( $file=="." || $file==".." ) continue;
			if( is_dir($dir_.$file) )
			{
				$rows = self::get_dir_files_recursive( $dir_.$file."/", $ext_ );
				foreach( $rows as $row ) $files[] = $row;
			}
		}

		$hdir->close();
		sort( $files );
		return $files;
	}

	//----------------------------------------------------------------
	//	インスタンス取得
	//----------------------------------------------------------------
	private static function get_instance( $class_name_, $args_=false )
	{
	//	$instance_name = self::LOAD_CLASS[$class_name_];
		switch( $class_name_ )
		{
			case "self" :
				static $instance;
				if( !isset($instance) ){$instance = new self();}
				return $instance;
			case "Spreadsheet" :
				static $spreadsheet;
				if( !isset($spreadsheet) ){$spreadsheet = new Spreadsheet();}
				return $spreadsheet;
			case "XlsxWriter" :
				static $xlsx_writer;
				if( !isset($xlsx_writer) ){$xlsx_writer = new XlsxWriter($args_["excel"]);}
				return $xlsx_writer;
			case "XlsxReader" :
				static $xlsx_reader;
				if( !isset($xlsx_reader) ){$xlsx_reader = new XlsxReader();}
				return $xlsx_reader;
			case "StyleAlignment" :
				static $style_alignment;
				if( !isset($style_alignment) ){$style_alignment = new StyleAlignment();}
				return $style_alignment;
			case "StyleFill" :
				static $style_fill;
				if( !isset($style_fill) ){$style_fill = new StyleFill();}
				return $style_fill;

		}
	}
	private static function get_self_instance()
	{
		return self::get_instance("self");
	}
	private static function get_spreadsheet()
	{
		return self::get_instance("Spreadsheet");
	}
	private static function get_writer()
	{
		return self::get_instance("XlsxWriter", ["excel" => self::get_spreadsheet()]);
	}
//	private static function get_reader()
//	{
//		return self::get_instance("XlsxReader");
//	}
//	private static function debug( $msg_ )
//	{
//		crow_log::notice($msg_);
//	}
//	private static function exit( $msg_ )
//	{
//		print_r($msg_);exit;
//	}
//	private static function dump( $arg_ )
//	{
//		var_dump( $arg_ );exit;
//	}


}


?>
