<?php
/*

	メール送信バッチ(コントローラ)

	config_bach.txtに記載の設定値に基づいて動作するバッチでbatch_mail_sender.phpとセットで利用する。

	設定起動時間中はループ処理でタスクを取得しながらbatch_mail_sender.php をバックグラウンド実行し、
	実行されたbatch_mail_sender.phpが実際の送信処理を実行していく。

	タスクの処理は以下を考慮して行われる
	・同時起動プロセス数
	sesの場合
	・24時間送信上限
	・秒間送信上限数
	・エラーレート

	1. batch_mail_controller.php 実行
	2. ループ処理中、タスクの取得送信を繰り返す
		{
			リトライタスク取得
			送信処理 (batch_mail_sender.php 実行。これが並列)

			未処理タスク取得
			送信処理 (batch_mail_sender.php 実行)
		}
	3. 設定時間が来たら終了。次の1を待機。

*/

//	実行情報の設定
$role = 'batch';
$module = 'mail_controller';
$cleanup = true;

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

$g_hdb = crow::get_hdb();
$g_options = [];
$g_task_table_name = crow_config::get('app.batch.mail_task.table_name');
$g_model = 'model_'.$g_task_table_name;

$design = $g_hdb->get_design($g_task_table_name);
$g_pk = $design->primary_key;
$g_ts_started_date = time();
$g_error_rates = false;

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
	//	初期化
	init_controller();

	//	送信ループ実行
	run_send_tasks();
}

//--------------------------------------------------------------------------
//	時間内でタスクを処理し続ける
//--------------------------------------------------------------------------
function run_send_tasks()
{
	global $g_options;
	global $g_model;
	global $g_ts_started_date;
	global $g_error_rates;

	//	送信総数チェック
	if( check_send_total() === false )
	{
		return true;
	}

	//	エラーレートのチェックと調整
	$check_error_rate = check_error_rate();
	if( $check_error_rate === false )
	{
		if( $g_error_rates === false )
		{
			return true;
		}

		//	調整が必要だが調整用のメールが用意されていない場合はエラー終了
		$mail_addr_from = $g_options['error_rate_mail_from'];
		$mail_addr_to = $g_options['error_rate_mail_to'];
		if( $mail_addr_from === '' || $mail_addr_to === '' )
		{
			batchlog('STOP - no erorr_rate_mail settings');
			return false;
		}

		$mailer = crow_mail::create();
		$mailer
			->from($mail_addr_from)
			->to($mail_addr_to)
			->subject('ErrorRateDown')
			;

		while(1)
		{
			//	バッチの実行間隔に合わせて起動を制御する
			//	次の実行の前に停止する
			$elapsed = time() - $g_ts_started_date;
			if( $elapsed >= $g_options['run_per_sec'] )
			{
				batchlog('STOP over run_per_sec ' . $elapsed . ' >= ' . $g_options['run_per_sec']);
				return true;
			}

			$mailer->send();
			sleep($g_options['error_rate_mail_interval']);
		}

		return true;
	}

	//	継続的な送信タスク処理
	//	秒間送信数上限チェックの有無でメソッド分岐
	$send_loop_func = $g_options['is_check_send_rate'] === true
		? 'process_tasks_with_send_rate_check'
		: 'process_tasks';

	while(1)
	{
		//	バッチの実行間隔に合わせて起動を制御する
		//	次の実行の前に停止する
		$elapsed = time() - $g_ts_started_date;
		if( $elapsed >= $g_options['run_per_sec'] )
		{
			batchlog('STOP over run_per_sec ' . $elapsed . ' > ' . $g_options['run_per_sec']);
			break;
		}

		//	リトライタスクの処理の開始
		$rows = $g_model::pick_and_retry($g_options['pick_num']);
		if( $send_loop_func($rows) === false )
		{
			batchlog('ERROR pick_and_retry');
			return false;
		}

		//	待機タスクの処理開始
		$rows = $g_model::pick_and_run($g_options['pick_num']);
		if( count($rows) <= 0 )
		{
			batchlog('STOP no task record more');
			break;
		}

		if( $send_loop_func($rows) === false )
		{
			batchlog('ERROR pick_and_num');
			return false;
		}
	}

	return true;
}

