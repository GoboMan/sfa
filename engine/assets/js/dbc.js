/*

	データキャッシュ

	データリストのキャッシュを保持する。
	SPA向けにメモリで保持しているだけなので、接続ごとにクリアされる。

	データリストを表すキー文字列と、データリストに含まれる各アイテムを表すキー文字列の二つで
	一つのデータを指す。

	例えばユーザリストのようなものを"user.list"という名前で管理しようとした場合、
	set_listでユーザリストをセットする。

		//	ユーザリストのリフレッシュ、user_rowsはキーをuser_idとする連想配列
		let user_rows = self.get_user_rows_with_ajax();
		dbc.set_list("user.list", user_rows)

	特定のアイテムだけを更新しようとした場合は
	アイテムのIDをキーとしてsetで更新する。

		//	ユーザの更新
		let user_row = self.get_login_row();
		dbc.set("user.list", self.get_logined_id(), user_row);

	取得はリスト一括取得と、キーを指定しての個別アイテム取得がある

		//	キャッシュ内の全ユーザ取得
		let rows = dbc.get_list("user.list");

		//	ログイン中ユーザ取得
		let row = dbc.get("user.list", self::get_logined_id());

	merge_list()ではアイテムが存在すれば更新し、存在しなければリストへ追加する
	リスト自体がなければリストが作成される

		let new_user_rows = self.get_user_rows_with_ajax();
		dbc.merge_list("user.list", new_user_rows);

	指定したデータを、指定したviewpartのpropにバインドする
	dbc側の変動がpropに連動するようになり、propの変動はdbcに連動するわけではない。

	例）
		dbc.set("lang", "user.name", "ユーザ名")
		でセットしている値を、viewpartのprop=dataにbindする場合、

			dbc.bind("lang", "user.name", self, "data");

		とすれば、dbcの値が変動するたびにviewpartのdataプロパティも更新される。
		一度bindしたものは画面上からviewpartが消去されない限りdbcの内部データとして残り続けるが、
		画面から消える前にバインド情報を削除したい場合にはunbindで可能

			//	バインド解除、viewpartとprop指定
			dbc.unbind(self, "data");

	bindしているデータがdbcから削除されることもあるが
	その場合、bind元のpropにはnullで連動される

	例）
		<props>
		{
			user_row : {name:'xxx', gender:'male'}
		}
		</props>
		<template>
		 <div>
		  <div ref="name">{{ user_row ? user_row.name : '-deleted-' }}</div>
		  <div ref="gender">{{ user_row ? user_row.gender : '-deleted-' }}</div>
		  <button ref="btn_delete">delete</button>
		 </div>
		</template>
		<init>
		{
			//	dbcのenv::user_rowを、本パーツのuser_rowにバインド
			dbc.bind("env", "user_row", self, "user_row");
		}
		</init>
		<ready>
		{
			//	ボタン押下でdbcから削除すると、画面上は '-deleted-' に変化する
			$(self.ref('btn_delete')).on('click',
				function(){ dbc.remove('env', 'user_row'); });
		}
		</ready>

	データの保持について。
	dbcのデータはリストごとにブラウザのローカルストレージに保存するかどうかを指定できる。
	デフォルトは保存しないようになっていて、set_autosave(リスト名, フラグ)で切替可能。

	例）
		//	accountというリストだけ保存しないようにする
		dbc.set_autosave('account', false);

		//	保存してあるものはスクリプトの頭などで指示して読みこむ
		//	この時に set_autosave() で false 指定したリストは読み込まれない
		dbc.read_storage_all();

		//	書き込みは自動で行われる。
		//	この時に set_autosave() で false 指定したリストは保存されない
		//	↑の行でaccountリストは保存しないに設定してあるので、↓のコードでは保存されない
		dbc.set("account", "user_name", "fujisaki");

*/
let dbc_class = function()
{
	this.m_autosave_lists = [];
	this.m_list = {};
	this.m_names = [];
	this.m_binds = {};
};

