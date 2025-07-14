/*
--------------------------------------------------------------------------------
ビューパーツ管理

	■ インスタンス作成
	instance = viewpart_activate(partname_, args_)

		最初の要素をviewpart_activate()で画面に出現させる。
		これは内部的にviewpartのインスタンス作成と、画面へのマウントを同時に行う。

	instance = viewpart_create(partname_, args_, pref_name_ = null)
		動的にビューパーツを追加する際は、viewpart_create()で作成する。
		viewpart_create() だけではまだ画面上には出現しない。
		その後に append_to_element() や append_to_part()、prepend_to_part() を以てDOMに追加され、画面に描画される。
		pref_name_を指定すると、生成した子パーツにpref名を割り当てる。
		pref_name_ は省略するとパーツ名と同じpref名が割り当てられる

	instance_array = viewpart_create_array(partname_, args_array_, pref_name_ = null)
		複数のインスタンスを一括作成して、インスタンスの配列を返却する
		args_ の件数分作成し、[0]番目のインスタンスはargs_[0]のパラメータで初期化される
		pref_name_を指定すると、生成したすべての子パーツに同じpref名を割り当てる。
		pref_name_ は省略するとパーツ名と同じpref名が割り当てられる

	■ インスタンス検索
	viewpart_find_by_uid(uid_)

		viewpartのインスタンスは全てユニークなIDを持っている。
		そのIDを使ってviewpartのインスタンスを取得するのが本メソッドとなる。

	■ モジュール名取得
	instance.module()

		_common_配下にあるパーツの場合は、フォルダに複数のパーツをまとめることができる。
		フォルダまでのパスを表す文字列がモジュール名となる。

		例）
		viewparts/front/_common_/header.part				→ モジュール名はなし。パーツ名は header となる（これが標準配置したパターン）
		viewparts/front/_common_/create/_.part				→ モジュール名は「create」パーツ名は「create」 となる
		viewparts/front/_common_/create/form.part			→ モジュール名は「create」パーツ名は「create_form」 となる
		viewparts/front/_common_/create/tweet/_.part		→ モジュール名は「create_tweet」パーツ名は「create_tweet」 となる
		viewparts/front/_common_/create/tweet/buttons.part	→ モジュール名は「create_tweet」パーツ名は「create_tweet_buttons」 となる

		テンプレート内への [[]] による埋め込みに指定するパーツ名は、
		同一モジュール内であればモジュール名部分を省略可能。
		上記例では、create_tweetパーツ内で、[[create_tweet_buttons]] ではなく [[buttons]]と記載可能。
		ただしviewpart_create()に指定するパーツ名は省略できないので、instance.module()を使って相対的に記載する。
		→ viewpart_create("create_tweet_buttons")か、viewpart_create(self.module() + "buttons")

	■ ルートエレメント取得
	instance.root()

		viewpartはtemplate直下に一つだけエレメントを持つ。
		本メソッドはそのエレメント（Nodeオブジェクト）を返却する

	■ ユニークID取得
	instance.uid()

		viewpartのインスタンスに振られたユニークなIDを返却する

	■ 自身のpref名を取得
	instance.pref()

		pref()の引数を省略すると自身のpref名を取得できる。
		引数を指定すると、後述の子パーツ取得となる


	■ 子パーツ取得
	instance.pref(pref_name_)
	instance.prefs(pref_name_)

		prefを指定して、子パーツを検索してそのインスタンスを取得する。
		同名のprefで複数の子パーツが存在する場合があり、prefs()ではそれらを配列で返却する。
		pref()の場合は、最初に見つかった1つめのインスタンスを返却する。

	■ 子エレメント取得
	instance.ref(ref_name_)
	instance.refs(ref_name_)

		ref属性が付いているエレメントを本メソッドで検索して取得する。
		複数ヒットする場合、refs()で全件を取得できる。
		ref()の場合は、最初に見つかった1つめのエレメントを返却する。

	■ 子エレメントをjQueryオブジェクトで取得
	instance.jq(ref_name_)

		refで探したエレメントをjQuery化して返却する。
		一度取得すれば、次回以降はキャッシュからの返却となる。
		ref名を省略時は、ルートエレメントのjQueryオブジェクトを取得する。

	■ 親パーツ取得
	instance.parent()

		親がビューパーツだった場合に、そのインスタンスを返却する。
		親がエレメントだった場合にはnullを返却する。

	■ プロパティのキー一覧を取得
	instance.prop_keys()

	■ プロパティ取得/設定
	instance.prop(prop_name_)
	instance.prop(prop_name_, value_, [watch_ = true])

		ビューパーツのpropsで定義されているプロパティを取得/変更する
		変更した場合、watch式のイベントが発火する
		watch_ にfalseを指定するとイベント発火は行われない

	■ 全てのプロパティを取得/設定
	instance.props()
	instance.props(props_)

		引数の指定がない場合は全てのプロパティをオブジェクトで返却する
		引数にオブジェクトの指定がある場合は、プロパティを更新する

		例）
		instance.props({key1 : val1, key2 : val2});
		は、
		instance.prop(key1, val1);
		instance.prop(key2, val2);
		と同等の処理で、指定されたキーのプロパティのみ更新する

	■ プロパティの変更イベントを強制発火する
	instance.update(prop_name_)
	instance.update_all(prop_name_)

	例えば次のようなプロパティセットがあり、

		<props>
		{
			data1 : "単一データ",
			data2 : {title : "複合データ", number : 13}
		}
		</props>
		<template>
			<div>単一値 : {{ data1 }}</div>
			<div>複合値 : {{ data2.title }}、{{ data2.number }}</div>
			<button classs="ui_button" ref="btn1">単一データ変更</button>
			<button classs="ui_button" ref="btn2">複合データ変更</button>
		</template>

	値を変更する場合は次のようにする。

		<ready>
		{
			//	単一データの変更
			$(self.ref('btn1')).on('click', () =>
			{
				self.prop('data1', "変更後の単一データ");
			});

			//	複合データの変更
			$(self.ref('btn2')).on('click', () =>
			{
				self.prop('data2').number = 20;
				self.update('data2');
			});
		}
		</ready>

	とすると、prop()やupdate()により値の変更と同時に、画面上のテキストも変化する。
	update_all()はすべてのプロパティに対して更新イベントを発火する。

	■ ready時、ready処理前の追加処理
	instance.on_preready(function_(part_))

		<ready>実行前に追加で実行する処理を指定する。
		ここで指定した処理はキューに積まれ、<ready>実行前に全て実行される。
		既に<ready>が実行済みだった場合は、指定された処理を即座に実行する。

	■ ready時、ready処理後の追加処理
	instance.on_ready(function_(part_))

		<ready>実行後に追加で実行する処理を指定する。
		ここで指定した処理はキューに積まれ、<ready>完了直後に全て実行される。
		既に<ready>が実行済みだった場合は、指定された処理を即座に実行する。

	■ 自身が画面から削除されるときのコールバックを指定する
	instance.on_remove(function_(part_));

		remove()やremove_pref()により削除される際に実行する処理を指定する。
		ここで指定した処理はキューに積まれ、画面からの消去と同時に全て実行される。

	■ watchの開始/停止を制御する
	instance.watch_start()
	instance.watch_stop()

		通常は<init>後にwatchが動き出す。つまり、
		通常のライフサイクルは <init> → <watch>開始 となり、
		暫くしてDOMの作成が完了すると<ready>が実行される。

		例えばwatch内でDOMの操作を行う場合、タイミングによっては
		DOMが作成される前にDOMへの操作を行ってしまう問題が発生する。
		その対処として、本メソッドでwatchの開始と停止を制御することが可能

		例）DOMの作成完了を以てwatchを開始する
		<init>
		{
			//	DOMの構築完了までwatchを一旦停止
			self.watch_stop();
		}
		</init>
		<ready>
		{
			//	DOMの構築が完了したのでwatchを開始する
			self.watch_start();
		}
		</ready>

	■ 指定したパーツを作成可能かどうかチェックする
	instance.exists_partname(partname_)

		作成可能な場合は true を返却し、作成できない場合には false を返却する。

	■ 子パーツを作成する
	instance.create_child(partname_, ars_={}, pref_name_ = null)

		viewpart_create()とほぼ同等の機能だが、こちらは同モジュール内のパーツの場合にモジュール名部分を省略できる。
		pref_name_ は省略した場合、パーツ名からモジュール名部分を除外した名前となる

	■ 子パーツを作成して、自身に追加する
	instance.create_child_and_append(partname_, args_ = {}, ref_name_ = null, pref_name_ = null)
	instance.create_child_and_prepend(partname_, args_ = {}, ref_name_ = null, pref_name_ = null)

		子パーツを作成して、自身に追加する
		子の追加先は自身が管理しているref_で指定する。指定がない場合には自身のルートへ追加される
		※create_child_and_prependは、末尾ではなく先頭に追加する

	■ 子パーツリストを作成する
	instance.create_children(partname_, args_array_ = [], pref_name_ = null)

		viewpart_create_array()とほぼ同等の機能で、違いは create_child と同様

	■ 子パーツリストを作成して、自身に追加する
	instance.create_children_and_append(partname_, args_array_, ref_name_ = null, pref_name_ = null)
	instance.create_children_and_prepend(partname_, args_array_, ref_name_ = null, pref_name_ = null)

		子の追加先は自身が管理しているref_で指定する。指定がない場合には自身のルートへ追加される
		prependの方は、追加した子リストに対してargs_array_が逆順で渡される。

	■ 本インスタンスに対してメッセージを投げて、伝播は行わない
	instance.post(msg_, params_={})

	■ メッセージを親/子に投げる
	instance.postup(msg_, params_={})
	instance.postdown(msg_, params_={})

		msg_で指定した文字列で親や子にメッセージを投げる。
		受け付ける親や子がいない場合はさらに階層を深く辿っていく。

		メッセージを受け付ける場合は、<recv></recv>内にメソッドを記載する。
		例えば子から error というメッセージを投げる場合

		子）
			self.postup('error', 'エラー内容');

		親）
		<recv>
		{
			error(sender_, param_)
			{
				ui.dialog.popup_error('エラー', param_);
			}
		}
		</recv>

		のような形になる。

		※ 受け側の第一引数は、発信者のインスタンスが固定で渡る。post時に指定したパラメータは第二引数に渡ってくる。
		※ イベントの伝播を停止したい場合には、受け付けたメソッド側で true を返却するとよい

	■ メッセージを自分以上/自分以下に投げる
	instance.postmeup(msg_, params_={})
	instance.postmedown(msg_, params_={})

		自分を含めて親や子に伝播する
		postmeup()は、post()とpostup()の二つを実行するのと同等で、
		postmedown()は、post()とpostdown()の二つを実行するのと同等となる

	■ エレメントのテキスト要素をプロパティにバインド/アンバインド
	instance.bind_text(elm_, prop_name_)
	instance.unbind_text(elm_, prop_name_)

		エレメントのテキスト要素と、propsで定義したプロパティをバインド/アンバインドする。
		バインドした場合、プロパティの変更時に画面上の値も変化するようになる。

	■ エレメントの属性をプロパティにバインド/アンバインド
	instance.bind_attr(elm_, attr_name_, prop_name_)
	instance.unbind_attr(elm_, attr_name_, prop_name_)

		エレメントの属性とpropsで定義したプロパティをバインド/アンバインドする。

	■ エレメントのスタイルをプロパティにバインド/アンバインド
	instance.bind_style(elm_, style_name_, prop_name_)
	instance.unbind_style(elm_, style_name_, prop_name_)

		エレメントのスタイルとpropsで定義したプロパティをバインド/アンバインドする。
		スタイル名はケバブケースの文字列で指定する。

	■ エレメントのサイズ変更をプロパティにバインド/アンバインド
	instance.bind_resize(elm_, prop_name_)
	instance.unbind_resize(elm_, prop_name_)

		エレメントのサイズが変更された際に、指定した名前のプロパティを変更する。
		プロパティの値として DOMRectReadOnly のオブジェクトが格納される。
		これは x, y, width, height, top, right, bottom, left プロパティから構成される。
		本メソッドによるバインドは読み取り専用となり、
		self.prop() などで値を更新したとしてもDOMのサイズは変更されない。

	■ 入力エレメントをプロパティにバインド/アンバインド
	instance.bind_input(elm_, prop_name_)
	instance.unbind_input(elm_, prop_name_)

		エレメントの入力要素と、propsで定義したプロパティをバインド/アンバインドする。
		バインドした場合、プロパティの変更時に画面上の値も変化するようになる。
		逆に画面上の値を変更した場合にはプロパティが変化する。

	■ ラジオボタンとチェックボックスのチェック状態をプロパティにバインド/アンバインド
	instance.bind_checked(elm_, prop_name_)
	instance.unbind_checked(elm_, prop_name_)

	■ create済みのパーツを子として追加
	append(viewpart_, ref_name_ = null)
		viewpart_create()で作成したviewpartのインスタンスを指定して、
		自身の子パーツとして追加する。ref_name_には自身が管理するrefを指定できる。
		ref_name_を指定した場合にはそのエレメントの直下に追加され、
		指定しない場合には自身が管理するルートエレメントの直下に追加される

	■ create済みのパーツの配列を子として追加
	append_array(viewpart_array_, ref_name_ = null)
		append()と同じ仕様だが、第一引数にはビューパーツの配列を指定する点が異なる。
		複数のビューパーツを子として一括追加する

	■ 指定エレメントの子として追加
	instance.append_to_element(elm_)

		指定エレメントに対してルートエレメントをマウントする。

	■ 指定パーツの指定refへ子として追加
	instance.append_to_part(viewpart_instance_, ref_name_)

		指定ビューパーツのインスタンスに対してマウントする。
		ref_name_を指定することで、マウントするポイントを指示可能。
		ref_name_を指定しない場合には、指定ビューパーツのルートエレメントに対してマウントする。

	■ 指定パーツの指定refへ子として、先頭または指定パーツの前に追加
	instance.prepend_to_part(viewpart_instance_, ref_name_ = null, before_viewpart_ = null)

		指定ビューパーツのインスタンスに対してマウントする。
		ref_name_を指定することで、マウントするポイントを指示可能。
		ref_name_を指定しない場合には、指定ビューパーツのルートエレメントに対してマウントする。
		before_viewpart_ を指定しない場合は先頭に挿入し、指定する場合にはそのパーツの前に挿入する。

	■ 自身を削除する
	instance.remove();

		ビューパーツのヒエラルキーと画面上から、ビューパーツのインスタンスを削除する。

	■ 子パーツを削除する
	instance.remove_pref(pref_name_);

		子パーツのpref文字列を指定して削除する。

	■ 試験機能
		crow configの「viewpart.test」に "auto" か "manual" を指定すると、viewpartの試験用メソッドが有効になる。
		"auto" を指定した場合、パーツの<test>セクションに記載したメソッドが、パーツ初期化時に自動で全て実行されるようになる。
		"manual" を指定した場合、初期化時の自動実行は行われないが、いつでも手動で<test>セクションのメソッドを実行可能となる。
		<test>に記載したメソッドは、実行されるとブラウザのコンソールに結果を出力する。

		例）hello.php
		<test>
		{
			check_hello()
			{
				//	両辺の値が等しいかチェックする
				//	不正であれば失敗する。
				self.assert_eq(2 + 3, 5);
			}
		}
		</test>

		→ ブラウザには試験が失敗した旨が表示される。

		例）上記を手動で実行する場合
		<ready>
		{
			//	<test>のメソッドを全て実行する
			self.run_tests();
		}
		</ready>

		試験機能を有効にした場合に使えるようになるメソッドは次の通り。

		//	試験（<test>内のメソッド）を全て実行
		self.run_tests();

		//	指定した名前の試験を実行
		self.run_test(test_method_name_);

		//	true チェック
		self.assert_true(condition, msg_ = null)

		//	false チェック
		self.assert_false(condition, msg_ = null)

		//	"===" チェック
		self.assert_eq(left, right, msg_ = null)

		//	"!==" チェック
		self.assert_nq(left, right, msg_ = null)

		//	">" チェック
		self.assert_gt(left, right, msg_ = null)

		//	">=" チェック
		self.assert_ge(left, right, msg_ = null)

		//	"<" チェック
		self.assert_lt(left, right, msg_ = null)

		//	"<=" チェック
		self.assert_le(left, right, msg_ = null)

--------------------------------------------------------------------------------
*/