//--------------------------------------------------------------------------
//	1セットごとのレコードの送信処理
//--------------------------------------------------------------------------
function process_tasks( $rows_ )
{
	global $g_options;
	global $g_pk;

	//	pidと子プロセスリソースの連想配列
	$sender_fp_row = [];
	foreach( $rows_ as $row )
	{
		//	並行起動プロセス上限チェック
		while(1)
		{
			check_and_close_process($sender_fp_row);

			//	上限以下の場合は送信プロセス生成
			if( count($sender_fp_row) < $g_options['max_send_process'] )
			{
				break;
			}

			//	指定時間待機
			usleep($g_options['process_check_interval']);
		}

		$id = $row->{$g_pk};
		$cmd = sprintf($g_options['run_sender_cmd'], $id);
		list($fp, $pipes) = exec_batch_background_with_ctr($cmd);
		if( is_resource($fp) === false )
		{
			batchlog('failed to exec command - cannot get batch process resource - cmd:'.$cmd);
			return false;
		}

		$status_row = proc_get_status($fp);
		$pid = isset($status_row['pid']) ? $status_row['pid'] : "";

		// パイプからエラーを取得
		$stderr = stream_get_contents($pipes[2]);
		if( isset($stderr) === false || strlen($stderr) > 0 )
		{
			batchlog('process error [pid:'.$pid.'] - '.$stderr);
			proc_close($fp);
			continue;
		}

		$sender_fp_row[$pid] = $fp;
	}

	foreach( $sender_fp_row as $fp )
	{
		proc_close($fp);
	}

	return true;
}

//--------------------------------------------------------------------------
//	1セットごとのレコードの送信処理
//--------------------------------------------------------------------------
function process_tasks_with_send_rate_check( $rows_ )
{
	global $g_options;
	global $g_pk;

	//	pidと子プロセスリソースの連想配列
	$sender_fp_row = [];

	//	pidと送信実行タスク開始のマイクロ秒の配列
	$process_time_rows = [];

	//	送信レート評価時間内に収まる配列
	$sec_process_rows = [];

	foreach( $rows_ as $row )
	{
		//	並行起動プロセス上限チェック
		while(1)
		{
			check_and_close_process($sender_fp_row);

			//	上限以下の場合は送信プロセス生成
			if( count($sender_fp_row) < $g_options['max_send_process'] )
			{
				break;
			}

			//	指定時間待機
			usleep($g_options['process_check_interval']);
		}

		//	送信レート評価時間内の送信数上限チェック
		while(1)
		{
			$time_current = microtime(true);
			$sec_process_rows = [];
			foreach( $process_time_rows as $time_row )
			{
				$elapsed = $time_current - $time_row[0];
				if( $elapsed < $g_options['send_rate_sec'] )
				{
					$sec_process_rows[] = $time_row;
				}
			}

			if( count($sec_process_rows) < $g_options['max_send_rate'] )
			{
				break;
			}

			//	指定時間待機
			usleep($g_options['send_rate_interval']);
		}
		$process_time_rows = $sec_process_rows;

		//	送信タスク実行
		$id = $row->{$g_pk};
		$cmd = sprintf($g_options['run_sender_cmd'], $id);
		$send_start = microtime(true);
		list($fp, $pipes) = exec_batch_background_with_ctr($cmd);
		if( is_resource($fp) === false )
		{
			batchlog('failed to exec command - cannot get batch process resource - cmd:'.$cmd);
			return false;
		}

		$status_row = proc_get_status($fp);
		$pid = isset($status_row['pid']) ? $status_row['pid'] : "";

		// パイプからエラーを取得
		$stderr = stream_get_contents($pipes[2]);
		if( isset($stderr) === false || strlen($stderr) > 0 )
		{
			batchlog('process error [pid:'.$pid.'] - '.$stderr);
			proc_close($fp);
			continue;
		}

		$sender_fp_row[$pid] = $fp;
		$process_time_rows[] = [$send_start, $pid];
	}
	foreach( $sender_fp_row as $fp )
	{
		proc_close($fp);
	}

	return true;
}

//--------------------------------------------------------------------------
//	現在の管理子プロセスの状態をチェック
//--------------------------------------------------------------------------
function check_and_close_process( &$fp_row_ )
{
	foreach( $fp_row_ as $fp )
	{
		$status_row = proc_get_status($fp);
		if( $status_row['running'] === false )
		{
			proc_close($fp);
			$pid = $status_row['pid'];
			unset($fp_row_[$pid]);
		}
	}
}

