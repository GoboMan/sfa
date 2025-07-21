/*

	取引先詳細シーン

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	row : null,
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div ref="detail_entity_panel" class="detail_entity_panel ui_panel padding border shadow" style="display:none; width:0px;">
  <div class="ui_panel transparent layout_horizon full_horizon margin_bottom">
   <div class="ui_heading" style="font-size:1.4em; margin:0;">取引先詳細</div>
   <div class="spacer"></div>
   <button ref="btn_delete_entity" class="ui_button warn delete margin_right">削除</button>
   <button ref="btn_back" class="ui_button cancel">キャンセル</button>
  </div>

  <?php /**** 取引先情報コンテナ ****/?>
  <div class="ui_panel transparent layout_vertical_left full padding_xlarge margin_bottom_large border radius shadow">
   <div class="ui_panel layout_horizon full_horizon padding_bottom_large" style="border-bottom:1px dashed #ccc;">
    <h2 style="font-size:1.2em;">取引先情報</h2>
    <div class="spacer"></div>
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

   <?php /**** 更新ボタン ****/?>
   <div class="ui_panel layout_vertical full padding_xlarge">
    <button ref="btn_update_entity" class="ui_button done">更新</button>
   </div>
  </div>

  <?php /**** 取引先営業コンテナ ****/?>


 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.detail_entity_panel
{
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
		self.hide_detail_panel();
	});

	//	更新
	self.jq('btn_update_entity').on('click', () =>
	{
		let params = collect_input_data(self, 'detail_entity_panel');
		let entity_id = dbc.get('selected_entity_row', 'selected').entity_id;
		params.entity_id = entity_id;

		ajax.post
		(
			g.entity_actions.ajax_update,
			params,
			(data_) =>
			{
				let new_row = JSON.parse(data_);

				//	dbc更新
				dbc.set("entities", new_row.entity_id, new_row);

				//	メッセージ表示
				ui.toast.add('取引先を更新しました');

				//	パネル非表示
				self.hide_detail_panel();
			},
			(code_, msg_) =>
			{
				ui.toast.add_error(msg_);
			}
		);
	});

	//	削除
	self.jq('btn_delete_entity').on('click', () =>
	{
		let entity_id = dbc.get('selected_entity_row', 'selected').entity_id;

		ui.dialog.popup_warn
		(
			'取引先削除',
			'取引先を削除しますか？',
			() =>
			{
				ajax.post
				(
					g.entity_actions.ajax_delete,
					{ entity_id : entity_id },
					(data_) =>
					{
						ui.toast.add('取引先を削除しました');

						let vp_entity_row = dbc.get('selected_vp_entity_row', 'selected');

						//	dbcから削除
						dbc.remove("entities", entity_id);
						dbc.unbind(self, 'row');

						//	一覧から削除
						vp_entity_row.remove();

						//	パネル非表示
						self.hide_detail_panel();
					},
					(code_, msg_) =>
					{
						ui.toast.add_error(msg_);
					}
				);
			},
			null,
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

	//	detailパネル非表示
	hide_detail_panel()
	{
		let vp_entity = viewpart_find_by_name('scene_entity');
		if( vp_entity.prop('show_detail_panel') === false ) return;

		//	アニメーションで非表示にする
		self.jq('detail_entity_panel').stop().animate({ width : 0 }, 100, () => { self.jq('detail_entity_panel').css('display', 'none');} );

		vp_entity.prop('show_detail_panel', false);
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