//	インスタンスを作成してアタッチは行わない
//	pref_name_ は省略するとパーツ名と同じpref名が割り当てられる
function viewpart_create(partname_, args_, pref_name_ = null)
{
	let elm = viewpart_get_template(partname_);
	if( elm == null )
	{
		console.log('not found viewpart ' + partname_);
		return null;
	}
	return new viewpart(elm, args_, pref_name_);
}

//	複数のインスタンスを一括作成して、インスタンスの配列を返却する
//	args_ の件数分作成し、[0]番目のインスタンスはargs_[0]のパラメータで初期化される
//	pref_name_ は省略するとパーツ名と同じpref名が割り当てられる
function viewpart_create_array(partname_, args_array_, pref_name_ = null)
{
	let instances = [];
	for( let index in args_array_ )
	{
		let item = viewpart_create(partname_, args_array_[index], pref_name_);
		instances.push(item);
	}
	return instances;
}

//	インスタンスを作成して、元の場所にアタッチする
//	複数のパーツがある場合には、最初の一つ目のみ処理が行われる
function viewpart_activate(partname_, args_, root_ = null)
{
	let root = root_ == null ? document : root_;
	let elm = root.querySelector('template[viewpart=' + partname_ + ']');
	if( elm == null )
	{
		console.log('not found viewpart ' + partname_);
		return null;
	}
	let part = new viewpart(elm, args_);
	part.replace_with(elm, null);
	return part;
}

//	ビューパーツ実体をIDで探す
function viewpart_find_by_uid(uid_)
{
	if( uid_ in viewpart_global ) return viewpart_global[uid_];
	return null;
}

