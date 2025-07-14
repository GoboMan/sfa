<?php
/*

	crow partial view

	ビューパーツをパース、結合してキャッシュを作成する
	キャッシュはアクション単位で作られる

	※ パーツファイルの構成
	<props></props>
	<template></template>
	<style></style>
	<init></init>
	<ready></ready>
	<method></method>
	<watch></watch>
	<recv></recv>
	<test></test>

	仕様
	http://php-crow.com/?p=1657

*/
class crow_viewpart
{
	//--------------------------------------------------------------------------
	//	HTML取得
	//--------------------------------------------------------------------------
	public static function html( $role_ = false, $module_ = false, $action_ = false )
	{
		$sections = self::build($role_, $module_, $action_);
		if( $sections === false || count($sections) <= 0 ) return '';
		return $sections['html'];
	}

	//--------------------------------------------------------------------------
	//	icss未コンパイル状態のソースを取得
	//--------------------------------------------------------------------------
	public static function icss( $role_ = false, $module_ = false, $action_ = false )
	{
		$sections = self::build($role_, $module_, $action_);
		if( $sections === false || count($sections) <= 0 ) return '';
		return $sections['icss'];
	}

	//--------------------------------------------------------------------------
	//	JSソースを取得
	//--------------------------------------------------------------------------
	public static function js( $role_ = false, $module_ = false, $action_ = false )
	{
		$sections = self::build($role_, $module_, $action_);
		if( $sections === false || count($sections) <= 0 ) return '';
		return $sections['js'];
	}

