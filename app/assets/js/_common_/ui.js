/*

	共通UI制御


	最初から画面に配置されているUIの初期化は自動で行われるが、
	ajaxなどで追加したコントロールの初期化は自分で行う必要がある。

	例）ajaxの返却でタブ（ui_tab）が動的に作成された場合のパターン

		//	ajax返却の内容でタブを生成
		var tab1_name = json_.d.tab1;
		var tab2_name = json_.d.tab2;
		var tab = $('<div class="ui_tab"></div>')
			.append( $('<div class="selected" for="tab1" selected>' + tab1_name + '</div>') )
			.append( $('<div for="tab2">' + tab2_name + '</div>') )
			.appendTo( $('#container') )
			;

		//	タブボディを生成
		$('<div id="tab1 selected">タブ1の内容</div>').appendTo( $('#container') );
		$('<div id="tab2">タブ2の内容</div>').appendTo( $('#container') );

		//	★ エレメント指定でタブコントローラの作成
		ui.tab.create_from_elm(tab);


	■ ui_button への連続クリック防止について

		<button class="ui_button done" cooldown="3">ボタン</button>

		のようにui_buttonクラスを持つエレメントに、"cooldown"を指定すると
		連続クリックを防止することが可能となる。
		連続クリック防止中は、そのエレメントに"disabled"属性が付くこととなる。

		cooldownには次の数値を指定する
		- 負数指定時 : 一度押下するとずっとdisabledのままとなる。
		- 0 : 制御なしで、cooldown 未指定時と同じ。
		- 正数指定時 : 指定秒数分待機後にdisabledが解除される。

*/

//	UI管理
var ui =
{
	//	タブ
	tab :
	{
		create : selector_ =>
		{
			let inst = new ui_tab();
			inst.m.protect.init( $(selector_) );
			return inst;
		},
		create_from_elm : elm_ =>
		{
			let inst = new ui_tab();
			inst.m.protect.init(elm_);
			return inst;
		}
	},

	//	ダイアログ
	dialog :
	{
		//	ダイアログのカウンタ（bodyのスクロール禁止・解除の制御用）
		count : 0,

		//	カスタムダイアログの作成と表示、button_callbacks_についての詳細は ui_dialog のコメントを参照
		popup : (selector_, button_callbacks_) =>
		{
			let dialog = new ui_dialog();
			dialog.popup(selector_, button_callbacks_==undefined ? null : button_callbacks_);
			return dialog;
		},

		//	メッセージダイアログ
		//	parent_ には親のセレクタを指定する。省略時はbodyとなる。
		popup_message : (title_, msg_, on_close_ = null, close_text_ = null, parent_ = null) =>
		{
			let dialog = new ui_dialog();
			dialog.popup_message
			(
				title_, msg_,
				on_close_,
				close_text_ == null ? "閉じる" : close_text_,
				parent_
			);
			return dialog;
		},

		//	エラーダイアログ
		//	parent_ には親のセレクタを指定する。省略時はbodyとなる。
		popup_error : (title_, msg_, on_close_ = null, close_text_ = null, parent_ = null) =>
		{
			let dialog = new ui_dialog();
			dialog.popup_error
			(
				title_, msg_,
				on_close_,
				close_text_ == null ? "閉じる" : close_text_,
				parent_
			);
			return dialog;
		},

		//	確認ダイアログ
		popup_confirm : (title_, msg_, on_done_ = null, on_close_ = null, yes_text_ = null, no_text_ = null, parent_ = null) =>
		{
			let dialog = new ui_dialog();
			dialog.popup_confirm
			(
				title_, msg_,
				on_done_,
				on_close_,
				yes_text_ === null ? "はい" : yes_text_,
				no_text_ === null ? "いいえ" : no_text_,
				parent_
			);
			return dialog;
		},

		//	警告ダイアログ
		popup_warn : (title_, msg_, on_done_ = null, on_close_ = null, yes_text_ = null, no_text_ = null, parent_ = null) =>
		{
			let dialog = new ui_dialog();
			dialog.popup_confirm
			(
				title_, msg_,
				on_done_,
				on_close_,
				yes_text_ === null ? "はい" : yes_text_,
				no_text_ === null ? "いいえ" : no_text_,
				parent_,
				'cancel',
				'warn'
			);
			return dialog;
		},

		//	テキスト入力ダイアログ
		popup_input : (title_, prompt_, default_, placeholder_, on_ok_ = null, on_cancel_ = null, ok_text_ = null, cancel_text_ = null, parent_ = null) =>
		{
			let dialog = new ui_dialog();
			dialog.popup_input
			(
				title_, prompt_, default_, placeholder_,
				on_ok_,
				on_cancel_,
				ok_text_ === null ? "OK" : ok_text_,
				cancel_text_ === null ? "キャンセル" : cancel_text_,
				parent_
			);
			return dialog;
		},

		//	ローディング
		popup_loading : () =>
		{
		}
	},

	//	トースト
	toast :
	{
		inst : null,

		//	メッセージ追加
		add : function(msg_)
		{
			this.add_with_level(msg_, 'notice');
		},

		//	警告メッセージ追加
		add_warning : function(msg_)
		{
			this.add_with_level(msg_, 'warning');
		},

		//	エラーメッセージ追加
		add_error : function(msg_)
		{
			this.add_with_level(msg_, 'error');
		},

		add_with_level : function(msg_, level_)
		{
			if( ui.toast.inst == null )
			{
				ui.toast.inst = $('<div id="ui_toast_container"></div>');
				ui.toast.inst.appendTo( $('body') );
			}

			let toast = new ui_toast(msg_);
			toast.add(msg_==undefined ? '' : msg_, level_);
			return toast;
		}
	},

	//	ドラッグコントロール
	dragger :
	{
		create : (selector_, mode_ = "move") =>
		{
			let inst = new ui_dragger(mode_);
			inst.m.protect.init( $(selector_) );
			return inst;
		},
		create_from_elm : (elm_, mode_ = "move") =>
		{
			let inst = new ui_dragger(mode_);
			inst.m.protect.init(elm_);
			return inst;
		}
	},

	//	スプリッタ
	splitter :
	{
		create : (selector_) =>
		{
			let inst = new ui_splitter();
			inst.m.protect.init( $(selector_) );
			return inst;
		},
		create_from_elm : (elm_) =>
		{
			let inst = new ui_splitter();
			inst.m.protect.init(elm_);
			return inst;
		}
	}

};

