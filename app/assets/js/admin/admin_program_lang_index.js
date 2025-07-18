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
					let name = dlg.find('[name="name"]').val();
					
					ajax.post
					(
						g.actions.create,
						{
							name : name,
						},
						(data_) =>
						{
							ui.dialog.popup_message('完了', 'プログラム言語が登録されました。',
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
			let program_lang_id = $(this).attr('program_lang_id');
			let name = $(this).attr('name');

			let dlg = $('#create_dlg');
			dlg.find('[name="name"]').val(name);


			ui.dialog.popup
			(
				'#create_dlg',
				{
					'.ui_button.close' : ()=>
					{
						let dlg = $('#create_dlg');
						dlg.find('[name="name"]').val('');
						
					},
					'.ui_button.done': ()=>
					{
						let dlg = $('#create_dlg');
						let name = dlg.find('[name="name"]').val();
						
						ajax.post
						(
							g.actions.update,
							{
								program_lang_id : program_lang_id,
								name : name,
							},
							(data_) =>
							{
								ui.dialog.popup_message('完了', 'プログラム言語名が変更されました。',
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
		let program_lang_id = $(this).attr('program_lang_id'); 
		popup_custom
		(
		"プログラム言語削除",
		"このプログラム言語の登録を削除します。よろしいですか？",
		function() 
		{
			ajax.post
			(
				g.actions.delete,
				{
					program_lang_id : program_lang_id,
				},
				(data_) =>
				{
					ui.dialog.popup_message('完了','プログラム言語が削除されました。',() => location.reload());
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