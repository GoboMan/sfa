/*

	取引先レコード

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	row			: null,

	//	このviewpartが作成された瞬間は、
	//	entity_id : 1,
	//	name : 'test',
	//	user_id : 1, ...
	//	のように値が設定される。
	//	なのでinitの時のみ、そのentity_idを使用してdbcとバインドする
	//	その後は、rowの値を使用する
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <tr class="row border" ref="row">
  <td>{{ row.name ? row.name : '-' }}</td>
  <td class="min">{{ row.user_name ? row.user_name : '-' }}</td>
  <td class="min">{{ row.updated_at ? change_unixts_to_ymd(row.updated_at) : '-' }}</td>
 </tr>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.row
{
	cursor : pointer;
}
.row:hover
{
	//	todo : ホバー時に背景色を変更する
	background-color : gray;
}
</style>

//------------------------------------------------------------------------------
//	init
//------------------------------------------------------------------------------
<init>
{
	//	dbcに登録されているentitiesデータ内、entity_idが一致するrowを
	//	自身のrowにバインドする
	let entity_id = self.prop('entity_id');
	dbc.bind("entity_list", entity_id, self, "row");

}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
	//	詳細表示
	self.jq('row').on('click', () =>
	{
		self.show_detail_panel(self.prop('row').entity_id);
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


	//	createパネル表示
	show_detail_panel(entity_id_, anim_ = true)
	{
		//	entity_idがない場合は、パネルを表示しない
		if( entity_id_ == undefined || entity_id_ == null ) return null;

		let vp_entity = self.parent();

		//	サイズ調整
		vp_entity.adjust_create_detail_panel_size();

		//	パネル表示
		if( anim_ === true )
		{
			if( vp_entity.prop('show_detail_panel') === false )
			{
				vp_entity.prop('jq_detail_entity_panel').css('display', 'block').stop().animate({ width : vp_entity.prop('scalefull_width') }, 200);
			}
		}
		else
		{
			vp_entity.prop('jq_detail_entity_panel').css('display', 'block');
		}
		vp_entity.prop('show_detail_panel', true);

		//	データを取得
		ajax.post
		(
			g.entity_actions.ajax_detail,
			{ entity_id : entity_id_ },
			(data_) =>
			{
				let row = JSON.parse(data_);
				let vp_detail = vp_entity.pref('detail_entity_panel');

				//	データをフォームに反映
				apply_vals_to_form(vp_detail, 'detail_entity_panel', row);

				//	選択された取引先をdbcに設定する
				dbc.set("selected_entity_row", 'selected', row);
				dbc.set("selected_vp_entity_row", 'selected', self);
			},
			(code_, msg_) =>
			{
				ui.toast.add_error(msg_);
			}
		);
	},
}
</method>

//------------------------------------------------------------------------------
//	recv
//------------------------------------------------------------------------------
<recv>
{
}
</recv>