	//--------------------------------------------------------------------------
	//	ビルド
	//--------------------------------------------------------------------------
	private static function build( $role_, $module_, $action_ )
	{
		$role = $role_ !== false ? $role_ : crow_request::get_role_name();
		$module = $module_ !== false ? $module_ : crow_request::get_module_name();
		$action = $action_ !== false ? $action_ : crow_request::get_action_name();
		$lang = crow_msg::lang();

		//	action用のviewpartsが存在しないならスキップする。
		//	viewpartsを使っていないactionで、commonのコンパイルが走らないようにするため。
		$root_path = CROW_PATH."app/viewparts/".$role."/".$module."/".$action;
		$disk = crow_storage::disk();
		$fnames = $disk->get_files($root_path, false, ["php"]);
		if( count($fnames) <= 0 ) return false;

		//	自身のキャッシュにあればそれを返却
		$cache_fname = "partial_".$role."_".$module."_".$action."_".$lang;
		if( isset(self::$m_caches[$cache_fname]) === true )
			return self::$m_caches[$cache_fname];

		//	crowキャッシュにあればそれを返却
		$parts = crow_cache::load($cache_fname);
		if( $parts !== false )
		{
			self::$m_caches[$cache_fname] = $parts;
			return $parts;
		}

		//	exeがあればそれを実行
		$compiler = trim(crow_config::get_if_exists('viewpart.compiler', ''));
		if( $compiler != "" )
		{
			$separator = "/";
			if( strtolower(substr($compiler, -4)) == ".exe" ) $separator = "\\";
			$compiler = str_replace("[CROW_PATH]", CROW_PATH, $compiler);

			//	ファイルパスはwindowsならエスケープする
			$crow_path = CROW_PATH;
			$tmp_fname = CROW_PATH."output".$separator."temp".$separator.md5(uniqid(rand(),1));
			$crow_path_esc = $crow_path;
			$tmp_fname_esc = $tmp_fname;
			if( $separator == "\\" )
			{
				$crow_path_esc = str_replace("\\", "\\\\", $crow_path_esc);
				$tmp_fname_esc = str_replace("\\", "\\\\", $tmp_fname_esc);
			}

			//	実行
			exec($compiler." \"".$crow_path_esc."\" ".$role." ".$module." ".$action." \"".$tmp_fname_esc."\"");

			//	結果読み込み
			$code_html = file_get_contents($tmp_fname."_html");
			$code_icss = file_get_contents($tmp_fname."_icss");
			$code_js = file_get_contents($tmp_fname."_js");
			unlink($tmp_fname."_html");
			unlink($tmp_fname."_icss");
			unlink($tmp_fname."_js");

			//	キャッシュに保持
			$sections = ['html' => $code_html, 'icss' => $code_icss, 'js' => $code_js];
			self::$m_caches[$cache_fname] = $sections;
			crow_cache::save($cache_fname, $sections);
			return $sections;
		}

		//	共通パーツ
		//
		//	1. app/viewparts/_common_/[any][/any].../[any].part
		//	2. app/viewparts/[role]/_common_/[any][/any].../[any].part
		//	3. app/viewparts/[role]/[module]/_common_/[any][/any].../[any].part
		//	4. app/viewparts/[role]/[module]/[action]/[any].part
		//
		//	サブフォルダを作ると、パーツ名はアンダーバーで繋げた名前となる。
		//	またその場合、単にアンダーバーだけのファイル名の場合は、そのフォルダまでのパスがパーツ名となる
		//
		//	例）
		//	viewparts/front/_common_/create_note/_.part			→ モジュール名は、cretae_note
		//	viewparts/front/_common_/create_note/buttons.part	→ モジュール名は、cretae_note_buttons
		//	viewparts/front/_common_/create_note/button.part	→ モジュール名は、cretae_note_button
		$part_paths =
		[
			CROW_PATH."app/viewparts/_common_",
			CROW_PATH."app/viewparts/".$role."/_common_",
			CROW_PATH."app/viewparts/".$role."/".$module."/_common_",
			CROW_PATH."app/viewparts/".$role."/".$module."/".$action,
		];
		$parts = [];
		foreach( $part_paths as $part_path )
		{
			self::parse_with_dir($part_path, "", $parts);
		}

		//	埋め込みコードを解決する
		self::link_embed_part_tags($parts);
		self::link_embed_bind_texts($parts);

		//	html/icss/js を作成
		$html = "";
		if( count($parts) > 0 )
		{
			//	html
			foreach( $parts as $name => $part )
			{
				if( isset($part['template']) === false ) continue;
				$module = isset($part['module']) ? $part['module'] : '';
				$html .= '<template viewpart="'.$name.'" module="'.$module.'">'.$part['template'].'</template>';
			}
		}

		//	icss
		$icss = "";
		if( count($parts) > 0 )
		{
			$icss = implode(" ", array_column($parts, 'style'));
		}

		//	js
/*
		viewpart.prototype._initpart_ = function()
		{
			let self = this;

			if( self.m.name == "" ) {}
			else if( self.m.name == "data_table" )
			{
				self.m.module_name = "xxx";

				//	------ 動的埋め込みコード -------
				//	....
			}
		};
		viewpart.prototype._props_ = function()
		{
			if( self.m.name == "" ) return {};
			else if( self.m.name == "data_table" )
			{
				return
				//	------ 動的埋め込みコード -------
				//	{....}
				;
			}
		};
		viewpart.prototype._ready_ = function()
		{
			let self = this;

			if( self.m.name == "" ) {}
			else if( self.m.name == "data_table" )
			{
				self.m.module_name = "xxx";

				//	------ 動的埋め込みコード -------
				//	....
			}
		};
		viewpart.prototype._watch_ = function()
		{
			let self = this;

			if( self.m.name == "" ) return;
			else if( self.m.name == "data_table" )
			{
				self.m.module_name = "xxx";

				//	------ 動的埋め込みコード -------
				//	....
			}
		};
		viewpart.prototype._method_ = function()
		{
		};
		viewpart.prototype._recv_ = function()
		{
		};
		viewpart.prototype._syswatch_ = function()
		{
		};
		viewpart.prototype._test_ = function()
		{
		};
*/
		$js = "";
		if( count($parts) > 0 )
		{
			$js_inits = "";
			$js_ready = "";
			$js_props = "";
			$js_watch = "";
			$js_recv = "";
			$js_method = "";
			$js_syswatch = "";
			$js_test = "";

			foreach( $parts as $name => $part )
			{
				if( isset($part['sysinit']) === true || isset($part['init']) === true )
				{
					$js_inits .= "else if( self.m.name=='".$name."'){"
						.(isset($part['sysinit']) === true ? $part['sysinit'] : '')
						.(isset($part['init']) === true ? $part['init'] : '')
						."}";
				}
				if( isset($part['props']) === true )
				{
					$js_props .= "else if( this.m.name=='".$name."'){ return "
						.$part['props'].";}";
				}
				if( isset($part['ready']) === true )
				{
					$js_ready .= "else if( this.m.name=='".$name."'){".$part['ready']."}";
				}
				if( isset($part['watch']) === true )
				{
					$js_watch .= "else if( self.m.name=='".$name."'){ return "
						.$part['watch'].";}";
				}
				if( isset($part['recv']) === true )
				{
					$js_recv .= "else if( self.m.name=='".$name."'){ return "
						.$part['recv'].";}";
				}
				if( isset($part['method']) === true )
				{
					$js_method .= "else if( self.m.name=='".$name."'){ return "
						.$part['method'].";}";
				}
				if( isset($part['syswatch']) === true )
				{
					$js_syswatch .= "else if( self.m.name=='".$name."'){ return "
						.$part['syswatch'].";}";
				}
				if( isset($part['test']) === true )
				{
					$js_test .= "else if(self.m.name=='".$name."'){ return "
						.$part['test'].";}";
				}
			}

			if( $js_inits != "" )
			{
				$js_inits = "viewpart.prototype._initpart_ = function(){"
					.'let self = this; self.watch_start(); if(self.m.name==""){}'.$js_inits."};";
			}
			else
			{
				$js_inits = "viewpart.prototype._initpart_ = function(){};";
			}

			if( $js_props != "" )
			{
				$js_props = "viewpart.prototype._props_ = function(){"
					.'if(this.m.name=="") return {};'.$js_props."return {};};";
			}
			else
			{
				$js_props = "viewpart.prototype._props_ = function(){};";
			}

			if( $js_ready != "" )
			{
				$js_ready = "viewpart.prototype._ready_ = function(){"
					.'let self = this; if(self.m.name==""){}'.$js_ready."};";
			}
			else
			{
				$js_ready = "viewpart.prototype._ready_ = function(){};";
			}

			if( $js_watch != "" )
			{
				$js_watch = "viewpart.prototype._watch_ = function(){"
					.'let self = this; if(self.m.name=="") return {};'.$js_watch."return {};};";
			}
			else
			{
				$js_watch = "viewpart.prototype._watch_ = function(){return {};};";
			}

			if( $js_recv != "" )
			{
				$js_recv = "viewpart.prototype._recv_ = function(){"
					.'let self = this; if(self.m.name=="") return {};'.$js_recv."return {};};";
			}
			else
			{
				$js_recv = "viewpart.prototype._recv_ = function(){return {};};";
			}

			if( $js_method != "" )
			{
				$js_method = "viewpart.prototype._method_ = function(){"
					.'let self = this; if(self.m.name=="") return {};'.$js_method."return {};};";
			}
			else
			{
				$js_method = "viewpart.prototype._method_ = function(){return {};};";
			}

			if( $js_syswatch != "" )
			{
				$js_syswatch = "viewpart.prototype._syswatch_ = function(){"
					.'let self = this; if(self.m.name=="") return {};'.$js_syswatch."return {};};";
			}
			else
			{
				$js_syswatch = "viewpart.prototype._syswatch_ = function(){return {};};";
			}
			$js = $js_inits.$js_syswatch.$js_props.$js_ready.$js_watch.$js_recv.$js_method;

			//	必要な場合はテストコードを追加する
			$config_val = crow_config::get("viewpart.test", '');
			if( ($config_val == "auto" || $config_val == "manual") && $js_test != "" )
			{
				$js .= "viewpart.prototype._test_opt_ = function(){return '".$config_val."';};";
				$js .= "viewpart.prototype._test_ = function(){"
					.'let self = this; if(self.m.name=="") return {};'.$js_test."return {};};";
			}
		}

		//	キャッシュに保持
		$sections = ['html' => $html, 'icss' => $icss, 'js' => $js];
		self::$m_caches[$cache_fname] = $sections;
		crow_cache::save($cache_fname, $sections);
		return $sections;
	}