//	ビューパーツ実体をパーツ名で探す
//	複数ある場合は最初の一つを見つけた段階で検索を停止する
function viewpart_find_by_name(name_)
{
	let keys = Object.keys(viewpart_global);
	for( let index = 0; index < keys.length; index++ )
	{
		let part = viewpart_global[keys[index]];
		if( part.m.name == name_ ) return part;
	}
	return null;
}

//	ビューパーツ実体をパーツ名で全て探して、配列で返却する
function viewpart_find_all_by_name(name_)
{
	let keys = Object.keys(viewpart_global);
	let founds = [];
	for( let index = 0; index < keys.length; index++ )
	{
		let part = viewpart_global[keys[index]];
		if( part.m.name == name_ ) founds.push(part);
	}
	return founds;
}

//	指定した名前のパーツ取得
function viewpart_get_template(partname_)
{
	return document.querySelector('template[viewpart=' + partname_ + ']');
}

/*
--------------------------------------------------------------------------------
ビューパーツ実体

	viewpart_create(), viewpart_activate() で作成する

--------------------------------------------------------------------------------
*/
var viewpart_global = {};
var viewpart = function(template_, args_ = null, pref_name_ = null)
{
	//	内部データ
	this.m =
	{
		uid : "",				//	ユニークID
		module : "",			//	モジュール名
		name : "",				//	パーツ名
		root : null,			//	template直下の唯一のエレメント
		pref : null,			//	自身の part ref
		child_parts : {},		//	子パーツの連想配列、キーはref、値はviewpart配列
		child_binds : [],		//	子へのプロパ変化通知用
		parent_part : null,		//	親パーツ、親がDOMNodeの場合はnull
		props : {},				//	プロパティ
		binder : {},			//	リアクティブ値の入れ物
		bind_funcs : {},		//	リアクティブ反映先
		mounted : false,		//	DOMにマウント済み？
		watching : false,		//	watch有効？
		ready_called : false,	//	ready済み？
		preready_queue : [],	//	DOMマウント時、readyイベント前に実行する処理キュー,
		ready_queue : [],		//	DOMマウント時、readyイベント後に実行する処理キュー,
		remove_queue : [],		//	画面から削除時に実行するキュー
		is_inputting : false,	//	input要素起点でのフロー中？
		observers : null,		//	利用している mutation observer のリスト
		jq : [],				//	jQueryオブジェクトキャッシュ
		test_result : true,		//	テスト結果
		test_result_msg : ''	//	テスト結果詳細
	};

	if( template_.hasAttribute("viewpart") == false )
	{
		console.log("template is not viewpart");
		return;
	}
	this.m.name = template_.getAttribute("viewpart");

	//	モジュール名
	if( template_.hasAttribute("module") == true )
		this.m.module = template_.getAttribute("module");

	//	インスタンスへメソッド追加
	let methods = this._method_();
	for( method_name in methods )
	{
		this[method_name] = methods[method_name];
	}

	//	直下の唯一のDOMをルートとする
	let clone = template_.content.cloneNode(true);
	let child_elms = clone.childNodes;
	let root_ref = "";
	for( let i = child_elms.length - 1; i >= 0; i-- )
	{
		if( this.m.root === null && child_elms[i].tagName )
		{
			if( child_elms[i].hasAttribute('ref') === true )
			{
				root_ref = child_elms[i].getAttribute('ref');
			}
			this.m.root = child_elms[i];
		}
		else
		{
			if( child_elms[i].tagName )
			{
				console.log('warning, there must be one element directly under the template');
				return;
			}
			child_elms[i].remove();
		}
	}
	if( this.m.root === null )
	{
		console.log('warning, not found element under the template');
		return;
	}

	//	pref_name_ 指定がないならパーツ名と同じ割り当てる
	if( pref_name_ === null ) pref_name_ = this.m.name;
	this.m.pref = pref_name_;

	//	uidを作成
	this.m.uid = this.make_random();

	//	インスタンスをグローバル領域に保持する
	viewpart_global[this.m.uid] = this;

	//	参照エレメントをuidを使用してユニーク化しておく
	//	これは埋め込みテンプレートを具現化した時に被らないようにする対処となる
	if( root_ref != "" )
	{
		this.m.root.setAttribute('ref', root_ref + this.m.uid);
	}
	let childs = this.m.root.querySelectorAll('[ref]');
	if( childs != null && childs.length > 0 )
	{
		let child_cnt = childs.length;
		for( let index = 0; index < child_cnt; index++ )
		{
			let ref_elm = childs.item(index);
			let ref_val = ref_elm.getAttribute('ref');
			ref_elm.setAttribute('ref', ref_val + this.m.uid);
		}
	}

	//	プロパティ初期化
	this.m.props = this._props_();
	if( args_ != null )
	{
		for( let key in args_)
		{
			this.m.props[key] = args_[key];
		}
	}

	let self = this;
	for( let key in this.m.props )
	{
		self.m.bind_funcs[key] = [];

		Object.defineProperty(self.m.binder, key,
		{
			configurable : true,

			get()
			{
				return self.m.props[key];
			},
			set(newval_)
			{
				let old = self.m.props[key];
				self.m.props[key] = newval_;

				let len = self.m.bind_funcs[key].length;
				for( let index = 0; index < len; index++ )
				{
					self.m.bind_funcs[key][index](newval_);
				}
				self.fire_watch(key, old, newval_);
			}
		});
	}

	//	ルートの属性バインド
	if(true)
	{
		let elm = this.m.root;
		let attrs = elm.attributes;
		let attr_cnt = attrs.length;
		for( let attr_index = 0; attr_index < attr_cnt; attr_index++ )
		{
			let attr = attrs[attr_index];

			//	":"開始の属性値をバインド
			if( attr.value.startsWith(":") === true )
			{
				//	"::"で同名バインド
				let prop_name = attr.value=="::" ? attr.name : attr.value.substring(1);
				if( prop_name in this.m.props )
				{
					if( attr.name == "checked" )
						this.bind_checked(elm, prop_name);
					else if( attr.name == "required" || attr.name == "disabled" || attr.name == "readonly" )
						this.bind_boolean(elm, attr.name, prop_name);
					else this.bind_attr(elm, attr.name, prop_name);
				}
			}
		}
	}

	//	通常タグの属性バインド
	let elms = this.m.root.querySelectorAll('*');
	let elm_cnt = elms.length;
	for( let elm_index = 0; elm_index < elm_cnt; elm_index++ )
	{
		let elm = elms[elm_index];
		if( elm.tagName == "TEMPLATE" ) continue;
		let attrs = elm.attributes;
		let attr_cnt = attrs.length;
		for( let attr_index = 0; attr_index < attr_cnt; attr_index++ )
		{
			let attr = attrs[attr_index];

			//	":"開始の属性値をバインド
			if( attr.value.startsWith(":") === true )
			{
				//	"::"で同名バインド
				let prop_name = attr.value=="::" ? attr.name : attr.value.substring(1);
				if( prop_name in this.m.props )
				{
					if( attr.name == "checked" )
						this.bind_checked(elm, prop_name);
					else if( attr.name == "required" || attr.name == "disabled" || attr.name == "readonly" )
						this.bind_boolean(elm, attr.name, prop_name);
					else this.bind_attr(elm, attr.name, prop_name);
				}
			}
		}
	}

	//	包含するパーツの解決
	childs = this.m.root.querySelectorAll('template');
	if( childs != null && childs.length > 0 )
	{
		let child_cnt = childs.length;
		for( let index = 0; index < child_cnt; index++ )
		{
			let template_from = childs.item(index);
			if( template_from.hasAttribute('from') == false )
			{
				console.log('warning, not found "from" attribute');
				continue;
			}

			let from = template_from.getAttribute('from');
			let attrs = template_from.attributes;
			let args = {};
			let pref = null;
			let bind_props = [];
			for( let attr_index in Object.keys(attrs) )
			{
				let attr = attrs[attr_index];

				//	fromキーは無視
				if( attr.name == "from" )
				{
				}
				//	pref指定は属性とはしない
				else if( attr.name == "pref" )
				{
					pref = attr.value;
				}
				//	":" 開始の値はプロパティバインド
				else if( attr.value.startsWith(":") === true )
				{
					//	"::"で同名バインド
					let prop_name = attr.value=="::" ? attr.name : attr.value.substring(1);
					if( prop_name in this.m.props )
					{
						args[attr.name] = this.m.props[prop_name];
						bind_props.push([prop_name, attr.name]);
					}
				}
				//	"@" 開始は初回のみプロパティから適用
				else if( attr.value.startsWith("@") === true )
				{
					let prop_name = attr.value.substring(1);
					if( prop_name in this.m.props )
						args[prop_name] = this.m.props[prop_name];
				}
				//	それ以外は通常属性
				else
				{
					//	true/falseは真偽値として渡す
					if( attr.value == "true" ) args[attr.name] = true;
					else if( attr.value == "false" ) args[attr.name] = false;
					else args[attr.name] = attr.value;
				}
			}

			//	fromで指定されたテンプレからパーツへの実体化
			let template_elm = document.querySelector('template[viewpart=' + from + ']');
			if( template_elm == null )
			{
				console.log("not found part " + from);
				continue;
			}
			let child_part = new viewpart(template_elm, args, pref);
			if( child_part == null ) continue;
			child_part.replace_with(template_from, this);

			//	pref指定がなかった場合、上記のインスタンス作成時に生成されている
			if( pref === null ) pref = child_part.m.name;

			//	必要なら子へのプロパティバインド
			if( bind_props.length > 0 )
				this.m.child_binds.push({child: child_part, props: bind_props});
		}
	}

	//	必要なら単体テストのメソッドをアタッチ
	if( this._test_ )
	{
		//	単発テストも行う可能性があるため、予めメソッドを追加しておく
		let tests = this._test_();
		if( Object.keys(tests).length > 0 )
		{
			for( test_name in tests )
				this[test_name] = tests[test_name];
		}
	}
};

