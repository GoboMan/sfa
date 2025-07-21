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
 <div ref="root" class="root ui_panel transparent layout_vertical full">

  <div class="ui_panel layout_horizon full_horizon padding shadow">
   <button ref="btn_open_sidebar" class="ui_button info small margin_right">→</button>
   <div style="font-size: 1.2em;">Right SFA</div>
   <div class="spacer"></div>
  </div>

  <div ref="sidebar_wrapper" class="close_sidebar ui_panel transparent layout_horizon_top full">
   <?php /**** メニュー ****/ ?>
   <div ref="btn_wrapper" class="btn_wrapper ui_panel layout_vertical_left full_vertical padding_large" style="width: 300px;">
    <div class="ui_panel transparent layout_horizon full_horizon padding_bottom border_bottom">
     <div style="font-size: 1.2em;">メニュー</div>
     <div class="spacer"></div>
     <button ref="btn_close_sidebar" class="ui_button cancel small">X</button>
    </div>
    <div ref="btn_project" class="btn ui_panel transparent full_horizon margin_top_large">案件一覧</div>
    <div ref="btn_workforce" class="btn ui_panel transparent full_horizon ">人材一覧</div>
    <div ref="btn_entity" class="btn ui_panel transparent full_horizon ">取引先一覧</div>
    <div class="spacer"></div>
    <div ref="btn_logout" class="ui_panel">ログアウト</div>
   </div>

   <?php /**** コンテンツ ****/ ?>
   <div class="body_wrapper ui_panel transparent full" ref="body_wrapper">
    [[scenes]]
   </div>
  </div>

 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.root
{	
	.open_sidebar
	{	
		.btn_wrapper
		{
			//	ヘッダーの高さに合わせて下げる
			position : fixed;
			top : 0;
			left : 0;
			z-index : 100;

			box-shadow : 3px 0 6px rgba(0, 0, 0, 0.1);
		}
		.body_wrapper
		{
			margin-left : 300px;
		}
	}
	.close_sidebar
	{
		.btn_wrapper
		{
			display : none;
		}
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

	//	サイドバーを閉じる制御
	self.jq('btn_close_sidebar').on('click', function()
	{
		self.jq('sidebar_wrapper').addClass('close_sidebar');
		self.jq('sidebar_wrapper').removeClass('open_sidebar');
	});

	self.jq('btn_open_sidebar').on('click', function()
	{
		self.jq('sidebar_wrapper').addClass('open_sidebar');
		self.jq('sidebar_wrapper').removeClass('close_sidebar');
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
