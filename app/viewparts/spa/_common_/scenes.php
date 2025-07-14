/*

	シーンマネージャ

	各シーンパーツには下記メソッドを必須とする
	・scene_path

	<titleにサブタイトルをセットする場合には下記を実装しておく
	・scene_title

	各シーンパーツには必要であれば下記メソッドを実装しておくと
	イベントがコールバックされる
	・scene_suspend
	・scene_resume
	・scene_destroy

	例）dispatch()でルート文字列からシーンを判断して指定された処理を実行できる

		g.scenes.dispatch
		(
			crow_request::get_route_path()で取得した文字列,
			{
				"/tag/:tag" : function(args_)
				{
					g.scenes.push("timeline", args_);
				},
				"/:account" : function(args_)
				{
					g.scenes.push("timeline", Object.assign({profile:true}, args_));
				},
				....
			},
			function(route_)
			{
				//	ルールに一致しない場合にはこちらが呼ばれる
				ui.popup_error('', '不正なアクセス');
			}
		);

		例えば"/tag/abc"がルート文字列だとすると、
		timelineシーンが tag=abc で初期化されてスタックに積まれる
		"/fujisaki"がルート文字列だとすると、
		timelineシーンが profile=true、account = fujisaki で初期化されてスタックに積まれる


	シーン読み込み時などで指定した処理を実行させることが可能

		//	シーンのロード開始時
		g.scenes.on_load(function(){});

		//	シーンのロード完了時
		g.scenes.on_loaded(function(){});

		//	シーンの破棄時
		g.scenes.on_uload(function(){});

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="scene_mng">
  <div class="client" ref="client"></div>
  <div class="render" ref="render"></div>
 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.scene_mng
{
	height : 100%;

	> .client
	{
		position : relative;
		width : 100%;
		height : 100%;
		left : 0;
		top : 0;
	}

	//	裏画面
	> .render
	{
		position : absolute;
		width : 100%;
		height : 100%;
		left : 0;
		top : 0;
		z-index : -1;
	}
}
</style>

//------------------------------------------------------------------------------
//	init
//------------------------------------------------------------------------------
<init>
{
	//	制御用
	self.v =
	{
		stack : [],
		scroll_poses : {},
		index : -1,
		on_load : null,
		on_loaded : null,
		on_unload : null
	};

	//	ヒストリ制御初期化
	window.addEventListener("popstate", function(e_)
	{
		if( e_.state != null ) self.pop(e_.state);
		else history.back();
		return;
	});
}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
}
</ready>

//------------------------------------------------------------------------------
//	watch
//------------------------------------------------------------------------------
<watch>
{
}
</watch>

//------------------------------------------------------------------------------
//	method
//------------------------------------------------------------------------------
<method>
{
	//	ディスパッチ
	dispatch(route_, rules_, default_)
	{
		let trim_slashes = function(str_)
		{
			let str = str_.startsWith("/") === true ?
				str_.substring(1) : str_;
			return str.endsWith("/") === true ?
				str.substring(0, str.length - 1) : str;
		};

		//	"/" でパーツ分解
		let route_parts = trim_slashes(route_).split("/");
		for( let rule_ in rules_ )
		{
			let args = {};
			let rule_parts = trim_slashes(rule_).split("/");
			if( rule_parts.length != route_parts.length ) continue;
			let unmatch = false;
			for( let part_index = 0; part_index < rule_parts.length; part_index++ )
			{
				let rule_part = rule_parts[part_index];
				if( rule_part.startsWith(":") === true )
					args[rule_part.substring(1)] = route_parts[part_index];
				else if( rule_part != route_parts[part_index] )
				{
					unmatch = true;
					break;
				}
			}
			if( unmatch === false )
			{
				//	一致したのでコールバック
				rules_[rule_](args);
				return;
			}
		}

		//	見つからなければデフォルトコールバック
		default_();
	},

	//	シーンを積む
	push(scene_name_, args_ = {}, replace_ = false)
	{
		//	裏画面にシーンを作成
		let child = self.create_child_and_append(scene_name_, args_, "render");
		if( child === null )
		{
			console.log("not found " + scene_name_);
			return null;
		}

		//	スクロール位置リストを更新
		let top_scene = self.get_top_scene();
		if( top_scene !== null )
			self.v.scroll_poses[top_scene.uid()] = window.scrollY;

		//	ロード前フック
		if( self.v.on_load !== null )
			self.v.on_load(child);

		//	DOMマウントまで待ってから遷移する
		let replace = replace_;
		child.on_preready(function(child_)
		{
			//	ロード後フック
			if( self.v.on_loaded !== null )
				self.v.on_loaded(child_);

			//	1画面以上が積まれている場合、既存画面にイベント発行
			if( self.v.stack.length > 0 )
			{
				//	現在のインデックスより後に画面が積まれていた場合は逆順で破棄する
				for( let index = self.v.stack.length - 1; index > self.v.index; index-- )
				{
					let scene = self.v.stack[index];
					if( self.v.on_unload !== null )
						self.v.on_unload(scene);
					exists_object_item(scene, 'scene_destroy', func => func());

					scene.remove();
					self.v.stack.splice(index, 1);
				}

				//	現在の画面はsuspend
				let scene = self.v.stack[self.v.index];
				exists_object_item(scene, 'scene_suspend', func => func());
				$(scene.root()).addClass('hide');
			}

			//	新しい画面にresume
			exists_object_item(child_, 'scene_resume', func => func());

			//	表画面へフリップ
			child_.change_parent_part(self, 'client');

			if( replace === true )
			{
				if( self.v.stack.length > 0 )
				{
					let last_scene = self.v.stack.pop();
					exists_object_item(last_scene, 'scene_suspend', func => func());
					last_scene.remove();
				}
				self.v.stack.push(child_);
				history.replaceState({uid: child_.uid()}, null, child_.scene_path());
				exists_object_item(child_, 'scene_title', func => document.title = func());
				exists_object_item(child_, 'scene_description', func =>
				{
					let desc = $("meta[name ='description']");
					if( desc.get().length <= 0 ) $('<meta name="description">')
						.attr('content', func()).appendTo($(document.head));
					else desc.attr('content', func());
				});
			}
			else
			{
				self.v.stack.push(child_);
				history.pushState({uid: child_.uid()}, null, child_.scene_path());
				exists_object_item(child_, 'scene_title', func => document.title = func());
				exists_object_item(child_, 'scene_description', func =>
				{
					let desc = $("meta[name ='description']");
					if( desc.get().length <= 0 ) $('<meta name="description">')
						.attr('content', func()).appendTo($(document.head));
					else desc.attr('content', func());
				});
				self.v.index++;
			}
		});

		//	最上部へスクロール
		window.scrollTo(0,0);
		return child;
	},

	//	シーンはそのままでURLのみ変更する。
	//	戻った場合には、変更前のURLで同じシーンに対して scene_resume() がコールバックされる。
	//	戻った時にpropに設定するパラメータをback_props_に連想配列で指定する
	push_only_url(new_scene_url_, back_props_ = {})
	{
		let top = self.get_top_scene();
		let rep_uid = top === null ? -999 : top.uid();
		history.pushState({uid: rep_uid, back_props:back_props_}, null, new_scene_url_);
	},

	//	シーンの置き換え
	replace(scene_name_, args_ = {})
	{
		self.push(scene_name_, args_, true);
	},

	//	シーンはそのままでURLのみ変更する。
	//	push_only_urlとは異なり、スタックには積まずにアドレスバーのURLのみ変更する
	replace_only_url(scene_url_, back_props_ = {})
	{
		let top = self.get_top_scene();
		let rep_uid = top === null ? -999 : top.uid();
		history.replaceState({uid: rep_uid, back_props:back_props_}, null, scene_url_);
	},

	//	シーン再開時にpropにセットする値が指定されている場合はpropを変更する
	resume_back_props(part_)
	{
		let state = history.state;
		exists_object_item(state, 'back_props', back_props_ =>
		{
			array_each(back_props_, (v_, i_) =>
			{
				part_.prop(i_, v_);
			});
		});
	},

	//	シーンを一つ戻す/進む
	pop(state_ = null)
	{
		//	state_がnullなら単純なhistory.back
		if( state_ === null )
		{
			history.back();
			return;
		}

		//	URLのみ積まれていた場合には、同シーンへresume発行
		if( state_.uid == -999 )
		{
			let topscene = self.get_top_scene();
			array_each(state_.backprops, (v_, k_) => topscene.prop(k_, v_));
			exists_object_item(topscene, 'scene_resume', func => func());
			return;
		}

		//	以下はブラウザの戻る/進む両方で発生する可能性あり

		//	シーンが積まれていないなら何もしない
		if( self.v.index < 1 ) return;
		if( self.v.stack.length <= 1 ) return;

		//	スクロール位置リストを更新
		let scene = self.v.stack[self.v.index];
		self.v.scroll_poses[scene.uid()] = window.scrollY;

		//	現在のシーンをsuspend
		exists_object_item(scene, 'scene_suspend', func => func());
		$(scene.root()).addClass('hide');

		//	popされたシーンを表示
		for( index = 0; index < self.v.stack.length; index++ )
		{
			scene = self.v.stack[index];
			if( scene.uid() == state_.uid )
			{
				self.v.index = index;
				exists_object_item(scene, 'scene_resume', func => func());
				$(scene.root()).removeClass('hide');

				let title = scene.scene_title();
				if( title === null ) title = "";
				title = title == "" ? g.title_prefix : (title + "|" + g.title_prefix);
				document.title = title;

				//	スクロール位置を戻す
				exists_object_item(self.v.scroll_poses, scene.uid(), pos_ =>
				{
					setTimeout(() => window.scrollTo(0, pos_), 200);
				});
				break;
			}
		}
	},

	//	現在積まれているシーンの数を取得
	get_count()
	{
		return self.v.stack.length;
	},

	//	一番上に積まれているシーンを取得する
	get_top_scene()
	{
		return self.v.index >= 0 ?
			self.v.stack[self.v.index] :
			null
			;
	},

	//	一番上に積まれたものを0として、指定したインデックスのシーンを取得する
	get_scene_at(index_)
	{
		let index = self.v.index - index_;
		return index >= 0 ? self.v.stack[index] : null;
	},

	//	すべてのシーンを削除する
	clear()
	{
		console.log("not implemented");
	},

	//	イベントフック設定
	on_load(func_)
	{
		self.v.on_load = func_;
		return self;
	},
	on_loaded(func_)
	{
		self.v.on_loaded = func_;
		return self;
	},
	on_unload(func_)
	{
		self.v.on_unload = func_;
		return self;
	}
}
</method>

//------------------------------------------------------------------------------
//	recv
//------------------------------------------------------------------------------
<recv>
{
}
</recv>