//	モジュール名取得
viewpart.prototype.module = function()
{
	return this.m.module;
};

//	パーツ名の取得
viewpart.prototype.name = function()
{
	return this.m.name;
};

//	ルートエレメント取得
viewpart.prototype.root = function()
{
	return this.m.root;
};

//	ユニークID取得
viewpart.prototype.uid = function()
{
	return this.m.uid;
};

//	ref指定で要素が存在するかチェック
viewpart.prototype.ref_exists = function(name_)
{
	//	自身をチェック
	if( this.m.root.hasAttribute('ref') == true )
	{
		if( this.m.root.getAttribute('ref') == name_ + this.m.uid )
			return true;
	}

	//	子をチェック
	let nlist = this.m.root.querySelectorAll('[ref="' + name_ + this.m.uid + '"]');
	return nlist !== null && nlist.length > 0;
};

//	ref指定で要素を取得、NodeListを返却するのでそのままjQueryに入力可能
viewpart.prototype.refs = function(name_)
{
	return this.m.root.querySelectorAll('[ref="' + name_ + this.m.uid + '"]');
};

//	ref指定で要素を1つ取得、複数ある場合には最初に見つかったアイテムを返却
viewpart.prototype.ref = function(name_)
{
	//	自身をチェック
	if( this.m.root.hasAttribute('ref') == true )
	{
		if( this.m.root.getAttribute('ref') == name_ + this.m.uid )
			return this.m.root;
	}

	//	子をチェック
	let nlist = this.m.root.querySelectorAll('[ref="' + name_ + this.m.uid + '"]');
	if( nlist == null )
	{
		console.log("not found ref element 1 : " + this.m.name + " -> " + name_);
		return null;
	}

	let child_cnt = nlist.length;
	for( let index = 0; index < child_cnt; index++ )
	{
		return nlist.item(index);
	}

	console.log("not found ref element 2 : " + this.m.name + " -> " + name_);
	return null;
};

//	refで指定したエレメントのjQueryオブジェクトを返却する
//	一度取得したオブジェクトは内部にキャッシュされるため次回からの取得は早くなる
//	ref名を省略時は、ルートエレメントのjQueryオブジェクトを取得する。
viewpart.prototype.jq = function(name_ = "[root]")
{
	if( name_ in this.m.jq ) return this.m.jq[name_];
	this.m.jq[name_] = name_ == "[root]" ? $(this.root()) : $(this.ref(name_));
	return this.m.jq[name_];
};

//	親ビューパーツ取得
viewpart.prototype.parent = function()
{
	return this.m.parent_part;
};

//	pref指定で子ビューパーツの存在をチェック
viewpart.prototype.pref_exists = function(pref_name_)
{
	//	pref名そのもので検索
	if( this.m.child_parts[pref_name_] )
		return this.m.child_parts[pref_name_].length > 0;

	//	モジュール名を付与して検索
	let pref = this.m.module + "_" + pref_name_;
	if( this.m.child_parts[pref] )
		return this.m.child_parts[pref].length > 0;

	return false;
};

//	pref指定で子ビューパーツを1つ取得、複数あった場合、最初のアイテムを返却する。見つからなければ null
//	引数を省略すると、自身のpref文字列を取得する
viewpart.prototype.pref = function(pref_name_ = null)
{
	if( pref_name_ === null )
	{
		return this.m.pref;
	}

	//	pref名そのもので検索
	if( this.m.child_parts[pref_name_] )
		return this.m.child_parts[pref_name_].length > 0 ? this.m.child_parts[pref_name_][0] : null;

	//	モジュール名を付与して検索
	let pref = this.m.module + "_" + pref_name_;
	if( this.m.child_parts[pref] )
	{
		return this.m.child_parts[pref].length > 0 ? this.m.child_parts[pref][0] : null;
	}

	return null;
};

//	pref指定で子ビューパーツを全て取得、配列で返却する
//	prefを省略すると全ての子パーツを返却
viewpart.prototype.prefs = function(pref_name_ = undefined)
{
	if( pref_name_ === undefined )
	{
		let ret = [];
		for( let pref in this.m.child_parts )
		{
			for( let index in this.m.child_parts[pref] )
				ret.push(this.m.child_parts[pref][index]);
		}
		return ret;
	}

	if( this.m.child_parts[pref_name_] )
	{
		return this.m.child_parts[pref_name_].length > 0 ? this.m.child_parts[pref_name_] : [];
	}

	//	モジュール名を付与して検索
	let pref = this.m.module + "_" + pref_name_;
	if( this.m.child_parts[pref] )
	{
		return this.m.child_parts[pref].length > 0 ? this.m.child_parts[pref] : [];
	}

	return [];
};

//	プロパティのキー一覧を取得
viewpart.prototype.prop_keys = function()
{
	return Object.keys(this.m.props);
};

//	プロパティを取得 / 設定
//	watch_にtrueを指定するとwatchが発火する
viewpart.prototype.prop = function(name_, value_ = undefined, watch_ = true)
{
	//	name_がパス指定の場合、ディープアクセスとなる
	if( name_.indexOf('.') >= 0 )
	{
		let path_strs = name_.split('.');

		//	階層パス指定での取得
		if( value_ === undefined )
		{
			let prop_ref = this.m.props;
			for( let index = 0; index < path_strs.length && prop_ref !== null; index++ )
			{
				let key = path_strs[index];
				prop_ref = key in prop_ref ? prop_ref[key] : null;
			}
			return prop_ref;
		}

		//	階層パス指定での設定
		let prop_ref = this.m.props;
		let len = path_strs.length;
		for( let index = 0; index < len - 1 && prop_ref !== null; index++ )
		{
			let key = path_strs[index];
			prop_ref = key in prop_ref ? prop_ref[key] : null;
		}
		if( prop_ref === null ) return;

		//	watch_ 指定がない場合にはpropsの直接更新
		if( watch_ === false )
		{
			prop_ref[path_strs[len - 1]] = value_;
			return this;
		}

		//	binderにある場合はbinder経由で更新する
		//	この場合 watch 発火はbinder側で行われる
		if( name_ in this.m.binder )
		{
			this.m.binder[name_] = value_;
			return this;
		}

		//	それ以外は直接更新する
		//	watch はここで発火
		let old = prop_ref[path_strs[len - 1]];
		prop_ref[path_strs[len - 1]] = value_;
		this.fire_watch(name_, old, value_);
		return this;
	}

	//	階層パス指定なし時の取得
	if( value_ === undefined )
	{
		return name_ in this.m.props ? this.m.props[name_] : null;
	}

	//	watch_ 指定がない場合にはpropsの直接更新
	if( watch_ === false )
	{
		if( name_ in this.m.props )
			this.m.props[name_] = value_;
		return this;
	}

	//	binderにある場合はbinder経由で更新する
	//	この場合 watch 発火はbinder側で行われる
	if( name_ in this.m.binder )
	{
		this.m.binder[name_] = value_;
	}

	//	それ以外は直接更新する
	//	watch はここで発火
	else if( name_ in this.m.props )
	{
		let old = this.m.props[name_];
		this.m.props[name_] = value_;
		this.fire_watch(name_, old, value_);
	}
	return this;
};

//	全てのプロパティを取得 / 設定
viewpart.prototype.props = function(props_ = undefined)
{
	if( props_ === undefined )
	{
		return this.m.props;
	}
	for( let name_ in props_ )
	{
		this.prop(name_, props_[name_]);
	}
};

//	プロパティの更新イベントを強制発火する
viewpart.prototype.update = function(name_)
{
	this.prop(name_, this.prop(name_));
};

//	全てのプロパティの更新イベントを強制発火する
viewpart.prototype.update_all = function()
{
	for( name in this.m.props )
		this.update(name);
};

//	ready時、ready処理前に追加実行する処理を追加する、既にready発火済みならば即座に実行される
viewpart.prototype.on_preready = function(func_)
{
	if( this.m.ready_called === true ) func_();
	else this.m.preready_queue.push(func_);
	return this;
};

//	ready時、ready処理後に追加実行する処理を追加する、既にready発火済みならば即座に実行される
viewpart.prototype.on_ready = function(func_)
{
	if( this.m.ready_called === true ) func_();
	else this.m.ready_queue.push(func_);
	return this;
};

//	破棄時に追加実行する処理を追加する
viewpart.prototype.on_remove = function(func_)
{
	this.m.remove_queue.push(func_);
	return this;
};