	//--------------------------------------------------------------------------
	//	ディレクトリを指定して、配下のモジュールとパーツファイルをパースし、
	//	キーがパーツ名で値がパーツの配列を返却する
	//--------------------------------------------------------------------------
	private static function parse_with_dir( $dir_, $module_ = "", &$result_ = [] )
	{
		$disk = crow_storage::disk();

		//	ディレクトリ（モジュール）を解析、これは再帰構造になる可能性あり
		$module_dirs = $disk->get_dirs($dir_);
		foreach( $module_dirs as $module_dir )
		{
			$module_name = crow_storage::extract_dirname($module_dir);
			if( $module_ != "" ) $module_name = $module_."_".$module_name;
			self::parse_with_dir($module_dir, $module_name, $result_);
		}

		//	単一パーツを解析
		$fnames = $disk->get_files($dir_, false, ["php"]);
		foreach( $fnames as $fname )
		{
			$part = self::parse($fname, $module_);
			if( isset($part['name']) === false ) continue;
			$result_[$part['name']] = $part;
		}
	}

	//--------------------------------------------------------------------------
	//	パーツファイルパースして、セクションの連想配列を返却する
	//
	//	[
	//		name : "xxx",
	//		module : "xxx",
	//		props : "xxx",
	//		template : "xxx",
	//		style : "xxx",
	//		init : "xxx",
	//		ready : "xxx",
	//		method : "xxx",
	//		recv : "xxx",
	//		test : "xxx",
	//	]
	//--------------------------------------------------------------------------
	private static function parse( $fname_, $module_ )
	{
		$sections = ["props", "template", "style", "init", "ready", "watch", "method", "recv", "test"];
		$open_tags = [];
		$close_tags = [];
		foreach( $sections as $section )
		{
			$open_tags[] = "<".$section.">";
			$close_tags[] = "</".$section.">";
		}

		ob_start();
		eval("?>".file_get_contents($fname_));
		$lines = explode("\n", ob_get_contents());
		ob_end_clean();

		$section = false;
		$codes = [];
		foreach( $lines as $line )
		{
			if( $section === false )
			{
/*
				$compos = strpos($line, "//");
				if( $compos !== false )
				{
					//	先頭の"//"のみチェック。そうしないとhttp://なども引っかかってしまう
					if( strlen(trim(substr($line, 0, $compos))) <= 0 )
						$line = substr($line, 0, $compos);
				}
*/
			}

			if( $section === false )
			{
				$line = trim($line);
				$index = array_search($line, $open_tags);
				if( $index === false ) continue;
				$section = $sections[$index];
				$codes[$section] = "";
			}
			else
			{
				$index = array_search(trim($line), $close_tags);
				if( $index !== false )
				{
					$section = false;
					continue;
				}
				$codes[$section] .= $line."\n";
			}
		}

		//	ブラケットを除去して空のセクションは破棄する
		$extract_codes = [];
		foreach( $codes as $section => $code )
		{
			$extract = trim($code);
			if(
				substr($extract, 0, 1) == "{" &&
				substr($extract, -1) == "}"
			){
				$extract = trim(substr($extract, 1, strlen($extract) - 2));
				if( $extract == "" ) continue;
			}
			$extract_codes[$section] = $code;
		}
		$codes = $extract_codes;

		//	テンプレから作成したインスタンスの追加先に、パーツ名を属性として付与するので
		//	スタイルもその属性から辿るようにしておく
		$part_name = crow_storage::extract_filename_without_ext($fname_);
		if( $module_ != "" )
		{
			//	パーツ名が"_"の場合とパーツ名＝モジュール名末尾の場合は、モジュール名と同じパーツ名とする
			if( $part_name == "_" ) $part_name = $module_;
			else if( substr($module_, 0, strlen($part_name)) == $part_name ) $part_name = $module_;
			else $part_name = $module_."_".$part_name;
		}

		$codes["name"] = $part_name;
		$codes["module"] = $module_;
		if( isset($codes["style"]) === true )
			$codes["style"] = '[viewpart*=":'.$codes["name"].'"] {'.$codes["style"].'}';

		return $codes;
	}

