/*

	projectシーン

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
 <div class="project ui_panel transparent layout_vertical_left full" ref="project">
  <div class="ui_panel transparent layout_horizon full_horizon margin_vertical">
   <div class="spacer"></div>
   <button class="ui_button info" ref="btn_add_project">+ 案件登録</button>
  </div>

  <?php /**** テーブル ****/ ?>
  <table class="ui_list full_horizon">
   <thead>
    <tr>
     <th class="min"></th>
     <th></th>
     <th></th>
     <th class="min"></th>
     <th class="min"></th>
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
	//	todo一覧を取得して、viewにマウントする
	self.get_todo_rows_with_ajax();

	//	todo作成
	self.jq('btn_add_project').on('click', () =>
	{
		//	ポップアップ
		ui.dialog.popup
		(
			'.create',
			{
				'.ui_button.close' : () => null,
				'.ui_button.done' : () =>
				{
					let dlg = self.pref('create').jq('create');

					let title = dlg.find('input[name="title"]').val();
					let description = dlg.find('input[name="description"]').val();
					let status = dlg.find('select[name="status"]').val();

					ajax.post
					(
						g.actions.ajax_create_todo,
						{
							title : title,
							description : description,
							status : status,
						},
						(data_) =>
						{
							ui.toast.add('succeed', 'notice');

							//	追加したレコードを一覧に追加
							let new_row = JSON.parse(data_);
							self.create_child_and_append("row", new_row, "rows");

							//	dbcにデータを追加、既にある場合は上書きする
							dbc.set("todos", new_row.todo_id, new_row);

							//	ダイアログの中身を初期状態にする
							dlg.find('input[name="title"]').val('');
							dlg.find('input[name="description"]').val('');
							dlg.find('select[name="status"]').val('1');
						},
						(code_, msg_) =>
						{
							ui.toast.add_error(msg_, 'error');
						}
					);
				}
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
		return g.url_base;
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

	//	todo一覧取得
	get_todo_rows_with_ajax()
	{
		ajax.post
		(
			g.actions.ajax_get_rows,
			{},
			(data_) =>
			{
				//	todo一覧を取得し、viewにマウントする
				let rows = JSON.parse(data_);

				//	todo一覧を埋め込む
				self.create_children_and_append("row", rows, "rows");

				//	dbcにデータを追加、既にある場合は上書きする
				dbc.set_list("todos", rows);
			},
			(code_, msg_) =>
			{
				ui.toast.add(msg_, 'error');
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
