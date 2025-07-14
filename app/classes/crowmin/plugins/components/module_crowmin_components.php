<?php
/*

	コンポーネント管理

*/
class	module_crowmin_components extends module_crowmin
{
	//--------------------------------------------------------------------------
	//	index
	//--------------------------------------------------------------------------
	public function action_index()
	{
		//	コンポーネントリスト読み込み
		list($components, $realpaths) = self::load_components_file();
		crow_response::set('apply_list', $components);

		//	実際に存在するコンポーネント一覧を作成
		$disk = crow_storage::disk();
		$disk_dirs = $disk->get_dirs(CROW_PATH."components");
		$exists_dirs = [];
		if( count($disk_dirs) > 0 )
		{
			foreach( $disk_dirs as $dir )
				$exists_dirs[] = crow_storage::extract_dirname($dir);
		}
		crow_response::set('exists_dirs', $exists_dirs);

		//	現在アプライ済みの一覧取得
		$applied = crow_component::get_applied_components();
		crow_response::set('applied', $applied);
		crow_response::set('last_applied_component', end($applied));

		//	バックアップされている一覧を取得
		$backup_dir = CROW_PATH."output/backup/before_override";
		$root_len = strlen($backup_dir);
		$backup_items = $disk->get_files($backup_dir, true, [], true);
		$backup_files = [];
		foreach( $backup_items as $item )
		{
			$path = substr($item['path'], $root_len + 1);
			$path = str_replace("\\", "/", $path);
			$backup_files[] = $path;
		}

		//	現状のファイル一覧
		$items = $disk->get_files(CROW_PATH."app", true, [], true);
		$root_len = strlen(CROW_PATH."app");
		$hierarchy = [];
		foreach( $items as $index => $item )
		{
			$path = substr($item['path'], $root_len + 1);
			$path = str_replace("\\", "/", $path);

			$dir = crow_storage::extract_dirpath($path);
			$subdirs = explode("/", $dir);

			$current = &$hierarchy;
			foreach( $subdirs as $subdir )
			{
				if( isset($current[$subdir]) === false )
					$current[$subdir] = [];
				$current = &$current[$subdir];
			}

			unset($items[$index]['path']);
			$items[$index]['name'] = crow_storage::extract_filename($path);
			$items[$index]['dir'] = $dir;
			$items[$index]['stat'] = '';

			//	コンポーネントによる改変ありかチェック

			//	元ファイルが存在するか
			if( in_array("app/".$path, $backup_files) === false )
			{
				//	存在しないので新規追加
				$items[$index]['stat'] = 'add';
			}

			//	変更されたか
			else
			{
				$current_item_path = CROW_PATH."app/".$items[$index]['dir']."/".$items[$index]['name'];
				$backup_item_path = $backup_dir."/app/".$items[$index]['dir']."/".$items[$index]['name'];
				if( file_get_contents($current_item_path) != file_get_contents($backup_item_path) )
				{
					$items[$index]['stat'] = 'mod';
				}
			}
		}

		//	削除されたファイルを探し出す
		foreach( $backup_files as $backup_file )
		{
			$found = false;
			foreach( $items as $item )
			{
				$current_item_path = "app/".$item['dir']."/".$item['name'];
				if( $current_item_path == $backup_file )
				{
					$found = true;
					break;
				}
			}
			if( $found === false )
			{
				//	4は "app/" の文字列長
				$backup_file_dir = crow_storage::extract_dirpath(substr($backup_file, 4));

				$items[] =
				[
					'name' => crow_storage::extract_filename($backup_file),
					'dir' => $backup_file_dir,
					'stat' => 'del',
				];
			}
		}

		//	itemsを名前でソートしておく
		usort($items, function($a_, $b_)
		{
			if( $a_['name'] > $b_['name'] ) return 1;
			if( $a_['name'] < $b_['name'] ) return -1;
			return 0;
		});

		//	viewへ
		crow_response::set('items', $items);
		crow_response::set('hierarchy', $hierarchy);
		crow_response::set('deleted', $backup_deleted);
	}