	//--------------------------------------------------------------------------
	//	埋め込みパーツタグの解決
	//
	//	あるパーツ内で別のパーツが
	//
	//		[[part_name arg1="val1" arg2=12345 arg3=:propname1 arg4=@propname1]]
	//
	//	のように埋め込まれている。
	//	これをdom解析が通るように、templateタグに置き換える
	//
	//	":"指定の場合はプロパティとバインドされる
	//	"@"指定の場合はプロパティの値を初回のみセットする
	//	これらプレフィクスの特殊制御はJS側で行う
	//--------------------------------------------------------------------------
	private static function link_embed_part_tags( &$parts_ )
	{
		$pattern = '/\[\[(.*?)\]\]/isu';
		foreach( $parts_ as $name => $part )
		{
			if( isset($part["template"]) === false ) continue;

			$src = $part["template"];
			if( preg_match_all($pattern, $src, $m, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE) )
			{
				for( $i=count($m[0]) - 1; $i >= 0; $i-- )
				{
					$code = $m[0][$i][0];
					$len = strlen($code);
					$offset = $m[0][$i][1];

					$code = substr($code, 2, strlen($code) - 4);
					$pos = strpos($code, " ");
					$cmd = "";
					$args = "";
					if( $pos === false )
					{
						$cmd = $code;
					}
					else
					{
						$cmd = substr($code, 0, $pos);
						$args = trim(substr($code, $pos + 1));
					}

					//	パーツ名が現在モジュールに含まれる別のパーツだった場合、
					//	モジュールを付与したパーツ名に置き換える
					if( $part['module'] != "" )
					{
						if( array_key_exists($part['module']."_".$cmd, $parts_) === true )
							$cmd = $part['module']."_".$cmd;
					}
					$src = substr($src, 0, $offset)
						.'<template from="'.$cmd.'" '.$args.'></template>'
						.substr($src, $offset + $len)
						;
				}
			}
			$parts_[$name]["template"] = $src;
		}
	}

