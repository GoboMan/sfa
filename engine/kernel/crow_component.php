<?php
/*

	crow component

	コンポーネントのアプライを行う


	crow3_xxx/app/components/配下に、複数のコンポーネントを配置することができる。
	1コンポーネントあたり1フォルダで、フォルダ名がコンポーネント名となる。
	各コンポーネントで本体のcrowのファイルをオーバーライドする。

	コンポーネントフォルダ直下に "components.php" を配置し、アプライするコンポーネントの指定などを行う。
	crow_componentは、components.php 内で列挙された順番でアプリケーション本体をオーバーライドしていく。

	例）components.php

		//------------------------------------------------------------------------------
		//	コンポーネントコンフィグ
		//------------------------------------------------------------------------------
		function config()
		{
			return
			[
				"key1" => "value1",
				"key2" => "value2",
				"key3" => true,
			];
		}

		//------------------------------------------------------------------------------
		//	適用するコンポーネントを順番通りに返却する
		//------------------------------------------------------------------------------
		function components()
		{
			return
			[
				"init",
				"bbs_base",
				"bbs_patch",
			];
		}

		//------------------------------------------------------------------------------
		//	ロールごとにindex.phpの物理パスを返却する
		//	終端はパスデリミタとする
		//------------------------------------------------------------------------------
		function realpaths()
		{
			return
			[
				"front" => "C:\\laragon\\www\\component_test\\",
				"admin" => "C:\\laragon\\www\\component_test\\admin\\",
				"crowmin" => "C:\\laragon\\www\\component_test\\crowmin\\",
			];
		}

	例）ファイルの配置例
	crow3_xxx
		- app
		- components
			- components.php // コンポーネント全体制御ファイル
			- bbs_base // コンポ―ネント名でフォルダ作成
				- component.php // コンポーネントの設定ファイル（なくともよい、詳細は本ドキュメント後半に記載する）
				- app
					- config
					- classes
					- ... crow/app と同様の構成だがoverrideしないフォルダやファイルは作成する必要なし ...

				- doc_front // frontロールのdocument配下ファイル
					- img
						- append.png // 追加する画像ファイル

				- doc_... "doc_"にロール名を続けるとdocument配下へのoverrideになる。

			- bbs_patch // コンポーネントはいくつも置ける
			- init // crowのデフォルトファイルなどを削除するコンポーネントを置くことを推奨


	どのようにオーバーライドするのかを、コンポーネント側で決定する。
	単純な上書きなのか、位置を指定しての挿入、置き換えなのか。
	大きく分けると、
	・ファイル全体を上書きする
	・ファイル内容の一部を書き換える
	・ファイルやディレクトリの削除
	のパターンがある。


	1. ファイル全体を上書きするパターン
		基本はこのパターンで、component側のファイルで本体側のファイルを上書きする。
		本体側にそのファイルがない場合は新規にファイルが作成される。


	2. ファイル内容の一部を書き換えるパターン
		component側のファイル名に「@override」を付与すると、一部書き換えとして機能する。
		例）「app/views/front/top/index@override.txt」

			※ただし、"@override"オプションは doc_xxx配下では無効とする。

		override指定したファイルでは「@@」で始まる行でオーバーライド方法を指定して、本体側のファイルに対して部分的に改変を行う。
		@@で始まるコマンドは次の通りとなる。

		2.1. 追加

			書式
				@@append
				@@append : 行の検索構文
				@@append_one : 行の検索構文
				@@append_child : ブロックの検索構文
				@@append_child_one : ブロックの検索構文

			説明
				append
					一致した全ての行の後に追加する
					検索構文を引数で取らない場合には、ファイルの終端への追加となる

				append_one
					最初に一致した行の後に追加する

				append_child
					一致した全てのブロックについて、
					ブロック内の終端位置に追加する

				append_child_one
					最初に一致したブロックについて、
					ブロック内の終端位置に追加する

			例1
				@@append
				app.setting = app settings

				→ appendに引数がないのでこの場合はファイルの終端への追加となる

			例2
				・元ファイル
				<div class="block">
				 <div class="info">
				  <span>INFO</span>
				 <div>
				</div>

				・コマンド
				@@append_child : class="block"
				<span>INSERTED</span>

				・結果ファイル
				<div class="block">
				 <div class="info">
				  <span>INFO</span>
				 <div>
				 <span>INSERTED</span>
				</div>

				→ class="block"で検索したブロック内の終端へ追加している

		2.2. 挿入

			書式
				@@prepend
				@@prepend : 行の検索構文
				@@prepend_one : 行の検索構文
				@@prepend_child : ブロックの検索構文
				@@prepend_child_one : ブロックの検索構文

			説明
				prepend
					一致した全ての行の前に挿入する
					検索構文を引数で取らない場合には、ファイルの先頭への挿入となる

				prepend_one
					最初に一致した行の前に挿入する

				prepend_child
					一致した全てのブロックについて、
					ブロック内の先頭位置に挿入する

				prepend_child_one
					最初に一致したブロックについて、
					ブロック内の先頭位置に挿入する

			例1
				@@prepend
				 <!DOCTYPE html>
				 <html>

				→ prependに引数がないのでこの場合はファイルの先頭への挿入となる

			例2
				・元ファイル
				<div class="block">
				 <div class="info">
				  <span>INFO</span>
				 <div>
				</div>

				・コマンド
				@@prepend_child : class="block"
				<span>INSERTED</span>

				・結果ファイル
				<div class="block">
				 <span>INSERTED</span>
				 <div class="info">
				  <span>INFO</span>
				 <div>
				</div>

				→ class="block"で検索したブロック内の先頭へ挿入している

		2.3. 兄弟要素としてブロックの追加/挿入

			書式
				@@before : ブロックの検索構文
				@@before_one : ブロックの検索構文
				@@after : ブロックの検索構文
				@@after_one : ブロックの検索構文

			説明

				before
					一致した全てのブロックの兄弟要素として上に挿入する

				before_one
					最初に一致したブロックの兄弟要素として上に挿入する

				after
					一致した全てのブロックの兄弟要素として下に追加する

				after_one
					最初に一致したブロックの兄弟要素として下に追加する

		2.4. 置換

			書式
				@@replace : 行の検索構文
				@@replace_one : 行の検索構文
				@@replace_block : ブロックの検索構文
				@@replace_block_one : ブロックの検索構文
				@@replace_word : 行の検索構文, 置換前, 置換後
				@@replace_word_one : 行の検索構文, 置換前, 置換後

			説明
				replace
					一致した全ての行を置換する

				replace_one
					一致した最初の行を置換する

				replace_block
					一致した全てのブロックを置換する

				replace_block_one
					一致した最初のブロックを置換する

				replace_word
				replace_word_one
					一致した行の中で、"置換前"に指定した文字列を"置換後"に指定した文字列に置き換える

		2.5. 削除

			書式
				@@delete : 行の検索構文[, 行の検索構文]
				@@delete_one : 行の検索構文[, 行の検索構文]
				@@delete_block : ブロックの検索構文
				@@delete_block_one : ブロックの検索構文

			説明
				delete
					一致した全ての行を削除する
					第二引数で範囲の指定が可能

				delete_one
					一致した最初の行を削除する
					第二引数で範囲の指定が可能

				delete_block
					一致した全てのブロックを削除する

				delete_block_one
					一致した最初のブロックを削除する

		2.6. IF制御

			書式
				@@if : コンポーネント式
				@@elseif: コンポーネント式
				@@else
				@@endif

			説明
				指定したコンポーネントがその時点で適用中かどうかで
				以降のコマンドを適用するか無視するかを制御する。
				条件式の中で使える関数として、comp()、comps()、conf()がある。

			例）
				@@if : comp("red")
					@@prepend
					xxxx

				@@elseif : comp("blue") && conf("apply_color") = "blue"
					@@prepend
					yyyy

				@@else
					@@prepend
					zzzz

				@@endif

				もし"red"コンポーネントが適用されていれば"xxxx"がprependされる。
				それ以外でもしコンポーネント"blue"が適用済み
				且つコンポーネントコンフィグの"apply_color"が"blue"の場合、"yyyy"がprependされる。
				それ以外は"zzzz"がprependされる。
				endifでifブロック終了。

				comp()関数とconf()関数については、「4. component.php について」
				に記載している関数と同じ仕様となる。

		2.7. コメント

			書式
				@@ : コメント

			説明
				行全体をコメントとして扱う


		2.8. 記載例

			上記コマンドを続けて複数記載して、一つの@overrideファイルを作り上げる。

				@@:---------------------------------------------------------------------------
				@@:	追加する
				@@:---------------------------------------------------------------------------
				@@append : <form id="sample">
				 <div>
				  <input type="text" name="custom_input">
				 </div>

				@@:---------------------------------------------------------------------------
				@@:	範囲置き換え
				@@:---------------------------------------------------------------------------
				@@replace : <form method="post">
				 <form method="post" enctype="multipart/form-data">

		2.9. 検索構文について

			検索構文は、クォーテーション(Single/Double)で括られた文字列で指定し、
			見つかった位置の行頭を対象位置とする。
			複数の検索文字列を「/」で区切ると、検索位置を段階的に指定することができる。

			例えば、元の文書が次のような形だったする。
				<div id="first_block">
				  <div class="data">内容</div>
				</div>
				<div id="second_block">
				 <div class="title">タイトル</div>
				 <div class="data_area">
				  <div class="data">内容</div>
				 </div>
				</div>

			この中の"second_block"の中の"data"クラスの部分について変更を行いたい場合、

				@@replace : class="data"
				 <div class="data">置き換えた</div>

			とすると、first_blockの方も変更されてしまうため、段階指定で次のように記載する。

				@@replace : id="second_block" / class="data"
				 <div class="data">置き換えた</div>

			とするとsecond_blockの方を置き換えることができる。
			「/」で続けると、見つかった位置からさらに下に向けて検索する、という意味となる。
			「/」で次の段階で絞り込む時には上位段階よりインデントが浅くなってはならない。

		2.10. 検索構文の &&, || について

			検索構文に「&&」と「||」を指定できる。
			優先順位は&&の方が上として、混在はできない。

			跡えば、

				@@after: body / border || min

			とすると、bodyブロック配下の、borderまたはminを含むブロックが対象となる。

		2.11. 階層直下と完全一致の指定について

			function method()
			{
				if( aaa == bbb )
				{
					if( sub == ccc )
					{
					}
					else
					{
						//	ここはヒットしない
					}
				}
				else
				{
					//	ここに追加
				}
			}

			というソースがあるとして、if( aaa == bbb ) の階層の else に対して追加する場合、
			@@append_child : method() >> `else`

			とかけばよい。
			「>>」の記号は「直下」を表し、
			バッククォート「`」は行をトリムした結果と完全一致することを表す。

		2.12. 兄弟要素の指定について

			チルダ（~）で兄弟要素を辿ることが出来る。

			function method()
			{
				if( aaa == bbb )
				{
					if( sub == ccc )
					{
						echo "if";
					}
					//	ここはヒットさせない
					else
					{
						echo "else";
					}
				}
				else if( yyy == zzz )
				{
					echo "else if";
				}
				//	★ここに追加したい
				else
				{
					echo "else";
				}
			}

			というソースがあるとして、★の場所に挿入する場合、
			@@before aaa == bbb ~ `else`

			とすると、aaa == bbbのif文の兄弟要素であるelseブロックの前に挿入、という意味になる。

		2.13. インデントについて

			各コマンドのコンテンツ部分について、検索構文で発見した行のインデントが適用される。

			例えば、次のファイルをオーバーライドする場合
				<div>
				  <div class="data">内容</div>
				</div>

			「内容」の行はインデントがスペース二つなので、ここをreplaceで置き換える場合、

			@@replace : 'class="data"'
			<div class="new">
			  <span>置換</span>
			</div>

			とすると、コマンド処理後は

				<div>
				  <div class="new">
				    <span>置換</span>
				  </div>
				</div>

			となり、スペース二つのインデントが各行に適用された状態で置換が行われる。


	3. ファイルやディレクトリの削除

		ファイルを削除するには、「!」を付与したファイル名でファイルを置けばよい。
		ディレクトリも同様とする。

		例）"test"コンポーネントで、app/classes/install ディレクトリを削除する場合

		「components/test/app/classes/!install」
		というファイルを置けばよい。
		→ ※ディレクトリではなくファイルを置くことに注意

		例）"test"コンポーネントで、app/classes/spa/module_spa_top.php ファイルを削除する場合

		「components/test/app/classes/spa/!module_spa_top.php」
		というファイルを置けばよい。


	4. component.php について

		各コンポーネントの直下に、「component.php」を設置すると、
		コンポーネントの設定やオーバーライドに関する詳細な挙動を設定できる。
		このファイルが配置されていない場合には、通常通りコンポーネントの適用が行われる。

		例）component.php

			//------------------------------------------------------------------------------
			//	コンポーネントコンフィグへの追加/上書き
			//------------------------------------------------------------------------------
			function config()
			{
				return
				[
					"key1" => "value1",
					"key2" => "value2",
					"key3" => "true",
				];
			}

			//------------------------------------------------------------------------------
			//	初期化、正常の場合は true を返却し、異常の場合は文字列を返却すること
			//------------------------------------------------------------------------------
			function init()
			{
				//	必須コンポーネントのチェック
				if( comps(['init']) === false )
					return 'required component not applied';

				//	必須コンフィグのチェック
				if( conf('key1', false) === false )
					return 'required config not defined : key1';

				//	正常
				return true;
			}

			//------------------------------------------------------------------------------
			//	適用を無視するファイルを返却
			//------------------------------------------------------------------------------
			function ignores()
			{
				$ignores =
				[
					"app/classes/front/module_front_test@override.php",
					"[front]/!favicon.ico",
				];

				if( comp('init') === true && conf('key1') == "value1" )
					$ignores[] = "app/path/to.php";

				return $ignores;
			}

		crow_comonent は、component.php 内に記載された config() と ignores() 関数を認識する。
		コンポーネントによりコンフィグを追加するには config() でその値を返す。。
		コンポーネントの適用対象外とするファイルがある場合、ignores() でファイルパスを配列で返却すればよい。

		中身は php で書けるため、複雑な条件による調整も可能となる。
		本php内で利用可能なメソッドとして、次の二つがある。

			(1)
				function comp(コンポーネント名)

				コンポーネントがこの時点で適用されているか、
				または自身のコンポーネント名が指定された場合は true を返却する。
				それ以外は false を返却する。

			(2)
				function comps(コンポーネント名の配列)

				指定された配列に列挙してあるコンポーネントが全て、
				この時点で適用されているか、または自身のコンポーネントの場合は true を返却する。
				それ以外は false を返却する。

			(3)
				function conf(キー名[, デフォルト値 = ''])

				指定したキー名がコンポーネントコンフィグにある場合は、
				その値を返却する。ない場合にはデフォルト値を返却する。

		これらの関数を利用して、例えばignores() の返却リストを調整することを想定する。


	5. その他注意点

		コンポーネントをアプライすると本体が改変されて、次回からアプライは行われなくなる。
		再構築する場合にはrun()に、"components"を"force"で渡すか、output/applied_components を削除すればよい。
		crowキャッシュとは切り離しているため、crow::runのcleanupオプションでは状態クリアされない。

		再構築時は、元のファイルを output/backup/before_override から復元するため、
		before_override フォルダは削除しないようにすること

		複数開発者が同時にアプライを行うことへの対策として、簡易的に排他制御を行っている。
		output/temp/components.lock
		ファイルがあるかどうかでロック中かどうかを判断している。
		何かしらの問題でロックしたままアプライが出来なくなった場合にはこのファイルを削除すればよい。

*/
class crow_component
{
	//--------------------------------------------------------------------------
	//	アプライを実行 : crow_core から実行する想定
	//	適用するコンポーネントのリストを指定する場合には、第二引数で名前の配列を渡せばよい
	//--------------------------------------------------------------------------
	public static function apply( $force_ = false, $component_list_ = false )
	{
		//	crow_errorはまだ初期化されていないので、画面にエラーを出す。
		//	関数返却時には元に戻す
		$disp = ini_get('display_errors');
		ini_set('display_errors', "on");

		//	アプライ済み且つ、強制更新でなければ何もしない
		if( $force_ === false && self::is_applied() === true )
		{
			ini_set('display_errors', $disp);
			return;
		}

		//	ロック中は何も行わない
		if( self::is_locked() === true )
		{
			echo "applying components, crow is locked.";
			exit;
		}
		self::lock();

		//	コンポーネント全体制御ファイルを読み込む
		list($components, $realpaths) = self::load_components_file();

		//	適用するコンポーネント指定がある場合
		if( $component_list_ !== false )
			$components = $component_list_;

		//	バックアップから復元
		self::restore($realpaths);

		//	バックアップを行う
		self::backup($realpaths);

		//	コンポーネントの適用
		$component_dir = self::P(CROW_PATH."components/");
		if( count($components) > 0 )
		{
			self::$m_components_during_build = [];
			foreach( $components as $name )
			{
				self::$m_components_during_build[] = $name;

				//	コンポーネントごとの制御ファイルを読みこむ
				list($unit_result, $ignores) = self::load_component_file($name);
				if( $unit_result !== true )
				{
					self::unlock();
					self::output_error($unit_result);
					return;
				}

				//	適用
				self::override_root($name, $realpaths, self::P($component_dir.$name."/"), $ignores);
			}
		}

		//	組み込み完了を表すファイルを出力する
		self::mark_applied($components);

		//	エラー出力設定を戻しておく
		ini_set('display_errors', $disp);

		//	ロック解除
		self::unlock();
	}