//	配置済みエレメントへの初期化
$(function()
{
	//	manualが指定されていないものについてのみ初期化する
	$('.ui_tab').each(function()
	{
		if( $(this).hasClass('manual') === false )
			ui.tab.create_from_elm($(this));
	});
	$('.ui_splitter').each(function()
	{
		if( $(this).hasClass('manual') === false )
			ui.splitter.create_from_elm($(this));
	});

	//	ui_buttonエレメントの追加変更を監視
	//	<button class="ui_button" cooldown="4">aaa</button>
	//
	//	cooldownに指定する数値により挙動が変わる。
	//	- 負数を指定した場合 : 一度押下したら押せなくなる
	//	- 0を指定した場合 : 制御なし
	//	- 正数を指定した場合 : 押された直後にdisabledにし、その後指定された秒数待ってからdisabledを解除する
	let ui_button_click = function()
	{
		if( this.getAttribute('disabled') !== null ) return;

		//	cooldownの指定がある場合のみイベントを仕込む
		let cooldown = this.getAttribute('cooldown');
		if( cooldown === null || cooldown === "" ) return;

		cooldown = parseInt(cooldown);
		if( cooldown == 0 ) return;

		//	1. disabledにして、
		this.setAttribute('disabled', true);
		if( cooldown < 0 ) return;

		//	2. 指定秒数を待機した後、disabledを解除する
		let elm_button = this;
		setTimeout(() => elm_button.removeAttribute('disabled'), cooldown * 1000);
	};
	let button_obs = new MutationObserver((list_, observer_) =>
	{
		array_each(list_, record_ =>
		{
			array_each(record_.addedNodes, node_ =>
			{
				//	テキストノードは除外
				if( node_.tagName === undefined ) return;

				//	cooldownの指定がある場合のみイベントを仕込む
				let cooldown = node_.getAttribute('cooldown');
				if(
					node_.classList.contains('ui_button') &&
					cooldown !== "" && cooldown !== null
				){
					node_.removeEventListener('click', ui_button_click);
					node_.addEventListener('click', ui_button_click);
				}
			});
		});
	})
	;
	button_obs.observe(document.querySelector('body'),
	{
		childList : true,
		attributes : true,
		subtree : true
	});
	array_each(document.querySelectorAll('.ui_button'), elm_ =>
	{
		elm_.addEventListener('click', ui_button_click);
	});
});

/*
--------------------------------------------------------------------------------
タブ

	<div class="ui_tab" id="tab_main">
	 <div class="selected" for="tab_1">タブ1</div>
	 <div for="tab_2">タブ2</div>
	 <div class="blank"></div>
	 <div class="controls">右側ボタンエリア</div>
	</div>
	<div class="ui_tab_body selected" id="tab_1">
	 タブ内容
	</div>
	<div class="ui_tab_body" id="tab_2">
	 タブ内容
	</div>

	上記のようにあらかじめ配置してある場合は自動で初期化される。
	※自動で初期化したくない場合には、クラスに "ui_tab" の他に、"manual" を追加で指定する。

	手動でエレメントを配置して使用する場合には下記の初期化を行う必要がある。

	//	初期化（その後に操作を行わないなら、変数に入れる必要はない）
	let tab = ui.tab.create('#tab');

	//	タブ変更時のイベントセット
	tab.on_selected( function(sender_, selected_id_)
	{
		console.log("ID=" + selected_id_ + "のタブが選択されました");
	});

	//	現在選択されているタブを取得
	console.log("現在 ID=" + tab.get_selected_id() + "のタブが選択されています");

--------------------------------------------------------------------------------
*/
var ui_tab = function()
{
	let self = this;
	this.m =
	{
		elm					: null,
		selected_id			: '',
		callback_selected	: null,

		//	外部から呼ぶ想定でないメソッドを protect にまとめる
		protect :
		{
			init : function(elm_)
			{
				let self_in = self;
				self.m.elm = elm_;
				$('> div', self.m.elm).each( function()
				{
					let item_id = $(this).attr('for');
					if( item_id == undefined ) return;
					if( item_id.length <= 0 ) return false;

					if( $(this).hasClass('selected') ) self_in.m.selected_id = item_id;
					$(this).on('click', function(){self_in.select(item_id);});
				});
				return self;
			}
		}
	};
};

