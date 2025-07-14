<?php

//	設定は、../../app/config/sweep.txt に記載する。


//	ディレクトリパスを取得する
function extract_dirpath($path_)
{
	$path = str_replace("\\", "/", $path_);
	$pos = strrpos($path,"/");
	if( $pos === false ) return "";
	return substr($path, 0, $pos);
}

//	ファイル一覧取得
function get_dir_files($dir_)
{
	if( substr($dir_, -1) != "/" ) $dir_ .= "/";
	if( is_dir($dir_) === false ) return [];
	$files = [];
	$hdir = dir($dir_);
	while($file = $hdir->read())
	{
		if( $file == "." || $file == ".." ) continue;
		if( is_file($dir_.$file) === false ) continue;
		$files[] = $file;
	}
	$hdir->close();
	return $files;
}

//	子ディレクトリ一覧を取得
function get_sub_dirs($dir_)
{
	if( substr($dir_, -1) != "/" ) $dir_ .= "/";
	if( is_dir($dir_) === false ) return [];
	$dirs = [];
	$hdir = dir($dir_);
	while($file = $hdir->read())
	{
		if( $file == "." || $file == ".." ) continue;
		if( is_dir($dir_.$file) === true )
		{
			$dirs[] = $file;
		}
	}
	$hdir->close();
	return $dirs;
}

//	拡張子を除いたファイル名計算
function extract_filename_without_ext( $path_ )
{
	$path = str_replace("\\", "/", $path_);
	$pos = strrpos($path, "/");
	if( $pos !== false ) $path = substr($path, $pos+1);

	$pos = strrpos($path, ".");
	if( $pos === false ) return $path;
	return substr($path, 0, $pos);
}

//	php設定
date_default_timezone_set('Asia/Tokyo');
$work_dir = extract_dirpath($_SERVER['SCRIPT_NAME']);
if( strlen($work_dir) <= 0 ) $work_dir = getcwd();
$work_dir .= "/";

//	設定読み込み
$lines = file($work_dir."../../app/config/sweep.txt");
$period = 30;
$names = [];
foreach( $lines as $line )
{
	$line = trim($line);
	if( substr($line, 0, 2) == "//" ) continue;
	$pos = strpos($line, "=");
	if( $pos === false ) continue;
	$key = trim(substr($line, 0, $pos));
	$val = trim(substr($line, $pos+1));

	if( $key == "sweep.period" ) $period = intval($val);
	else if( $key == "sweep.name" ) $names = explode(",", $val);
}

//	掃除処理
$dir_path = $work_dir."../../output/logs/";
sweep_recursive($dir_path, $period, $names);

function sweep_recursive($dir_path_, $period_, $sweep_names_)
{
	$file_names = get_dir_files($dir_path_);
	$delete_date = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - $period_ * 60*60*24;
	foreach( $file_names as $file_name )
	{
		//	ファイル名を識別子部分と日付部分に分割する
		$without_ext = extract_filename_without_ext($file_name);
		$pos = strrpos($without_ext, "_");
		if( $pos === false ) continue;
		$type_name = substr($without_ext, 0, $pos);
		$date_str = substr($without_ext, $pos + 1);

		//	識別子が設定されたリストにない場合は何もしない
		if( in_array($type_name, $sweep_names_) === false ) continue;

		//	日付部分が、設定された期間を過ぎていたら削除
		list($y, $m, $d) = sscanf($date_str, "%04d%02d%02d");
		$timestamp = mktime(0, 0, 0, $m, $d, $y);
		if( $timestamp < $delete_date )
		{
			unlink( $dir_path_.$file_name );
		}
	}

	//	子ディレクトリについても再帰処理
	$sub_dirs = get_sub_dirs($dir_path_);
	foreach( $sub_dirs as $dir )
		sweep_recursive($dir_path_.$dir."/", $period_, $sweep_names_);
}

?>
