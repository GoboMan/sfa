<?php

class	module_crowmin extends crow_module
{
	const CODE_OK		= 100;
	const CODE_NG		= 200;

	//--------------------------------------------------------------------------
	//	preload
	//--------------------------------------------------------------------------
	public function preload()
	{
		//	ログイン済みでなければログイン画面へ飛ばす
		if( crow_auth::is_logined() === false )
		{
			crow::redirect("auth");
			return false;
		}

		//	ログイン済みならプラグインを適用して通過
		crow_response::set('plugins', self::$m_plugins);
		return true;
	}

	//--------------------------------------------------------------------------
	//	プラグイン用
	//--------------------------------------------------------------------------
	const PLUGIN_PATH = CROW_PATH."app/classes/crowmin/plugins/";
	private static $m_plugins = [];
	public static function init_for_plugin()
	{
		//	プラグイン一覧取得
		$disk = crow_storage::disk();
		$plugin_dirs = $disk->get_dirs(self::PLUGIN_PATH);
		self::$m_plugins = [];
		if( count($plugin_dirs) > 0 )
		{
			foreach( $plugin_dirs as $plugin_dir )
				self::$m_plugins[] = $disk->extract_dirname($plugin_dir);
		}

		//	pluginのJSパスを追加
		if( count(self::$m_plugins) > 0 )
		{
			foreach( self::$m_plugins as $plugin_name )
			{
				crow::add_js_dir(self::PLUGIN_PATH.$plugin_name."/js/");
			}
		}

		//	pugins配下をオートロードの対象とする
		spl_autoload_register(function($class_name_)
		{
			if( substr($class_name_,0,15) != "module_crowmin_" ) return;
			$plugin_name = substr($class_name_,15);
			if( in_array($plugin_name, self::$m_plugins) !== true ) return;

			crow::add_view_dir(self::PLUGIN_PATH.$plugin_name."/views/");
			crow::add_css_dir(self::PLUGIN_PATH.$plugin_name."/css/");
			crow::add_js_dir(self::PLUGIN_PATH.$plugin_name."/js/");
			crow::add_query_dir(self::PLUGIN_PATH.$plugin_name."/query/");

			include_once self::PLUGIN_PATH.$plugin_name."/".$class_name_.".php";
		});
	}

	//--------------------------------------------------------------------------
	//	正常を出力して終了
	//--------------------------------------------------------------------------
	public function exit_ok( $data_ = '' )
	{
		$resp = crow_utility::array_to_json(
		[
			'r'	=> self::CODE_OK,
			'd'	=> $data_,
			'csrf'	=>
			[
				'key'	=> crow::get_csrf_key(),
				'val'	=> crow::get_csrf_val()
			]
		], true);
		crow::output_start();
		header("Content-Type: application/json; charset=utf-8");
		echo crow_utility::array_to_json(
		[
			'r'	=> self::CODE_OK,
			'd'	=> $data_,
			'csrf'	=>
			[
				'key'	=> crow::get_csrf_key(),
				'val'	=> crow::get_csrf_val()
			]
		]);
		crow::output_end();
		exit;
	}
	public function exit_ok_with_code( $code_, $data_ = '' )
	{
		crow::output_start();
		header("Content-Type: application/json; charset=utf-8");
		echo crow_utility::array_to_json(
		[
			'r'	=> $code_,
			'd'	=> $data_,
			'csrf'	=>
			[
				'key'	=> crow::get_csrf_key(),
				'val'	=> crow::get_csrf_val()
			]
		]);
		crow::output_end();
		exit;
	}

	//--------------------------------------------------------------------------
	//	異常を出力して終了
	//--------------------------------------------------------------------------
	public function exit_ng( $code_, $data_ = '' )
	{
		crow::output_start();
		header("Content-Type: application/json; charset=utf-8");
		echo crow_utility::array_to_json(
		[
			'r'	=> $code_,
			'd'	=> $data_,
			'csrf'	=>
			[
				'key'	=> crow::get_csrf_key(),
				'val'	=> crow::get_csrf_val()
			]
		]);
		crow::output_end();
		exit;
	}
}


?>