	//--------------------------------------------------------------------------
	//	アプライされている？
	//--------------------------------------------------------------------------
	public static function is_applied()
	{
		//	組み込みが完了したら Output/applied_components が出来るので、
		//	その存在で組み込み済みかどうかを判断する
		return is_file(self::$m_exists_fname) === true;
	}

	//--------------------------------------------------------------------------
	//	アプライ済みのコンポーネント一覧を取得
	//--------------------------------------------------------------------------
	public static function get_applied_components()
	{
		if( self::is_applied() === false ) return [];
		$lines = file(self::$m_exists_fname);
		$components = [];
		foreach( $lines as $line ) $components[] = trim($line);
		return $components;
	}

	//--------------------------------------------------------------------------
	//	アプライ済みをマークする
	//--------------------------------------------------------------------------
	private static function mark_applied($component_list_)
	{
		file_put_contents(self::$m_exists_fname, implode("\r\n", $component_list_));
	}

	//--------------------------------------------------------------------------
	//	ロック制御
	//--------------------------------------------------------------------------
	public static function is_locked()
	{
		return is_file(self::$m_lock_path);
	}
	private static function lock()
	{
		file_put_contents(self::$m_lock_path, "", LOCK_EX);
	}
	private static function unlock()
	{
		unlink(self::$m_lock_path);
	}

