/*
--------------------------------------------------------------------------------
ツリーエディタ

	■ 使い方

		//	インスタンス作成
		let tree_editor = create_tree_editor(nodes, root, id_key, text_key, event_maps);

		//	描画
		tree_editor.draw($('#tree'));

	■ インスタンス作成時の引数

		create_tree_editor
		(
			//	第一引数：ノードリストを指定する。キーを親ノードのID、値を兄弟要素のrowsとする。
			{
				'0' :
				[
					//	ルートノードの子ノード
					{'id' : 1, 'name' : 'hoge', 'parent' : 0},
					{'id' : 2, 'name' : 'fuga', 'parent' : 0},
					{'id' : 3, 'name' : 'piyo', 'parent' : 0},
				],
				'2' :
				[	//	id=2のノードの子ノード
					{'id' : 4, 'name' : 'foo', 'parent' : 2},
					{'id' : 5, 'name' : 'bar', 'parent' : 2},
				],
			},

			//	第二引数：ルートノードのrowを指定する。少なくともIDと文言の要素は用意する。
			{'id' : 0, 'name' : 'root'},

			//	第三引数：ノードのIDのキーを指定する。
			'id',

			//	第四引数：文言のキーを指定する。
			'name',

			//	第五引数：イベントマップリストを指定する。キーをセレクタ、値をイベントマップとする。
			{
				'.line' :
				{
					//	イベントマップはキーをイベント、値をコールバックとする。
					//	コールバックの引数は、tree_editorのインスタンス、ノードのエレメント、セレクタのエレメントを受け取る。
					'click' : function( sender_, elm_node_, self_ )
					{
						//	trueを返すとe_.stopPropagation()が走らない。
						return true;
					},
				},
			}
		);

	■ コールバック

		各操作時に実行するコールバックを設定できる。
		用途としてはノードリストの更新を想定している。
		ただしadd_children()はノードリスト更新用の値を受け取らないため、コールバックでのノードリスト更新はできない。

		コールバックの第一引数は、tree_editorのインスタンスを受け取る。
		コールバックの第二引数以降は、各操作メソッドに渡した引数を受け取る。(下記参照)

			//	子ノード追加時のコールバック(親ノードのIDを受け取る)
			tree_editor.on_add_children(function( sender_, parent_ )
			{
				//	このコールバックに限ってはノードリストを更新できない。
				//	ノードリストの更新はadd_children()の直前に書く。
			});

			//	子ノード削除時のコールバック(親ノードのIDを受け取る)
			tree_editor.on_remove_children(function( sender_, parent_ )
			{
			});

			//	ノード追加時のコールバック(ノードのrowと親ノードのIDを受け取る)
			tree_editor.on_add_node(function( sender_, row_, parent_ )
			{
			});

			//	ノード削除時のコールバック(ノードのIDを受け取る)
			tree_editor.on_remove_node(funciton( sender_, node_ )
			{
			});

			//	ノード移動時のコールバック(ノードのIDと移動先ノードのIDを受け取る)
			tree_editor.on_move_node(function( sender_, node_, to_ )
			{
			});

			//	ノードの文言変更時のコールバック(ノードのIDと文言を受け取る)
			tree_editor.on_rewrite_node(function( sender_, node_, line_text_ )
			{
			});

		ノードエレメント作成時にもコールバックを使用できる。
		tree_editorにはui_draggerを適用する専用の機能がないので、必要ならこのコールバック内部で記述する。

			//	ノードエレメント作成時のコールバック(ノードのエレメントを受け取る)
			tree_editor.on_create_elm_node(function( sender_, elm_node_ )
			{
			});

	■ 描画されるHTML

		<div class="tree_editor">を基底とする。
		<div class="node">をノードエレメントとする。
		ルートノードは通常のノードと同じ構造とする。
		一つのツリーエディタにつきルートノードは一つとする。

		ノードのIDを<div class="node">のdata-node属性に保持する。
		ルートノードの<div class="node">にはrootクラスが付与される。
		子ノード展開中の親ノードの<div class="node">にはopenクラスが付与される。

			<div class="tree_editor">

			 <div class="node root" data-node="0">
			  <div class="line">
			   <div class="icon"></div>
			   <div class="text">root</div>
			  </div>
			  <div class="children">

			   <div class="node" data-node="1"></div>
			   <div class="node" data-node="2"></div>
			   <div class="node" data-node="3"></div>

			  </div>
			 </div>

			</div>

--------------------------------------------------------------------------------
*/

