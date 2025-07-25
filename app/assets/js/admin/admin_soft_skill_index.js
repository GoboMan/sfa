var g={};

function init(opt_)
{
	g=opt_;

	$('#create_btn').on('click', function()
	{
		ui.dialog.popup
		(
			'#create_dlg',
			{
				'.ui_button.close' : ()=>
				{
					let dlg = $('#create_dlg');
					dlg.find('[name="name"]').val('');
					dlg.find('[name="synonyms"]').val('');
					$('.input_row').remove(); 
				},
				'.ui_button.done': ()=>
				{
					let rows = $('#input_table_body').find('.first_row, .input_row');
					let entries = [];
			
					rows.each(function () 
					{
						const row = $(this);
						const name = row.find('[name="name"]').val();
						const synonyms = row.find('[name="synonyms"]').val();
						entries.push(
						{ 
							name: name,
							synonyms : synonyms,
						});
					});
					console.log(entries);

					ajax.post
					(
						g.actions.create,
						{ 
							admins: entries,
						},
						() => 
						{
							ui.dialog.popup_message('完了', '新しいソフトスキルが登録されました。', 
								() => location.reload());
						},
						(msg_, code_) => 
						{
							ui.toast.add_error(msg_);
							console.log(msg_);
						}
					);
				},
			}
		); 
	});

	$('#create_dlg').find('.ui_button.info').on('click', function()
	{
		const newRow = `
		<div class="ui_panel layout_horizon padding_horizon input_row">
			<div class="margin_horizon" style="white-space:nowrap;">スキル名</div>
			<div class="margin full_horizon">
				<input type="text" class="ui_text full_horizon" name="name">
			</div>
			<div class="margin_horizon" style="white-space:nowrap;">類義語</div>
			<div class="margin full_horizon">
				<input type="text" class="ui_text full_horizon" name="synonyms">
			</div>
		</div>`;

		$('#input_table_body').append(newRow);
	});

	$('.edit_btn').on('click', function()
	{
		let soft_skill_id = $(this).attr('soft_skill_id');
		let name = $(this).attr('name');
		let synonyms = $(this).attr('synonyms');

		let dlg = $('#edit_dlg');
		dlg.find('[name="soft_skill_id"]').val(soft_skill_id);
		dlg.find('[name="name"]').val(name);
		dlg.find('[name="synonyms"]').val(synonyms);

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
				
				ajax.post
				(
					g.actions.update,
					{
						soft_skill_id : soft_skill_id,
						name : name,
						synonyms : synonyms,
					},
					(data_) =>
					{
						ui.dialog.popup_message('完了', 'スキルが変更されました。',
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
		let soft_skill_id = $(this).attr('soft_skill_id'); 

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
					soft_skill_id : soft_skill_id,
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

function popup_custom
(
	title_, 
	msg_, 
	on_done_ = null, 
	on_close_ = null, 
	yes_text_ = null, 
	no_text_ = null, 
	parent_ = null
)
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