//	選択されているIDを取得
ui_tab.prototype.get_selected_id = function()
{
	return this.m.selected_id;
};

//	IDを指定してタブを選択
ui_tab.prototype.select = function(id_)
{
	$('.ui_tab_body[id=' + this.m.selected_id + ']').removeClass('selected');
	$('#' + id_).addClass('selected');
	$('> div', this.m.elm).removeClass('selected');
	$('> div[for='+id_+']', this.m.elm).addClass('selected');
	this.m.selected_id = id_;
	if( this.m.callback_selected != null )
		this.m.callback_selected(this, this.m.selected_id);
	return this;
};

//	タブ変更時のイベント処理をセット
ui_tab.prototype.on_selected = function(callback_)
{
	this.m.callback_selected = callback_;
	return this;
};

/*
--------------------------------------------------------------------------------
ダイアログ

	メッセージ、エラー、確認ポップアップ、テキスト入力ポップアップは直接メソッドを実行すると動的にエレメントを作成する。
	テキスト入力はポップアップ時にテキストボックスにフォーカスし、Escで閉じEnterで確定する。

	例）
		ui.dialog.popup_message("タイトル", "メッセージ");
		ui.dialog.popup_error("タイトル", "エラーダイアログメッセージ");
		ui.dialog.popup_confirm("タイトル", "確認ダイアログメッセージ");
		ui.dialog.popup_warn("タイトル", "警告ダイアログメッセージ");
		ui.dialog.popup_input("タイトル", "プロンプト", "デフォルト", "プレースホルダ", function(text_){});
		ui.dialog.popup_loading();

	コールバックが必要な場合）

		//	メッセージダイアログ
		ui.dialog.popup_message("タイトル", "メッセージ", function()
		{
			//	閉じる押下時
		});

		//	エラーダイアログ
		ui.dialog.popup_error("タイトル", "メッセージ", function()
		{
			//	閉じる押下時
		});

		//	確認ダイアログ
		ui.dialog.popup_confirm
		(
			"タイトル", "メッセージ",
			function()
			{
				//	はい押下時
			},
			function()
			{
				//	いいえ押下時
			},
			"はい",		//	Yesボタンの文言、省略時は「はい」
			"いいえ",	//	Noボタンの文言、省略時は「いいえ」
		);

		//	テキスト入力ダイアログ
		ui.dialog.popup_input
		(
			"タイトル", "プロンプト", "デフォルト", "プレースホルダ",
			function()
			{
				//	OK押下時
			},
			function()
			{
				//	キャンセル押下時
			},
			"OK",			//	OKボタンの文言、省略時は「OK」
			"キャンセル",	//	キャンセルボタンの文言、省略時は「キャンセル」
		);

	上記とは異なるカスタムダイアログを使用するには
	次のような構成でエレメントを作成しておき、ui_dialog.popup() で表示する。

	<div class="ui_dialog" id="dialog_test">
	 <div class="box">
	  <div class="header">タイトル</div>
	  <div class="body">カスタムした中身</div>
	  <div class="footer">
	   <div class="ui_button close">閉じる</div>
	  </div>
	 </div>
	</div>

	JS側での表示指示

		let dlg = ui.dialog.popup( "#dialog_test" );

		//	何かの契機でダイアログを閉じる場合
		$('xxxxx').on('click', function()
		{
			dlg.close();
		});

	上記は第二引数を省略しているが、第二引数で各ボタンのハンドリングを指示可能
	下記、ボタンリストを渡す例

		ui.dialog.popup
		(
			"#dialog_test",

			//	ボタンセレクタをキー、値にコールバックを指定
			{
				//	コールバックは引数にui_dialogのインスタンスを受け取る
				"#dialog_test .ui_button.done" : function(sender_)
				{
					if( バリデーションチェック )
					{
						//	falseを返却すると、ダイアログを閉じない
						return false;
					}
				},

				//	コールバックに null を指定すると単にダイアログを閉じるだけ
				"#dialog_test .ui_button.close" : null
			}
		);

--------------------------------------------------------------------------------
*/
var ui_dialog = function()
{
	let self = this;
	this.m =
	{
		elm : null,

		//	カスタムの場合のコールバックリスト
		callbacks : {}
	}
};

//	スクロール禁止・解除のハンドル
ui_dialog.prototype.no_scroll_handle = function(e)
{
	e.preventDefault();
};

//	非表示にする
ui_dialog.prototype.close = function()
{
	if( this.m.elm == null ) return;
	this.m.elm.removeClass('show');

	//	フォーカスを外す
	$('.ui_button', this.m.elm).blur();

	//	開いているポップアップのカウントを減らす
	(ui.dialog.count - 1) < 0 ? 0 : ui.dialog.count--;

	//	全てのポップアップが閉じたらbodyのスクロール禁止を解除
	//	if( ui.dialog.count <= 0 )
	//	{
	//		$('body').css('overflow-y', 'auto');
	//		document.removeEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//		document.removeEventListener('wheel', this.no_scroll_handle, { passive: false });
	//	}
};

