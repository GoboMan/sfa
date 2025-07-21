/*

	project row

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	row					: null,

	//	国籍enum
	nat_only_japan		: <?= model_project::nat_only_japan ?>,
	nat_only_foreign	: <?= model_project::nat_only_foreign ?>,
	nat_both			: <?= model_project::nat_both ?>,

	//	商流enum
	limit_direct		: <?= model_project::limit_direct ?>,

}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <tr class="row border" ref="row">
  <td class="min">{{ row.created_at ? row.created_at : '-' }}</td>
  <td class="min">{{ row.nationality ? row.nationality : '-' }}</td>
  <td class="min">{{ row.depth_limit ? row.depth_limit : '-' }}</td>
  <td class="min">{{ row.min_budget ? row.min_budget : '-' }}</td>
  <td class="min">{{ row.max_age ? row.max_age : '-' }}</td>
  <td class="min">{{ row.nearest_station ? row.nearest_station : '-' }}</td>
  <td class="min">{{ row.status ? row.status : '-' }}</td>
  <td>{{ row.name ? row.name : '-' }}</td>
 </tr>
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
	//	バインド
	let props = self.props();
	console.log(props);
	dbc.bind("project_list", props.project_id, self, "row");
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
}
</method>

//------------------------------------------------------------------------------
//	recv
//------------------------------------------------------------------------------
<recv>
{
}
</recv>