//------------------------------------------------------------------------------
//	ツリーエディタのインスタンス生成
//
//	第一引数	: ノードリスト
//	第二引数	: ルートノード
//	第三引数	: IDのキー
//	第四引数	: 文言のキー
//	第五引数	: イベントマップリスト
//------------------------------------------------------------------------------
function create_tree_editor( nodes_, root_, id_key_, line_text_key_, event_maps_ )
{
	let inst = new tree_editor();
	inst.m.protect.init(nodes_, root_, id_key_, line_text_key_, event_maps_);
	return inst;
}

//------------------------------------------------------------------------------
//	ツリーエディタ
//------------------------------------------------------------------------------
var tree_editor = function()
{
	let self = this;
	self.m =
	{
		elm : null,
		content :
		{
			nodes : null,
			root : null,
			id_key : '',
			line_text_key : '',
			event_maps : {},
		},

		//	操作時のコールバック
		on_create_elm_node : null,
		on_add_children : null,
		on_remove_children : null,
		on_add_node : null,
		on_remove_node : null,
		on_move_node : null,
		on_rewrite_node : null,

		building : false,
		callback_enabled : false,

		//	外部から呼ぶ想定でないメソッドをprotectにまとめる
		protect :
		{
			//	初期化
			init : function( nodes_, root_, id_key_, line_text_key_, event_maps_ )
			{
				self.m.content =
				{
					nodes			: nodes_,
					root			: root_,
					id_key			: id_key_,
					line_text_key	: line_text_key_,
					event_maps		: event_maps_,
				};
				return self;
			},

			//	ノードエレメント生成
			create_elm_node : function( row_ )
			{
				let elm_node = $('<div class="node">')
					.attr('data-node', row_[self.m.content.id_key])
					.append
					(
						$('<div class="line">').append
						(
							$('<div class="icon">'),
							$('<div class="text">').text(row_[self.m.content.line_text_key]),
						),
						$('<div class="children">'),
					)
					;

				//	イベントを仕込む
				array_each(self.m.content.event_maps, function( event_map_, selector_ )
				{
					let elm = elm_node.find(selector_);
					if( elm.length > 0 )
					{
						array_each(event_map_, function( func_, event_ )
						{
							elm.on(event_, function( e_ )
							{
								//	コールバックからtrueが返されたらstopPropagation()はしない
								if( func_(self, elm_node, $(this)) !== true )
								{
									e_.stopPropagation();
								}
							});
						});
					}
				});

				//	コールバック実行
				if( self.m.on_create_elm_node !== null )
					self.m.on_create_elm_node(self, elm_node);

				return elm_node;
			},

			//------------------------------------------------------------------
			//	HTMLを構築
			//------------------------------------------------------------------
			build : function()
			{
				self.m.building = true;
				self.m.elm = $('<div class="tree_editor">').append(self.m.protect.create_elm_node(self.m.content.root).addClass('root'));
				self.add_children(self.m.content.root[self.m.content.id_key]);
				self.m.building = false;
				return self.m.elm;
			},

			//------------------------------------------------------------------
			//	操作時コールバックの実行可否
			//------------------------------------------------------------------
			is_callback_enabled : function()
			{
				return self.m.building === false && self.m.callback_enabled === true;
			},
		},
	};
};

//------------------------------------------------------------------------------
//	インスタンスを複製
//------------------------------------------------------------------------------
tree_editor.prototype.clone = function()
{
	return create_tree_editor(this.m.content.nodes, this.m.content.root, this.m.content.id_key, this.m.content.line_text_key, this.m.content.event_maps)
		.on_create_elm_node(this.m.on_create_elm_node)
		.on_add_children(this.m.on_add_children)
		.on_remove_children(this.m.on_remove_children)
		.on_add_node(this.m.on_add_node)
		.on_remove_node(this.m.on_remove_node)
		.on_move_node(this.m.on_move_node)
		.on_rewrite_node(this.m.on_rewrite_node)
		;
};