//--------------------------------------------------------------------------
//	ローカルストレージに自動で書き込みを行うか
//--------------------------------------------------------------------------
dbc_class.prototype.set_autosave = function(list_name_, enable_)
{
	let index = this.m_autosave_lists.indexOf(list_name_);
	if( enable_ === true )
	{
		if( index < 0 ) this.m_autosave_lists.push(list_name_);
	}
	else
	{
		if( index >= 0 ) this.m_autosave_lists.splice(index, 1);
	}
	return this;
};

//--------------------------------------------------------------------------
//	ローカルストレージからの読み込み
//--------------------------------------------------------------------------
dbc_class.prototype.read_storage_all = function()
{
	let storage = localStorage;
	for( let i = 0; i < storage.length; i++ )
	{
		let key = storage.key(i);
		if( key.startsWith("dbc.") === false ) continue;

		let physic_key = key.slice(4);
		if( this.m_autosave_lists.indexOf(physic_key) < 0 ) continue;

		let val = storage.getItem(key);
		this.set_list(physic_key, JSON.parse(val));
	}
	return this;
};

//--------------------------------------------------------------------------
//	セッションストレージへの書き込み
//--------------------------------------------------------------------------
dbc_class.prototype.write_storage_all = function()
{
	let storage = localStorage;
	for( let i = 0; i < this.m_names.length; i++ )
	{
		let key = this_names[i];
		let val = JSON.stringify(this.m_list[key]);
		if( this.m_autosave_lists.indexOf(key) < 0 ) continue;

		storage["dbc." + key] = val;
	}
	return this;
};
dbc_class.prototype.write_storage_list = function(list_name_)
{
	if( this.m_autosave_lists.indexOf(list_name_) < 0 ) return this;

	localStorage["dbc." + list_name_] =
		JSON.stringify(this.m_list[list_name_]);

	return this;
};

dbc_class.prototype.remove_storage_list = function(list_name_)
{
	if( this.m_autosave_lists.indexOf(list_name_) < 0 ) return this;
	localStorage.removeItem("dbc." + list_name_);
	return this;
};

//--------------------------------------------------------------------------
//	リストが存在するかチェック
//--------------------------------------------------------------------------
dbc_class.prototype.exists_list = function(list_name_)
{
	return this.m_names.indexOf(list_name_) >= 0;
};

//--------------------------------------------------------------------------
//	アイテムが存在するかチェック
//--------------------------------------------------------------------------
dbc_class.prototype.exists = function(list_name_, key_)
{
	return
		this.m_names.indexOf(list_name_) >= 0 &&
		Object.keys(this.m_list[list_name_]).indexOf("" + key_) >= 0
		;
};

//--------------------------------------------------------------------------
//	リスト取得、存在しない場合はnull返却
//--------------------------------------------------------------------------
dbc_class.prototype.get_list = function(list_name_)
{
	return this.m_names.indexOf(list_name_) < 0 ?
		null : this.m_list[list_name_];
};

//--------------------------------------------------------------------------
//	アイテム取得、存在しない場合はdefault_で指定した値を返却
//--------------------------------------------------------------------------
dbc_class.prototype.get = function(list_name_, key_, default_ = null)
{
	let key = "" + key_;
	let rows = this.get_list(list_name_);
	return (rows === null || Object.keys(rows).indexOf(key) < 0) ?
		default_ : rows[key];
};

//--------------------------------------------------------------------------
//	リストをクリアしてセットしなおす
//--------------------------------------------------------------------------
dbc_class.prototype.set_list = function(list_name_, rows_)
{
	this.m_list[list_name_] = rows_;
	if( this.m_names.indexOf(list_name_) < 0 )
		this.m_names.push(list_name_);

	//	必要ならストレージへ保存
	if( this.m_enable_autosave === true )
	{
		this.write_storage_list(list_name_);
	}

	//	バインドしているパーツへ反映
	if( Object.keys(this.m_binds).indexOf(list_name_) >= 0 )
	{
		let bind_list = this.m_binds[list_name_];
		let bind_keys = Object.keys(bind_list);
		for( let key in rows_ )
		{
			if( bind_keys.indexOf(key) < 0 ) continue;
			let len = bind_list[key].length;
			for( let index = len - 1; index >= 0; index-- )
			{
				bind_list[key][index].part.prop(
					bind_list[key][index].prop, rows_[key]);
			}
		}
	}

	return this;
};

