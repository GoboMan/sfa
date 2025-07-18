/*

	todo row

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
 <tr class="row border" ref="row">
  <td class="min">{{ row.todo_id ? row.todo_id : '-' }}</td>
  <td>{{ row.title ? row.title : '-' }}</td>
  <td>{{ row.description ? row.description : '-' }}</td>
  <td class="min">{{ row.status ? row.status : '-' }}</td>
  <td class="min">
   <button class="ui_button info small" ref="btn_edit">edit</button>
   <button class="ui_button warn small" ref="btn_delete">delete</button>
  </td>
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
	dbc.bind("todos", props.todo_id, self, "row");
}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
	//	todo編集
	self.jq('btn_edit').on('click', function()
	{
		let prop = self.prop('row');

		//	親パーツを取得し、子パーツである編集ダイアログを取得
		let todo = viewpart_find_by_name('scene_todo');
		let dlg = todo.pref('edit').jq('edit');

		//	値をセット
		dlg.find('input[name="title"]').val(prop.title);
		dlg.find('input[name="description"]').val(prop.description);
		dlg.find('select[name="status"]').val(prop.status);

		//	ポップアップ
		ui.dialog.popup
		(
			'.edit',
			{
				'.ui_button.close' : () => null,
				'.ui_button.done' : () =>
				{
					let title = dlg.find('input[name="title"]').val();
					let description = dlg.find('input[name="description"]').val();
					let status = dlg.find('select[name="status"]').val();

					//	更新
					ajax.post
					(
						g.actions.ajax_update_todo,
						{
							todo_id : prop.todo_id,
							title : title,
							description : description,
							status : status,
						},
						(data_) =>
						{
							ui.toast.add('succeed', 'notice');

							//	dbcを更新
							dbc.set
							(
								'todos',
								prop.todo_id,
								{
									todo_id : prop.todo_id,
									title : title,
									description : description,
									status : status,
								}
							);

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

	//	todo削除
	self.jq('btn_delete').on('click', function()
	{
		ui.dialog.popup_warn
		(
			'Delete Todo',
			'Are you sure you want to delete this todo?',
			() =>
			{
				ajax.post
				(
					g.actions.ajax_delete_todo,
					{ todo_id : prop.todo_id },
					(data_) =>
					{
						ui.toast.add('succeed', 'notice');

						//	dbcから削除, bindを解除
						dbc.remove('todos', prop.todo_id);
						dbc.unbind(self, 'row');

						//	一覧から削除
						self.remove();

					},
					(code_, msg_) =>
					{
						ui.toast.add_error(msg_, 'error');
					}
				);
			},
			() => null,
			'Delete',
			'Cancel',
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
