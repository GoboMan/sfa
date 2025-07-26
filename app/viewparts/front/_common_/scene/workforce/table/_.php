/*

	workforceテーブル

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	shown : false,

	is_main : false,
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="ui_panel transparent layout_vertical_left full_horizon margin_horizon">
  <table ref="workforce_table" class="ui_list full_horizon margin_horizon">
   <thead>
    <tr class="border">
     <th class="min">日時</th>
     <th class="min">国</th>
     <th class="min">雇</th>
     <th class="min">商</th>
     <th class="min">単金</th>
     <th class="min">年齢</th>
     <th class="min">最寄駅</th>
     <th class="min">出社</th>
     <th class="">名前</th>
    </tr>
   </thead>
   <tbody ref="rows"></tbody>
  </table>
 </div>
 
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
table
{
	td
	{
		white-space : nowrap;
		overflow : hidden;
		text-overflow : ellipsis;
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
	//	メインであれば、人材一覧取得（左側に表示しているとき）
	if( self.prop('is_main') === true )
	{
		self.get_workforce_rows_with_ajax();
	}
}
</ready>

//------------------------------------------------------------------------------
//	watch
//------------------------------------------------------------------------------
<watch>
{
	shown(old_, new_)
	{
		if( new_ == true )
		{
			self.jq('workforce_table').removeClass('hide');
		}
		else
		{
			self.jq('workforce_table').addClass('hide');
		}
	}
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
		return '人材一覧';
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

	//	人材一覧取得
	get_workforce_rows_with_ajax()
	{
		ajax.post
		(
			g.workforce_actions.ajax_get_rows,
			{},
			(data_) =>
			{
				let data = JSON.parse(data_);
				let workforce_rows = data.rows;
				let pager_total = data.total;
				let pager_start_index = data.start_index;
				let pager_row_per_page = data.row_per_page;
				let pager_prev_page_no = data.prev_page_no;
				let pager_next_page_no = data.next_page_no;

				//	ページャ情報を設定する
				dbc.set('workforce_pager', 'total', pager_total);
				dbc.set('workforce_pager', 'start_index', pager_start_index);
				dbc.set('workforce_pager', 'row_per_page', pager_row_per_page);
				dbc.set('workforce_pager', 'prev_page_no', pager_prev_page_no);
				dbc.set('workforce_pager', 'next_page_no', pager_next_page_no);

				if( Object.keys(workforce_rows).length > 0 )
				{
					//	project一覧を埋め込む
					self.create_children_and_append("row", workforce_rows, "rows");

					//	dbcにデータを追加、既にある場合は上書きする
					dbc.set_list("workforce_list", workforce_rows);
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