//------------------------------------------------------------------------------
//	内容を更新
//
//	引数はcreate_tree_editor()と同じ
//	falseを指定された要素は更新しない
//------------------------------------------------------------------------------
tree_editor.prototype.set_content = function( nodes_ = false, root_ = false, id_key_ = false, line_text_key_ = false, event_maps_ = false )
{
	if( nodes_			!== false ) this.m.content.nodes			= nodes_;
	if( root_			!== false ) this.m.content.root				= root_;
	if( id_key_			!== false ) this.m.content.id_key			= id_key_;
	if( line_text_key_	!== false ) this.m.content.line_text_key	= line_text_key_;
	if( event_maps_		!== false ) this.m.content.event_maps		= event_maps_;
	return this;
};

//------------------------------------------------------------------------------
//	ノードエレメント作成時のコールバック
//
//	ui_draggerなどを想定
//------------------------------------------------------------------------------
tree_editor.prototype.on_create_elm_node = function( callback_ )
{
	this.m.on_create_elm_node = callback_;
	return this;
};

//------------------------------------------------------------------------------
//	ツリー操作時のコールバックを設定
//
//	コールバックの第一引数はツリーエディタのインスタンスを受け取る
//	第二引数以降は各操作メソッドに渡した値を受け取る
//------------------------------------------------------------------------------
tree_editor.prototype.on_add_children = function( callback_ )
{
	this.m.on_add_children = callback_;
	return this;
};
tree_editor.prototype.on_remove_children = function( callback_ )
{
	this.m.on_remove_children = callback_;
	return this;
};
tree_editor.prototype.on_add_node = function( callback_ )
{
	this.m.on_add_node = callback_;
	return this;
};
tree_editor.prototype.on_remove_node = function( callback_ )
{
	this.m.on_remove_node = callback_;
	return this;
};
tree_editor.prototype.on_move_node = function( callback_ )
{
	this.m.on_move_node = callback_;
	return this;
};
tree_editor.prototype.on_rewrite_node = function( callback_ )
{
	this.m.on_rewrite_node = callback_;
	return this;
};

//------------------------------------------------------------------------------
//	描画
//------------------------------------------------------------------------------
tree_editor.prototype.draw = function( elm_ )
{
	elm_.empty().append(this.m.protect.build());
	return this;
};

//------------------------------------------------------------------------------
//	ノード取得
//------------------------------------------------------------------------------
tree_editor.prototype.get_node = function( node_ )
{
	return this.m.elm.find('.node[data-node="' + node_ + '"]');
};

//------------------------------------------------------------------------------
//	親ノード取得
//------------------------------------------------------------------------------
tree_editor.prototype.get_parent = function( node_ )
{
	return this.m.elm.find('.node[data-node="' + node_ + '"]').parents('.node').first();
};

//------------------------------------------------------------------------------
//	子ノード取得
//------------------------------------------------------------------------------
tree_editor.prototype.get_children = function( node_ )
{
	return this.m.elm.find('.node[data-node="' + node_ + '"] > .children');
};

//------------------------------------------------------------------------------
//	子ノードの存在チェック
//------------------------------------------------------------------------------
tree_editor.prototype.has_children = function( node_ )
{
	return this.m.elm.find('.node[data-node="' + node_ + '"] > .children > .node').length > 0;
};

//------------------------------------------------------------------------------
//	子孫ノードか判定
//------------------------------------------------------------------------------
tree_editor.prototype.is_descendant = function( ancestor_, descendant_ )
{
	return this.m.elm.find('.node[data-node="' + ancestor_ + '"] .node[data-node="' + descendant_ + '"]').length > 0;
};

