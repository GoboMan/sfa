/*
	db_designのテーブルオプションに、"mail_task"が付与されている場合に、
	crow_db.php により追加で読み込まれるコード
	db_designで、

		node, mail_task()
		{
			...カラム...
		}

	とすると追加される。
	上記例では各種オプションが指定されていないため、
	各ロールのベースクラスなどで、最初に設定を行う。

	model_node::set_opt_storage_table("files")	// ストレージテーブル名

	それらをdb_designで一括で指定する場合には、

		node, mail_task(files)
		{
			...カラム...
		}

	とすればよい。

	Step 1. タスクの作成

		$row = model_xxxx::create();
		$row->check_and_save();

		//	即時実行ではなく実行時間を指定する場合
		//	例）現在の24時間後に実行
		//	$row->task_schedule_date = time() + 60*60*24;

	Step 2. タスクの実行

		//	10件ずつ実行のために拾う
		$rows = model_xxxx::pick_and_run(10);

		//	1件ずつ処理する
		$hdb = crow::get_hdb_writer();
		foreach( $rows as $row )
		{
			//	タスク単位でトランザクションを張るのがよい
			$hdb->begin();

			$row->xxxx = 処理結果や更新した値など

			//	success() か、failure() で結果を適用
			//	$row->failure();
			$row->success();

			//	保存
			if( $row->check_and_save() === false )
			{
				$hdb->rollback();
				break;
			}
			$hdb->commit();
		}

*/

//	db_designのオプションで指定した駆動条件
//	オプションを省略した場合や、自分で指定する場合には set_opt_xxx() で指定する
private static $m_opt_storage_table = false;

//--------------------------------------------------------------------------
//	コンストラクタ
//--------------------------------------------------------------------------
public function construct()
{
	$this->mimetype = crow_config::get('mail.mimetype', '')=='text/html' ?
		self::type_html : self::type_text
		;
}

//--------------------------------------------------------------------------
//	現在オプションの取得
//	これらのメソッドは手動で設定した値を取るもので、db_designで設定した値は取れない。
//	手動設定とdb_designの値をマージした値が必要な場合は、get_mail_task_defines()を使うこと
//--------------------------------------------------------------------------
public static function get_opt_storage_table()
{
	return self::$m_opt_storage_table;
}

//--------------------------------------------------------------------------
//	オプションの手動指定
//--------------------------------------------------------------------------
//	添付データソーステーブル名の指定。
public static function set_opt_storage_table( $table_name_ )
{
	self::$m_opt_storage_table = $table_name_;
}

//--------------------------------------------------------------------------
//	db_design 上でのオプション指定を読み込む
//	すでに手動で指定されていた場合は何もしない（手動指定が優先される）
//--------------------------------------------------------------------------
public static function get_mail_task_defines()
{
	$d_storage_table = false;

	$hdb = crow::get_hdb_reader();
	if( $hdb === false )
	{
		crow_log::error('failed to get db handle, mail_task::read_mail_task_defines');
		return [$d_storage_table];
	}

	//	手動指定の分を適用
	if( self::$m_opt_storage_table !== false ) $d_storage_table = self::$m_opt_storage_table;

	//	デフォルト定義を適用
	$table_design = $hdb->get_design(self::create()->m_table_name);
	foreach( $table_design->options as $option )
	{
		if( $option['name'] != "mail_task" ) continue;
		if( self::$m_opt_storage_table === false )
		{
			$d_storage_table = isset($option['args'][0]) ? $option['args'][0] : '';
		}
		break;
	}
	return [$d_storage_table];
}

//--------------------------------------------------------------------------
//	メール設定からタスクに適用
//	
//	$inst_ には送信までの設定をしたcrow_mailのインスタンスをセットする
//--------------------------------------------------------------------------
public function input_from_mail($inst_)
{
	$this->from = $inst_->from();
	$this->name = $inst_->name();
	$this->subject = $inst_->subject();

	$to = $inst_->to();
	$this->to = is_array($to) === true
		? implode(',', $to) : $to;

	$cc = $inst_->cc();
	$this->cc = is_array($cc) === true
		? implode(',', $cc) : $cc;

	$bcc = $inst_->bcc();
	$this->bcc = is_array($bcc) === true
		? implode(',', $bcc) : $bcc;

	$reply_to = $inst_->reply_to();
	$this->reply_to = is_array($reply_to) === true
		? implode(',', $reply_to) : $reply_to;

	$this->return_path = $inst_->return_path();

	//	mimetype
	$this->mimetype = $inst_->mimetype() == 'text/html' ?
		self::type_html : self::type_text;

	// テンプレート設定
	$this->body = $inst_->body();

	return $this;
}

//--------------------------------------------------------------------------
//	送信形式文字列の取得
//--------------------------------------------------------------------------
public static function get_mimetype_text($mimetype_)
{
	return $mimetype_ === self::type_html ? 'text/html' : 'text/plain';
}

//--------------------------------------------------------------------------
//	送信形式文字列の取得
//--------------------------------------------------------------------------
public function mimetype_text()
{
	return self::get_mimetype_text($this->mimetype);
}