//--------------------------------------------------------------------------
//	設定初期化
//--------------------------------------------------------------------------
function init_controller()
{
	global $g_options;

	$g_options = get_options();

	//	SESの場合には初期値をAPIで取得する
	if( $g_options['protocol'] === 'aws' )
	{
		$quota = crow_mail::create()->get_aws_quota();
		if( is_array($quota) === true )
		{
			$g_options['max_send_total'] = $quota['Max24HourSend'];
			$g_options['max_send_rate'] = $quota['MaxSendRate'];
			$g_options['current_send_total'] = $quota['SentLast24Hours'];
		}
	}
}

//--------------------------------------------------------------------------
//	設定値
//--------------------------------------------------------------------------
function get_options()
{
	$prefix = 'app.batch.mail_task.';

	return
	[
		//	メール送信設定
		'protocol' => crow_config::get('mail.protocol', 'local'),

		//	何秒間隔でバッチを実行するか(cronに登録している実行間隔)
		'run_per_sec' => crow_config::get($prefix.'run_per_sec', 60),

		//	1回あたり何レコード処理するか
		'pick_num' => crow_config::get($prefix.'pick_num', 60),

		//	並列起動プロセス上限
		'max_send_process' => crow_config::get($prefix.'max_send_process', 5),

		//	並列起動プロセスの次のチェックへの待ち時間
		'process_check_interval' => crow_config::get($prefix.'process_check_interval', 0.1) * 1000000,

		//	実際の送信タスク起動コマンド(並列送信用) [php] batch_path table_name=xxx table_id=xxx
		'run_sender_cmd' => implode(' ',
		[
			crow_config::get($prefix.'php_exec_cmd', 'php'),
			CROW_PATH.'app/batch/batch_mail_sender.php',
			'table_name='.crow_config::get($prefix.'table_name'),
			'table_id=%d',
		]),

		//----------------------------------------------------------------------
		//	送信レート
		//----------------------------------------------------------------------

		//	レート評価の有無
		'is_check_send_rate' => crow_config::get($prefix.'is_check_send_rate', false) === 'true',

		//	送信数のチェック秒数
		'send_rate_sec' => crow_config::get($prefix.'send_rate_sec', 1),

		//	レート評価時間内の送信数上限
		'max_send_rate' => crow_config::get($prefix.'max_send_rate', 0),

		//	送信レート上限対策用スリープマイクロ秒数
		'send_rate_interval' => crow_config::get($prefix.'send_rate_interval', 0.2) * 1000000,

		//----------------------------------------------------------------------
		//	送信総数
		//----------------------------------------------------------------------

		//	送信総数上限(ses時有効。動的)
		'max_send_total' => 0,

		//	現在の合計送信数(ses時有効。動的)
		'current_send_total' => 0,

		//----------------------------------------------------------------------
		//	エラーレート対策
		//	aws sesエラーレート対策用動作用のメールアドレス(認証ドメインアドレス以外)
		//----------------------------------------------------------------------
		'error_rate_mail_from' => crow_config::get($prefix.'error_rate_mail_from', ''),
		'error_rate_mail_to' => crow_config::get($prefix.'error_rate_mail_to', ''),
		'error_rate_mail_interval' => crow_config::get($prefix.'error_rate_mail_interval', 1),
	];
}

//--------------------------------------------------------------------------
//	送信総数チェック
//--------------------------------------------------------------------------
function check_send_total()
{
	global $g_options;

	if( $g_options['max_send_total'] != 0 )
	{
		if( $g_options['max_send_total'] <= $g_options['current_send_total'] )
		{
			batchlog('STOP over max_send_total '
				.$g_options['current_send_total'].' / '.$g_options['max_send_total']
			);
			return false;
		}
	}

	return true;
}

//--------------------------------------------------------------------------
//	エラーレートのチェックと調整
//	バウンス率:推奨5%未満。10%超過で確認中ステータスに自動変更
//	苦情率:推奨0.1%未満。0.5%超過で確認中ステータスに自動変更
//--------------------------------------------------------------------------
function check_error_rate()
{
	global $g_options;
	global $g_error_rates;

	// メール送信ONになってない場合には何もしない
	if( crow_config::get('mail.send') != 'true' )
	{
		return true;
	}
	// SES以外
	if( $g_options['protocol'] !== 'aws' )
	{
		return true;
	}

	$mailer = crow_mail::create();
	$g_error_rates = $mailer->get_aws_error_rates();
	if( $g_error_rates === false )
	{
		return false;
	}

	$prefix = 'app.batch.mail_task.';
	$warning_rate_bounce = crow_config::get_if_exists($prefix.'error_rate_bounce_warning', 5);
	$warning_rate_complaint = crow_config::get_if_exists($prefix.'error_rate_complaint_warning', 0.1);
	$check_bounce = $warning_rate_bounce > $g_error_rates['bounce_rate'];
	$check_complaint = $warning_rate_complaint > $g_error_rates['complaint_rate'];

	//	エラーレートのどちらかが警戒ラインに達した場合には
	//	ダミーの正常メール送信を繰り返してエラーレートを下げる
	if( $check_bounce === true && $check_complaint === true )
	{
		return true;
	}

	return false;
}