//--------------------------------------------------------------------------
//	アイテムセット、指定したリストやアイテムが存在しない場合は作成される
//--------------------------------------------------------------------------
dbc_class.prototype.set = function(list_name_, key_, row_)
{
	let key = "" + key_;
	let rows = this.get_list(list_name_);
	if( rows === null )
	{
		rows = {};
		rows[key] = row_;
		this.set_list(list_name_, rows);
	}
	else
	{
		rows[key] = row_;
	}

	//	必要ならストレージへ保存
	if( this.m_enable_autosave === true )
	{
		this.write_storage_list(list_name_);
	}

	//	バインドしているパーツへ反映
	this.update(list_name_, key_);

	return this;
};

//--------------------------------------------------------------------------
//	リストを削除
//--------------------------------------------------------------------------
dbc_class.prototype.remove_list = function(list_name_)
{
	if( this.exists_list(list_name_) === false ) return;
	delete this.m_list[list_name_];

	let index = this.m_names.indexOf(list_name_);
	if( index >= 0 ) this.m_names.splice(index, 1);

	//	必要ならストレージへ保存
	if( this.m_enable_autosave === true )
	{
		this.remove_storage_list(list_name_);
	}

	//	バインドしているパーツへ反映
	if( Object.keys(this.m_binds).indexOf(list_name_) >= 0 )
	{
		let bind_keys = Object.keys(this.m_binds[list_name_]);
		for( key in bind_keys )
		{
			let len = this.m_binds[list_name_][key].length;
			for( let index = 0; index < len ; index++ )
			{
				this.m_binds[list_name_][key][index].part.prop(
					this.m_binds[list_name_][key][index].prop, null);
			}
		}
	}
};

//--------------------------------------------------------------------------
//	リストからアイテムを削除
//--------------------------------------------------------------------------
dbc_class.prototype.remove = function(list_name_, key_)
{
	let key = "" + key_;
	let rows = this.get_list(list_name_);
	if( rows === null ) return;
	if( Object.keys(rows).indexOf(key) < 0 ) return;
	delete rows[key];

	//	必要ならストレージへ保存
	if( this.m_enable_autosave === true )
	{
		this.write_storage_list(list_name_);
	}

	//	バインドしているパーツへ反映
	if( Object.keys(this.m_binds).indexOf(list_name_) >= 0 )
	{
		let bind_keys = Object.keys(this.m_binds[list_name_]);
		if( bind_keys.indexOf(key) >= 0 )
		{
			let len = this.m_binds[list_name_][key].length;
			for( let index = len - 1; index >= 0; index-- )
			{
				this.m_binds[list_name_][key][index].part.prop(
					this.m_binds[list_name_][key][index].prop, null);
			}
		}
	}
};

//--------------------------------------------------------------------------
//	リストをマージする。
//
//	渡されたアイテムの連想配列を1アイテムずつチェックし、
//	そのアイテムが既にリストに存在していれば更新、存在していなければ新規追加される。
//	リスト自体がキャッシュに存在しなければリストが作成される
//--------------------------------------------------------------------------
dbc_class.prototype.merge_list = function(list_name_, rows_)
{
	let rows = this.get_list(list_name_);
	if( rows === null ) return this.set_list(list_name_, rows_);
	let keys = Object.keys(rows_);
	for( let key_index in keys )
	{
		let key = keys[key_index];
		rows[key] = rows_[key];
	}
	return this.set_list(list_name_, rows);
};