	//--------------------------------------------------------------------------
	//	埋め込みバインドテキストの解決
	//
	//	<span>{{ msg }}</span>
	//
	//	のようにテキストバインドが埋め込まれているタグについて、
	//	self.bind_text(self.ref("ランダムハッシュ値"), "msg")
	//	と同等の処理が行えるように置き換える
	//	タグにref指定がない場合は自動でランダムなハッシュを割り当てる。
	//
	//--------------------------------------------------------------------------
	private static function link_embed_bind_texts( &$parts_ )
	{
		$pattern = '/<(([^<]*?)[^>]*)>([^<]*{{.*?}}[^<\/]*)<\/\2>/isu';

		foreach( $parts_ as $name => $part )
		{
			if( isset($part["template"]) === false ) continue;

			$src = $part["template"];
			$init_text_codes = "";

			$auto_ref_count = 0;
			if( preg_match_all($pattern, $src, $m, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE) )
			{
				$bind_infos = [];
				for( $i = count($m[0]) - 1; $i >= 0; $i-- )
				{
					//	タグ解析
					list($tag, $attrs) = self::parse_element($m[1][$i][0]);

					//	属性にrefを含まない場合は自動で作成
					//
					//	自動で作成する名前について。htmlのリクエストとjsのリクエストが異なるので、
					//	ランダム文字列だとhtml側とjs側のrefが一致しなくなる。
					//	そのため連番で名前を作成する
					$ref = "";
					$added_ref = false;
					if( isset($attrs['ref']) === true ) $ref = $attrs['ref'];
					else
					{
						$ref = "ref".sprintf("%03d", ++$auto_ref_count);
						$added_ref = true;
					}

					//	テキスト部分から計算式を作成し、初期化時に流すようにする
					list($text_exp, $binds) = self::make_text_expression($m[3][$i][0]);

					$init_text_codes .= 'self.array_each(self.refs("'.$ref.'"), function(re_){re_.innerHTML='.$text_exp.';});';
					$bind_infos[] = ["exp" => $init_text_codes, "binds" => $binds];

					//	テキスト部分を空にする
					$src = substr($src, 0, $m[3][$i][1])
						.substr($src, $m[3][$i][1] + strlen($m[3][$i][0]));

					//	必要ならタグにref追加
					if( $added_ref === true )
					{
						$src = substr($src, 0, $m[1][$i][1])
							.$m[1][$i][0]
							.' ref="'.$ref.'"'
							.substr($src, $m[1][$i][1] + strlen($m[1][$i][0]));
					}
				}

				//	テキストにバインドする属性をsystem watchに追加する
				$bind_props = [];
				foreach( $bind_infos as $bind_info )
				{
					foreach( $bind_info["binds"] as $prop )
						$bind_props[$prop] = true;
				}
				$syswatch_codes = [];
				foreach( $bind_props as $bind_prop => $dummy )
				{
					$syswatch_code = $bind_prop."(old_, new_){";
					foreach( $bind_infos as $bind_info )
					{
						if( in_array($bind_prop, $bind_info["binds"]) === false ) continue;
						$syswatch_code .= $bind_info["exp"];
					}
					$syswatch_code .= "}";
					$syswatch_codes[] = $syswatch_code;
				}

				if( isset($parts_[$name]["syswatch"]) === false ) $parts_[$name]["syswatch"] = "";
				$parts_[$name]["syswatch"] = "{".implode(",", $syswatch_codes)."}";
			}

			$parts_[$name]["template"] = $src;
			if( strlen($init_text_codes) > 0 )
			{
				if( isset($parts_[$name]["sysinit"]) === false ) $parts_[$name]["sysinit"] = "";
				$parts_[$name]["sysinit"] .= $init_text_codes;
			}
		}
	}

