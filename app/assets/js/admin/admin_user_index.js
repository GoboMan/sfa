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
					let email = dlg.find('[name="email"]').val();
					let login_id = dlg.find('[name="login_id"]').val();
					let login_pw = dlg.find('[name="login_pw"]').val();
					let create_at = dlg.find('[name="create_at"]').val();
					
					ajax.post
					(
						g.actions.create,
						{
							name : name,
							email : email,
							login_id : login_id,
							login_pw : login_pw,
							create_at : create_at,
						},
						(data_) =>
						{
							ui.dialog.popup_message('完了', '新規ユーザーが登録されました。',
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
		let user_id = $(this).attr('user_id');
		let name = $(this).attr('name');
		let email = $(this).attr('email');
		let login_id = $(this).attr('login_id');
		let login_pw = $(this).attr('login_pw');

		let dlg = $('#edit_dlg');
		dlg.find('[name="name"]').val(name);
		dlg.find('[name="email"]').val(email);
		dlg.find('[name="login_id"]').val(login_id);
		dlg.find('[name="login_pw"]').val(login_pw);


			ui.dialog.popup
			(
				'#edit_dlg',
				{
					'.ui_button.close' : ()=>
					{
						let dlg = $('#edit_dlg');
						dlg.find('[name="name"]').val('');
						dlg.find('[name="email"]').val('');
						dlg.find('[name="login_id"]').val('');
						dlg.find('[name="login_pw"]').val('');
						
					},
					'.ui_button.done': ()=>
					{
						let dlg = $('#edit_dlg');
						let name = dlg.find('[name="name"]').val();
						let email = dlg.find('[name="email"]').val();
						let login_id = dlg.find('[name="login_id"]').val();
						let login_pw = dlg.find('[name="login_pw"]').val();
						
						ajax.post
						(
							g.actions.update,
							{
								user_id : user_id,
								name : name,
								email : email,
								login_id : login_id,
								login_pw : login_pw,
							},
							(data_) =>
							{
								ui.dialog.popup_message('完了', 'ユーザー情報が変更されました。',
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

	$('.delete_btn').on('click', function() 
	{
		let user_id = $(this).attr('user_id'); 
		console.log('msg');
		popup_custom
		(
		"ユーザー情報削除",
		"このユーザーを削除します。よろしいですか？",
		function() 
		{
			ajax.post
			(
				g.actions.delete,
				{
					user_id : user_id
				},
				(data_) =>
				{
					ui.dialog.popup_message('完了','ユーザーが削除されました。',() => location.reload());
				},
				(msg_,code_) =>
				{
					ui.toast.add_error(msg_);
				}
			);
		},
		function() // on close
		{
			return null;
		});
	});
}

function popup_custom(title_, msg_, on_done_ = null, on_close_ = null, yes_text_ = null, no_text_ = null, parent_ = null)
{
	let dialog = new ui_dialog();
	dialog.popup_confirm
	(
		title_, msg_,
		on_done_,
		on_close_,
		yes_text_ === null ? "はい" : yes_text_,
		no_text_ === null ? "いいえ" : no_text_,
		parent_
	);
	return dialog;
}