//--------------------------------------------------------------------------
//	バインド追加
//--------------------------------------------------------------------------
dbc_class.prototype.bind = function(list_name_, key_, viewpart_, prop_name_)
{
	//	アイテムがなければnullで作成しておく
	let key = "" + key_;
	let rows = this.get_list(list_name_);
	if( rows === null )
	{
		rows = {};
		rows[key] = null;
		this.set_list(list_name_, rows);
	}
	else if( Object.keys(rows).indexOf(key) < 0 )
	{
		this.set(list_name_, key, null);
	}
	else
	{
		//	既に設定済みなら初期値として割り当てる
		viewpart_.prop(prop_name_, rows[key]);
	}

	//	バインド
	if( Object.keys(this.m_binds).indexOf(list_name_) < 0 )
	{
		this.m_binds[list_name_] = {};
		this.m_binds[list_name_][key] = [];
	}
	else if( Object.keys(this.m_binds[list_name_]).indexOf(key) < 0 )
	{
		this.m_binds[list_name_][key] = [];
	}
	this.m_binds[list_name_][key].push({part:viewpart_, prop:prop_name_});

	//	パーツ破棄時にバインド解除を行う
	//	※ 現状 1propあたり1コールバックなので改善の余地はある
	let self = this;
	let prop = prop_name_;
	viewpart_.on_remove(function(part_)
	{
		self.unbind(part_, prop);
	});
};

//--------------------------------------------------------------------------
//	バインド解除
//--------------------------------------------------------------------------
dbc_class.prototype.unbind = function(viewpart_, prop_name_)
{
	for( let list_name in this.m_binds )
	{
		let list = this.m_binds[list_name];
		for( let key in list )
		{
			let items = list[key];
			while(true)
			{
				let deleted = false;
				let len = items.length;
				for( let index = 0; index < len; index++ )
				{
					if( items[index].part.uid() == viewpart_.uid() && items[index].prop == prop_name_ )
					{
						items.splice(index, 1);
						deleted = true;
						break;
					}
				}
				if( deleted === false ) break;
			}
		}
	}
};

//--------------------------------------------------------------------------
//	指定リストの一括バインド
//
//	<props>
//	{
//		msg_logo : 'part.footer.link.title',
//		msg_copyright : 'part.footer.copyright'
//	}
//	</props>
//	<template>
//	 <div>
//	  <div title=:msg_logo>ロゴ</div>
//	  <div>{{ msg_copyright }}</div>
//	 </div>
//	</template>
//
//	のようなプロパティがあったとして、言語をdbcからマップしたいといった場合、
//	本来なら下記のようにする。
//
//		dbc.bind("lang", "part.footer.link.title", self, 'msg_logo');
//		dbc.bind("lang", "part.footer.copyright", self, 'msg_copyright');
//
//	そうすると文言が多くなった際に面倒になるため、bind_batch()で一括指定できる。
//	適用させるために次のルールがある。
//	・propsの各プロパティの名前を、リスト名 + "_" で始まるようにする（"msg_"や"lang_"など）
//	・propsの初期値は、アイテムのキー名にする。
//
//	このメソッドを実行した後、バインドされると同時にpropsの値は、現在のアイテム値がセットされる。
//
//	上記の例で本メソッドによる一括適用を行うためには次のように指定する
//
//		dbc.bind_batch(self, "msg");
//
//--------------------------------------------------------------------------
dbc_class.prototype.bind_batch = function(viewpart_, list_name_)
{
	let vp = viewpart_;
	let prefix = list_name_ + "_";
	for( let prop_name in vp.m.props )
	{
		if( prop_name.startsWith(prefix) === false ) continue;
		let key = vp.m.props[prop_name];
		vp.m.props[prop_name] = this.get(list_name_, key);
		this.bind(list_name_, key, vp, prop_name);
		vp.update(prop_name);
	}
};

//--------------------------------------------------------------------------
//	bind先への更新通知を強制実行する
//--------------------------------------------------------------------------
dbc_class.prototype.update = function(list_name_, key_)
{
	let key = "" + key_;
	let val = this.get(list_name_, key_);
	if( Object.keys(this.m_binds).indexOf(list_name_) >= 0 )
	{
		let bind_keys = Object.keys(this.m_binds[list_name_]);
		if( bind_keys.indexOf(key) >= 0 )
		{
			let len = this.m_binds[list_name_][key].length;
			for( let index = len - 1; index >= 0; index-- )
			{
				this.m_binds[list_name_][key][index].part.prop(
					this.m_binds[list_name_][key][index].prop, val);
			}
		}
	}
};


//--------------------------------------------------------------------------
//	シングルトンインスタンス
//--------------------------------------------------------------------------
var dbc = new dbc_class();