//	指定した名前のパーツを作成できるかチェックする
viewpart.prototype.exists_partname = function(partname_)
{
	return
		viewpart_get_template(partname_) !== null ||
		viewpart_get_template(this.module() + "_" + partname_) !== null
		;
};

//	子パーツを作成する
//
//	viewpart_create()とほぼ同等の機能だが、こちらは同モジュール内のパーツの場合にモジュール名部分を省略できる。
//	pref_name_ は省略した場合、パーツ名からモジュール名部分を除外した名前となる
//
//	例）
//	_common_/user_rows/_.php
//	_common_/user_rows/row.php
//	の二つのパーツがあるとして、_.phpからrow.phpをappendしようとすると、
//	例えばrow.phpのinit()の中で、
//		viewpart_create("user_rows_row").append_to_part(self);
//
//	とする必要があるが、本メソッドを使うと
//
//		self.create_child("row");
//
//	と書けるため、モジュールのパス変更にも耐えるようになる。
viewpart.prototype.create_child = function(partname_, args_ = {}, pref_name_ = null)
{
	let elm = viewpart_get_template(this.module() + "_" + partname_);
	if( elm == null )
	{
		elm = viewpart_get_template(partname_);
		if( elm == null )
		{
			console.log('not found viewpart ' + partname_);
			return null;
		}
		return viewpart_create(partname_, args_, pref_name_);
	}

	//	リーフ名で見つかった場合
	//	pref_name_が省略されている場合には、リーフ名をpref値とする
	pref_name = pref_name_ === null ? partname_ : pref_name_;
	return viewpart_create(this.module() + "_" + partname_, args_, pref_name);
};

//	子パーツを作成して、自身に追加する
//	子の追加先は自身が管理しているref_name_で指定する。指定がない場合には自身のルートへ追加される
viewpart.prototype.create_child_and_append = function(partname_, args_ = {}, ref_name_ = null, pref_name_ = null)
{
	let child = this.create_child(partname_, args_, pref_name_);
	this.append(child, ref_name_);
	return child
};

//	子パーツを作成して、自身に追加する。追加位置は末尾ではなく先頭となる
//	子の追加先は自身が管理しているref_name_で指定する。指定がない場合には自身のルートへ追加される
//	before_viewpart_ で子パーツを指定すると、そのパーツの前に挿入し、指定しない場合は先頭に挿入する。
viewpart.prototype.create_child_and_prepend = function(partname_, args_ = {}, ref_name_ = null, pref_name_ = null, before_viewpart_ = null)
{
	let child = this.create_child(partname_, args_, pref_name_);
	this.prepend(child, ref_name_, before_viewpart_);
	return child;
};

//	子パーツを作成して、自身の指定refで指すエレメントと置き換える
viewpart.prototype.create_child_and_replace = function(partname_, args_ = {}, ref_name_ = null, pref_name_ = null)
{
	let child = this.create_child(partname_, args_, pref_name_);
	child.replace_with(this.ref(ref_name_), this);
	return child;
};

//	子パーツリストを作成する
//	viewpart_create_array()とほぼ同等の機能で、違いは create_child と同様
viewpart.prototype.create_children = function(partname_, args_array_ = [], pref_name_ = null)
{
	let elm = viewpart_get_template(this.module() + "_" + partname_);
	if( elm == null )
	{
		elm = viewpart_get_template(partname_);
		if( elm == null )
		{
			console.log('not found viewpart ' + partname_);
			return null;
		}
		return viewpart_create_array(partname_, args_array_, pref_name_);
	}

	//	リーフ名で見つかった場合
	//	pref_name_が省略されている場合には、リーフ名をpref値とする
	pref_name = pref_name_ === null ? partname_ : pref_name_;
	return viewpart_create_array(this.module() + "_" + partname_, args_array_, pref_name);
};

//	子パーツリストを作成して、自身に追加する
//	子の追加先は自身が管理しているref_で指定する。指定がない場合には自身のルートへ追加される
viewpart.prototype.create_children_and_append = function(partname_, args_array_, ref_name_ = null, pref_name_ = null)
{
	let children = this.create_children(partname_, args_array_, pref_name_);
	this.append_array(children, ref_name_);
	return children;
};

//	子パーツリストを作成して、自身に追加する。追加位置は末尾ではなく先頭となる
//	子の追加先は自身が管理しているref_で指定する。指定がない場合には自身のルートへ追加される
//	before_viewpart_ で子パーツを指定すると、そのパーツの前に挿入し、指定しない場合は先頭に挿入する。
viewpart.prototype.create_children_and_prepend = function(partname_, args_array_, ref_name_ = null, pref_name_ = null, before_viewpart_ = null)
{
	let reverse_args = args_array_.reverse();
	let childs = [];
	for( let index in reverse_args )
	{
		childs.push(this.create_child_and_prepend(partname_, reverse_args[index], ref_name_, pref_name_, before_viewpart_));
	}
	return childs;
};

//	本インスタンスにメッセージを投げる
viewpart.prototype.post = function(msg_, params_ = {})
{
	let recvs = this._recv_();
	if( msg_ in recvs )
	{
		let ret = recvs[msg_](this, params_);
		if( ret === true ) return this;
	}
	return this;
};

//	メッセージを子に投げる
viewpart.prototype.postdown = function(msg_, params_ = {})
{
	let self = this;
	let postdown_core = function(part_, msg_, params_ = {})
	{
		for( let pref in part_.m.child_parts )
		{
			let childs = part_.m.child_parts[pref];
			for( let index in childs )
			{
				let child = childs[index];
				let recvs = child._recv_();
				if( msg_ in recvs )
				{
					//	メッセージ処理が true を返却したら伝播停止
					let ret = recvs[msg_](self, params_);
					if( ret === true ) continue;
				}
				postdown_core(child, msg_, params_);
			}
		}
	};
	postdown_core(this, msg_, params_);
	return this;
};

//	メッセージを親に投げる
viewpart.prototype.postup = function(msg_, params_ = {})
{
	let parent_part = this.m.parent_part;
	while( parent_part !== null )
	{
		let recvs = parent_part._recv_();
		if( msg_ in recvs )
		{
			//	メッセージ処理が true を返却したら伝播停止
			let ret = recvs[msg_](this, params_);
			if( ret === true ) break;
		}
		parent_part = parent_part.m.parent_part;
	}
	return this;
};

//	メッセージを自分以上に投げる
viewpart.prototype.postmeup = function(msg_, params_ = {})
{
	let recvs = this._recv_();
	if( msg_ in recvs )
	{
		let ret = recvs[msg_](this, params_);
		if( ret === true ) return this;
	}
	return this.postup(msg_, params_);
};

//	メッセージを自分以下に投げる
viewpart.prototype.postmedown = function(msg_, params_ = {})
{
	let recvs = this._recv_();
	if( msg_ in recvs )
	{
		let ret = recvs[msg_](this, params_);
		if( ret === true ) return this;
	}
	return this.postdown(msg_, params_);
};

//	自身のプロパティを子パーツのプロパティにバインドする
//	target_prop_name_ に null 指定で同名バインドとなる
viewpart.prototype.bind_prop = function(self_prop_name_, child_part_, target_prop_name_ = null)
{
	let target_name = target_prop_name_ === null ? self_prop_name_ : target_prop_name_;

	//	現在値をセット
	if( target_name in child_part_.m.props )
	{
		child_part_.m.props[target_name] = this.m.props[self_prop_name_];
	}
	else return this;

	//	バインド設定
	for( let index = 0; index < this.m.child_binds.length; index++ )
	{
		if( this.m.child_binds[index].child.uid() == child_part_.uid() )
		{
			this.m.child_binds[index].props.push([self_prop_name_, target_name]);
			return this;
		}
	}
	this.m.child_binds.push({child:child_part_, props:[[self_prop_name_, target_name]]});
	return this;
};

//	エレメントのテキスト要素をプロパティにバインドする
viewpart.prototype.bind_text = function(elm_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props) )
	{
		//	現在の値を反映する
		let self = this;
		elm_.textContent = self.m.props[prop_name_];

		//	binderで変更を受け取り、propsへ反映する
		let elm = elm_;
		self.m.bind_funcs[prop_name_].push(function(newval_)
		{
			elm.textContent = newval_;
		});
	}
	return this;
};

//	エレメントのテキスト要素のバインドを解除する
viewpart.prototype.unbind_text = function(elm_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
		delete this.m.binder[prop_name_];
	return this;
};

//	エレメントの属性をバインドする
viewpart.prototype.bind_attr = function(elm_, attr_name_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props))
	{
		//	現在の値を反映する
		let self = this;
		elm_.setAttribute(attr_name_, self.m.props[prop_name_]);

		//	binderで変更を受け取り、propsへ反映する
		let attr_name = attr_name_;
		let elm = elm_;
		self.m.bind_funcs[prop_name_].push(function(newval_)
		{
			elm.setAttribute(attr_name, newval_);
		});
	}
	return this;
};

//	エレメント属性のバインドを解除する
viewpart.prototype.unbind_attr = function(elm_, attr_name_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
		delete this.m.binder[prop_name_];
	return this;
};

