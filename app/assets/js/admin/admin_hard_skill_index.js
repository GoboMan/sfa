var g={};

function init(opt_)
{
	g=opt_;

	$('#create_btn').on('click', ()=>
	{
		ui.dialog.popup(
			'#create_dlg',
			{
				'.ui_button.close' :()=>
				{
					let dlg = $('#create_dlg');
					dlg.find('[name="name"]').val('');
					dlg.find('[name="synonyms"]').val('');
					$('.input_row').remove(); 
				},
				'.ui_button.done': ()=>
				{
					let rows = $('#input_table_body').find('.first_row, .input_row');

					console.log(rows);
					let entries = [];
			
					rows.each(function () 
					{
						const row = $(this);
						const name = row.find('[name="name"]').val();
						const synonyms = row.find('[name="synonyms"]').val();
						const skill_type = row.find('[name="skill_type"]').val();
						entries.push(
						{ 
							name: name,
							synonyms : synonyms,
							skill_type : skill_type,
						});
					});

					ajax.post
					(
						g.actions.create,
						{ 
							admins: entries,
						},
						() => 
						{
							ui.dialog.popup_message('完了', '新しいハードスキルが登録されました。', 
								() => location.reload());
						},
						(msg_, code_) => 
						{
							ui.toast.add_error(msg_);
							console.log(msg_);
						}
					);
				}
			}
		); 
	});

	$('#create_dlg').find('.ui_button.info').on('click', function()
	{
		let newRow = $('#input_row_template').find('.input_row').clone(false);

		$('#input_table_body').append(newRow);
	});

	$('.edit_btn').on('click', function()
	{
		let hard_skill_id = $(this).attr('hard_skill_id');
		let name = $(this).attr('name');
		let synonyms = $(this).attr('synonyms');
		let skill_type = $(this).attr('skill_type');


		let dlg = $('#edit_dlg');
		dlg.find('[name="name"]').val(name);
		dlg.find('[name="synonyms"]').val(synonyms);
		dlg.find('[name="skill_type"]').val(skill_type);


		ui.dialog.popup
		(
			'#edit_dlg',
			{
				'.ui_button.close' : null,
				'.ui_button.done': ()=>
				{
					let dlg = $('#edit_dlg');
					let name = dlg.find('[name="name"]').val();
					let synonyms = dlg.find('[name="synonyms"]').val();
					let skill_type = dlg.find('[name="skill_type"]').val();
					
					ajax.post
					(
						g.actions.update,
						{
							hard_skill_id : hard_skill_id,
							name : name,
							synonyms : synonyms,
							skill_type : skill_type,
						},
						(data_) =>
						{
							ui.dialog.popup_message('完了', 'ハードスキルが変更されました。',
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
		let hard_skill_id = $(this).attr('hard_skill_id'); 
		popup_custom
		(
		"ハードスキル削除",
		"このハードスキルの登録を削除します。よろしいですか？",
		function() 
		{
			ajax.post
			(
				g.actions.delete,
				{
					hard_skill_id : hard_skill_id,
				},
				(data_) =>
				{
					ui.dialog.popup_message('完了','ハードスキルが削除されました。',() => location.reload());
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