	//--------------------------------------------------------------------------
	//	ソース差分を計算する
	//--------------------------------------------------------------------------
	public static function diff($src_fpath_)
	{
		$backup_dir = self::P(self::$m_backup_dir);
		$disk = crow_storage::disk();

		//	ファイル名分解
		$dir = crow_storage::extract_dirpath($src_fpath_)."/";
		$target_fname = self::P(crow_storage::extract_filename($src_fpath_));
		$target_delete_fname = self::P($dir."!".$target_fname);
		$target_override_fname = "";
		{
			$fname = crow_storage::extract_filename_without_ext($src_fpath_);
			$ext = crow_storage::extract_ext($src_fpath_);
			$target_override_fname = $ext != "" ?
				self::P($dir.$fname."@override.".$ext) :
				self::P($dir.$fname."@override")
				;
		}

		//	バックアップファイル確認
		$tmp_fpath = str_replace(":", "@_@", $src_fpath_);
		$tmp_fpath = str_replace("/", "@", $tmp_fpath);
		$tmp_fpath = str_replace("\\", "@", $tmp_fpath);
		$tmp_fpath = $backup_dir.self::P($tmp_fpath);

		//	新規リスト確認
		$appended_list = [];
		if( is_file($backup_dir.".appended") === true )
			$appended_list = self::array_rtrim(file($backup_dir.".appended"));

		$history = [];

		//	最初の履歴
		$origin = [];
		$origin_is_none = false;
		if( in_array(self::P($src_fpath_), $appended_list) === true )
		{
			$origin_is_none = true;
			$history[] =
			[
				"component" => "",
				"logic" => "none",
				"body" => [],
			];
		}
		else if( is_file($tmp_fpath) === true )
		{
			$origin = self::array_rtrim(file($tmp_fpath));
			$history[] =
			[
				"component" => "",
				"logic" => "origin",
				"body" => $origin,
			];
		}
		else
		{
			$origin = self::array_rtrim(file(self::P(CROW_PATH.$src_fpath_)));
			$history[] =
			[
				"component" => "",
				"logic" => "origin",
				"body" => $origin,
			];
		}

		//	コンポーネントリストロード
		list($components, $realpaths) = self::load_components_file();
		if( count($components) <= 0 ) return $history;

		//	順番に適用したものを計算
		$pre = $history[0]["body"];
		self::$m_components_during_build = [];
		for( $i = 0; $i < count($components); $i++ )
		{
			$name = $components[$i];
			self::$m_components_during_build[] = $name;

			//	コンポーネントごとの制御ファイルを読みこむ
			list($unit_result, $ignores) = self::load_component_file($name);
			if( $unit_result !== true ) return false;

			//	無視指定の確認
			$found_ignore = false;
			foreach( $ignores as $ignore )
			{
				if( self::P($ignore) == self::P($src_fpath_) )
				{
					$found_ignore = true;
					break;
				}
			}
			if( $found_ignore === true ) continue;

			//	削除指定の確認
			$check_path = self::P(CROW_PATH."components/".$name."/".$target_delete_fname);
			if( is_file($check_path) === true )
			{
				$history[] =
				[
					"component" => $name,
					"logic" => "del",
					"body" => [],
					"diff" => self::calc_diff($pre, []),
				];
				$pre = [];
				continue;
			}

			//	overrideの確認
			$check_path = self::P(CROW_PATH."components/".$name."/".$target_override_fname);
			if( is_file($check_path) === true )
			{
				$result = self::override_file($disk, $name, CROW_PATH."components/".$name."/", false, $target_override_fname, false, $pre);
				$result = self::array_rtrim(explode("\n", $result));

				$history[] =
				[
					"component" => $name,
					"logic" => "override",
					"body" => $result,
					"diff" => self::calc_diff($pre, $result),
				];
				$pre = $result;
				continue;
			}

			//	新規追加か、上書きの確認
			$check_path = self::P(CROW_PATH."components/".$name."/".$src_fpath_);
			if( is_file($check_path) === true )
			{
				$lines = self::array_rtrim(file($check_path));
				if( count($history) <= 1 && $origin_is_none === true )
				{
					$history[] =
					[
						"component" => $name,
						"logic" => "new",
						"body" => $lines,
						"diff" => self::calc_diff($pre, $lines),
					];
				}
				else
				{
					$history[] =
					[
						"component" => $name,
						"logic" => "reset",
						"body" => $lines,
						"diff" => self::calc_diff($pre, $lines),
					];
				}
				$pre = $lines;
				continue;
			}
		}

		//	履歴返却
		return $history;
	}
	private static function array_rtrim($lines_)
	{
		$new_lines = [];
		foreach( $lines_ as $line ) $new_lines[] = rtrim($line, "\r\n");
		return $new_lines;
	}
	private static function calc_diff($from_lines_, $to_lines_)
	{
		$diff_path1 = self::P(CROW_PATH."output/temp/".uniqid());
		$diff_path2 = self::P(CROW_PATH."output/temp/".uniqid());

		if( count($from_lines_) > 0 && $from_lines_[count($from_lines_) - 1] != "" )
			$from_lines_[] = "";
		if( count($to_lines_) > 0 && $to_lines_[count($to_lines_) - 1] != "" )
			$to_lines_[] = "";

		file_put_contents($diff_path1, implode("\n", $from_lines_));
		file_put_contents($diff_path2, implode("\n", $to_lines_));

		$diff = "";

		//	windows
		if( self::$m_delim == "\\" )
		{
			$diff = str_replace("\\", "\\\\", self::P(CROW_PATH."engine/assets/bin/diff.exe"));
			$from = str_replace("\\", "\\\\", $diff_path1);
			$to = str_replace("\\", "\\\\", $diff_path2);

			$cmd = '"'.$diff.'" -u "'.$from.'" "'.$to.'"';
			exec($cmd, $output, $ret);
			$diff = self::parse_diff_result($output, $from_lines_, $to_lines_);
		}

		//	linux
		else
		{
			$output = [];
			$cmd = 'diff -u "'.$diff_path1.'" "'.$diff_path2.'"';
			exec($cmd, $output, $ret);
			$diff = self::parse_diff_result($output, $from_lines_, $to_lines_);
		}

		unlink($diff_path1);
		unlink($diff_path2);

		return $diff;
	}

	//	"diff -u file1 file2" の結果パース。
	//	おそらく "git diff" と同じ形式になる
	private static function parse_diff_result($lines_, $from_lines_, $to_lines_)
	{
		$hunks = [];
		for( $i = 2; $i < count($lines_); $i++ )
		{
			$line = $lines_[$i];

			if( substr($line, 0, 2) == "@@" )
			{
				if( ! preg_match("/^@@ ([-+][0-9]+),([0-9]+) ([-+][0-9]+),([0-9]+) @@.*$/", $line, $m) ) continue;
				$hunks[] = crow_utility::array_to_obj(
				[
					"old_pos"	=> intval($m[1]),
					"old_size"	=> intval($m[2]),
					"new_pos"	=> intval($m[3]),
					"new_size"	=> intval($m[4]),
					"lines"		=> [],
				]);
			}
			else if( count($hunks) > 0 )
			{
				$hindex = count($hunks) - 1;
				$hunks[$hindex]->lines[] = $line;
			}
		}

		//	差分情報をマージしたプレビューを作成
		$new_line_no = 1;
		$diff_lines = array();
		foreach( $hunks as $hunk )
		{
			for( $i=$new_line_no; $i<$hunk->new_pos; $i++ )
				$diff_lines[] = " ".$to_lines_[$i-1];

			for( $i=0; $i<count($hunk->lines); $i++ )
			{
				if( strlen($hunk->lines[$i]) >= 1 )
					$diff_lines[] = $hunk->lines[$i];
			}

			$new_line_no = $hunk->new_pos + $hunk->new_size;
		}
		for( $i=$new_line_no; $i<count($to_lines_); $i++ )
			$diff_lines[] = " ".$to_lines_[$i-1];

		return $diff_lines;
	}