//	エレメントのスタイルをバインドする
viewpart.prototype.bind_style = function(elm_, style_name_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props))
	{
		//	キャメルケース変換
		let style_name = style_name_.replace(/-([a-z])/g, e => e[1].toUpperCase());

		//	現在の値を反映する
		let self = this;
		elm_.style[style_name] = "" + self.m.props[prop_name_];

		//	observerで検知する
		let prop_name = prop_name_;
		let elm = elm_;
		let observer = new MutationObserver(function()
		{
			if( self.m.props[prop_name] != elm.style[style_name] )
				self.prop(prop_name, elm.style[style_name]);
		});
		observer.observe(elm_, {attributes: true, attributeFilter:["style"]});

		//	破棄時に切断するように、インスタンスは保持しておく
		if( self.m.observers === null ) self.m.observers = [observer];
		else self.m.observers.push(observer);

		//	binderで変更を受け取り、propsへ反映する
		self.m.bind_funcs[prop_name].push(function(newval_)
		{
			elm.style[style_name] = newval_;
		});
	}
	return this;
};

//	エレメント属性のバインドを解除する
viewpart.prototype.unbind_style = function(elm_, style_name_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
		delete this.m.binder[prop_name_];
	return this;
};

//	エレメントのサイズ変更をバインドする
//	変化を検知するのみで、propからエレメントのサイズを変更することは出来ない
//	バインド先のprop値は DOMRectReadOnly のオブジェクトとなり、
//	x, y, width, height, top, right, bottom, left プロパティから構成される。
viewpart.prototype.bind_resize = function(elm_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props))
	{
		let self = this;
		let prop_name = prop_name_;
		let elm = elm_;
		let observer = new ResizeObserver(function(entries_)
		{
			self.prop(prop_name, entries_[0].contentRect);
		});
		observer.observe(elm_);

		//	破棄時に切断するように、インスタンスは保持しておく
		if( self.m.observers === null ) self.m.observers = [observer];
		else self.m.observers.push(observer);
	}
	return this;
};

//	エレメントサイズのバインドを解除する
viewpart.prototype.unbind_resize = function(elm_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
		delete this.m.binder[prop_name_];
	return this;
};

//	入力エレメントをプロパティにバインドする
viewpart.prototype.bind_input = function(elm_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props) )
	{
		//	現在の値を入力要素へ適用する
		let self = this;
		elm_.value = self.m.props[prop_name_];

		//	binderで変更を受け取り、propsへ反映する
		let elm = elm_;
		self.m.bind_funcs[prop_name_].push(function(newval_)
		{
			//	input起点の流れでなく、js起点の場合のみinputのvalueを更新する
			if( self.m.is_inputting === false )
				elm.value = newval_;
		});

		//	input要素の変化をbinderへ橋渡し
		elm_.setAttribute("bind_to", self.make_bind_to(prop_name_));
		let listen_type = self.get_listen_type_from_elm(elm_);
		elm_.addEventListener(listen_type, self.bind_input_handler);
	}
	return this;
};
viewpart.prototype.bind_input_handler = function(e_)
{
	let bind_to = viewpart_extract_bind_to(this);
	if( bind_to == null ) return;
	let self = bind_to[0];
	let prop_name = bind_to[1];

	self.m.is_inputting = true;
	self.m.binder[prop_name] = this.value;
	self.m.is_inputting = false;

	return this;
};

//	入力エレメントからプロパティへのバインドを解除する
viewpart.prototype.unbind_input = function(elm_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
	{
		delete this.m.binder[prop_name_];
		let listen_type = this.get_listen_type_from_elm(elm_);
		elm_.removeEventListener(listen_type, this.bind_input_handler);
	}
	return this;
};

//	チェックボックスとラジオボタンのチェック状態をプロパティにバインドする
viewpart.prototype.bind_checked = function(elm_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props) )
	{
		//	radio/checkboxでないならNG
		let type = elm_.hasAttribute('type');
		if( type == false )
		{
			console.log("not checkable element : " + prop_name_);
			return this;
		}
		type = elm_.getAttribute('type');
		if( type != "radio" && type != "checkbox" )
		{
			console.log("not checkable element : " + prop_name_);
			return this;
		}

		//	現在の値を入力要素へ適用する
		let self = this;
		elm_.checked = self.m.props[prop_name_];

		//	binderで変更を受け取り、propsへ反映する
		let prop_name = prop_name_;
		let elm = elm_;
		self.m.bind_funcs[prop_name].push(function(newval_)
		{
			//	自身のチェック更新
			elm.checked = newval_ == true || newval_ == "true" || newval_ == 1 || newval_ == "1";

			//	チェックがONになって且つradioの場合は、他のradioをOFFにするために、
			//	適当な属性を更新する。これによりobserverのイベントを発火させる
			if( elm.checked === true && elm.getAttribute('type') == "radio" )
			{
				let radio_name = elm.getAttribute('name');
				let neighbours = document.querySelectorAll('input[type=radio][name="' + radio_name + '"]');
				self.array_each(neighbours, function(n_)
				{
					if( n_ == elm ) return;
					n_.setAttribute('vp_radio_dummy_for_fire', false);
				});
			}
		});

		//	radioの場合はchangeではなくobserver経由で変化を検知する
		if( type == "radio" )
		{
			let observer = new MutationObserver((mutations_list) =>
			{
				if( self.m.mounted !== true ) return;

				//	チェックしていない場合はwatch発火、
				//	チェックしている場合は既にwatch済みなので、プロパ変更するだけ
				if( elm.checked === false ) self.prop(prop_name, elm.checked);
				else self.m.props[prop_name] = elm.checked;
			});
			observer.observe(elm, {attributes: true});

			//	破棄時に切断するように、インスタンスは保持しておく
			if( self.m.observers === null ) self.m.observers = [observer];
			else self.m.observers.push(observer);

			//	ユーザ操作によりチェックがonになったことを検知する。
			//	さらに同名の他のボタンを探して、ダミーのプロパティを更新する。→ observerで検知させる
			elm_.addEventListener("change", function()
			{
				let radio_name = elm.getAttribute('name');
				let neighbours = document.querySelectorAll('input[type=radio][name="' + radio_name + '"]');
				self.array_each(neighbours, function(n_)
				{
					if( n_ == this ) return;
					n_.setAttribute('vp_radio_dummy_for_fire', false);
				});
				this.setAttribute('vp_radio_dummy_for_fire', true);
			});
		}

		//	input要素の変化をbinderへ橋渡し
		else
		{
			elm_.setAttribute("bind_to", self.make_bind_to(prop_name));
			let listen_type = self.get_listen_type_from_elm(elm_);
			elm_.addEventListener(listen_type, self.bind_checked_handler);
		}
	}
	return this;
};
viewpart.prototype.bind_checked_handler = function(e_)
{
	let bind_to = viewpart_extract_bind_to(this);
	if( bind_to === null ) return;
	bind_to[0].m.binder[bind_to[1]] = this.checked;
	return this;
};

//	チェックボックスとラジオボタンのチェック状態バインドを解除する
viewpart.prototype.unbind_checked = function(elm_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
	{
		delete this.m.binder[prop_name_];
		let listen_type = this.get_listen_type_from_elm(elm_);
		elm_.removeEventListener(listen_type, this.bind_checked_handler);
	}
	return this;
};

//	キーのあるなしで変化する属性のバインド、requiredやdisabledなど。
viewpart.prototype.bind_boolean = function(elm_, attr_name_, prop_name_)
{
	if( elm_ !== null && (prop_name_ in this.m.props) )
	{
		//	現在の値を入力要素へ適用する
		let self = this;
		elm_[attr_name_] = self.m.props[prop_name_];

		//	binderで変更を受け取り、propsへ反映する
		let attr_name = attr_name_;
		let elm = elm_;
		self.m.bind_funcs[prop_name_].push(function(newval_)
		{
			elm[attr_name] = newval_ == true || newval_ == "true" || newval_ == 1 || newval_ == "1";
		});
	}
	return this;
};

//	キーのあるなしで変化する属性のバインドを解除する
viewpart.prototype.unbind_boolean = function(elm_, prop_name_)
{
	if( elm_ != null && (prop_name_ in this.m.binder) )
		delete this.m.binder[prop_name_];
	return this;
};

//	create済みのパーツを子として追加
//	子の追加先は自身が管理しているref_で指定する。指定がない場合には自身のルートへ追加される
viewpart.prototype.append = function(viewpart_, ref_name_ = null)
{
	if( viewpart_ == null )
	{
		console.log('viewpart_ is null, this name is ' + this.m.name);
		return this;
	}
	viewpart_.append_to_part(this, ref_name_);
	return this;
};

//	create済みのパーツを子として先頭に追加
//	子の追加先は自身が管理しているref_で指定する。指定がない場合には自身のルートへ追加される
viewpart.prototype.prepend = function(viewpart_, ref_name_ = null, before_viewpart_ = null)
{
	if( viewpart_ == null )
	{
		console.log('viewpart_ is null, this name is ' + this.m.name);
		return this;
	}
	viewpart_.prepend_to_part(this, ref_name_, before_viewpart_);
	return this;
};