//	破棄
ui_dialog.prototype.destroy = function()
{
	if( this.m.elm == null ) return;
	this.m.elm.remove();
	this.m.elm = null;

	//	開いているポップアップのカウントを減らす
	(ui.dialog.count - 1) < 0 ? 0 : ui.dialog.count--;

	//	全てのポップアップが閉じたらbodyのスクロール禁止を解除
	//	if( ui.dialog.count <= 0 )
	//	{
	//		$('body').css('overflow-y', 'auto');
	//		document.removeEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//		document.removeEventListener('wheel', this.no_scroll_handle, { passive: false });
	//	}
};

//	セレクタを指定して表示、イベントを仕込む受け取りたいボタンリストを指定する
ui_dialog.prototype.popup = async function( selector_, button_callbacks_ )
{
	if( this.m.elm != null ) this.destroy();
	this.m.elm = $(selector_);
	this.m.callbacks = button_callbacks_ === undefined ? null : button_callbacks_;

	let self = this;
	setTimeout(() => self.m.elm.addClass('show'), 10);

	//	必要ならボタン処理を仕込む
	if( button_callbacks_ != null )
	{
		for( let key in this.m.callbacks )
		{
			let btn = $(key, self.m.elm);
			if( btn.get().length <= 0 ) btn = $(key);

			btn.off().data('callback_key', key).on('click', async function()
			{
				let key = $(this).data('callback_key');
				let callback = self.m.callbacks[key];

				//	コールバック指示がある場合は実行して、falseが返却されたら閉じない
				if( callback !== null )
				{
					let result = await callback(self, $(this));
					if( result === false ) return;
				}
				self.close();
			});
		}
	}

	//	bodyのスクロール禁止
	//	ui.dialog.count++;
	//	$('body').css('overflow-y', 'hidden');
	//	document.addEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//	document.addEventListener('wheel', this.no_scroll_handle, { passive: false });
};

//	メッセージ表示
ui_dialog.prototype.popup_message = function( title_, msg_, on_close_ = null, close_text_ = null, parent_ = null )
{
	if( this.m.elm !== null ) this.destroy();

	this.m.elm = $('<div class="ui_dialog"></div>')
		.append( $('<div></div>')
			.append( $('<div class="header">' + title_ + '</div>') )
			.append( $('<div class="body"></div>')
				.append( $('<div class="ui_panel padding_large">' + msg_ + '</div>') )
			)
			.append( $('<div class="footer"></div>')
				.append( $('<button class="ui_button cancel">' + (close_text_ === null ? '' : close_text_) + '</button>') )
			)
		)
		.appendTo( $(parent_ === null ? 'body' : parent_) )
		;

	let on_close = on_close_ === null ? null : on_close_;
	let self = this;

	setTimeout(() => self.m.elm.addClass('show'), 10);

	$('.ui_button.cancel', this.m.elm).on('click', function()
	{
		if( on_close != null )
		{
			if( on_close() === false ) return;
		}
		self.m.elm.removeClass('show');
		setTimeout(() => self.destroy(), 300);
	});

	//	bodyのスクロール禁止
	//	ui.dialog.count++;
	//	$('body').css('overflow-y', 'hidden');
	//	document.addEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//	document.addEventListener('wheel', this.no_scroll_handle, { passive: false });

	//	ボタンへフォーカス
	$('.ui_button.cancel', this.m.elm).focus();
};

//	エラー表示
ui_dialog.prototype.popup_error = function( title_, err_, on_close_ = null, close_text_ = null, parent_ = null )
{
	if( this.m.elm !== null ) this.destroy();

	this.m.elm = $('<div class="ui_dialog"></div>')
		.append( $('<div></div>')
			.append( $('<div class="header">' + title_ + '</div>') )
			.append( $('<div class="body"></div>')
				.append( $('<div class="ui_panel padding_large">' + err_ + '</div>') )
			)
			.append( $('<div class="footer"></div>')
				.append( $('<button class="ui_button cancel">' + (close_text_ === null ? '' : close_text_) + '</button>') )
			)
		)
		.appendTo( $(parent_ === null ? 'body' : parent_) )
		;

	let on_close = on_close_;
	let self = this;

	setTimeout(() => self.m.elm.addClass('show'), 10);

	$('.ui_button.cancel', this.m.elm).on('click', function()
	{
		if( on_close !== null )
		{
			if( on_close() === false ) return;
		}
		self.m.elm.removeClass('show');
		setTimeout(() => self.destroy(), 300);
	});

	//	bodyのスクロール禁止
	//	ui.dialog.count++;
	//	$('body').css('overflow-y', 'hidden');
	//	document.addEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//	document.addEventListener('wheel', this.no_scroll_handle, { passive: false });

	//	ボタンへフォーカス
	$('.ui_button.cancel', this.m.elm).focus();
};

