/*

	TOP ルート

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	show_sidebar : true,
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="root ui_panel transparent layout_horizon_top full" ref="root">

  <?php /**** メニュー ****/ ?>
  <div ref="btn_wrapper" class="ui_panel layout_vertical_left full_vertical padding_large" style="width: 300px;">
   <div class="ui_panel transparent full_horizon border_bottom">Right SFA</div>
   <div ref="btn_project" class="btn ui_panel transparent full_horizon margin_top_large">案件一覧</div>
   <div ref="btn_workforce" class="btn ui_panel transparent full_horizon ">人材一覧</div>
   <div ref="btn_entity" class="btn ui_panel transparent full_horizon ">取引先一覧</div>
   <div class="spacer"></div>
   <div ref="btn_logout" class="ui_panel">ログアウト</div>
   <div ref="btn_toggle_sidebar"></div>
  </div>

  <?php /**** コンテンツ ****/ ?>
  <div class="body_wrap ui_panel transparent full" ref="body_wrap">
   [[scenes]]
  </div>

 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.root
{
	position : relative;

	.btn_wrapper
	{
		position : fixed;
		top : 0;
		left : 0;
		z-index : 100;
	}
	.btn
	{
		border-radius: 20px;
		padding : 10px 20px;
		margin-bottom: 10px;
		&.selected
		{
			background-color: #024;
			color: #fff;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4), 0 6px 12px rgba(0, 0, 0, 0.4);
		}
	}
	.btn:hover
	{
		cursor: pointer;
		background-color: #f2f2f2;
		color: #444;
	}

}

</style>

//------------------------------------------------------------------------------
//	init
//------------------------------------------------------------------------------
<init>
{

}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
	//	シーンビルドを行う
	self.build_scene();

	//	画面切り替え制御
	let cols = ['project', 'workforce', 'entity'];
	array_each(cols, (col) =>
	{
		self.jq('btn_' + col).on('click', () => g.scenes.replace("scene_" + col));
	});

	//	サイドバーのボタン背景色切り替え
	let btn_wrapper = self.jq('btn_wrapper');
	btn_wrapper.find('.btn').on('click', function()
	{
		btn_wrapper.find('.btn').removeClass('selected');
		$(this).addClass('selected');
	});

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
	//	シーンビルド
	build_scene()
	{
		//	シーン管理の初期化とページディスパッチ
		(g.scenes = self.pref('scenes')).dispatch
		(
			g.access_path,
			{
				"/" : function(args_)
				{
					g.scenes.push("scene_project", args_);
				},
				"/workforce" : function(args_)
				{
					g.scenes.push("scene_workforce", args_);
				},
				"/entity" : function(args_)
				{
					g.scenes.push("scene_entity", args_);
				},
			},

			//	ルートがマッチしなければNotFoundへ。
			function()
			{
				g.scenes.push("scene_notfound");
			}
		);
	}
}
</method>

//------------------------------------------------------------------------------
//	recv
//------------------------------------------------------------------------------
<recv>
{
	//	エラーハンドラ
	//	paramasは、[code:コード, data:データ, callback:closeコールバック(null可)] を指定する
	error(sender_, params_)
	{
		ui.dialog.popup_error("error", params_[0], params_[1]);
		return true;
	},

	//	メッセージハンドラ
	//	paramsは、[data:データ, callback:closeコールバック(null可)] を指定する
	message(sender_, params_)
	{
		ui.dialog.popup_message("message", params_[0], params_[1]);
		return true;
	}
}
</recv>