	//--------------------------------------------------------------------------
	//	コンポーネント全体制御ファイル読み込み
	//--------------------------------------------------------------------------
	public static function load_components_file()
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
	//	コンポーネント制御ファイル読み込み
	//--------------------------------------------------------------------------
	public static function load_component_file($component_name_)
	{
		//	ファイル頭と末尾にphpタグがあれば除去する
		$fname = self::P(CROW_PATH."components/".$component_name_."/component.php");
		if( is_file($fname) === false ) return [true, []];

		$src = trim(file_get_contents($fname));
		if( substr($src, 0, 5) == "<"."?php" && substr($src, -2) == "?".">" )
			$src = trim(substr($src, 5, strlen($src) - 7));

		//	実行できる形に内部を調整する
		//
		//	後でif構文判定時や、component.php（components.phpではない）内でもevalを使う。
		//	その時に、ここで定義した関数が生き続けてしまうため都度定義してしまうとdup declare となる。
		//	その対策として、この段階でまとめて関数を仕込むこととする。
		$namespace = "component_".$component_name_;
		$php = ""
			."namespace ".$namespace." { use \\crow_component;"
				.$src
				.'if( function_exists("'.$namespace.'\\config") ) crow_component::conf_updates(config());'
				.'function conf($key_, $def_ = \'\') {return crow_component::conf($key_, $def_);}'
				.'function comp($comp_) {return crow_component::comp($comp_);}'
				.'function comps($comps_) {return crow_component::comps($comps_);}'
				.'if( function_exists("'.$namespace.'\\init") && ($e = init()) !== true ) return [$e, []];'
				."return [true, function_exists('".$namespace."\\ignores') ? ignores() : []];"
			."}"
			;
		return eval($php);
	}