	private function load_components_file()
	{
		//	ファイル頭と末尾にphpタグがあれば除去する
		$fname = self::P(CROW_PATH."components/components.php");
		$src = trim(file_get_contents($fname));
		if( substr($src, 0, 5) == "<"."?php" && substr($src, -2) == "?".">" )
			$src = trim(substr($src, 5, strlen($src) - 7));

		//	実行できる形に内部を調整する
		//
		//	後でif構文判定時や、component.php（components.phpではない）内でもevalを使う。
		//	その時に、ここで定義した関数が生き続けてしまうため都度定義してしまうとdup declare となる。
		//	その対策として、この段階でまとめて関数を仕込むこととする。
		$php = ""
			."namespace components { use \\crow_component;"
				.$src
				.'if( function_exists("components\\config") ) crow_component::conf_set(config());'
				.'function conf($key_, $def_ = \'\') {return crow_component::conf($key_, $def_);}'
				."return [function_exists('components\\components') ? components() : [], function_exists('components\\realpaths') ? realpaths() : []];"
			."}"
			;
		return eval($php);
	}

	//--------------------------------------------------------------------------
	//	指定したコンポーネントまで、適用状態を元に戻す。
	//	空文字を指定することで、すべてのコンポーネントを剥がす。
	//--------------------------------------------------------------------------
	public function action_ajax_restore()
	{
		//	コンポーネントリスト読み込み
		list($components, $roles) = $this->load_components_file();

		//	指定されたところまでのコンポーネントリストを作成
		$component_name = crow_request::get('component', '');
		$new_apply_list = [];
		if( $component_name != "" )
		{
			$found = false;
			foreach( $components as $component )
			{
				if( $component == $component_name )
				{
					$new_apply_list[] = $component;
					$found = true;
				}
				else if( $found === false )
					$new_apply_list[] = $component;
			}
		}

		//	処理中ならエラーとする
		if( crow_component::is_locked() === true )
			app::exit_ng('applying components, crow is locked.');

		//	上記で作成したリストを指定して再構築する
		crow_component::apply(true, $new_apply_list);

		//	正常終了
		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	diff取得
	//--------------------------------------------------------------------------
	public function action_ajax_diff()
	{
		$i_path = crow_request::get('path', false);
		if( $i_path === false ) app::exit_ng();

		$diff = crow_component::diff("app/".$i_path);
		app::exit_ok($diff);
	}

	//--------------------------------------------------------------------------
	//	コンポーネントソースの取得
	//--------------------------------------------------------------------------
	public function action_ajax_get_component_src()
	{
		$i_path = crow_request::get('path', false);
		$i_component_name = crow_request::get('component_name', false);
		if( $i_path === false ) app::exit_ng();
		if( $i_component_name === false ) app::exit_ng();

		//	コンポーネントソースを取得
		$i_path = self::P($i_path);
		$dir = crow_storage::extract_dirpath($i_path)."/";
		$fname = crow_storage::extract_filename_without_ext($i_path);
		$ext = crow_storage::extract_ext($i_path);

		$override_src = self::P(CROW_PATH."components/".$i_component_name."/app/".$dir.$fname."@override.".$ext);
		$normal_src = self::P(CROW_PATH."components/".$i_component_name."/app/".$dir.$fname.".".$ext);
		$remove_src = self::P(CROW_PATH."components/".$i_component_name."/app/".$dir."!".$fname.".".$ext);

		$src = "";
		if( is_file($override_src) === true )
		{
			$lines = self::array_rtrim(file($override_src));
			$lines[] = "";

			$disp_path = str_replace("\\", "/", $dir.$fname."@override.".$ext);
			$src = ["override", $disp_path, $lines];
		}
		else if( is_file($normal_src) === true )
		{
			$lines = self::array_rtrim(file($normal_src));
			$lines[] = "";

			$disp_path = str_replace("\\", "/", $dir.$fname.".".$ext);
			$src = ["normal", $disp_path, $lines];
		}
		else if( is_file($remove_src) === true )
		{
			$src = ["removed"];
		}
		else
		{
			$src = ["notfound"];
		}
		app::exit_ok($src);
	}

	//--------------------------------------------------------------------------
	//	コンポーネントソースの保存
	//--------------------------------------------------------------------------
	public function action_ajax_save_component_src()
	{
		$i_path = crow_request::get('path', false);
		$i_component_name = crow_request::get('component_name', false);
		$i_body = crow_request::get('body', false);
		$i_type = crow_request::get('type', false);

		if( $i_path === false || $i_component_name == false ||
			$i_body === false || $i_type === false
		)	app::exit_ng();

		$i_path = self::P($i_path);
		$dir = crow_storage::extract_dirpath($i_path)."/";
		$fname = crow_storage::extract_filename_without_ext($i_path);
		$ext = crow_storage::extract_ext($i_path);

		if( $i_type == "override" )
		{
			$override_src = self::P(CROW_PATH."components/".$i_component_name."/app/".$dir.$fname."@override.".$ext);
			file_put_contents($override_src, $i_body);
		}
		else if( $i_type == "normal" )
		{
			$normal_src = self::P(CROW_PATH."components/".$i_component_name."/app/".$dir.$fname.".".$ext);
			file_put_contents($normal_src, $i_body);
		}
		else app::exit_ng();

		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	空ファイルの作成
	//--------------------------------------------------------------------------
	public function action_ajax_create_empty()
	{
		$i_path = crow_request::get('path', false);
		$i_fname = crow_request::get('fname', false);
		$i_component_name = crow_request::get('component_name', false);

		if( $i_path === false || $i_fname === false || $i_component_name == false )
			app::exit_ng();

		//	入力されたファイル名を拡張子とファイル名に分割
		//	拡張子は"."を先頭に含める
		$fname = crow_storage::extract_filename_without_ext($i_fname);
		$ext = crow_storage::extract_ext($i_fname);
		if( $ext != "" ) $ext = ".".$ext;

		//	"@override"を除去
		$is_override = strpos($fname, "@override") !== false;
		if( $is_override ) $fname = substr($fname, 0, strlen($fname) - strlen("@override"));

		//	各パスを計算する
		$path_builded = self::P(CROW_PATH."app/".$i_path."/".$fname.$ext);
		$path_src = self::P(CROW_PATH."components/".$i_component_name."/app/".$i_path."/".$i_fname);
		$path_backup = self::P(CROW_PATH."output/backup/before_override/app/".$i_path."/".$fname.$ext);

		//	デフォルトへの新規の場合
		if( $i_component_name == "" )
		{
			//	構築済みスペースへファイル作成
			if( is_file($path_builded) === true ) app::exit_ng('already exists file '.$path_builded);

			//	バックアップ領域へファイル作成
			if( is_file($path_backup) === true ) app::exit_ng('already exists file '.$path_backup);

			file_put_contents($path_builded, "");
			file_put_contents($path_backup, "");
		}
		//	コンポーネント指定の新規の場合
		else
		{
			//	構築済みスペースへファイル作成
			if( is_file($path_builded) === true ) app::exit_ng('already exists file '.$path_builded);

			//	コンポーネントソース領域へファイル作成
			if( is_file($path_src) === true ) app::exit_ng('already exists file '.$path_src);

			file_put_contents($path_builded, "");
			file_put_contents($path_src, "");
		}

		app::exit_ok();
	}

	//--------------------------------------------------------------------------
	//	配列の行末改行を除去
	//--------------------------------------------------------------------------
	private static function array_rtrim($lines_)
	{
		$new_lines = [];
		foreach( $lines_ as $line ) $new_lines[] = rtrim($line, "\r\n");
		return $new_lines;
	}

	//--------------------------------------------------------------------------
	//	OSごとのパスの違いを解決
	//--------------------------------------------------------------------------
	public static function P($path_)
	{
		return self::$m_delim == "\\" ?
			str_replace("/", "\\", $path_) :
			str_replace("\\", "/", $path_)
			;
	}

	private static $m_delim = DIRECTORY_SEPARATOR;
}


?>
