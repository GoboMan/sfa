/*
	db_designのテーブルオプションに、"task"が付与されている場合に、
	crow_db.php により追加で読み込まれるコード
	db_designで、

		node, task()
		{
			...カラム...
		}

	とすると追加される。


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
	if( $params_ !== false && is_array($params_) === true && count($params_) > 0 )
	{
		foreach( $params_ as $key => $val )
		{
			if( property_exists($this, $key) === false ) continue;
			$this->{$key} = $val;
		}
	}
}