	//--------------------------------------------------------------------------
	//	バックアップを行う
	//--------------------------------------------------------------------------
	private static function backup($realpaths_)
	{
		$backup_dir = self::P(self::$m_backup_dir);
		if( is_dir($backup_dir) === false )
		{
			mkdir($backup_dir, 0777);
			chmod($backup_dir, 0777);
		}
		$disk = crow_storage::disk();

		//	app配下のバックアップ
		self::copy_recursive(self::P(CROW_PATH."app/"), self::P($backup_dir."app/"));

		//	document配下のバックアップ
		if( is_dir($backup_dir."docs") === false )
		{
			mkdir($backup_dir."docs", 0777);
			chmod($backup_dir."docs", 0777);
		}
		foreach( $realpaths_ as $role => $realpath )
		{
			$ignores = $realpath["ignores"];
			foreach( $ignores as $ignore_index => $ignore_path )
				$ignores[$ignore_index] = rtrim(self::P($ignore_path), "\r\n\t ".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			self::copy_recursive($realpath["path"], self::P($backup_dir."docs/".$role."/"), $ignores);
		}
	}
	private static function copy_recursive($from_, $to_, $ignores_ = [])
	{
		$from = rtrim($from_, "\r\n\t ".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$to = rtrim($to_, "\r\n\t ".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		if( is_dir($from) === false ) return;
		if( in_array($from, $ignores_) === true ) return;

		if( is_dir($to) === false )
		{
			mkdir($to, 0777);
			chmod($to, 0777);
		}
		$handle = opendir($from);
		if( $handle )
		{
			while( ($file = readdir($handle)) !== false )
			{
				if( $file == "." || $file == ".." ) continue;

				//	ディレクトリは再帰
				if( is_dir($from.$file) )
				{
					self::copy_recursive($from.$file, $to.$file, $ignores_);
				}

				//	ファイルはコピー
				else
				{
					copy($from.$file, $to.$file);
				}
			}
			closedir($handle);
		}
	}

	//--------------------------------------------------------------------------
	//	バックアップから復元する
	//--------------------------------------------------------------------------
	private static function restore($realpaths_)
	{
		$backup_dir = self::P(self::$m_backup_dir);
		$disk = crow_storage::disk();

		//	app配下の復元
		if( is_dir(self::P($backup_dir."app/")) )
		{
			self::remove_recursive(self::P(CROW_PATH."app/"));
			self::copy_recursive(self::P($backup_dir."app/"), self::P(CROW_PATH."app/"));
		}

		//	document配下の復元
		//	document配下についてはignore指定のファイルが残っている可能性があるのと、事故防止の観点で削除は行わないようにする
		foreach( $realpaths_ as $role => $realpath )
		{
			$doc_path = $realpath['path'];
			if( trim($doc_path) == "" || $doc_path == "/" || $doc_path == "\\" || trim($role) == "" ) continue;
			if( is_dir(self::P($backup_dir.$role."/")) )
			{
				//self::remove_recursive($doc_path);
				self::copy_recursive(self::P($backup_dir."docs/".$role."/"), $doc_path);
			}
		}
	}
	private static function remove_recursive($from_)
	{
		if( trim($from_) == "" || $from_ == "/" || preg_match("/^.*:\\$/", $from_, $m) ) return;
		$from = rtrim($from_, "\r\n\t ".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		if( is_dir($from) === false ) return;
		$handle = opendir($from);
		if( $handle )
		{
			while( ($file = readdir($handle)) !== false )
			{
				if( $file == "." || $file == ".." ) continue;

				//	ディレクトリは再帰
				if( is_dir($from.$file) )
				{
					self::remove_recursive($from.$file);
				}

				//	ファイルは削除
				else
				{
					if( unlink($from.$file) === false ) ;
				}
			}
			closedir($handle);

			try
			{
				if( rmdir($from) === false ) ;
			}
			catch(Exception $e_) {}
		}
	}

	//--------------------------------------------------------------------------
	//	コンポーネントルートを指定してオーバーライド開始
	//--------------------------------------------------------------------------
	private static function override_root($name_, $realpaths_, $root_dir_, $ignores_)
	{
		$disk = crow_storage::disk();

		//	app
		self::override_dir($disk, $name_, self::P($root_dir_."app/"), self::P(CROW_PATH."app/"), false, $ignores_);

		//	html
		foreach( $realpaths_ as $role => $realpath )
		{
			$doc_path = $realpath['path'];
			if( trim($doc_path) == "" || $doc_path == "/" || $doc_path == "\\" || trim($role) == "" ) continue;
			self::override_dir($disk, $name_, self::P($root_dir_."doc_".$role."/"), self::P($doc_path), true, $ignores_);
		}
	}

	//--------------------------------------------------------------------------
	//	ディレクトリをオーバーライド
	//--------------------------------------------------------------------------
	private static function override_dir($disk_, $name_, $src_dir_, $dst_dir_, $for_html_, $ignores_ = [])
	{
		//	ディレクトリがなければ作成する
		if( is_dir($dst_dir_) === false ) mkdir($dst_dir_);

		//	子ディレクトリの走査
		$sub_dirs = self::P($disk_->get_dirs($src_dir_));
		foreach( $sub_dirs as $sub_dir )
		{
			$sub_dir_name = crow_storage::extract_dirname($sub_dir);

			//	先頭が否定(!)ならディレクトリ削除
			if( substr($sub_dir_name, 0, 1) == "!" )
				self::remove_dir($disk_, self::P($dst_dir_.substr($sub_dir_name, 1)."/"));

			//	それ以外はディレクトリオーバーライド
			else
				self::override_dir($disk_, $name_, $sub_dir, self::P($dst_dir_.$sub_dir_name."/"), $for_html_, $ignores_);
		}

		//	ファイル名一覧を取得
		$src_files = $disk_->get_files($src_dir_);
		foreach( $src_files as $index => $src_file )
			$src_files[$index] = crow_storage::extract_filename($src_file);

		//	ファイル一覧をチェック
		foreach( $src_files as $src_file )
		{
			//	ignoreに含まれる場合は無視
			$found_ignore = false;
			foreach( $ignores_ as $ignore )
			{
				if( self::P($ignore) == self::exclude_crowpath($dst_dir_.$src_file) )
				{
					$found_ignore = true;
					break;
				}
			}
			if( $found_ignore === true ) continue;

			//	先頭が否定(!)ならファイル削除
			if( substr($src_file, 0, 1) == "!" )
			{
				$remove_path = self::P($dst_dir_.substr($src_file, 1));
				if( is_dir($remove_path) === true )
					self::remove_dir($disk_, $remove_path);
				else
					self::remove_file($disk_, $remove_path);
			}

			//	末尾が "@override" なら差分パッチ
			else if( strpos($src_file, "@override") !== false )
			{
				//	ただし、html配下の場合は無効とする
				if( $for_html_ === true )
				{
					self::output_error('"@override" option is disabled under htdoc');
					return;
				}
				else
				{
					$src_fname = $src_file;
					$dst_fname = str_replace("@override", "", $src_file);

					$result = self::override_file($disk_, $name_, $src_dir_, $dst_dir_, $src_fname, $dst_fname);
					if( $result !== false )
						file_put_contents($dst_dir_.$dst_fname, $result);
				}
			}

			//	それ以外なら新規作成
			else
			{
				$src_fname = $src_file;
				$dst_fname = $src_file;
				self::append_file($disk_, $name_, $src_dir_, $dst_dir_, $src_fname, $dst_fname);
			}
		}
	}

	//--------------------------------------------------------------------------
	//	ファイルを削除
	//--------------------------------------------------------------------------
	private static function remove_file($disk_, $fpath_)
	{
		//	ファイルがないなら何もしない
		if( is_file($fpath_) === false )
			return;

		//	実体削除
		unlink($fpath_);
	}

	//--------------------------------------------------------------------------
	//	ディレクトリを削除
	//--------------------------------------------------------------------------
	private static function remove_dir($disk_, $dpath_)
	{
		//	ディレクトリがないなら何もしない
		if( is_dir($dpath_) === false )
		{
			self::output_error("specified for deletion dir does not exist, ".$dpath_);
			return;
		}

		//	再帰的に削除していく
		self::remove_dir_core($disk_, $dpath_);
	}
	private static function remove_dir_core($disk_, $dpath_)
	{
		if( is_dir($dpath_) === false ) return;

		//	子ディレクトリ削除
		foreach( $disk_->get_dirs($dpath_) as $dir )
			self::remove_dir_core($disk_, $dir);

		//	子ファイル削除
		foreach( $disk_->get_files($dpath_) as $file )
			self::remove_file($disk_, $file);

		//	自身の削除
		rmdir($dpath_);
	}

	//--------------------------------------------------------------------------
	//	ファイルを新規追加
	//--------------------------------------------------------------------------
	private static function append_file($disk_, $name_, $src_dir_, $dst_dir_, $src_fname_, $dst_fname_)
	{
		copy($src_dir_.$src_fname_, $dst_dir_.$dst_fname_);
	}

	//--------------------------------------------------------------------------
	//	ファイルをオーバーライド
	//--------------------------------------------------------------------------
	private static function override_file($disk_, $name_, $src_dir_, $dst_dir_, $src_fname_, $dst_fname_, $dst_lines_ = false)
	{
		//	オーバーライド先がない場合は処理なし
		if( is_file($dst_dir_.$dst_fname_) === false )
		{
			if( $dst_lines_ === false )
				return;
		}

		$src_lines = self::ltrim_cmdlines(file($src_dir_.$src_fname_));
		$dst_lines = self::ltrim_cmdlines($dst_lines_ === false ? file($dst_dir_.$dst_fname_) : $dst_lines_);

		//	行単位のインデント数をカウントしておく
		$dst_indents = [];
		$dst_indent_lens = [];
		foreach( $dst_lines as $index => $line )
		{
			$line = rtrim($line, "\r\n");
			$dst_lines[$index] = $line;
			$indent = preg_match('/^([ \t]*).*$/', $line, $m) ? $m[1] : "";
			$dst_indents[] = $indent;
			$dst_indent_lens[] = strlen($indent);
		}

		//	結果用 [元行番号, コンテンツ] の配列
		$result_lines = [];
		for( $i = 0; $i < count($dst_lines); $i++ )
			$result_lines[] = [$i, $dst_lines[$i]];

		//	if制御用のスタック作成（100ネストまで耐える）
		$if_nest = -1;
		$if_stack = [];
		for( $i = 0; $i < 100; $i++ ) $if_stack[] = '';

		//	"@@" コマンドを拾って処理していく
		for( $src_index = 0; $src_index < count($src_lines); $src_index++ )
		{
			//	コマンドラインパース
			$src_line = $src_lines[$src_index];
			if( substr($src_line, 0, 2) != "@@" ) continue;
			list($cmd, $args, $args_raw) = self::parse_cmdline(substr($src_line, 2));

			//	次のコマンド位置までがコンテンツとなる
			$contents = "";
			for( $next_index = $src_index + 1; $next_index < count($src_lines); $next_index++ )
			{
				$next_line = $src_lines[$next_index];
				if( substr($next_line, 0, 2) == "@@" ) break;
				$contents .= rtrim($next_line)."\r\n";
			}

			//	コンテンツの末尾の改行はまとめて一つの改行とする、空行のみの場合は空とする。
			$contents = rtrim($contents)."\r\n";
			if( $contents == "\r\n" ) $contents = "";

			//	@@ のみならコメント扱い
			if( $cmd == "" )
			{
			}

			//	@@if
			else if( $cmd == "if" )
			{
				$if_nest++;

				if( self::check_if($args_raw) === true )
				{
					$if_stack[$if_nest] = 'running';
				}
				else
				{
					$if_stack[$if_nest] = 'next';
				}
				continue;
			}

			//	@@elseif
			else if( $cmd == "elseif" )
			{
				if( $if_nest < 0 )
				{
					self::output_error('@@elseif hierarchy is misaligned');
					return false;
				}

				if( $if_stack[$if_nest] == 'running' )
				{
					$if_stack[$if_nest] = "done";
				}
				else if( $if_stack[$if_nest] == 'next' )
				{
					if( self::check_if($args_raw) === true )
					{
						$if_stack[$if_nest] = 'running';
					}
				}
				continue;
			}

			//	@@else
			else if( $cmd == "else" )
			{
				if( $if_nest < 0 )
				{
					self::output_error('@@else hierarchy is misaligned');
					return false;
				}

				if( $if_stack[$if_nest] == 'running' )
				{
					$if_stack[$if_nest] = "done";
				}
				else if( $if_stack[$if_nest] == 'next' )
				{
					$if_stack[$if_nest] = 'running';
				}
				continue;
			}

			//	@@endif
			else if( $cmd == "endif" )
			{
				if( $if_nest < 0 )
				{
					self::output_error('@@endif hierarchy is misaligned');
					return false;
				}

				$if_stack[$if_nest] = '';

				$if_nest--;
				if( $if_nest < -1 )
				{
					self::output_error('@@endif hierarchy is misaligned');
					return false;
				}
				continue;
			}

			//	ifブロックのスキップ中なら、以降のコマンドは無視する
			else if( $if_nest >= 0 && $if_stack[$if_nest] != 'running' )
			{
				continue;
			}

			//	@@prepend
			//	@@prepend : 位置
			//	@@prepend_one : 位置
			//	@@prepend_child : 位置
			//	@@prepend_child_one : 位置
			else if( in_array($cmd, ["prepend", "prepend_one", "prepend_child", "prepend_child_one"]) )
			{
				//	引数がないなら先頭挿入
				if( $cmd == "prepend" && count($args) <= 0 )
				{
					$result_lines = array_merge
					(
						[[-1, $contents]],
						$result_lines
					);
					continue;
				}

				//	セレクタで指定された位置を検索
				$search_child = $cmd == "prepend_child" || $cmd == "prepend_child_one";
				$found_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[0], 0, 0, $search_child);
				if( count($found_nos) <= 0 ) continue;

				//	prepend と prepend_one
				if( $cmd == "prepend" || $cmd == "prepend_one" )
				{
					foreach( $found_nos as $found_no )
					{
						$indented_contents = self::apply_indent($contents, $dst_indents[$found_no]);
						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							if( $result_lines[$i][0] == $found_no )
								$new_lines[] = [-1, $indented_contents];
							$new_lines[] = $result_lines[$i];
						}
						$result_lines = $new_lines;

						if( $cmd == "prepend_one" ) break;
					}
				}

				//	子指定の場合
				else if( $cmd == "prepend_child" || $cmd == "prepend_child_one" )
				{
					foreach( $found_nos as $found_no )
					{
						list($start_line_no, $end_line_no) = self::calc_block_range($dst_lines, $dst_indent_lens, $found_no);
						$indented_contents = self::apply_indent($contents, $dst_indents[$start_line_no]);

						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							if( $result_lines[$i][0] == $start_line_no )
								$new_lines[] = [-1, $indented_contents];
							$new_lines[] = $result_lines[$i];
						}
						$result_lines = $new_lines;

						if( $cmd == "prepend_child_one" ) break;
					}
				}
			}

			//	@@append
			//	@@append : 位置
			//	@@append_one : 位置
			//	@@append_child : 位置
			//	@@append_child_one : 位置
			else if( in_array($cmd, ["append", "append_one", "append_child", "append_child_one"]) )
			{
				//	引数がないなら先頭挿入
				if( $cmd == "append" && count($args) <= 0 )
				{
					$result_lines = array_merge
					(
						$result_lines,
						[[-1, $contents]],
					);
					continue;
				}

				//	セレクタで指定された位置を検索
				$search_child = $cmd == "append_child" || $cmd == "append_child_one";
				$found_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[0], 0, 0, $search_child);
				if( count($found_nos) <= 0 ) continue;

				//	append と append_one
				if( $cmd == "append" || $cmd == "append_one" )
				{
					foreach( $found_nos as $found_no )
					{
						$indented_contents = self::apply_indent($contents, $dst_indents[$found_no]);
						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							$new_lines[] = $result_lines[$i];
							if( $result_lines[$i][0] == $found_no )
								$new_lines[] = [-1, $indented_contents];
						}
						$result_lines = $new_lines;

						if( $cmd == "append_one" ) break;
					}
				}

				//	子指定の場合
				else if( $cmd == "append_child" || $cmd == "append_child_one" )
				{
					foreach( $found_nos as $found_no )
					{
						list($start_line_no, $end_line_no) = self::calc_block_range($dst_lines, $dst_indent_lens, $found_no);
						$indented_contents = self::apply_indent($contents, $dst_indents[$start_line_no]);

						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							$new_lines[] = $result_lines[$i];
							if( $result_lines[$i][0] == $end_line_no )
								$new_lines[] = [-1, $indented_contents];
						}
						$result_lines = $new_lines;

						if( $cmd == "append_child_one" ) break;
					}
				}
			}