//--------------------------------------------------------------------------
//	失敗したタスクを拾ってリトライ中とする
//
//	次の条件のタスクを失敗しているとみなす
//	1. ステータスが「処理失敗」（stat_failed）であるタスク
//	2. ステータスが「実行中」（stat_running）のまま
//	   一定時間（$time_limit_で指定した秒数）が経過したタスク
//
//	$time_limit_ に0を指定すると、2.の条件はスキップできる
//	拾ったタスクを「実行中」として、モデルの配列を返却する。
//	タスクの処理を行ったあと、success()かfailure()で結果をセットした上で
//	check_and_save()で保存すること。
//
//--------------------------------------------------------------------------
public static function pick_and_retry( $num_ = 1, $max_retry_ = 3, $time_limit_ = 0 )
{
	$hdb = crow::get_hdb_writer();

	$sql = self::sql_select_all()
		->and_where_open()
		->where('task_status', self::stat_failed)
		->and_where('task_retry', '<', $max_retry_)
		->where_close()
		;

	if( $time_limit_ > 0 )
	{
		$sql->or_where_open()
			->where('task_status', self::stat_running)
			->and_where('task_started_date', '<', date('Y-m-d H:i:s', time() - $time_limit_))
			->and_where('task_retry', '<', $max_retry_)
			->where_close()
			;
	}
	$sql->orderby_desc('task_priority')
		->and_orderby('task_schedule_date')
		->and_orderby('task_queued_date')
		->limit(0, $num_)
		->for_update()
		;

	$hdb->begin();

	$rows = self::create_array_from_sql($sql);
	if( count($rows) <= 0 )
	{
		$hdb->rollback();
		return [];
	}

	$ids = crow_utility::object_column($rows, self::primary_key);
	$now = time();

	//	処理中にして一旦コミットする
	$result = $hdb->query( crow_db_sql::create_update()
		->from(self::table_name)
		->value("task_status", self::stat_running)
		->value("task_started_date", date('Y-m-d H:i:s', $now))
		->value("task_ended_date", date('Y-m-d H:i:s', 0))
		->value_rawval("task_retry", "task_retry + 1")
		->where_rawval(self::primary_key, "in", "(".implode(",", $ids).")")
		->build()
	);
	if( $result === false )
	{
		$hdb->rollback();
		return [];
	}
	$hdb->commit();

	//	取得した行配列を返却
	foreach( $rows as $index => $row )
	{
		$rows[$index]->task_status = self::stat_running;
		$rows[$index]->task_started_date = $now;
		$rows[$index]->task_ended_date = 0;
		$rows[$index]->task_retry = intval($rows[$index]->task_retry) + 1;
	}
	return $rows;
}

//--------------------------------------------------------------------------
//	未処理のタスクを拾って処理中にする
//
//	指定した数の処理待ちタスクのうちtask_schedule_dateが現在日時より古いものを
//	優先度が高い順、実行予定日時の古い順、作成日の古い順番に取得する。
//	取得したタスクを「実行中」として、モデルの配列を返却する。
//	タスクの処理を行ったあと、success()かfailure()で結果をセットした上で
//	check_and_save()で保存すること。
//--------------------------------------------------------------------------
public static function pick_and_run( $num_ = 1 )
{
	$now = time();
	$hdb = crow::get_hdb_writer();
	$hdb->begin();
	$rows = self::create_array_from_sql(
		self::sql_select_all()
			->and_where('task_status', self::stat_queued)
			->and_where('task_schedule_date', '<=', date('Y-m-d H:i:s',$now))
			->orderby_desc('task_priority')
			->and_orderby('task_schedule_date')
			->and_orderby('task_queued_date')
			->limit(0, $num_)
			->for_update()
	);
	if( count($rows) <= 0 )
	{
		$hdb->rollback();
		return [];
	}

	$ids = crow_utility::object_column($rows, self::primary_key);

	//	処理中にして一旦コミットする
	$result = $hdb->query( crow_db_sql::create_update()
		->from(self::table_name)
		->values(
		[
			"task_status" => self::stat_running,
			"task_started_date" => date('Y-m-d H:i:s', $now),
			"task_ended_date" => date('Y-m-d H:i:s', 0),
			"task_retry" => 0,
		])
		->where_rawval(self::primary_key, "in", "(".implode(",", $ids).")")
		->build()
	);
	if( $result === false )
	{
		$hdb->rollback();
		return [];
	}
	$hdb->commit();

	//	取得した行配列を返却
	foreach( $rows as $index => $row )
	{
		$rows[$index]->task_status = self::stat_running;
		$rows[$index]->task_started_date = $now;
		$rows[$index]->task_ended_date = 0;
		$rows[$index]->task_retry = 0;
	}
	return $rows;
}

//--------------------------------------------------------------------------
//	タスク成功とする
//	タスク関連以外のカラムも更新する場合は「params_」で連想配列を指定する
//--------------------------------------------------------------------------
public function success( $time_ = false, $params_ = false )
{
	$this->task_status = self::stat_succeeded;
	$this->task_ended_date = $time_===false ? time() : $time_;
	if( $params_ !== false && is_array($params_) === true && count($params_) > 0 )
	{
		foreach( $params_ as $key => $val )
		{
			if( property_exists($this, $key) === false ) continue;
			$this->{$key} = $val;
		}
	}
}

//--------------------------------------------------------------------------
//	タスク失敗とする
//	タスク関連以外のカラムも更新する場合は「params_」で連想配列を指定する
//--------------------------------------------------------------------------
public function failure( $time_ = false, $params_ = false )
{
	$this->task_status = self::stat_failed;
	$this->task_ended_date = $time_===false ? time() : $time_;

	$protocol = crow_config::get('mail.protocol', 'local');
	if( $protocol === 'local' )
	{
	}
	else if( $protocol === 'smtp' )
	{
	}
	else if( $protocol === 'aws' )
	{
		$throttle_code = '454';
		if( $this->error_code == $throttle_code )
		{
			$this->task_status = self::stat_queued;
		}
	}

	if( $params_ !== false && is_array($params_) === true && count($params_) > 0 )
	{
		foreach( $params_ as $key => $val )
		{
			if( property_exists($this, $key) === false ) continue;
			$this->{$key} = $val;
		}
	}
}
