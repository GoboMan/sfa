<?php
/*

	メール送信バッチ(送信実体)

	本バッチは単体でも動作するが、基本的には
	batch_mail_controller.php
	から実行されることを想定している。

	送信に必要な情報をうけとってメール送信処理のみを行う
	引数
	・テーブル名
	・テーブルのID

	例) php batch_mail_sender.php table_name 1


	送信処理に基づいてタスクテーブル以外の更新をする必要がある場合には
	run_batchの中を変更する

*/

//	実行情報の設定
$role = "batch";
$module = "mail_sender";
$cleanup = false;

//	crow初期化
$path = '../../../crow3_xxx/';
define("CROW_PATH", $path);
require_once( CROW_PATH."crow.php" );

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

$g_hdb = crow::get_hdb();
$g_hdb_reader = crow::get_hdb_reader();
$g_table_name = crow::get_argv('table_name', false);
$g_table_id = crow::get_argv('table_id', false);
$g_task = false;
if( $g_table_name === false )
{
	batchlog('Invalid arg - table_name');
	exit;
}
if( $g_table_id === false || is_int(intval($g_table_id)) === false )
{
	batchlog('Invalid arg - table_id');
	exit;
}
$g_model = 'model_'.$g_table_name;
$g_task = $g_model::create_from_id($g_table_id);
if( $g_task === false )
{
	batchlog('Task Not Found - ' . $g_table_id);
	exit;
}

//	実行
run_batch();

//--------------------------------------------------------------------------
//	実行
//--------------------------------------------------------------------------
function run_batch()
{
	global $g_hdb;
	global $g_task;
	global $g_table_id;

	// 拡張機能初期化
	$mailer = crow_mail::create();

	$g_hdb->begin();

	// タスクとメーラーにオプションの適用
	apply_from_option($mailer);

	// 送信処理の実行
	batchlog(microtime(true).' Exec Send - '.$g_table_id);

	//	無視リストのチェック
	$ignore_domains = crow_config::get('app.batch.mail_task.ignore_domains');
	$ignore_domain_row = explode(',', $ignore_domains);
	$to_domain = explode('@', $mailer->to())[1];
	//	該当の場合には
	if( in_array($to_domain, $ignore_domain_row) === true )
	{
		crow_config::set('mail.send', 'false');
	}

	$result = $mailer->send();

	//--------------------------------------------------------------------------
	//	必要に応じて処理をカスタム
	//--------------------------------------------------------------------------
	// 送信成功時処理
	if( $result !== false )
	{
		$g_task->success();
		if( $g_task->check_and_save() === false )
		{
			batchlog('Failed to Success Task - ' . $g_table_id . ' - ' . $g_task->get_last_error());
			$g_hdb->rollback();
			exit;
		}
	}
	else
	{
		// 送信失敗時処理
		$update_value =
		[
			'error_code' => strval($mailer->get_last_error_code()),
			'error_reason' => $mailer->get_last_error(),
			'error_detected' => true,
		];
		$g_task->failure(false, $update_value);

		if( $g_task->check_and_save() === false )
		{
			batchlog('Failed to Failure Task - ' . $g_table_id . ' - ' . $g_task->get_last_error());
			$g_hdb->rollback();
			exit;
		}
	}

	$g_hdb->commit();
	batchlog(microtime(true).' End Task - '.$g_table_id);
}

//--------------------------------------------------------------------------
//	メールにオプションの適用
//	
//	$mailer_ crow_mailインスタンス
//--------------------------------------------------------------------------
function apply_from_option( &$mailer_ )
{
	global $g_model;
	global $g_hdb_reader;
	global $g_task;

	$param = $g_task->to_named_array();
	$param['mimetype'] = $g_task->mimetype_text();
	foreach( $param as $k => $v )
	{
		if( method_exists($mailer_, $k) === true )
		{
			if( in_array($k, ['cc', 'bcc', 'reply_to']) === true )
			{
				if( $v === '' ) continue;

				if( strpos($v, ',') !== false )
				{
					$v = explode(',', $v);
				}
			}

			$mailer_->{$k}($v);
		}
	}

	$from = crow::get_argv('from', false);
	if( $from !== false )
	{
		$mailer_->from($from);
	}

	$name = crow::get_argv('name', false);
	if( $name !== false )
	{
		$mailer_->name($name);
	}

	$to = crow::get_argv('to', false);
	if( $to !== false )
	{
		if( strpos($to, ',') !== false )
		{
			$to = explode(',', $to);
		}
		$mailer_->to($to);
	}

	$cc = crow::get_argv('cc', false);
	if( $cc !== false )
	{
		if( strpos($cc, ',') !== false )
		{
			$cc = explode(',', $cc);
		}
		$mailer_->cc($cc);
	}

	$bcc = crow::get_argv('bcc', false);
	if( $bcc !== false )
	{
		if( strpos($bcc, ',') !== false )
		{
			$bcc = explode(',', $bcc);
		}
		$mailer_->bcc($bcc);
	}

	$reply_to = crow::get_argv('reply_to', false);
	if( $reply_to !== false )
	{
		if( strpos($reply_to, ',') !== false )
		{
			$reply_to = explode(',', $reply_to);
		}
		$mailer_->reply_to($reply_to);
	}

	$return_path = crow::get_argv('return_path', false);
	if( $return_path !== false )
	{
		$mailer_->return_path($return_path);
	}

	$subject = crow::get_argv('subject', false);
	if( $subject !== false )
	{
		$mailer_->subject($subject);
	}

	$body = crow::get_argv('body', false);
	if( $body !== false )
	{
		$mailer_->body($body);
	}

	$mimetype = crow::get_argv('mimetype', false);
	if( $mimetype !== false )
	{
		$mailer_->mimetype($mimetype);
	}

	$storage_ids = crow::get_argv('storage_ids', false);
	if( $storage_ids !== false )
	{
		$g_task->storage_ids = $storage_ids;
	}

	//	DB設定値を上書き
	$g_task->input_from_mail($mailer_);

	//	ファイル添付(ストレージテーブルの仕様に従う)
	if( $g_task->storage_ids !== '' )
	{
		$storage_id_row = explode(',', $g_task->storage_ids);
		$defines = $g_model::get_mail_task_defines();
		$storage_table_name = $defines[0];
		$model_storage = 'model_'.$storage_table_name;
		$table_design = $g_hdb_reader->get_design($storage_table_name);
		if( $table_design === false )
		{
			batchlog('Cannot get table_design - '.$storage_table_name);
			exit;
		}
		$pk_column = $table_design->primary_key;

		$storage_rows = $model_storage::create_array_from_sql(
			$model_storage::sql_select_all()
				->and_where_in($pk_column, $storage_id_row)
		);
		$files = [];
		foreach( $storage_rows as $storage_row )
		{
			$files[$storage_row->file_name] = $storage_row->read();
		}
		$mailer_->rawfiles($files);
	}
}

//--------------------------------------------------------------------------
//	エラーコードのカスタム
//	
//	$code_    エラーコード
//	$message_ エラーメッセージ
//--------------------------------------------------------------------------
function get_custom_error_code( $code_, /* $message_ */ )
{
	return $code_;
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
