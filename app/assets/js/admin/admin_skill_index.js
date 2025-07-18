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
						ui.dialog.popup_message('完了', 'スキルが登録されました。',
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
			let skill_id = $(this).attr('skill_id');
			let name = $(this).attr('name');
			let synonyms = $(this).attr('synonyms');

			let dlg = $('#create_dlg');
			dlg.find('[name="skill_id"]').val(skill_id);
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
							skill_id : skill_id,
							name : name,
							synonyms : synonyms,
						},
						(data_) =>
						{
							ui.dialog.popup_message('完了', '新しいスキルが登録されました。',
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
		let skill_id = $(this).attr('skill_id'); 

		popup_custom
		(
		"スキル削除",
		"このスキルを削除します。よろしいですか？",
		function() 
		{
			ajax.post
			(
				g.actions.delete,
				{
					skill_id : skill_id,
				},
				(data_) =>
				{
					ui.dialog.popup_message('完了','スキルが削除されました。',
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