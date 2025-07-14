<?php
/***

	Excel操作
	phpoffice/にzipArchiveクラスがない場合用の処理を追記

***/

require_once(CROW_PATH."engine/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border as StyleBorder;
use PhpOffice\PhpSpreadsheet\Style\Fill as StyleFill;
use PhpOffice\PhpSpreadsheet\Style\Alignment as StyleAlignment;
use PhpOffice\PhpSpreadsheet\Style\Style as Style;
use PhpOffice\PhpSpreadsheet\IOFactory as XlsxIO;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Writer\CSV as CSVWriter;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

ini_set('memory_limit', '-1');
define("PLUGIN_PATH", CROW_PATH."app/classes/crowmin/plugins/excel/");
require_once("excel_db.php");
require_once("excel_app_structure.php");

class	module_crowmin_excel extends module_crowmin
{
	//	共通定数
	const VERSION		= "1.0.4";
	const PATH_OUTPUT	= PLUGIN_PATH."tmp/";
	const PATH_TMP		= PLUGIN_PATH."tmp/";
	const PATH_IMAGE	= PLUGIN_PATH."tmp/images/";
	const PATH_CONF		= PLUGIN_PATH."config/";

	//	DB定義書
	const NAME_CONF		= "db_design_excel.json";
	const NAME_CONF_DEF	= "db_design_excel_default.json";
	const NAME_OUTPUT	= "db_design.xlsx";
	const NAME_HISTORY	= "revize_history.csv";
	const LOAD_CLASS	=
	[
		"self"				=> "instance",
		"Spreadsheet"		=> "spreadsheet",
		"XlsxReader"		=> "excel_reader",
		"XlsxWriter"		=> "excel_writer",
		"CSVWriter"			=> "csv_writer",
		"Style"				=> "style",
		"StyleBorder"		=> "style_border",
		"StyleFill"			=> "style_fill",
		"StyleAlignment"	=> "style_alignment",
		"Drawing"			=> "drawing"
	];

	//	ファイル構成書
	const NAME_OUTPUT_APP_STRUCTURE = "app_structure.xlsx";
	const STR_TO_ITEM	= "  |---";
	const STR_NO_ITEM	= "  |";
	const STR_END_ITEM	= "   `---";
	const EXCLUDE_LIST	=
	[
		"/engine/vendor/", "/crowmin/", "/output/"
	];
	const APP_STRUCTURE_SHEET_NAME = "ファイル構成";
	const APP_STRUCTURE_ROOT_NAME = "application";
	const APP_STRUCTURE_ROOT_CELL = "A2";

	//--------------------------------------------------------------------------
	//	クラス
	//--------------------------------------------------------------------------
	use excel_db;
	use excel_app_structure;

	//--------------------------------------------------------------------------
	//	プリロード
	//--------------------------------------------------------------------------
	public function preload()
	{
		parent::preload();
		return true;
	}

	//----------------------------------------------------------------
	//	エクセルファイル出力
	//	zipArchiveClassがないLinux環境での出力用に
	//	\phpspreadsheet\src\PhpSpreadsheet\Writer\Xlsx.php
	//	\phpspreadsheet\src\PhpSpreadsheet\Writer\saveExcelHelper.php
	//	に処理を追加すること
	//----------------------------------------------------------------
	private static function save_excel( $type_ )
	{
		$excel = self::get_spreadsheet();
		$writer = self::get_writer( $excel );
		switch( $type_ )
		{
			case "database" :
			{
				$output_file = self::PATH_OUTPUT.self::NAME_OUTPUT;
				break;
			}
			case "app_structure" :
			{
				$output_file = self::PATH_OUTPUT.self::NAME_OUTPUT_APP_STRUCTURE;
				break;
			}
		}
		if( self::check_zip_archiver() )
			$writer->save( $output_file, XlsxWriter::SAVE_WITH_CHARTS );
		else
			$writer->save( $output_file, XlsxWriter::SAVE_WITH_CHARTS, self::PATH_TMP );
	}

	//----------------------------------------------------------------
	//	必要なファイルチェック
	//----------------------------------------------------------------
	private static function check_zip_archiver()
	{
		return class_exists("zipArchive");
	}

	//----------------------------------------------------------------
	//	ダウンロードファイル
	//----------------------------------------------------------------
	private static function download_file( $type_, $document_name_ = "" )
	{
		$instance = self::get_self_instance();
		$project = $instance->m_req_page_header["project"];

		switch( $type_ )
		{
			case "database" :
			{
				//	固定ファイル名
				$output_file = self::PATH_OUTPUT.self::NAME_OUTPUT;
				$basename = "DB定義書";
				break;
			}
			case "app_structure" :
			{
				//	固定ファイル名
				$output_file = self::PATH_OUTPUT.self::NAME_OUTPUT_APP_STRUCTURE;
				$basename = "アプリケーションファイル構成書";
				break;
			}
		}
		if( strlen($document_name_) > 0 ) $basename = $document_name_;

		//	ダウンロード時のファイル名
		$download_basename = strlen($project) > 0 ? $project."_".$basename : $basename;

		$ext = ".xlsx";
		$filename = $download_basename."_".date("YmdHis", filemtime($output_file)).$ext;

		// 出力情報の設定
		mb_http_output("pass");
		header('Pragma: public');
		header('Cache-Control: public');
		header('Content-Disposition: attachment; filename="'.$filename.'" ');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;');
		header('Content-Length: '.filesize($output_file));
		ob_end_clean();
		readfile($output_file);
		exit;
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
}

?>
