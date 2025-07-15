var g={};

function init(opt_)
{
	g=opt_;

	$('#create_btn').on('click', ()=>
	{
		ui.dialog.popup(
			'#create_dlg',
			{
				'.ui_button.close' : null,
				'.ui_button.done': ()=>
				{
					let dlg = $('#create_dlg');
					let name= dlg.find('[name="name"]').val();
					let login_id = dlg.find('[name="login_id"]').val();
					let login_pw = dlg.find('[name="login_pw"]').val();
					
					ajax.post
					(
						g.actions.create,
						{
							name : name,
							login_id : login_id,
							login_pw : login_pw,
						},
						(data_) =>
						{
							ui.dialog.popup_message('完了', '新しい管理者が登録されました。',
								() => location.reload());
						},
						(msg_, code_) =>
						{
							ui.toast.add_error(msg_);
						}
					);
				}
			}
		); 
	});

	$('.edit_btn').on('click', function()
		{
			let admin_id = $(this).attr('admin_id');
			let name = $(this).attr('name');
			let login_id = $(this).attr('login_id');
			let login_pw = $(this).attr('login_pw');

			console.log(admin_id);

			let dlg = $('#create_dlg');
			dlg.find('[name="name"]').val(name);
			dlg.find('[name="login_id"]').val(login_id);
			dlg.find('[name="login_pw"]').val(login_pw);


			ui.dialog.popup
			(
				'#create_dlg',
				{
					'.ui_button.close' : null,
					'.ui_button.done': ()=>
					{
						let dlg = $('#create_dlg');
						let name = dlg.find('[name="name"]').val();
						let login_id = dlg.find('[name="login_id"]').val();
						let login_pw = dlg.find('[name="login_pw"]').val();
						
						ajax.post
						(
							g.actions.update,
							{
								admin_id : admin_id,
								name : name,
								login_id : login_id,
								login_pw : login_pw,
							},
							(data_) =>
							{
								ui.dialog.popup_message('完了', '管理者情報が変更されました。',
									() => location.reload());
							},
							(msg_, code_) =>
							{
								ui.toast.add_error(msg_);
							}
						);
					}
				}
			); 
		});

	$('.edit_btn').on('click', function()
	{
		
	});
	

}