	//--------------------------------------------------------------------------
	//	埋め込みテキストを含むコードを分解して、JS側で結合する式を作成する
	//	[式, [bindするpropの配列]]
	//--------------------------------------------------------------------------
	private static function make_text_expression( $text_code_ )
	{
		$terms = [];
		$start = 0;
		$binds = [];
		$pattern = '/({{[{]*.*?}}[}]*)/isu';
		if( preg_match_all($pattern, $text_code_, $m, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE) )
		{
			for( $i=0; $i < count($m[0]); $i++ )
			{
				$prop = $m[1][$i][0];
				$prop_trimmed = trim(str_replace("}}", "", str_replace("{{", "", $prop)));

				//	"{{{"～"}}}"でhtmlエスケープなしとする。
				$noescape = false;
				if( substr($prop_trimmed, 0, 1) == "{" && substr($prop_trimmed, -1) == "}" )
				{
					$noescape = true;
					$prop_trimmed = trim(substr($prop_trimmed, 1, strlen($prop_trimmed) - 2));
				}

				$offset = $m[1][$i][1];

				if( $start < $offset )
				{
					$terms[] = '"'.crow_html::escape_js(substr($text_code_, $start, $offset - $start)).'"';
				}

				//	prop_trimmedは単一データでない場合、"." や "[" が出現する可能性があるので、
				//	その前までをpropのシンボルとして扱う
				$prop_after = "";
				if( preg_match('/^([0-9a-zA-Z_]+)(.*)$/isu', $prop_trimmed, $m2) )
				{
					$prop_trimmed = $m2[1];

					//	先頭空白に意味がある場合もあるので、右側のみトリム
					$prop_after = rtrim($m2[2]);
				}

				//	例えば
				//	user_row ? user_row.name : 'none'
				//	であれば、
				//	self.prop('user_row') ? self.prop('user_row').name : 'none'
				//	にする
				if( $prop_after != "" )
				{
					$after_parts = self::tokenizer($prop_after);
					$after_retouch = [];
					foreach( $after_parts as $after_part )
					{
						if( $after_part == $prop_trimmed )
							$after_retouch[] = $noescape ? 'self.prop("'.$prop_trimmed.'")' : 'htmlspecialchars(self.prop("'.$prop_trimmed.'"))';
						else
							$after_retouch[] = $after_part;
					}
					$prop_after = implode('', $after_retouch);
				}

				$terms[] = ($noescape ? 'self.prop("'.$prop_trimmed.'")' : 'htmlspecialchars(self.prop("'.$prop_trimmed.'"))').$prop_after;
				$binds[] = $prop_trimmed;

				$start = $offset + mb_strlen($prop);
			}
		}
		if( $start < strlen($text_code_) )
		{
			$terms[] = '"'.crow_html::escape_js(substr($text_code_, $start)).'"';
		}
		return [implode("+", $terms), $binds];
	}

