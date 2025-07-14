<?php
/*

	crow loader

*/
if( ! defined('CROW_PATH') )
{
	echo 'error : undefined CROW_PATH';
	exit;
}
if( ! file_exists(CROW_PATH."engine/kernel/crow_core.php") )
{
	echo 'error : not found crow_core.php';
	exit;
}

//	オートローダー
spl_autoload_register(function($class_name_)
{
	if( substr($class_name_,0,5)=="crow_" ){
		include_once CROW_PATH."engine/kernel/".$class_name_.'.php';
	}
	else if( substr($class_name_,0,6)=="model_" )
	{
		//	runオプションのcleanup=trueの場合には、ここに来た時点で最初のクリーンアップは終わっている。
		//	もしキャッシュファイルが存在すれば、それは新しく作られたファイルということになる。
		//	逆にcleanup=falseの場合には過去に作成済みのファイルとなる。
		//	というわけで、cleanupのオプションによらず、ここでキャッシュファイルを取得することに問題はない。
		//	キャッシュファイルが存在すれば読み込みを行うことで、不要なdb接続をしなくても済むようにする。
		if( is_file(CROW_PATH.'output/caches/table_models.php') === true )
		{
			require_once(CROW_PATH.'output/caches/table_models.php');
		}
		else
		{
			crow::get_hdb();
		}

		//	それでも読めない場合には _common_ から探す
		if( class_exists($class_name_) === false )
		{
			$path = CROW_PATH."app/classes/_common_/".$class_name_.'.php';
			if( is_file($path) )
			{
				include_once $path;
			}
		}
	}
	else if( substr($class_name_,0,7)=="module_" )
	{
		$path_arr = explode("_",$class_name_);
		$path = CROW_PATH."app/classes/".$path_arr[1]."/".$class_name_.".php";
		if( ! is_file($path) )
		{
			//	moduleクラスの場合は、ファイルが存在しなくてもエラーとしない
			return;
		}
		include_once $path;
	}
	else
	{
		$path = CROW_PATH."app/classes/_common_/".$class_name_.'.php';
		if( is_file($path) )
		{
			include_once $path;
		}
	}

	//	次につなげるためにエラーとはしない
	//	if( ! class_exists($class_name_) )
	//		trigger_error( 'undefined class ['.$class_name_.']', E_USER_ERROR );
});

//	コア
require_once( CROW_PATH."engine/kernel/crow_core.php" );

?>