			//	@before : ブロック位置
			//	@before_one : ブロック位置
			//	@after : ブロック位置
			//	@after_one : ブロック位置
			else if( in_array($cmd, ["before", "before_one", "after", "after_one"]) )
			{
				//	引数が必須
				if( count($args) <= 0 )
				{
					self::output_error('@@'.$cmd.' requires position, '.$src_dir_.$src_fname_);
					return false;
				}

				//	セレクタで指定された位置を検索
				$found_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[0], 0, 0, true);
				if( count($found_nos) <= 0 ) continue;

				foreach( $found_nos as $found_no )
				{
					list($start_line_no, $end_line_no) = self::calc_block_range_outer($dst_lines, $dst_indent_lens, $found_no);
					$indented_contents = self::apply_indent($contents, $dst_indents[$found_no]);

					$new_lines = [];
					if( $cmd == "before" || $cmd == "before_one" )
					{
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							if( $result_lines[$i][0] == $start_line_no )
								$new_lines[] = [-1, $indented_contents];
							$new_lines[] = $result_lines[$i];
						}
					}
					else if( $cmd == "after" || $cmd == "after_one" )
					{
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							$new_lines[] = $result_lines[$i];
							if( $result_lines[$i][0] == $end_line_no )
								$new_lines[] = [-1, $indented_contents];
						}
					}
					$result_lines = $new_lines;

					if( $cmd == "before_one" || $cmd == "after_one" ) break;
				}
			}

			//	@replace : 位置
			//	@replace_one : 位置
			//	@replace_block : 位置
			//	@replace_block_one : 位置
			//	@replace_word : 位置, 置換前, 置換後
			//	@replace_word_one : 位置, 置換前, 置換後
			else if( in_array($cmd, ["replace", "replace_one", "replace_block", "replace_block_one", "replace_word", "replace_word_one"]) )
			{
				//	引数が必須
				if( count($args) <= 0 )
				{
					self::output_error('@@'.$cmd.' requires position, '.$src_dir_.$src_fname_);
					return false;
				}

				//	セレクタで指定された位置を検索
				$search_child = $cmd == "replace_block" || $cmd == "replace_block_one";
				$found_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[0], 0, 0, $search_child);
				if( count($found_nos) <= 0 ) continue;

				//	上記で見つかった位置の置換
				if( $cmd == "replace" || $cmd == "replace_one" )
				{
					foreach( $found_nos as $found_no )
					{
						$indented_contents = self::apply_indent($contents, $dst_indents[$found_no]);
						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							if( $result_lines[$i][0] == $found_no )
								$new_lines[] = [-1, $indented_contents];
							else $new_lines[] = $result_lines[$i];
						}
						$result_lines = $new_lines;

						if( $cmd == "replace_one" ) break;
					}
				}

				//	ブロック指定の場合
				else if( $cmd == "replace_block" || $cmd == "replace_block_one" )
				{
					foreach( $found_nos as $found_no )
					{
						list($start_line_no, $end_line_no) = self::calc_block_range_outer($dst_lines, $dst_indent_lens, $found_no);
						$indented_contents = self::apply_indent($contents, $dst_indents[$found_no]);

						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							if( $result_lines[$i][0] == $start_line_no )
							{
								$new_lines[] = [-1, $indented_contents];
							}
							else if( $result_lines[$i][0] > $start_line_no && $result_lines[$i][0] <= $end_line_no)
							{
							}
							else $new_lines[] = $result_lines[$i];
						}
						$result_lines = $new_lines;

						if( $cmd == "replace_block_one" ) break;
					}
				}

				//	部分置換の場合
				else if( $cmd == "replace_word" || $cmd == "replace_word_one" )
				{
					if( count($args) < 3 )
					{
						self::output_error('@@'.$cmd.' required position and before_word and after_word, '.$src_dir_.$src_fname_." : ".count($args));
						return false;
					}

					foreach( $found_nos as $found_no )
					{
						$preline = $dst_lines[$found_no];
						$new_lines = [];
						for( $i = 0; $i < count($result_lines); $i++ )
						{
							if( $result_lines[$i][0] == $found_no )
							{
								$new_lines[] = [-1, str_replace($args[1], $args[2], $preline)."\r\n"];
							}
							else $new_lines[] = $result_lines[$i];
						}
						$result_lines = $new_lines;

						if( $cmd == "replace_word_one" ) break;
					}
				}
			}

			//	@@delete : "位置"
			//	@@delete : "範囲開始位置", "範囲終了位置"
			//	@@delete_one : "位置"
			//	@@delete_one : "範囲開始位置", "範囲終了位置"
			else if( $cmd == "delete" || $cmd == "delete_one" )
			{
				//	引数が必須
				if( count($args) <= 0 )
				{
					self::output_error('@@'.$cmd.' requires position or range, '.$src_dir_.$src_fname_);
					return false;
				}

				//	最初に指定されたセレクタが置換対象行、または範囲の開始位置となる
				$from_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[0], 0, 0, true);
				if( count($from_nos) > 0 )
				{
					//	単行指定の場合
					if( count($args) < 2 )
					{
						foreach( $from_nos as $from_no )
						{
							$new_lines = [];
							for( $i = 0; $i < count($result_lines); $i++ )
							{
								if( $result_lines[$i][0] == $from_no );
								else $new_lines[] = $result_lines[$i];
							}
							$result_lines = $new_lines;

							if( $cmd == "delete_one" ) break;
						}
					}
					//	範囲指定の場合
					else
					{
						//	範囲の終了位置をセレクト
						$to_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[1], 0, 0, true);

						//	開始位置の数と終了位置の数は一致している必要がある
						if( count($from_nos) != count($to_nos) )
						{
							self::output_error('@@delete range selector requires the same cnt of start and end points, '.$src_dir_.$src_fname_);
							return false;
						}

						//	適用
						for( $pi = 0; $pi < count($from_nos); $pi++ )
						{
							$from_no = $from_nos[$pi];
							$to_no = $to_nos[$pi];

							$in_contents = false;
							$new_lines = [];
							for( $i = 0; $i < count($result_lines); $i++ )
							{
								if( $in_contents === false )
								{
									if( $result_lines[$i][0] == $from_no ) $in_contents = true;
									else $new_lines[] = $result_lines[$i];
								}
								else if( $in_contents === true )
								{
									if( $result_lines[$i][0] == $to_no )
										$in_contents = false;
								}
							}
							$result_lines = $new_lines;

							if( $cmd == "delete_one" ) break;
						}
					}
				}
			}

			//	@delete_block : 位置
			//	@delete_block_one : 位置
			else if( $cmd == "delete_block" || $cmd == "delete_block_one" )
			{
				//	引数が必須
				if( count($args) <= 0 )
				{
					self::output_error('@@'.$cmd.' requires position, '.$src_dir_.$src_fname_);
					return false;
				}

				//	セレクタで指定された位置を検索
				$found_nos = self::select_lines($dst_lines, $dst_indent_lens, $args[0], 0, 0, true);
				if( count($found_nos) <= 0 ) continue;

				//	上記で見つかった位置の削除
				foreach( $found_nos as $found_no )
				{
					list($start_line_no, $end_line_no) = self::calc_block_range_outer($dst_lines, $dst_indent_lens, $found_no);
					$indented_contents = self::apply_indent($contents, $dst_indents[$found_no]);

					$new_lines = [];
					for( $i = 0; $i < count($result_lines); $i++ )
					{
						if( $result_lines[$i][0] >= $start_line_no && $result_lines[$i][0] <= $end_line_no );
						else $new_lines[] = $result_lines[$i];
					}
					$result_lines = $new_lines;

					if( $cmd == "delete_block_one" ) break;
				}
			}
		}

		//	結合
		$result = "";
		foreach( $result_lines as $line )
		{
			if( $line[0] < 0 ) $result .= $line[1];
			else $result .= $line[1]."\r\n";
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	コマンドライン解析
	//
	//	返却 :
	//	[
	//		COMMAND,
	//		[
	//			ARG1,
	//			ARG2,
	//			...
	//		],
	//		ARGS_RAW // 引数部分の加工前文字列
	//	]
	//
	//--------------------------------------------------------------------------
	private static function parse_cmdline($cmdline_)
	{
		//	":" までがコマンド
		$pos = strpos($cmdline_, ':');
		$cmd = trim($pos === false ? trim($cmdline_) : substr($cmdline_, 0, $pos));
		if( $pos === false ) return [$cmd, [], ''];

		//	引数はカンマ区切りだが、各引数はクォーテーションを考慮する
		$arg_line = trim(substr($cmdline_, $pos + 1));
		$arg_len = mb_strlen($arg_line);

		//	まずはトークンに分割する
		$tokens = [];
		$token = '';
		$instr = false;
		$end_with_quote = false;

		//	文字列を1文字ずつ処理
		for( $i = 0; $i < $arg_len; $i++ )
		{
			$ch = mb_substr($arg_line, $i, 1);

			//	文字列の開始または終了を検出
			if( $instr !== false )
			{
				if( $ch === $instr )
				{
					//	文字列の終端
					if( $end_with_quote )
					{
						if( $ch == "`" ) $tokens[] = '@@`@@'.$token;
						else $tokens[] = $token;
						$token = '';
					}
					else
					{
						$token .= $ch;
					}

					$instr = false;
				}
				else
				{
					//	文字列内の文字を追加
					$token .= $ch;
				}
			}
			else
			{
				if( $ch === '"' || $ch === "'" || $ch === "`" )
				{
					//	文字列の開始
					$instr = $ch;
					if( trim($token) != "" )
					{
						//	既に文字入力がある場合、今回が"`"ならクォート除去。
						if( $ch == "`" || $token == "@@>>@@" || $token == "@@~@@" )
						{
							$end_with_quote = true;
						}
						//	">>"、"~" の直後ならクォート除去（直後のみ除去しないので、substrではなく完全一致とする）
						else if( trim($token) == "@@>>@@" || trim($token) == "@@~@@" )
						{
							$end_with_quote = true;
						}
						//	そうでないならクォート含めて入力の継続とする
						else
						{
							$end_with_quote = false;
							$token .= $ch;
						}
					}
					else
					{
						$end_with_quote = true;
						$token = "";
					}
				}
				else if( $ch === ',' )
				{
					//	カンマで区切られたトークンの追加
					$token = trim($token);
					if( $token !== '' )
					{
						$tokens[] = $token;
						$token = '';
					}
					$tokens[] = ',';
				}
				else if( $ch === '/' )
				{
					//	スラッシュで区切られたトークンの追加
					$token = trim($token);
					if( $token !== '' )
					{
						$tokens[] = $token;
						$token = '';
					}
				}
				else if( $ch === '>' )
				{
					//	">>" で直下の意味とする
					if( mb_substr($arg_line, $i + 1, 1) == '>' )
					{
						$i++;

						$token = trim($token);
						if( $token != '' ) $tokens[] = $token;
						$token = '@@>>@@';
					}
				}
				else if( $ch === '~' )
				{
					//	チルダ"~"で兄弟要素のみを検索対象とする
					$token = trim($token);
					if( $token != '' ) $tokens[] = $token;
					$token = '@@~@@';
				}
				else if( $ch === '&' )
				{
					if( mb_substr($arg_line, $i + 1, 1) == '&' )
					{
						$i++;

						$token = trim($token);
						if( $token !== '' ) $token .= '@@&&@@';
					}
					else
					{
						$token .= $ch;
					}
				}
				else if( $ch === '|' )
				{
					if( mb_substr($arg_line, $i + 1, 1) == '|' )
					{
						$i++;

						$token = trim($token);
						if( $token !== '' ) $token .= '@@||@@';
					}
					else
					{
						$token .= $ch;
					}
				}
				else
				{
					//	その他の文字をトークンに追加
					$token .= $ch;
				}
			}
		}

		//	最後のトークンを追加
		$token = trim($token);
		if( $token !== '' ) $tokens[] = $token;

		//	結果作成
		$result = [];
		$item = [];
		foreach( $tokens as $token )
		{
			if( $token === ',' )
			{
				if( count($item) > 0 )
				{
					$result[] = $item;
					$item = [];
				}
			}
			else if( is_numeric($token) )
			{
				$result[] = [(int)$token];
			}
			else
			{
				$item[] = $token;
			}
		}
		if( count($item) > 0 ) $result[] = $item;

		return [$cmd, $result, $arg_line];
	}

	//--------------------------------------------------------------------------
	//	セレクタ配列で行を検索し、ヒットした数だけ行位置を配列で返却する。
	//	セレクタの配列次元は階層を表し、$selector_[0]は1階層目、$selector_[1]は2階層目..
	//	のように多段でセレクタを指定できる。
	//	本メソッド自体が再帰的に実行され、現在どの階層を処理するのかを$level_で指定する
	//--------------------------------------------------------------------------
	private static function select_lines($lines_, $indent_lens_, $selector_, $level_, $offset_lineno_, $search_child_, $pre_offset_ = false)
	{
		//	開始時点のインデントを計算する
		$offset = $offset_lineno_;
		$keyword = $selector_[$level_];
		$indent = $indent_lens_[$offset];
		$level_more = $level_ < (count($selector_) - 1);
		$founds = [];

		//	■パターンA
		//	if(xxx) : 前回レベルでヒットした行
		//	{       : $indentの対象となる行
		//	のようにブロックが開始する場合と、
		//
		//	■パターンB
		//	<div>   : 前回レベルでヒットした行
		//	 <div>  : $indentの対象となる行
		//	のようにブロックが開始する場合がある。
		//
		//	前者の開始インデントは、if(xxx)と同じだが、後者は一つ奥になる。
		//	この後の indent の判断を共通にするために、ここでその違いを吸収してパターンAと同じ計算に統一しておく。
		//
		//	本来はさらに、
		//		<div><span><? = $row->name ? ></span></div>
		//		if(true) {echo "abc";}
		//	のように1行に記載する場合もブロックと判断できるようにしたいが、現時点では不可能
		//
		if( $level_ > 0 && $pre_offset_ !== false )
		{
			$indent = $indent_lens_[$pre_offset_];
		}

		//	特殊指示があるかをチェックしておく
		$contain_and = strpos($keyword, '@@&&@@') !== false;
		$contain_or = strpos($keyword, '@@||@@') !== false;
		$just_below = strpos($keyword, '@@>>@@') !== false;
		$siblings = strpos($keyword, '@@~@@') !== false;
		$perfect_match = strpos($keyword, '@@`@@') !== false;

		//	一部の特殊指示は塗りつぶしておく
		if( $just_below === true ) $keyword = trim(str_replace('@@>>@@', '', $keyword));
		if( $siblings === true ) $keyword = trim(str_replace('@@~@@', '', $keyword));
		if( $perfect_match === true ) $keyword = trim(str_replace('@@`@@', '', $keyword));

		//	検索
		$below_indent = -1;
		$has_down_indent = false;
		for( $i = $offset; $i < count($lines_); $i++ )
		{
			$empty_line = trim($lines_[$i]) == "";

			//	子検索の場合、インデントが一度下がった後、前レベル開始時以下になったら検索終了
			if( $search_child_ === true && $siblings === false && $level_ > 0 && $empty_line === false )
			{
				if( $has_down_indent === false && $indent_lens_[$i] > $indent )
					$has_down_indent = true;
				else if( $has_down_indent === true && $indent_lens_[$i] <= $indent )
					break;
			}

			//	開始時点よりインデントが上がった場合は検索終了
			//	ただしトリムの空文字はスキップ
			if( $indent_lens_[$i] < $indent && $empty_line === false ) break;

			//	「直下」判定
			if( $just_below === true )
			{
				//	直下判定時の場合についても子検索時と同様に、
				//	一度直下を辿った後に開始時に戻ってきた場合、検索終了とする
				if( $below_indent >= 0 && $indent_lens_[$i] < $below_indent && $empty_line === false )
					break;

				//	直下インデントが未計算なら、最初にインデントが下がった段階を直下インデントとする
				if( $below_indent < 0 && $indent_lens_[$i] > $indent )
					$below_indent = $indent_lens_[$i];

				//	直下インデントを計算済みなら、直下インデント以外は処理なしとする
				else if( $indent_lens_[$i] != $below_indent )
					continue;
			}

			//	「兄弟要素」判定
			if( $siblings === true )
			{
				if( $indent_lens_[$i] != $indent )
					continue;
			}

			//	@@&&@@のほうが@@||@@より優先として検索する
			if( $contain_and === true || $contain_or === true )
			{
				$ors = explode("@@||@@", $keyword);
				$or_hit = false;
				foreach( $ors as $or_keyword )
				{
					$ands = explode("@@&&@@", $or_keyword);
					$and_hit = false;
					foreach( $ands as $and_keyword )
					{
						if(
							($perfect_match === true && trim($lines_[$i]) == trim($and_keyword)) ||
							($perfect_match === false && mb_strpos($lines_[$i], trim($and_keyword)) !== false)
						){
							$and_hit = true;
						}
						else
						{
							$and_hit = false;
							break;
						}
					}
					if( $and_hit === true )
					{
						$or_hit = true;
						break;
					}
				}
				if( $or_hit === true )
				{
					if( $level_more === true )
					{
						$founds = array_merge
						(
							$founds,
							self::select_lines($lines_, $indent_lens_, $selector_, $level_ + 1, $i + 1, $search_child_, $i)
						);
					}
					else $founds[] = $i;
				}
			}
			else
			{
				if(
					($perfect_match === true && trim($lines_[$i]) == $keyword) ||
					($perfect_match === false && mb_strpos($lines_[$i], $keyword) !== false)
				){
					if( $level_more === true )
					{
						$founds = array_merge
						(
							$founds,
							self::select_lines($lines_, $indent_lens_, $selector_, $level_ + 1, $i + 1, $search_child_, $i)
						);
					}
					else $founds[] = $i;
				}
			}
		}
		return $founds;
	}

	//--------------------------------------------------------------------------
	//	インデント配列と基準となる行番号を指定して、
	//	基準行番号から見たブロック開始行番号と終了行番号を配列で返却する
	//--------------------------------------------------------------------------
	private static function calc_block_range($lines_, $indents_, $offset_, $outer_ = false)
	{
		//	1. 基準行以降でインデントが下がった行が、ブロック開始位置
		//	2. その後インデントが上がる行の直前の行が、ブロック終了位置
		//	3. ただし、1.の時点でインデントが下がる行を見つける前に上がってしまった場合は、
		//	   基準行自体が開始位置で且つ終了位置とする
		$org_indent = $indents_[$offset_];
		$start_line_pos = 0;
		for( $i = $offset_ + 1; $i < count($indents_); $i++ )
		{
			if( $indents_[$i] > $org_indent )
			{
				$start_line_pos = $i;
				break;
			}
			else if( $indents_[$i] < $org_indent && trim($lines_[$i]) != "" )
				return [$offset_, $offset_];
		}
		if( $start_line_pos <= 0 ) return [$offset_, $offset_];

		//	終了位置の検索
		$end_line_pos = -1;
		for( $i = $start_line_pos + 1; $i <count($indents_); $i++ )
		{
			if( trim($lines_[$i]) == "" ) continue;
			if( $indents_[$i] <= $org_indent )
			{
				$end_line_pos = $i - 1;
				break;
			}
		}
		if( $end_line_pos < 0 ) $end_line_pos = count($indents_) - 1;

		//	アウター指定の場合は、外側に向かってインデントがorg_indentに揃う位置を計算する
		if( $outer_ === true )
		{
			for( $i = $start_line_pos; $i >= $offset_; $i-- )
			{
				if( $indents_[$i] == $org_indent )
				{
					$start_line_pos = $i;

					//	ただし、対象行が開始ブラケットだった場合は、その上の行を開始位置とする
					if( in_array(trim($lines_[$start_line_pos]), ["{", "[", "("]) === true )
					{
						if( $start_line_pos > 0 ) $start_line_pos--;
					}

					//	さらにコメントがすぐ上にある場合にはその上までスキップする
					while( $start_line_pos > 0 && substr(trim($lines_[$start_line_pos - 1]), 0, 2) == "//" )
						$start_line_pos--;

					break;
				}
			}
			for( $i = $end_line_pos; $i < count($indents_); $i++ )
			{
				if( $indents_[$i] <= $org_indent )
				{
					//	インデントが開始インデントと同じ位置になった場合は、
					//	その行が終了位置となる
					if( $indents_[$i] == $org_indent )
					{
						//	ただし、すぐに次の行でインデントが下がる場合、
						//
						//	if(xxxx) ... A
						//		aaa  ... B
						//	else     ... C
						//		bbb  ... D
						//
						//	のように、ifブロック(A)がelse行(C)で戻るので、
						//	@@after : xxx の終端は (C) になる
						//	すると、(B)と(C)の間にafter追加したいのに、(C)と(D)の間に追加されることになる。
						//	これを防ぐため、終端後すぐにインデントが下がる場合には、一つ前の行を終端とする。
						//	この場合は、(C)が終端ではなく(B)を終端とする必要がある
						if( isset($indents_[$i + 1]) && $indents_[$i + 1] > $org_indent )
							$end_line_pos = $i - 1;
						else
							$end_line_pos = $i;
					}

					//	インデントが開始インデントよりも多くさがった場合は、
					//	その行の一つ前の行が終了位置となる
					else
						$end_line_pos = $i - 1;

					break;
				}
			}
		}

		return [$start_line_pos, $end_line_pos];
	}
	private static function calc_block_range_outer($lines_, $indents_, $offset_)
	{
		return self::calc_block_range($lines_, $indents_, $offset_, true);
	}

	//--------------------------------------------------------------------------
	//	if条件式判定
	//--------------------------------------------------------------------------
	private static function check_if($args_raw_)
	{
		$php = ""
			."namespace ifblock_".uniqid()."{ use \\crow_component;"
				.'function conf($key_, $def_ = \'\') {return crow_component::conf($key_, $def_);}'
				.'function comp($comp_) {return crow_component::comp($comp_);}'
				.'function comps($comps_) {return crow_component::comps($comps_);}'
				.'return '.$args_raw_.';'
			."}"
			;
		return eval($php);
	}

	//--------------------------------------------------------------------------
	//	インデント適用
	//--------------------------------------------------------------------------
	private static function apply_indent($content_, $indent_)
	{
		if( trim($content_) == "" ) return "";
		if( strpos($content_,"\n") === false ) return $indent_.$content_;

		$result = "";
		$lines = explode("\n", $content_);
		foreach( $lines as $line )
		{
			$result .= $indent_.rtrim($line)."\r\n";
		}
		return rtrim($result)."\r\n";
	}

	//--------------------------------------------------------------------------
	//	パスを指定し、存在しないディレクトリを作成する
	//--------------------------------------------------------------------------
	private static function force_mkdir($path_)
	{
		$os_path = self::P($path_);
		$path_parts = explode(DIRECTORY_SEPARATOR, $os_path);

		$path = DIRECTORY_SEPARATOR;
		foreach( $path_parts as $path_part )
		{
			$path .= $path_part;
			if( is_dir($path) === false ) mkdir($path, 0777);
			$path .= DIRECTORY_SEPARATOR;
		}
	}

	//--------------------------------------------------------------------------
	//	eval 内コードからの状態取得用
	//--------------------------------------------------------------------------
	public static function conf($key_, $def_)
	{
		return isset(self::$m_component_config[$key_]) ?
			self::$m_component_config[$key_] : $def_;
	}
	public static function comp($comp_)
	{
		return in_array($comp_, self::$m_components_during_build);
	}
	public static function comps($comps_)
	{
		if( is_array($comps_) === true && count($comps_) > 0 )
		{
			foreach( $comps_ as $comp )
			{
				if( self::comp($comp) === false )
					return false;
			}
		}
		return true;
	}
	public static function conf_set($confs_)
	{
		self::$m_component_config = $confs_;
	}
	public static function conf_update($key_, $val_)
	{
		self::$m_component_config[$key_] = $val_;
	}
	public static function conf_updates($items_)
	{
		foreach( $items_ as $key => $val )
			self::$m_component_config[$key] = $val;
	}

	//--------------------------------------------------------------------------
	//	各行の "@@" の前のブランクを削除
	//--------------------------------------------------------------------------
	private static function ltrim_cmdlines($lines_)
	{
		$lines = [];
		$last_indent = 0;
		foreach( $lines_ as $line )
		{
			$trim_line = ltrim($line);
			if( substr($trim_line, 0, 2) == "@@" )
			{
				$lines[] = $trim_line;
				$last_indent = strlen($line) - strlen($trim_line);
			}
			else if( $last_indent > 0 )
			{
				$lines[] = substr($line, $last_indent);
			}
			else
			{
				$lines[] = $line;
			}
		}
		return $lines;
	}

	//--------------------------------------------------------------------------
	//	パス文字列からcrowpathを除外する
	//--------------------------------------------------------------------------
	public static function exclude_crowpath($path_)
	{
		$crow_path_p = self::P(CROW_PATH);
		$path_p = self::P($path_);

		$pos = mb_strpos($path_p, $crow_path_p);
		return $pos !== false ?
			mb_substr($path_p, $pos + strlen($crow_path_p)) :
			$path_p
			;
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

	//--------------------------------------------------------------------------
	//	エラー出力
	//--------------------------------------------------------------------------
	private static function output_error($err_)
	{
		$current = count(self::$m_components_during_build) > 0 ? end(self::$m_components_during_build) : '(none)';
		echo "--- crow_component error, component : ".$current." ---<br>\r\n";
		echo $err_."<br>\r\n";
		exit;
	}

	private static $m_delim = DIRECTORY_SEPARATOR;
	private static $m_exists_fname = CROW_PATH."output/applied_components";
	private static $m_backup_dir = CROW_PATH."output/backup/before_override/";
	private static $m_lock_path = CROW_PATH."output/temp/components.lock";
	private static $m_components_during_build = [];
	private static $m_component_config = [];
}
?>