//	確認表示
ui_dialog.prototype.popup_confirm = function( title_, msg_, on_yes_ = null, on_no_ = null, yes_text_ = null, no_text_ = null, parent_ = null, btn_cancel_class_ = 'cancel', btn_done_class_ = 'done' )
{
	if( this.m.elm !== null ) this.destroy();

	this.m.elm = $('<div class="ui_dialog"></div>')
		.append( $('<div></div>')
			.append( $('<div class="header">' + title_ + '</div>') )
			.append( $('<div class="body"></div>')
				.append( $('<div class="ui_panel padding_large">' + msg_ + '</div>') )
			)
			.append( $('<div class="footer"></div>')
				.append( $('<button class="ui_button ' + btn_cancel_class_ + '">' + (no_text_ === null ? '' : no_text_) + '</button>') )
				.append( $('<button class="ui_button ' + btn_done_class_ + '">' + (yes_text_ === null ? '' : yes_text_) + '</button>') )
			)
		)
		.appendTo( $(parent_ === null ? 'body' : parent_) )
		;

	let self = this;

	setTimeout(() => self.m.elm.addClass('show'), 10);

	let on_no = on_no_;
	$('.ui_button.' + btn_cancel_class_, this.m.elm).on('click', function()
	{
		if( on_no !== null )
		{
			if( on_no() === false ) return;
		}
		self.m.elm.removeClass('show');
		setTimeout(() => self.destroy(), 300);
	});

	let on_yes = on_yes_;
	$('.ui_button.' + btn_done_class_, this.m.elm).on('click', function()
	{
		if( on_yes !== null )
		{
			if( on_yes() === false ) return;
		}
		self.m.elm.removeClass('show');
		setTimeout(() => self.destroy(), 300);
	});

	//	bodyのスクロール禁止
	//	ui.dialog.count++;
	//	$('body').css('overflow-y', 'hidden');
	//	document.addEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//	document.addEventListener('wheel', this.no_scroll_handle, { passive: false });
};

//	テキスト入力
ui_dialog.prototype.popup_input = function( title_, prompt_, default_, placeholder_, on_ok_ = null, on_cancel_ = null, ok_text_ = null, cancel_text_ = null, parent_ = null )
{
	if( this.m.elm !== null ) this.destroy();

	this.m.elm = $('<div class="ui_dialog"></div>')
		.append( $('<div></div>')
			.append( $('<div class="header">' + title_ + '</div>') )
			.append( $('<div class="body ui_panel full padding_large"></div>')
				.append( $('<div class="ui_panel padding_small">' + prompt_ + '</div>') )
				.append( $('<input type="text" class="ui_text full"></div>').attr('placeholder', placeholder_).val(default_) )
			)
			.append( $('<div class="footer"></div>')
				.append( $('<button class="ui_button cancel">' + (cancel_text_ === null ? '' : cancel_text_) + '</button>') )
				.append( $('<button class="ui_button done">' + (ok_text_ === null ? '' : ok_text_) + '</button>') )
			)
		)
		.appendTo( $(parent_ === null ? 'body' : parent_) )
		;

	let self = this;

	setTimeout(() => self.m.elm.addClass('show'), 10);

	let on_cancel = on_cancel_;
	$('.ui_button.cancel', this.m.elm).on('click', function()
	{
		if( on_cancel !== null )
		{
			if( on_cancel() === false ) return;
		}
		self.m.elm.removeClass('show');
		setTimeout(() => self.destroy(), 300);
	});

	let on_ok = on_ok_;
	let done_logic = function()
	{
		if( on_ok != null )
		{
			if( on_ok($('.ui_text', self.m.elm).val()) === false ) return;
		}
		self.m.elm.removeClass('show');
		setTimeout(() => self.destroy(), 300);
	};
	$('.ui_button.done', this.m.elm).on('click', done_logic);

	//	テキストボックスにエスケープとエンターをバインドし、フォーカスを与える
	setTimeout( () =>
	{
		$('.ui_text', self.m.elm).keyup((e_) =>
		{
			if( e_.keyCode == 13 ) done_logic();
			if( e_.keyCode == 27 ) self.close();
		}).focus();
	}, 100 );

	//	bodyのスクロール禁止
	//	ui.dialog.count++;
	//	$('body').css('overflow-y', 'hidden');
	//	document.addEventListener('touchmove', this.no_scroll_handle, { passive: false });
	//	document.addEventListener('wheel', this.no_scroll_handle, { passive: false });
};


/*
--------------------------------------------------------------------------------
トースト

	ユーザーの操作を妨げることないよう通知します。

	例）
		ui.toast.add("メッセージ");

--------------------------------------------------------------------------------
*/
var ui_toast = function()
{
	let self = this;
	this.m =
	{
		elm : null,
	}
};

//	非表示にする
ui_toast.prototype.close = function()
{
	if( this.m.elm === null ) return;
	this.m.elm.removeClass('show');
};

//	破棄
ui_toast.prototype.destroy = function()
{
	if( this.m.elm === null ) return;
	this.m.elm.remove();
	this.m.elm = null;
};

//	メッセージ表示
//	levelは、notice / warning / error
ui_toast.prototype.add = function( msg_, level_ = 'notice' )
{
	if( this.m.elm !== null ) this.destroy();
	this.m.elm = $('<div class="ui_toast"></div>')
		.append( $('<div>'+msg_+'</div>').addClass(level_) )
		.appendTo( ui.toast.inst )
		;
	let self = this;

	//	表示 → 非表示
	setTimeout(() => self.m.elm.addClass('show'), 10);
	setTimeout(() => self.m.elm.removeClass('show'), 4000);
	setTimeout(() => self.m.elm.addClass('squash'), 4200);
	setTimeout(() => self.destroy(), 4500);
};

