/*

	取引先レコード

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
 <div ref="create_entity_panel" class="create_entity_panel ui_panel padding border shadow" style="display:none; width:0px;">
  <?php /**** 取引先情報コンテナ ****/?>
  <div class="ui_panel transparent layout_vertical_left full padding_xlarge border radius shadow">
   <div class="ui_panel layout_horizon full_horizon padding_bottom_large" style="border-bottom:1px dashed #ccc;">
    <h1 style="font-size:1.5em;">取引先登録</h1>
    <div class="spacer"></div>
    <button class="ui_button cancel" ref="btn_back">戻る</button>
   </div>

   <table class="ui_list full_horizon" style="margin-top:10px;">
   <?php /**** 営業担当 ****/?>
   <tr>
    <td>
     <div class="input_wrapper margin_right">
      <div>営業担当</div>
      <div>
       <select class="ui_select" name="user_id">
        <?= crow_html::make_option_tag_with_obj(model_user::create_array(), 'name') ?>
       </select>
      </div>
     </div>
    </td>
   </tr>

    <?php /**** 取引先情報 ****/?>
    <tr>
     <td>
      <div class="input_wrapper margin_right">
       <div>取引先名</div>
       <div><input type="text" class="ui_text" name="name"></div>
      </div>
     </td>
     <td>
      <div class="input_wrapper margin_right">
       <div>取引先名（カナ）</div>
       <div><input type="text" class="ui_text" name="name_kana"></div>
      </div>
     </td>
    </tr>
    <?php /**** ランク ****/?>
    <tr>
     <td>
      <div class="input_wrapper margin_right">
       <div>案件ランク</div>
       <div>
        <select class="ui_select" name="upper_rank">
         <?= crow_html::make_option_tag(model_entity::get_upper_rank_map()) ?>
        </select>
       </div>
      </div>
     </td>
     <td>
      <div class="input_wrapper margin_right">
       <div>人材ランク</div>
       <div>
        <select class="ui_select" name="lower_rank">
         <?= crow_html::make_option_tag(model_entity::get_lower_rank_map()) ?>
        </select>
       </div>
      </div>
     </td>
    </tr>
    <?php /**** 取引情報 ****/?>
    <tr>
     <td>
      <div class="input_wrapper margin_right">
       <div>取引ステータス</div>
       <div>
        <select class="ui_select" name="deal_status">
         <?= crow_html::make_option_tag(model_entity::get_deal_status_map()) ?>
        </select>
       </div>
      </div>
     </td>
     <td></td>
    </tr>
    <tr>
     <td colspan="2">
      <div class="input_wrapper margin_right">
       <div>取引停止理由</div>
       <div><textarea class="ui_text" name="deal_stop_reason"></textarea></div>
      </div>
     </td>
    </tr>
   </table>

   <?php /**** 登録ボタン ****/?>
   <div class="ui_panel layout_vertical full padding_xlarge">
    <button ref="btn_create_entity" class="ui_button done">登録</button>
   </div>

  </div>

 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.create_entity_panel
{
	height : 100%;
	position : absolute;
	right : 0;
	top : 0;
	z-index : 2;
}

.input_wrapper
{
	display : flex;
	flex-direction : column;
	align-items : flex-start;

	&.margin_right
	{
		margin-right : 10px;
	}

	> div
	{
		width : 100%;

		input, select
		{
			width : 100%;
		}
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
	//	戻る
	self.jq('btn_back').on('click', () =>
	{
		self.hide_create_panel();
	});

	//	登録
	self.jq('btn_create_entity').on('click', () =>
	{
		let params = collect_input_data(self, 'create_entity_panel');

		ajax.post
		(
			g.entity_actions.ajax_create,
			params,
			(data_) =>
			{
				let new_row = JSON.parse(data_);
				let vp_entity = viewpart_find_by_name('scene_entity');

				//	一覧とdbcに追加
				vp_entity.create_child_and_append("row", new_row, "rows");
				dbc.set("entities", new_row.entity_id, new_row);

				//	メッセージ表示
				ui.toast.add('取引先を登録しました');

				//	フォーム初期化
				self.jq('create_entity_panel').find('input, textarea').val('');
				self.jq('create_entity_panel').find('select').val(1);

				//	パネル非表示
				self.hide_create_panel();
				console.log("最後までいけた");
			},
			(code_, msg_) =>
			{
				ui.toast.add_error(msg_);
			}
		);

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
	//	復元パス取得
	scene_path()
	{

	},

	//	タイトル取得
	scene_title()
	{
		return 'Todo';
	},

	//	休止時
	scene_suspend()
	{
	},

	//	再開時
	scene_resume()
	{
	},

	//	破棄時
	scene_destroy()
	{
	},

	//	createパネル非表示
	hide_create_panel()
	{
		let vp_entity = viewpart_find_by_name('scene_entity');
		if( vp_entity.prop('shown_create_panel') === false ) return;

		//	アニメーションで非表示にする
		g.jq_create_entity_panel.stop().animate({ width : 0 }, 100, () => { g.jq_create_entity_panel.css('display', 'none');} );

		vp_entity.prop('shown_create_panel', false);
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
