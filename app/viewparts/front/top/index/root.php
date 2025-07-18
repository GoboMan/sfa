/*

	SPA ルート

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
 <div class="root ui_panel transparent layout_horizon_top full" ref="root">

  <?php /**** メニュー ****/ ?>
  <div class="ui_panel layout_vertical_left full_vertical padding_large" style="width: 300px;">
   <div class="ui_panel transparent full_horizon padding_vertical border_bottom">Right SFA</div>
   <div ref="btn_project" class="ui_panel transparent full_horizon padding_vertical margin_top_large">案件一覧</div>
   <div ref="btn_workforce" class="ui_panel transparent full_horizon padding_vertical ">人材一覧</div>
   <div ref="btn_entity" class="ui_panel transparent full_horizon padding_vertical ">取引先一覧</div>
   <div class="spacer"></div>
   <div ref="btn_logout" class="ui_panel">ログアウト</div>
  </div>

  <?php /**** コンテンツ ****/ ?>
  <div class="body_wrap ui_panel transparent full padding" ref="body_wrap">
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
	$(self.ref('btn_todo')).on('click', () => { g.scenes.replace("scene_todo"); });
	$(self.ref('btn_user')).on('click', () => { g.scenes.replace("scene_user"); });

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