//--------------------------------------------------------------------------
//	バッチ起動
//--------------------------------------------------------------------------
function exec_batch( $cmd_, $args_ = '', $background_ = false, $ctr_process_ = false )
{
	$cmd_parts =
	[
		'%CMD%' => $cmd_,
		'%ARGS%' => $args_,
	];

	if( is_win() === true )
	{
		if( $background_ === true )
		{
			$tpl = 'start "" %CMD% %ARGS% >nul';
			$cmd = str_replace(array_keys($cmd_parts), $cmd_parts, $tpl);

			if( $ctr_process_ === true )
			{
				$descriptor =
				[
					// stdin
					0 => ["pipe", "r"],
					// stdout
					1 => ["pipe", "w"],
					// error
					2 => ["pipe", "w"],
				];
				$pipes = null;

				$fp = proc_open($cmd, $descriptor, $pipes);
				if( $fp === false )
				{
					batchlog('failed to exec command result:'.$result.' cmd ['.$cmd.']');
					return false;
				}

				return [$fp, $pipes];
			}
			else
			{
				$fp = popen($cmd, 'r');
				$result = pclose($fp);
				if( $result !== -1 )
				{
					batchlog('failed to exec command result:'.$result.' cmd ['.$cmd.']');
					return false;
				}

				return true;
			}
		}
		else
		{
			$tpl = '%CMD% %ARGS%';
			$cmd = str_replace(array_keys($cmd_parts), $cmd_parts, $tpl);
			exec($cmd, $output, $result);
			if( $result !== 0 )
			{
				batchlog('failed to exec command result:'.$result.' cmd ['.$cmd.']'."\n".print_r($output, true));
				return false;
			}
			return true;
		}
	}

	$tpl = '%CMD% %ARGS%';
	$cmd = str_replace(array_keys($cmd_parts), $cmd_parts, $tpl);
	if( $background_ === true )
	{
		$cmd .= '>/dev/null';

		if( $ctr_process_ === true )
		{
			$descriptor =
			[
				// stdin
				0 => ["pipe", "r"],
				// stdout
				1 => ["pipe", "w"],
				// error
				2 => ["pipe", "w"],
			];
			$pipes = null;

			$fp = proc_open($cmd, $descriptor, $pipes);
			if( $fp === false )
			{
				batchlog('failed to exec command result:'.$result.' cmd ['.$cmd.']');
				return false;
			}
			return [$fp, $pipes];
		}
		else
		{
			exec($cmd, $output, $result);
			if( $result !== 0 )
			{
				batchlog('failed to exec command result:'.$result.' cmd ['.$cmd.']'."\n".print_r($output, true));
				return false;
			}
			return true;
		}
	}
	else
	{
		exec($cmd, $output, $result);
		if( $result !== 0 )
		{
			batchlog('failed to exec command result:'.$result.' cmd ['.$cmd.']'."\n".print_r($output, true));
			return false;
		}
		return true;
	}
}

//--------------------------------------------------------------------------
//	コマンド実行(バックグラウンド)
//--------------------------------------------------------------------------
function exec_batch_background( $cmd_, $args_ = '' )
{
	$background = true;
	return exec_batch($cmd_, $args_, $background);
}

//--------------------------------------------------------------------------
//	コマンド実行(バックグラウンド実行のプロセスコントロールつき)
//--------------------------------------------------------------------------
function exec_batch_background_with_ctr( $cmd_, $args_ = '' )
{
	$background = true;
	$ctr_process = true;
	return exec_batch($cmd_, $args_, $background, $ctr_process);
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
//	Windowsかどうか
//--------------------------------------------------------------------------
function is_win()
{
	return strtolower(substr(PHP_OS, 0, 3)) === 'win';
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