/*
--------------------------------------------------------------------------------
ドラッグコントロール

エレメントに対するドラッグドロップのイベントを拾えるようにする
グリッド設定を行えば、位置をスナップさせることも可能

・move : 移動検知モード
	mousedown/touchstart で開始して mouseup までを管理する
	要素の位置をドラッグで移動する場合などの利用を想定する

・drag : ドラッグモード
	dragstart で開始して drop までを管理する
	エレメントを別のエレメント上にドロップするなどの利用を想定する

--------------------------------------------------------------------------------
*/
var ui_dragger = function( mode_ = "move" )
{
	let self = this;
	this.m =
	{
		mode		: mode_,
		elm			: null,
		on_begin	: null,
		on_move		: null,
		on_end		: null,
		grid		: 0,
		param		: null,
		data		:
		{
			touching : false,
			sx : 0,
			sy : 0
		},
		drag_leave_timer : null,
		prevent		: true,
		excludes	: [],

		//	外部から呼ぶ想定でないメソッドを protect にまとめる
		protect :
		{
			init : function(elm_)
			{
				self.m.elm = elm_;
				if( self.m.elm != undefined )
				{
					if( self.m.mode == "move" )
					{
						let elm = self.m.elm.get()[0];
						elm.onmousedown = self.m.protect.touch_start;
						elm.ontouchstart = self.m.protect.touch_start;
					}
					else if( self.m.mode == "drag" )
					{
						let elm = self.m.elm.get()[0];
						elm.draggable = 'true';
						elm.ondragstart = self.m.protect.dragstart;
						elm.ondragover = self.m.protect.dragover;
						elm.ondrop = self.m.protect.drop;
						elm.ondragend = self.m.protect.dragend;
					}
				}
				return self;
			},
			touch_start : function(e_)
			{
				if( self.m.prevent === true )
				{
					e_.preventDefault();
					e_.stopPropagation();
				}

				//	マウスとタッチの差を吸収
				let event;
				if( e_.type === "mousedown" ) {event = e_;}
				else if( e_.changedTouches ) {event = e_.changedTouches[0];}
				else return;

				//	除外設定エリアに含まれる場合はキャンセル
				for( let i = 0; i < self.m.excludes.length; i++ )
				{
					let ex = self.m.excludes[i];
					if(
						event.pageX > ex.x && event.pageX < ex.x + ex.w &&
						event.pageY > ex.y && event.pageY < ex.y + ex.h
					)	return;
				}

				//	位置保持
				self.m.data.touching = true;
				self.m.data.sx = event.pageX;
				self.m.data.sy = event.pageY;

				if( self.m.grid != 0 )
				{
					let modx = self.m.data.sx % self.m.grid;
					if( modx > 0 ) self.m.data.sx -= modx;
					let mody = self.m.data.sy % self.m.grid;
					if( mody > 0 ) self.m.data.sy -= mody;
				}

				//	開始コールバック
				if( self.m.on_begin )
					self.m.on_begin(self.m.param, self.m.data.sx, self.m.data.sy);

				//	移動イベント
				let touch_move = function(e_)
				{
					if( ! self.m.data.touching ) return;

					if( self.m.prevent === true )
					{
						e_.preventDefault();
						e_.stopPropagation();
					}

					//	マウスとタッチの差を吸収
					let event;
					if( e_.type === "mousemove" ) {event = e_;}
					else if( e_.changedTouches ) {event = e_.changedTouches[0];}
					else return;

					//	位置計算
					let sx = self.m.data.sx;
					let sy = self.m.data.sy;
					let ax = event.pageX - sx;
					let ay = event.pageY - sy;

					if( self.m.grid !== undefined )
					{
						let modx = (sx+ax) % self.m.grid;
						if( modx > 0 ) ax -= modx;
						let mody = (sy+ay) % self.m.grid;
						if( mody > 0 ) ay -= mody;
					}

					//	移動コールバック
					if( self.m.on_move )
						self.m.on_move(self.m.param, ax, ay, sx+ax, sy+ay);

					//	位置保持
					self.m.data.sx = sx + ax;
					self.m.data.sy = sy + ay;
				};

				//	終了イベント
				let touch_end = function(e_)
				{
					if( self.m.prevent === true )
					{
						e_.preventDefault();
						e_.stopPropagation();
					}

					//	イベントハンドラ削除
					document.body.removeEventListener("mousemove", touch_move, {passive : false});
					document.body.removeEventListener("mouseleave", touch_end, false);
					document.body.removeEventListener("mouseup", touch_end, false);
					document.body.removeEventListener("touchmove", touch_move, {passive : false});
					document.body.removeEventListener("touchleave", touch_end, false);
					document.body.removeEventListener("touchend", touch_end, false);

					//	終了コールバック
					if( self.m.on_end ) self.m.on_end(self.m.param);

					self.m.data.touching = false;
				};

				//	ドラッグ中のみイベント追加
				document.body.addEventListener("mousemove", touch_move, {passive : false});
				document.body.addEventListener("mouseleave", touch_end, false);
				document.body.addEventListener("mouseup", touch_end, false);

				document.body.addEventListener("touchmove", touch_move, {passive : false});
				document.body.addEventListener("touchleave", touch_end, false);
				document.body.addEventListener("touchend", touch_end, false);
			},

			dragstart : function(e_)
			{
				if( self.m.on_dragstart )
				{
					e_.stopPropagation();
					self.m.on_dragstart(self.m.param, e_);
				}
			},
			dragover : function(e_)
			{
				if( self.m.drag_leave_timer !== null )
				{
					clearTimeout(self.m.drag_leave_timer);
					if( self.m.on_dragover )
						self.m.on_dragover(self.m.param, e_);
				}
				else
				{
					if( self.m.on_dragenter )
						self.m.on_dragenter(self.m.param, e_);
				}
				self.m.drag_leave_timer = setTimeout(function()
				{
					if( self.m.on_dragleave )
						self.m.on_dragleave(self.m.param, e_);
					self.m.drag_leave_timer = null;
				}, 100);

			},
			drop : function(e_)
			{
				e_.stopPropagation();
				if( self.m.on_drop )
				{
					e_.preventDefault();
					self.m.on_drop(self.m.param, e_);
				}
			},
			dragend : function(e_)
			{
				e_.stopPropagation();
				if( self.m.on_dragend )
				{
					e_.preventDefault();
					self.m.on_dragend(self.m.param, e_);
				}
			}
		}
	};
};

