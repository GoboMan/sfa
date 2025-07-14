<?php
/*
	バッチサンプル
*/

//	実行情報の設定
$role = "batch";
$module = "sample";
$cleanup = true;

//	crow初期化
define("CROW_PATH", "../../../crow3_xxx/");
require_once( CROW_PATH."crow.php" );
if( true )
{
	$env = getenv("DISTRIBUTION");
	$dist = '';
	switch( $env )
	{
		case 'prd':	$dist = 'prd'; break;
		case 'stg':	$dist = 'stg'; break;
		case 'dev':	$dist = 'dev'; break;
	}
	crow::init_for_batch_with_module( $role, $module,
	[
		"cleanup" => $cleanup,
		"distribution" => $dist,
	]);
}


//	ここからcrowの機能がつかるようになる
//
//	$rows = model_xxx::create_array();
//

?>
