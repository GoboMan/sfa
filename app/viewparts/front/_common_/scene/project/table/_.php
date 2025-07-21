/*

	projectテーブル

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
 <div class="ui_panel transparent layout_vertical_left full_horizon margin_horizon">
  <table ref="project_table" class="ui_list full_horizon">
   <thead>
    <tr class="border">
     <th class="min">日時</th>
     <th class="min">国</th>
     <th class="min">商</th>
     <th class="min">単金</th>
     <th class="min">年齢</th>
     <th class="min">最寄駅</th>
     <th class="min">出社</th>
     <th class="">件名</th>
    </tr>
   </thead>
   <tbody ref="rows"></tbody>
  </table>

  [[pager module="project"]]
 </div>

</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>

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

	//	案件一覧取得
	self.get_project_rows_with_ajax();
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
		return g.url_base;
	},

	//	タイトル取得
	scene_title()
	{
		return '案件一覧';
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

	//	案件一覧取得
	get_project_rows_with_ajax()
	{
		ajax.post
		(
			g.project_actions.ajax_get_rows,
			{},
			(data_) =>
			{
				let data = JSON.parse(data_);
				let project_rows = data.rows;
				let pager_total = data.total;
				let pager_start_index = data.start_index;
				let pager_row_per_page = data.row_per_page;
				let pager_prev_page_no = data.prev_page_no;
				let pager_next_page_no = data.next_page_no;

				//	ページャ情報を設定する
				dbc.set('project_pager', 'total', pager_total);
				dbc.set('project_pager', 'start_index', pager_start_index);
				dbc.set('project_pager', 'row_per_page', pager_row_per_page);
				dbc.set('project_pager', 'prev_page_no', pager_prev_page_no);
				dbc.set('project_pager', 'next_page_no', pager_next_page_no);

				if( Object.keys(project_rows).length > 0 )
				{
					//	project一覧を埋め込む
					self.create_children_and_append("row", project_rows, "rows");

					//	dbcにデータを追加、既にある場合は上書きする
					dbc.set_list("project_list", project_rows);
				}
				else
				{
					//	nodata用のviewpartを埋め込む
					self.create_child_and_append("row_nodata", {}, "rows");
				}

			},
			(code_, msg_) =>
			{
				console.log(code_, msg_);
				ui.toast.add(msg_, 'error');
			}
		)
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