//	create済みのパーツの配列を子として追加
//	子の追加先は自身が管理しているref_name_で指定する。指定がない場合には自身のルートへ追加される
viewpart.prototype.append_array = function(viewpart_array_, ref_name_ = null)
{
	for( let index in viewpart_array_ )
		this.append(viewpart_array_[index], ref_name_);
	return this;
};

//	指定エレメントの子として追加
//	viewpart_ を指定すると、DOM階層が異なっていても指定したパーツの子として登録される
viewpart.prototype.append_to_element = function(elm_, viewpart_ = null)
{
	//	マウント済みなら何もしない
	if( this.m.mounted == true )
	{
		console.log('already mounted, uid = ' + this.m.uid);
		return this;
	}

	let target = elm_;
	this.add_viewpart_attr(target);

	//	論理初期化
	let self = this;
	self._initpart_();

	//	DOMへの追加を以て初期化を走らせる
	let observer = new MutationObserver((mutations_list) =>
	{
		observer.disconnect();

		//	on_prereadyを実行
		let rqlen = self.m.preready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.preready_queue[rqindex](self);
		self.m.preready_queue = [];

		//	物理初期化
		self._ready_();
		self.m.ready_called = true;

		//	必要なら単体テスト
		if( this._test_ && this._test_opt_() == 'auto' ) this.run_tests();

		//	on_readyを実行
		rqlen = self.m.ready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.ready_queue[rqindex](self);
		self.m.ready_queue = [];
	});
	observer.observe(target, {attributes: false, childList: true, subtree: false, characterData: false});

	//	追加
	target.appendChild(this.m.root);

	//	指定があれば、ビューパーツの子として登録
	if( viewpart_ !== null )
	{
		//	親ビューパーツを保持
		this.m.parent_part = viewpart_;

		//	親ビューパーツの子リストに追加
		if( this.m.parent_part.m.child_parts[this.m.pref] )
			this.m.parent_part.m.child_parts[this.m.pref].push(this);
		else
			this.m.parent_part.m.child_parts[this.m.pref] = [this];
	}

	//	マウント済みとする
	this.m.mounted = true;
	return this;
};

//	指定パーツの指定refへ子として追加
//	ref省略時は、指定パーツのルートへ子として追加する
viewpart.prototype.append_to_part = function(viewpart_, ref_name_ = null)
{
	//	マウント済みなら何もしない
	if( this.m.mounted == true )
	{
		console.log('already mounted, uid = ' + this.m.uid);
		return this;
	}

	//	追加先のパーツを取得
	let target = ref_name_ === null ? viewpart_.m.root : viewpart_.ref(ref_name_);
	if( target === null )
	{
		console.log('not found element, ref = ' + ref_name_);
		return this;
	}
	this.add_viewpart_attr(target);

	//	論理初期化
	let self = this;
	self._initpart_();

	//	DOMへの追加を以て初期化を走らせる
	let observer = new MutationObserver((mutations_list) =>
	{
		observer.disconnect();

		//	on_prereadyを実行
		let rqlen = self.m.preready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.preready_queue[rqindex](self);
		self.m.preready_queue = [];

		//	物理初期化
		self._ready_();
		self.m.ready_called = true;

		//	必要なら単体テスト
		if( this._test_ && this._test_opt_() == 'auto' ) this.run_tests();

		//	on_readyを実行
		rqlen = self.m.ready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.ready_queue[rqindex](self);
		self.m.ready_queue = [];
	});
	observer.observe(target, {attributes: false, childList: true, subtree: false, characterData: false});

	//	追加
	target.appendChild(this.m.root);

	//	親ビューパーツを保持
	this.m.parent_part = viewpart_;

	//	親ビューパーツの子リストに追加
	if( this.m.parent_part.m.child_parts[this.m.pref] )
		this.m.parent_part.m.child_parts[this.m.pref].push(this);
	else
		this.m.parent_part.m.child_parts[this.m.pref] = [this];

	//	マウント済みとする
	this.m.mounted = true;
	return this;
};

//	指定したパーツのrefに親を変更する
viewpart.prototype.change_parent_part = function(viewpart_, ref_name_ = null)
{
	//	変更先のエレメントを取得
	let target = ref_name_ === null ? viewpart_.m.root : viewpart_.ref(ref_name_);
	if( target === null )
	{
		console.log('not found element, ref = ' + ref_name_);
		return this;
	}
	this.add_viewpart_attr(target);

	//	変更前の親ビューパーツから削除
	//
	//	※ 削除コールバックはなし
	if( this.m.parent_part !== null )
	{
		for( let pref in this.m.parent_part.m.child_parts )
		{
			let found = false;
			let childs = this.m.parent_part.m.child_parts[pref];
			for( let index in childs )
			{
				if( childs[index].m.uid == this.m.uid )
				{
					this.m.parent_part.m.child_parts[pref].splice(index, 1);
					found = true;
					break;
				}
			}
			if( found == true ) break;
		}
	}

	//	物理的な親変更
	target.appendChild(this.m.root);

	//	親ビューパーツを保持
	this.m.parent_part = viewpart_;

	//	親ビューパーツの子リストに追加
	if( this.m.parent_part.m.child_parts[this.m.pref] )
		this.m.parent_part.m.child_parts[this.m.pref].push(this);
	else
		this.m.parent_part.m.child_parts[this.m.pref] = [this];

	return this;
};

//	指定パーツの指定refへ子として追加
//	ref省略時は、指定パーツのルートへ子として追加する
viewpart.prototype.prepend_to_part = function(viewpart_, ref_name_ = null, before_viewpart_ = null)
{
	//	マウント済みなら何もしない
	if( this.m.mounted == true )
	{
		console.log('already mounted, uid = ' + this.m.uid);
		return this;
	}

	//	追加先のエレメントを取得
	let target = ref_name_ === null ? viewpart_.m.root : viewpart_.ref(ref_name_);
	if( target === null )
	{
		console.log('not found element, ref = ' + ref_name_);
		return this;
	}
	this.add_viewpart_attr(target);

	//	論理初期化
	let self = this;
	self._initpart_();

	//	DOMへの追加を以て初期化を走らせる
	let observer = new MutationObserver((mutations_list) =>
	{
		observer.disconnect();

		//	on_prereadyを実行
		let rqlen = self.m.preready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.preready_queue[rqindex](self);
		self.m.preready_queue = [];

		//	物理初期化
		self._ready_();
		self.m.ready_called = true;

		//	必要なら単体テスト
		if( this._test_ && this._test_opt_() == 'auto' ) this.run_tests();

		//	on_readyを実行
		rqlen = self.m.ready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.ready_queue[rqindex](self);
		self.m.ready_queue = [];
	});
	observer.observe(target, {attributes: false, childList: true, subtree: false, characterData: false});

	//	before指定があれば、その要素の前に新しいパーツを挿入する
	if( before_viewpart_ !== null )
	{
		target.insertBefore(this.m.root, before_viewpart_.m.root);
	}
	//	それ以外は先頭に追加
	else
	{
		target.prepend(this.m.root);
	}

	//	親ビューパーツを保持
	this.m.parent_part = viewpart_;

	//	親ビューパーツの子リストに追加
	if( this.m.parent_part.m.child_parts[this.m.pref] )
		this.m.parent_part.m.child_parts[this.m.pref].push(this);
	else
		this.m.parent_part.m.child_parts[this.m.pref] = [this];

	//	マウント済みとする
	this.m.mounted = true;
	return this;
};

//	指定エレメントを自身で置き換える
viewpart.prototype.replace_with = function(elm_, parent_part_ = null)
{
	//	マウント済みなら何もしない
	if( this.m.mounted == true )
	{
		console.log('already mounted, uid = ' + this.m.uid);
		return this;
	}

	let target = elm_;
	this.add_viewpart_attr(target.parentNode);

	//	論理初期化
	let self = this;
	self._initpart_();

	//	DOMへの追加を以て初期化を走らせる
	let observer = new MutationObserver((mutations_list) =>
	{
		observer.disconnect();

		//	on_prereadyを実行
		let rqlen = self.m.preready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.preready_queue[rqindex](self);
		self.m.preready_queue = [];

		//	物理初期化
		self._ready_();
		self.m.ready_called = true;

		//	必要なら単体テスト
		if( this._test_ && this._test_opt_() == 'auto' ) this.run_tests();

		//	on_readyを実行
		rqlen = self.m.ready_queue.length;
		for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.ready_queue[rqindex](self);
		self.m.ready_queue = [];
	});
	observer.observe(target.parentNode, {attributes: false, childList: true, subtree: false, characterData: false});

	//	リプレイス
	target.replaceWith(this.m.root);

	//	親を保持しておく
	this.m.parent_part = parent_part_;

	//	親ビューパーツの子リストに追加
	if( this.m.parent_part !== null )
	{
		if( this.m.parent_part.m.child_parts[this.m.pref] )
			this.m.parent_part.m.child_parts[this.m.pref].push(this);
		else
			this.m.parent_part.m.child_parts[this.m.pref] = [this];
	}

	//	マウント済みとする
	this.m.mounted = true;
	return this;
};

