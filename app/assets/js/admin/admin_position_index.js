var g={};

function init(opt_)
{
	g=opt_;

	$('#create_btn').on('click', ()=>
	{
		console.log('msg');
		ui.dialog.popup(
		'#create_dlg',
		{
			'.ui_button.close' : null,
			'.ui_button.done': ()=>
			{
				let dlg = $('#create_dlg');
				let name = dlg.find('[name="name"]').val();
				let synonyms = dlg.find('[name="synonyms"]').val();
				
				ajax.post
				(
					g.actions.create,
					{
						name : name,
						synonyms : synonyms,
					},
					(data_) =>
					{
						ui.dialog.popup_message('完了', '新しいポジションが登録されました。',
							() => location.reload());
					},
					(msg_, code_) =>
					{
						ui.toast.add_error(msg_);
					}
				);
			}
		}); 
	});

	$('.edit_btn').on('click', function()
		{
			let position_id = $(this).attr('position_id');
			let name = $(this).attr('name');
			let synonyms = $(this).attr('synonyms');

			let dlg = $('#create_dlg');
			dlg.find('[name="position_id"]').val(position_id);
			dlg.find('[name="name"]').val(name);
			dlg.find('[name="synonyms"]').val(synonyms);

			ui.dialog.popup(
			'#create_dlg',
			{
				'.ui_button.close' : null,
				'.ui_button.done': ()=>
				{
					let dlg = $('#create_dlg');
					let name = dlg.find('[name="name"]').val();
					let synonyms = dlg.find('[name="synonyms"]').val();
					
					ajax.post
					(
						g.actions.update,
						{
							position_id : position_id,
							name : name,
							synonyms : synonyms,
						},
						(data_) =>
						{
							ui.dialog.popup_message('完了', '新しいポジションが登録されました。',
								() => location.reload());
						},
						(msg_, code_) =>
						{
							ui.toast.add_error(msg_);
						}
					);
				}
			}); 
		});
	
	$('.delete_btn').on('click', function() 
	{
		let position_id = $(this).attr('position_id'); 

		popup_custom
		(
		"ポジション削除",
		"このポジションを削除します。よろしいですか？",
		function() 
		{
			ajax.post
			(
				g.actions.delete,
				{
					position_id : position_id,
				},
				(data_) =>
				{
					ui.dialog.popup_message('完了','ポジションが削除されました。',
						() => location.reload());
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