//------------------------------------------------------------------------------
//	子ノードを追加
//
//	再帰的に子ノードを構築する
//	子ノードが一つもない状態で使う
//	呼び出す直前にノードリストを更新しておく
//------------------------------------------------------------------------------
tree_editor.prototype.add_children = function( parent_ )
{
	let self = this;
	if( self.m.protect.is_callback_enabled() === true && self.m.on_add_children !== null )
		self.m.on_add_children(self, parent_);
	array_each(self.m.content.nodes[parent_], function( row_ )
	{
		self.m.callback_enabled = false;
		self.add_node(row_, parent_);
		self.m.callback_enabled = true;
		if( row_[self.m.content.id_key] in self.m.content.nodes )
			self.add_children(row_[self.m.content.id_key]);
	});
	return self;
};

//------------------------------------------------------------------------------
//	子ノードを削除
//------------------------------------------------------------------------------
tree_editor.prototype.remove_children = function( parent_ )
{
	let self = this;
	if( self.m.protect.is_callback_enabled() === true && self.m.on_remove_children !== null )
		self.m.on_remove_children(self, parent_);
	self.get_children(parent_).empty();
	return self;
};

//------------------------------------------------------------------------------
//	ノード追加
//------------------------------------------------------------------------------
tree_editor.prototype.add_node = function( row_, parent_ )
{
	let self = this;
	if( self.m.protect.is_callback_enabled() === true && self.m.on_add_node !== null )
		self.m.on_add_node(self, row_, parent_);
	self.get_children(parent_).append(self.m.protect.create_elm_node(row_));
	return self;
};

//------------------------------------------------------------------------------
//	ノード削除
//------------------------------------------------------------------------------
tree_editor.prototype.remove_node = function( node_ )
{
	let self = this;
	if( self.m.protect.is_callback_enabled() === true && self.m.on_remove_node !== null )
		self.m.on_remove_node(self, node_);
	self.get_node(node_).remove();
	return self;
};

//------------------------------------------------------------------------------
//	移動
//------------------------------------------------------------------------------
tree_editor.prototype.move_node = function( node_, to_ )
{
	let self = this;
	if( self.m.protect.is_callback_enabled() === true && self.m.on_move_node !== null )
		self.m.on_move_node(self, node_, to_);
	self.get_children(to_).append(self.get_node(node_));
	return self;
};

//------------------------------------------------------------------------------
//	文言変更
//------------------------------------------------------------------------------
tree_editor.prototype.rewrite_node = function( node_, line_text_ )
{
	let self = this;
	if( self.m.protect.is_callback_enabled() === true && self.m.on_rewrite_node !== null )
		self.m.on_rewrite_node(self, node_, line_text_);
	self.get_node(node_).find('> .line .text').text(line_text_);
	return self;
};

//------------------------------------------------------------------------------
//	ノードを表示
//
//	ノードを指定しなければ全ノードを表示する
//------------------------------------------------------------------------------
tree_editor.prototype.show_node = function( node_ = false )
{
	if( node_ === false )
		this.m.elm.find('.node').addClass('open');
	else
		this.get_node(node_).parents('.node').addClass('open');
	return this;
};

//------------------------------------------------------------------------------
//	子ノードを表示
//------------------------------------------------------------------------------
tree_editor.prototype.open_children = function( node_ )
{
	this.get_node(node_).addClass('open');
	return this;
};

//------------------------------------------------------------------------------
//	子ノードを非表示
//------------------------------------------------------------------------------
tree_editor.prototype.close_children = function( node_ )
{
	this.get_node(node_).removeClass('open');
	return this;
};

//------------------------------------------------------------------------------
//	子ノードの表示/非表示を切り替え
//------------------------------------------------------------------------------
tree_editor.prototype.toggle_children = function( node_ )
{
	this.get_node(node_).toggleClass('open');
	return this;
};

//------------------------------------------------------------------------------
//	アクティブ化
//------------------------------------------------------------------------------
tree_editor.prototype.active = function( node_ )
{
	this.get_node(node_).addClass('active');
	return this;
};

//------------------------------------------------------------------------------
//	非アクティブ化
//
//	ノードIDを指定しなければ全ノードを非アクティブ化する
//------------------------------------------------------------------------------
tree_editor.prototype.inactive = function( node_ = false )
{
	if( node_ === false )
		this.m.elm.find('.node').removeClass('active');
	else
		this.get_node(node_).removeClass('active');
	return this;
};