	//--------------------------------------------------------------------------
	//	シンボル、演算子、スペーサーを分割する
	//--------------------------------------------------------------------------
	private static function tokenizer( $code_ )
	{
		$len = mb_strlen($code_);
		$str_mode = 0;
		$str = "";
		$parts = [];

		$add_parts = function(&$parts_, &$str_)
		{
			if( $str_ == "" ) return;
			$parts_[] = $str_;
			$str_ = "";
		};

		for( $offset = 0; $offset < $len; $offset++ )
		{
			$ch = mb_substr($code_, $offset, 1);

			if( $str_mode == 0 )
			{
				if( $ch == "'" )
				{
					$str_mode = 1;
					$add_parts($parts, $str);
					$str = $ch;
				}
				else if( $ch == '"' )
				{
					$str_mode = 2;
					$add_parts($parts, $str);
					$str = $ch;
				}
				else if( strpos("+-*/%.#!$&@:;~[](){}<> \t\n\r", $ch) !== false )
				{
					$add_parts($parts, $str);
					$str = $ch;
					$add_parts($parts, $str);
				}
				else
				{
					$str .= $ch;
				}
			}
			else if( $str_mode == 1 )
			{
				if( $ch == "\\" )
				{
					$offset++;
					$str .= mb_substr($code_, $offset, 1);
				}
				else if( $ch == "'" )
				{
					$str .= $ch;
					$str_mode = 0;
					$add_parts($parts, $str);
				}
				else
				{
					$str .= $ch;
				}
			}
			else if( $str_mode == 2 )
			{
				if( $ch == "\\" )
				{
					$offset++;
					$str .= mb_substr($code_, $offset, 1);
				}
				else if( $ch == '"' )
				{
					$str .= $ch;
					$str_mode = 0;
					$add_parts($parts, $str);
				}
				else
				{
					$str .= $ch;
				}
			}
		}
		if( $str != "" ) $add_parts($parts, $str);
		return $parts;
	}

	//--------------------------------------------------------------------------
	//	タグの中身 "<ここ>" をパースして、[タグ名, 属性の連想配列] の形で返却する
	//--------------------------------------------------------------------------
	private static function parse_element( $code_ )
	{
		//	tag 取得
		$code = trim($code_);
		$pos = strpos($code, " ");
		if( $pos === false ) return [$code_, []];

		//	属性部分を取得
		$tag = substr($code, 0, $pos);
		$args_code = trim(substr($code, $pos + 1));
		$len = mb_strlen($args_code);
		if( $len <= 0 ) return[$tag, []];

		//	オートマトンのステータス
		//	0: waiting key
		//	1: in key
		//	2: waiting eq
		//	3: waiting val
		//	4: in val
		$status = 0;

		//	文字列の状態
		//	0: none
		//	1: single
		//	2: double
		$str_mode = 0;

		//	パース
		$attrs = [];
		$key = false;
		$val = false;
		for( $offset=0; $offset < $len; $offset++ )
		{
			$ch = mb_substr($args_code, $offset, 1);

			//	status : キー待ち
			if( $status == 0 )
			{
				if( $ch == ' ' || $ch == "\t" ) continue;
				$key = $ch;
				$status = 1;
			}
			//	status : キー中
			else if( $status == 1 )
			{
				if( $ch == ' ' || $ch == "\t" )
				{
					$status = 2;
				}
				else if( $ch == '=' )
				{
					$status = 3;
				}
				else
				{
					$key .= $ch;
				}
			}
			//	status : EQ待ち
			else if( $status == 2 )
			{
				if( $ch == '=' )
				{
					$status = 3;
				}
			}
			//	status : 値待ち
			else if( $status == 3 )
			{
				if( $ch == ' ' || $ch == "\t" ) continue;

				if( $ch == "'" ) $str_mode = 1;
				if( $ch == '"' ) $str_mode = 2;
				else
				{
					$str_mode = 0;
					$val = $ch;
				}
				$status = 4;
			}
			//	status : 値中
			else if( $status == 4 )
			{
				if( $str_mode == 0 )
				{
					if( $ch == ' ' || $ch == "\t" )
					{
						$attrs[$key] = $val;
						$key = false;
						$val = false;
						$status = 0;
					}
					else
					{
						$val .= $ch;
					}
				}
				else if( $str_mode == 1 || $str_mode == 2 )
				{
					if( ($str_mode == 1 && $ch == "'") || ($str_mode == 2 && $ch == '"') )
					{
						$attrs[$key] = $val;
						$key = false;
						$val = false;
						$status = 0;
					}
					//	エスケープ時は先読み
					else if( $ch == "\\" )
					{
						$offset++;
						$next = mb_substr($args_code, $offset, 1);
						if( $next == "n" ) $val .= "\n";
						if( $next == "r" ) $val .= "\r";
						if( $next == "t" ) $val .= "\t";
						else $val .= $next;
					}
					else
					{
						$val .= $ch;
					}
				}
			}
		}
		if( $status == 4 )
		{
			$attrs[$key] = $val;
		}
		return [$tag, $attrs];
	}


	//	キャッシュ
	private static $m_caches = [];
}

?>
