<?php
/*

	メール送信エラー検出バッチ

	cronに登録して利用
	sesの場合に動作
	送信後ステータスのメールに対してエラー状況を調査していく
	検索範囲はconfig_batch.txtで指定

*/

//	実行情報の設定
$role = 'batch';
$module = 'mail_error_detector';
$cleanup = false;

//	crow初期化
$path = '../../../crow3_xxx/';
define('CROW_PATH', $path);
require_once( CROW_PATH.'crow.php' );

$env = getenv('DISTRIBUTION');
$dist = '';
switch( $env )
{
	case 'prd': $dist = 'prd'; break;
	case 'stg': $dist = 'stg'; break;
	case 'dev': $dist = 'dev'; break;
}
crow::init_for_batch_with_module( $role, $module,
[
	'cleanup' => $cleanup,
	'distribution' => $dist,
]);

define('CACHE_PATH', CROW_PATH.'output/caches/mail_error_list.json');
define('SES_ERROR_CODE', '400');

$g_task_table_name = crow_config::get('app.batch.mail_task.table_name');
$g_model = 'model_'.$g_task_table_name;

$hour = crow_config::get('app.batch.mail_task.error_detect_hour');
$g_ts_start_date = time() - 60*60*$hour;
$g_start_date = date('Y-m-d H:i:s', $g_ts_start_date);

//	バッチ同時実行時の同期ずらし
wait_run_batch();

batchlog('run_batch '.microtime(true));

// 実行
run_batch();

//--------------------------------------------------------------------------
//	時間内でタスクを処理し続ける
//--------------------------------------------------------------------------
function run_batch()
{
	global $g_model;
	global $g_ts_start_date;
	global $g_start_date;

	//	sesの時のみ稼働
	if( crow_config::get('mail.protocol') !== 'aws' )
	{
		return;
	}

	//	チェック対象差分アドレス
	//	APIエラーリストの取得
	$mailer = crow_mail::create();
	$new_rejected_list = $mailer->get_aws_rejected_list($g_ts_start_date);
	$new_error_list = [];
	if( $new_rejected_list !== false )
	{
		foreach( $new_rejected_list as $error_info )
		{
			$new_error_list[$error_info['addr']] = $error_info;
		}
	}

	//	キャッシュの取得
	$cache_error_list = [];
	if( file_exists(CACHE_PATH) === true )
	{
		$json_str = file_get_contents(CACHE_PATH);
		if( $json_str !== false )
		{
			$cache_error_list = json_decode($json_str, true);
			if( $cache_error_list === false )
			{
				$cache_error_list = [];
			}
		}
	}

	//	追加差分
	$diff_error_list = get_diff($cache_error_list, $new_error_list);

	//	対象のタスクを取得
	$rows = $g_model::create_array_from_sql(
		$g_model::sql_select_all()
			->and_where('task_status', $g_model::stat_succeeded)
			->and_where('error_detected', 0)
			->and_where('task_ended_date', '>', $g_start_date)
	);

	foreach( $rows as $row )
	{
		if( isset($diff_error_list[$row->to]) === false )
			continue;

		$error_info = $diff_error_list[$row->to];

		//--------------------------------------------------------------------------
		//	カスタム箇所
		//--------------------------------------------------------------------------
		// 送信失敗時処理
		$update_value =
		[
			'error_code' => SES_ERROR_CODE,
			'error_reason' => $error_info['reason'],
			'error_detected' => true,
		];
		$row->failure(false, $update_value);

		if( $row->check_and_save() === false )
		{
			batchlog('Failed to failure save Task - ' . $g_model . ' - ' . $row->get_last_error());
			exit;
		}
	}

	//	キャッシュファイルの更新
	$json_str = json_encode($new_error_list);
	file_put_contents(CACHE_PATH, $json_str);
	chmod(CACHE_PATH, 0777);
}

//--------------------------------------------------------------------------
//	差分取得
//--------------------------------------------------------------------------
function get_diff( $cache_error_list_, $api_error_list_ )
{
	$ret = [];
	foreach( $api_error_list_ as $mail_addr => $error_info )
	{
		if( isset($cache_error_list_[$mail_addr]) === false )
		{
			$ret[$mail_addr] = $error_info;
		}
	}
	return $ret;
}

//--------------------------------------------------------------------------
//	複数起動時のランダムミリ秒待機
//--------------------------------------------------------------------------
function wait_run_batch()
{
	$addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR']
		: (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
	$iplong = ip2long($addr);
	$sec = 0;
	foreach( str_split($iplong, 1) as $num )
	{
		$sec += $num;
	}
	$micro_sec = $sec * 1000;
	usleep($micro_sec);
}

//--------------------------------------------------------------------------
//	ログ出力
//--------------------------------------------------------------------------
function batchlog( $data_ )
{
	$r = crow_request::get_role_name();
	$m = crow_request::get_module_name();
	crow_log::log_with_name($r.'_'.$m, $data_);
}

?>