//	有効無効の切り替え
ui_dragger.prototype.set_enabled = function(enable_)
{
	if( this.m.mode == "drag" )
	{
		let elm = this.m.elm.get()[0];
		$(elm).attr('draggable', enable_ === true ? 'true' : 'false');
	}
	return this;
};

//	開始時のイベントを下位に伝播しない？
ui_dragger.prototype.set_prevent = function(prevent_)
{
	this.m.prevent = prevent_;
	return this;
};

//	グリッド設定、グリッドスナップありの場合そのピクセル数を指定する
ui_dragger.prototype.set_grid = function(grid_)
{
	this.m.grid = grid_;
	return this;
};

//	コールバックで渡されるパラメータを指定
ui_dragger.prototype.set_callback_param = function(callback_param_)
{
	this.m.param = callback_param_;
	return this;
};

//	ドラッグモードでのコールバックで渡されるパラメータを指定
ui_dragger.prototype.on_dragstart = function(callback_)
{
	this.m.on_dragstart = callback_;
	return this;
};

//	ドラッグモードのコールバック指定
ui_dragger.prototype.on_dragenter = function(callback_)
{
	this.m.on_dragenter = callback_;
	return this;
};
ui_dragger.prototype.on_dragover = function(callback_)
{
	this.m.on_dragover = callback_;
	return this;
};
ui_dragger.prototype.on_dragleave = function(callback_)
{
	this.m.on_dragleave = callback_;
	return this;
};
ui_dragger.prototype.on_drop = function(callback_)
{
	this.m.on_drop = callback_;
	return this;
};
ui_dragger.prototype.on_dragend = function(callback_)
{
	this.m.on_dragend = callback_;
	return this;
};

//	移動モードのコールバック指定
ui_dragger.prototype.on_begin = function(callback_)
{
	this.m.on_begin = callback_;
	return this;
};
ui_dragger.prototype.on_move = function(callback_)
{
	this.m.on_move = callback_;
	return this;
};
ui_dragger.prototype.on_end = function(callback_)
{
	this.m.on_end = callback_;
	return this;
};

//	除外設定、セレクタの配列を指定する
ui_dragger.prototype.set_excludes = function(selectors_)
{
	let self = this;
	self.m.excludes = [];
	for( let i = 0; i < selectors_.length; i++ )
	{
		$(selectors_[i]).each(function()
		{
			let elm = $(this);
			let off = elm.offset();
			self.m.excludes.push(
			{
				x : off.left, y : off.top,
				w : elm.outerWidth(), h : elm.outerHeight()
			});
		});
	}
	return this;
};