//	自身をビューパーツのヒエラルキーから削除する
viewpart.prototype.remove = function( remove_from_parent_ = true )
{
	//	子を削除
	for( let pref in this.m.child_parts )
	{
		let childs = this.m.child_parts[pref];
		let len = childs.length;
		for( let index = len - 1; index >= 0; index-- )
		{
			childs[index].remove(false, false);
		}
	}

	//	必要なら親から削除
	if( remove_from_parent_ == true && this.m.parent_part != null )
	{
		for( let pref in this.m.parent_part.m.child_parts )
		{
			let found = false;
			let childs = this.m.parent_part.m.child_parts[pref];
			for( let index in childs )
			{
				if( childs[index].m.uid == this.m.uid )
				{
					this.m.parent_part.m.child_parts[pref].splice(index, 1);
					found = true;
					break;
				}
			}
			if( found == true ) break;
		}
	}

	//	削除コールバック実行
	let self = this;
	let rqlen = self.m.remove_queue.length;
	for( let rqindex = 0; rqindex < rqlen; rqindex++ ) self.m.remove_queue[rqindex](self);
	self.m.remove_queue = [];

	//	オブザーバがあれば切断する
	if( this.m.observers !== null )
	{
		for( let oi = 0; oi < this.m.observers.length; oi++ )
			this.m.observers[oi].disconnect();
		this.m.observers = null;
	}

	//	マウント済みならDOMから削除
//	if( this.m.mounted == true )
	this.m.root.remove();

	//	パーツリストから削除
	delete viewpart_global[this.m.uid];
};

//	pref指定で子ビューパーツをヒエラルキーから削除する
viewpart.prototype.remove_pref = function( pref_name_ )
{
	let childs = this.prefs(pref_name_);
	for( let index = childs.length - 1; index >= 0; index-- )
		childs[index].remove();

	return this;
};

//	watchの停止/開始
viewpart.prototype.watch_start = function()
{
	this.m.watching = true;
};
viewpart.prototype.watch_stop = function()
{
	this.m.watching = false;
};


//------------------------------------------------------------------------------
//	テスト用
//------------------------------------------------------------------------------
viewpart.prototype.console_colors = function()
{
	return {
		black : '\u001b[30m',
		red : '\u001b[31m',
		green : '\u001b[32m',
		yellow : '\u001b[33m',
		blue : '\u001b[34m',
		magenta : '\u001b[35m',
		cyan : '\u001b[36m',
		white : '\u001b[37m',
		reset : '\u001b[0m'
	};
};

//	<test>セクションの中の全てのメソッドを実行する
//	全て成功した場合は true 返却、一つでも失敗があれば false 返却
viewpart.prototype.run_tests = function()
{
	if( this._test_ )
	{
		let tests = this._test_();
		let len = Object.keys(tests).length;
		if( len <= 0 ) return;

		let colors = this.console_colors();
		console.log('\npart : ' + this.m.name + ' (' + this.m.uid + ') : ' + len + ' tests');
		let passed = 0;
		let failed = 0;
		for( test_name in tests )
		{
			if( this.run_test(test_name) === true ) passed++;
			else failed++;
		}
		console.log('passed : ' + passed + ', failed : ' + failed + "\n\n");

		return failed > 0 ? false : true;
	}
	return false;
};

//	<test>セクションの中のメソッド名を指定して実行する
//	成功した場合は true 返却、失敗時は false 返却
viewpart.prototype.run_test = function(test_method_name_)
{
	if( this._test_ )
	{
		let colors = this.console_colors();
		this.m.test_result = true;
		this[test_method_name_]();

		if( this.m.test_result === true )
		{
			console.log(colors.green + " - " + test_method_name_
				+ " : passed" + colors.reset);
			return true;
		}

		console.log(colors.red + " - " + test_method_name_
			+ " : failed" + (this.m.test_result_msg != '' ? "\n   * " + this.m.test_result_msg : '') + colors.reset);
		return false;
	}
	return false;
};

//------------------------------------------------------------------------------
//	<test>セクション内のメソッドで使うことを想定する、検証メソッド
//------------------------------------------------------------------------------

//	true
viewpart.prototype.assert_true = function(cond_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( cond_ !== true )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('cond is not true, ' + cond_);
		this.m.test_result = false;
	}
};

//	false
viewpart.prototype.assert_false = function(cond_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( cond_ !== false )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('cond is not false, ' + cond_);
		this.m.test_result = false;
	}
};

//	"==="
viewpart.prototype.assert_eq = function(left_, right_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( left_ !== right_ )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('left value is ' + left_ + ', but right value is ' + right_);
		this.m.test_result = false;
	}
};

//	"!=="
viewpart.prototype.assert_ne = function(left_, right_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( left_ === right_ )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('left value is ' + left_ + ', but right value is ' + right_);
		this.m.test_result = false;
	}
};

//	"<"
viewpart.prototype.assert_lt = function(left_, right_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( left_ >= right_ )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('left value is ' + left_ + ', but right value is ' + right_);
		this.m.test_result = false;
	}
};

//	"<="
viewpart.prototype.assert_le = function(left_, right_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( left_ > right_ )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('left value is ' + left_ + ', but right value is ' + right_);
		this.m.test_result = false;
	}
};

//	">"
viewpart.prototype.assert_gt = function(left_, right_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( left_ <= right_ )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('left value is ' + left_ + ', but right value is ' + right_);
		this.m.test_result = false;
	}
};

//	">="
viewpart.prototype.assert_ge = function(left_, right_, msg_ = null)
{
	if( this.m.test_result !== true ) return;
	if( left_ < right_ )
	{
		this.m.test_result_msg = msg_ ? msg_ : ('left value is ' + left_ + ', but right value is ' + right_);
		this.m.test_result = false;
	}
};

//------------------------------------------------------------------------------
//	ここから private 想定
//------------------------------------------------------------------------------

//	ランダム文字列作成
viewpart.prototype.make_random = function()
{
	return new Date().getTime().toString(16) + Math.floor(1000 * Math.random()).toString(16);
};

//	指定エレメントにviewpart属性追加
viewpart.prototype.add_viewpart_attr = function(target_)
{
	let parent = target_;
	if( parent.hasAttribute("viewpart") == false )
	{
		parent.setAttribute("viewpart", ":" + this.m.name);
	}
	else
	{
		let current_parts = parent.getAttribute("viewpart");
		if( current_parts.indexOf(":" + this.m.name) < 0 )
		{
			parent.setAttribute("viewpart", current_parts + ":" + this.m.name);
		}
	}
};

//	bind_to プロパティ値を計算する
viewpart.prototype.make_bind_to = function(prop_name_)
{
	return this.m.uid + ":" + prop_name_;
};

//	bind_to プロパティが設定されているエレメントから、インスタンスとprop_nameを取得する
//	不正な場合はnull返却
function viewpart_extract_bind_to(elm_)
{
	if( elm_ === null || elm_ === undefined ) return null;
	if( elm_.hasAttribute("bind_to") == false ) return null;
	let bind_to = elm_.getAttribute("bind_to");
	let words = bind_to.split(':');
	if( words.length < 2 ) return null;
	let uid = words[0];
	let prop_name = words[1];

	let instance = viewpart_find_by_uid(uid);
	if( instance == null ) return null;

	return [instance, prop_name];
};

//	watchの発火
viewpart.prototype.fire_watch = function(name_, old_, new_)
{
	if( this.m.watching === false ) return;

	let syswatch = this._syswatch_();
	if( name_ in syswatch ) syswatch[name_](old_, new_);
	if( this.m.child_binds.length > 0 )
	{
		for( let index = 0; index < this.m.child_binds.length; index++ )
		{
			let child_bind = this.m.child_binds[index];
			for( let pdex = 0; pdex < child_bind.props.length; pdex++ )
			{
				if( child_bind.props[pdex][0] == name_ )
				{
					child_bind.child.prop(child_bind.props[pdex][1], new_);
					break;
				}
			}
		}
	}
	let watch = this._watch_();
	if( name_ in watch ) watch[name_](old_, new_);
};

//	入力エレメントからイベント種別取得
viewpart.prototype.get_listen_type_from_elm = function(elm_)
{
	switch(elm_.tagName)
	{
		case 'INPUT':
		{
			if( elm_.getAttribute('type') == "radio" || elm_.getAttribute('type') == "checkbox" )
			{
				return 'change';
			}
			return 'input';
		}
		case 'TEXTAREA':
		{
			return 'input';
		}
		case 'SELECT':
		{
			return 'change';
		}
	}
	return '';
};

//	配列/またはオブジェクトに対してイテレート実行する
viewpart.prototype.array_each = function(array_, func_)
{
	//	配列のループ
	if( Array.isArray(array_) )
	{
		for( let index = 0; index < array_.length; index++ )
			func_(array_[index], index);
	}
	//	オブジェクトのループ
	else if( array_ )
	{
		let keys = Object.keys(array_);
		for( let index = 0; index < keys.length; index++ )
			func_(array_[keys[index]], keys[index]);
	}
};
