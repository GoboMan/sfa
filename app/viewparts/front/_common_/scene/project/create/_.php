/*

	Project作成

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
 <div ref="create_project_panel" class="create_project_panel ui_panel padding_xlarge border shadow" style="display:none; width:0px;">
  <div class="ui_panel transparent layout_horizon full_horizon margin_bottom">
   <div class="ui_heading" style="font-size:1.4em; margin:0;">案件登録</div>
   <div class="spacer"></div>
   <button class="ui_button cancel" ref="btn_back">キャンセル</button>
  </div>
  <?php /**** 案件情報コンテナ ****/?>
  <div class="ui_panel transparent layout_vertical_left full padding_xlarge margin_bottom_large border radius shadow">
   <div class="ui_panel layout_horizon full_horizon padding_bottom_large" style="border-bottom:1px dashed #ccc;">
    <h2 style="font-size:1.2em;">案件情報</h2>
    <div class="spacer"></div>
   </div>

   <table class="ui_list full_horizon" style="margin-top:10px;">
   <tr>
    <td>
     <div>RA営業担当</div>
     <div>
      <select class="ui_select" name="user_id"><?= crow_html::make_option_tag_with_obj(model_user::create_array(), 'name') ?></select>
     </div>
    </td>
    <td>
     <div>案件名</div>
     <div><input type="text" class="ui_text" name="name"></div>
    </td>
   </tr>

   <tr>
    <td>
     <?php /**** todo : 一覧テーブルをダイアログで表示し、検索及び選択できるようにする ****/ ?>
     <div>取引先</div>
     <div><select class="ui_select" name="entity_id"><?= crow_html::make_option_tag_with_obj(model_entity::create_array(), 'name') ?></select></div>
    </td>
    <td>
     <div>業界</div>
     <div><select class="ui_select" name="industry_id"><?= crow_html::make_option_tag_with_obj(model_industry::create_array(), 'name') ?></select></div>
    </td>
   </tr>

   <tr>
    <td>
     <div>都道府県</div>
     <div>
      <select class="ui_select" name="prefecture_id"><?= crow_html::make_option_tag_with_obj(model_prefecture::create_array(), 'name') ?></select>
     </div>
    </td>
    <td>
     <div>稼働開始日</div>
     <div><input type="date" class="ui_text" name="start_date"></div>
    </td>
   </tr>

   <tr>
    <td>
     <div>契約形態</div>
     <div><select class="ui_select" name="contract_type"><?= crow_html::make_option_tag(model_project::get_contract_type_map()) ?></select></div>
    </td>
   </tr>

   <tr>
    <td>
     <div>募集ステータス</div>
     <div><select class="ui_select" name="hiring_status"><?= crow_html::make_option_tag(model_project::get_hiring_status_map()) ?></select></div>
    </td>
    <td>
     <div>流入経路</div>
     <div><select class="ui_select" name="source"><?= crow_html::make_option_tag(model_project::get_source_map()) ?></select></div>
    </td>
   </tr>

   <tr>
    <td>
     <div>最寄駅</div>
     <div><input type="text" class="ui_text" name="nearest_station"></div>
    </td>
   </tr>

   <tr>
    <td>
     <div>予算下限</div>
     <div><input type="number" class="ui_text" name="min_budget"></div>
    </td>
    <td>
     <div>予算上限</div>
     <div><input type="number" class="ui_text" name="max_budget"></div>
    </td>
   </tr>

   <tr>
    <td>
     <div>年齢下限</div>
     <div><input type="number" class="ui_text" name="min_age"></div>
    </td>
    <td>
     <div>年齢上限</div>
     <div><input type="number" class="ui_text" name="max_age"></div>
    </td>
   </tr>

    <tr>
     <td>
      <div>商流制限</div>
      <div><select class="ui_select" name="depth_limit"><?= crow_html::make_option_tag(model_project::get_depth_limit_map()) ?></select></div>
     </td>
     <td>
      <div>性別</div>
      <div><select class="ui_select" name="gender"><?= crow_html::make_option_tag(model_project::get_gender_map()) ?></select></div>
     </td>
    </tr>

    <tr>
     <td>
      <div>出社スタイル</div>
      <div><select class="ui_select" name="project_work_style"><?= crow_html::make_option_tag(model_project::get_work_style_map()) ?></select></div>
     </td>
     <td>
      <div>国籍</div>
      <div><select class="ui_select" name="nationality"><?= crow_html::make_option_tag(model_project::get_nationality_map()) ?></select></div>
     </td>
    </tr>

    <tr>
     <td colspan="2">
      <div>メール本文</div>
      <div><textarea class="ui_text" name="raw_content"></textarea></div>
     </td>
    </tr>

   </table>

   <?php /**** 登録ボタン ****/?>
   <div class="ui_panel layout_vertical full padding_xlarge">
    <button ref="btn_create_project" class="ui_button done">登録</button>
   </div>
  </div>

 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.create_project_panel
{
	position : absolute;
	right : 0;
	top : 0;
	z-index : 2;

	td
	{
		width : 50%;

		> div
		{
			width : 100%;

			input, select, textarea
			{
				width : 100%;
			}
		}

		textarea
		{
			height : 400px;
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
	self.jq('btn_create_project').on('click', () =>
	{
		let params = collect_input_data(self, 'create_project_panel');
		ajax.post
		(
			g.project_actions.ajax_create,
			params,
			(data_) =>
			{
				let new_row = JSON.parse(data_);
				let vp_project_table = viewpart_find_by_name('scene_project_table');

				//	todo : row nodataがあれば削除
				console.log(vp_project_table);

				//	一覧とdbcに追加
				dbc.set("project_list", new_row.project_id, new_row);
				vp_project_table.create_child_and_append("row", new_row, "rows");

				//	メッセージ表示
				ui.toast.add('案件を登録しました');

				//	フォーム初期化
				self.jq('create_project_panel').find('input, textarea').val('');
				self.jq('create_project_panel').find('select').val(1);

				//	パネル非表示
				self.hide_create_panel();
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
		let vp_project = self.parent();
		if( vp_project.prop('show_create_panel') === false ) return;

		//	アニメーションで非表示にする
		self.jq('create_project_panel').stop().animate({ width : 0 }, 100, () => { self.jq('create_project_panel').css('display', 'none');} );

		vp_project.prop('show_create_panel', false);
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