/*
--------------------------------------------------------------------------------
スプリッタ

	固定パネル/スプリットバー/可変パネルを
	ui_panelを使って並べておく。

	例えば縦に固定/バー/可変と並べる場合は、
	下記のような3つのdivを1つのdivで括ったhtmlを用意する。
	トップエレメントには layout_horizon か layout_vertical のいずれかを指定すること

		<div class="ui_panel layout_horizon full">
		 <div class="ui_panel full_vertical"></div> → 固定エリア
		 <div class="ui_panel full_vertical"></div> → リサイズバー（横幅や色やカーソルなどcssは自分で定義すること）
		 <div class="ui_panel spacer"></div> → 可変エリア
		</div>

	このトップのエレメントに対してクラスと属性を指定すると、自動で ui_splitter が初期化される。
	例）スプリッタをクラスで指定する
		<div class="ui_panel layout_horizon full">
		を
		<div class="ui_panel layout_horizon full ui_splitter">
		にすると、自動でリサイズ可能なスプリッタが初期化される

	固定領域の最小サイズを指定する場合は、固定領域のエレメントに min 属性を指定する
	例）最小を300pxに指定する
		<div class="ui_panel layout_horizon full ui_splitter">
		 <div class="ui_panel full_vertical" min=300></div>
		 <div class="ui_panel full_vertical"></div>
		 <div class="ui_panel spacer"></div>
		</div>

	クラスと属性を指定せずに手動で初期化することも可能
	例）JSで、
		ui.splitter.create("スプリッタのトップノードを指すセレクタ").set_min(200);
		とすると固定領域の最小サイズを200pxとして初期化される。

--------------------------------------------------------------------------------
*/
var ui_splitter = function()
{
	let self = this;
	this.m =
	{
		elm				: null,		//	トップエレメント
		elm_fixed		: null,		//	固定領域
		elm_main		: null,		//	可変領域
		elm_bar			: null,		//	リサイズバー
		bar_size		: 0,		//	リサイズバーのサイズ
		dir				: null,		//	向き、"horizon" or "vertical"
		fixed_size		: 0,		//	固定領域のサイズ
		main_size		: 0,		//	可変領域のサイズ
		area_size		: 0,		//	固定領域と可変領域の合計サイズ、バー部分を含まない
		min_size		: 0,		//	最小サイズ、0で制限なし
		auto_main_size	: true,		//	可変領域を自動でリサイズする場合はtrue

		//	外部から呼ぶ想定でないメソッドを protect にまとめる
		protect :
		{
			init : function(elm_)
			{
				self.m.elm = elm_;

				//	最初の spacer ではないエレメントがfixedになる
				//	spacer のエレメントがmainになる
				//	2つ目のエレメントがbarになる
				let elms = $('> .ui_panel', self.m.elm).get();
				if( elms.length < 3 )
				{
					console.log("splitter has no child");
					return null;
				}
				let first_fixed = $(elms[0]).hasClass('spacer') === false;
				self.m.elm_fixed = first_fixed ? $(elms[0]) : $(elms[2]);
				self.m.elm_main =  first_fixed ? $(elms[2]) : $(elms[0]);
				self.m.elm_bar = $(elms[1]);

				//	トップエレメントのクラスによって向きを判断しておく
				if( self.m.elm.hasClass('layout_horizon') === true ||
					self.m.elm.hasClass('layout_horizon_top') === true ||
					self.m.elm.hasClass('layout_horizon_bottom') === true ||
					self.m.elm.hasClass('layout_horizon_baseline') === true ||
					self.m.elm.hasClass('layout_horizon_stretch') === true
				)	self.m.dir = 'horizon';
				else if(
					self.m.elm.hasClass('layout_vertical') === true ||
					self.m.elm.hasClass('layout_vertical_left') === true ||
					self.m.elm.hasClass('layout_vertical_right') === true ||
					self.m.elm.hasClass('layout_vertical_baseline') === true ||
					self.m.elm.hasClass('layout_vertical_stretch') === true
				)	self.m.dir = 'vertical';
				else
				{
					console.log("not found any layout classes in root element for split");
					return null;
				}

				//	サイズが勝手に変化しないようにしておく
				self.m.elm_fixed.css("flex-shrink", "0");
				self.m.elm_bar.css("flex-shrink", "0");
				self.m.elm_main.css("flex-shrink", "1");

				//	属性で最小サイズ指定がある場合は取得しておく
				let min_attr = self.m.elm_fixed.attr('min');
				if( min_attr != undefined && min_attr != "" )
					self.m.min_size = parseInt(min_attr);

				//	各領域のサイズを保持しておく
				self.m.fixed_size = self.m.dir == "horizon" ? self.m.elm_fixed.width() : self.m.elm_fixed.height();
				self.m.main_size = self.m.dir == "horizon" ? self.m.elm_main.width() : self.m.elm_main.height();
				self.m.area_size = self.m.fixed_size + self.m.main_size;

				//	初期サイズ決定
				if( self.m.min_size > 0 && self.m.fixed_size < self.m.min_size )
					self.m.fixed_size = self.m.min_size;
				self.m.elm_fixed.width(self.m.fixed_size);
				self.m.elm_main.width(self.m.area_size - self.m.fixed_size);

				//	スプリットバーにドラッガを仕込む
				ui.dragger.create(self.m.elm_bar)
					.on_move(function(param_, ax_, ay_, x_, y_)
					{
						//	もしサイズが0だった場合、レンダリング前にサイズを取得した可能性があるので、取り直す
						if( self.m.fixed_size <= 0 )
						{
							self.m.fixed_size = self.m.dir == "horizon" ? self.m.elm_fixed.width() : self.m.elm_fixed.height();
							self.m.main_size = self.m.dir == "horizon" ? self.m.elm_main.width() : self.m.elm_main.height();
							self.m.area_size = self.m.fixed_size + self.m.main_size;
						}

						//	移動前と移動後の差分を、固定パネルに加算する
						if( self.m.dir == "horizon" )
						{
							self.m.fixed_size = self.m.fixed_size + ax_;
							if( self.m.min_size > 0 && self.m.fixed_size < self.m.min_size )
								self.m.fixed_size = self.m.min_size;
							self.m.elm_fixed.width(self.m.fixed_size);
							if( self.m.auto_main_size === true )
								self.m.elm_main.width(self.m.area_size - self.m.fixed_size);
						}
						else
						{
							self.m.fixed_size = self.m.fixed_size + ay_;
							if( self.m.min_size > 0 && self.m.fixed_size < self.m.min_size )
								self.m.fixed_size = self.m.min_size;
							self.m.elm_fixed.height(self.m.fixed_size);
							if( self.m.auto_main_size === true )
								self.m.elm_main.height(self.m.area_size - self.m.fixed_size);
						}
					})
					;

				return self;
			}
		}
	};
};
ui_splitter.prototype.set_min = function(min_)
{
	this.m.min_size = parseInt(min_);
};
