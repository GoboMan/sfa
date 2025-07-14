var g = {};

function init(opt_)
{
	g = opt_;
	g.current_line_diffs = [];
	g.elm_panel_center = $('#panel_center');
	g.elm_diff_scroll = $('#diff_scroll');
	g.elm_diff_scroll_handle = $('#diff_scroll_handle');

	//	windowリサイズ時に高さ調整
	window.onresize = () =>
	{
		adjust_height();
	};

	//	ロード完了時の初回高さ調整
	setTimeout( () =>
	{
		//	変更がないディレクトリは閉じておく
		$('.line.dir').each(function()
		{
			let dir_id = $(this).attr('dir_id');
			let child_elm = $('.child[dir_id="' + dir_id + '"]');

			if( $('.line.file.changed', child_elm).get().length > 0 )
			{
				child_elm.addClass('expand');
				$('.line.dir[dir_id=' + dir_id + '] > .expander').text('[－]');
			}
			else
			{
				child_elm.removeClass('expand').css('display', 'none');
				$('.line.dir[dir_id=' + dir_id + '] > .expander').text('[＋]');
			}
		});

		//	展開コントロールを仕込む
		$('.line.dir').on('click', function()
		{
			let dir_id = $(this).attr('dir_id');
			let child_elm = $('.child[dir_id="' + dir_id + '"]');
			if( child_elm.hasClass('expand') === true )
			{
				child_elm.removeClass('expand').slideUp(100);
				$('.line.dir[dir_id=' + dir_id + '] > .expander').text('[＋]');
			}
			else
			{
				child_elm.addClass('expand').slideDown(100);
				$('.line.dir[dir_id=' + dir_id + '] > .expander').text('[－]');
			}
		});

		//	ファイル差分表示
		$('.line.file').on('click', function()
		{
			$('.line.file.selected').removeClass('selected');
			$(this).addClass('selected');

			var path = $(this).attr('path');
			redraw_detail(path);
		});

		//	ファイル作成ボタン
		$('.line .create_link').on('click', function(e_)
		{
			e_.stopPropagation();
			e_.preventDefault();

			//	現在選択中のレイヤを作成先コンポーネントとする
			if( $('#apply_hist div.selected').get().length <= 0 )
			{
				ui.toast.add_error('コンポーネントが選択されていません');
				return;
			}

			let component_index = $('#apply_hist div.selected').attr('index');
			let component_name = g.current_hist[component_index].component;

			let path = $(this).closest('.dir').attr('path');

			ui.dialog.popup_input("空のファイルを作成", (component_name == "" ? "(root component)" : component_name) + "の 「" + path + "」 に空ファイルを作成します。<br>作成するファイル名を入力してください", "", "xxxx.php", (text_) =>
			{
				ajax.post
				(
					g.actions.ajax_create_empty,
					{
						path : path,
						fname : text_,
						component_name : component_name
					},
					data_ =>
					{
						//	とりあえずリロードする、あとでちゃんと作る
						location.reload();
					},
					(code_, msg_) =>
					{
						ui.dialog.popup_error('エラー', 'failed to create empty file : ' + path + "/" + text_);
					}
				);
			});
		});

		//	リストア制御
		$('#btn_restore').on('click', () =>
		{
			//	ボタンを非活性にしてからリクエスト
			$('#btn_restore').prop('disabled', true);
			ajax.post
			(
				g.actions.ajax_restore,
				{
					'component' : $('#restore_component').val()
				},
				(data_) =>
				{
					document.location = "<?= crow::make_url_self() ?>";
				},
				(code_, msg_) =>
				{
					$('#btn_restore').prop('disabled', false);
					ui.dialog.popup_error('エラー', 'failed to restore : ' + msg_);
				}
			);
		});

		//	高さ調整
		adjust_height();
		$('#loading').animate({opacity:0}, 300, "swing", function(){ $(this).remove() });
	}, 200);

	//	エディタ初期化
	g.editor_title = $('#editor .src_head .disp_name');
	g.editor_line_nos = $('#editor .line_nos')
		.on('scroll', () =>
		{
			g.editor_lines.scrollTop(g.editor_line_nos.scrollTop());
		})
		;

	g.editor_lines = $('#editor .lines')
		.on('input', function()
		{
			let lines = $(this).val().split("\n");
			let line_nos = "";
			for( let i = 0; i < lines.length; i++ ) line_nos += (i + 1) + "\r\n";
			g.editor_line_nos.val(line_nos);
		})
		.on('keydown', function(e_)
		{
			if( e_.keyCode == 9 )
			{
				e_.preventDefault();

				let elm = $(this).get()[0];
				let src = elm.value;
				let pos = elm.selectionStart;
				elm.value = src.substr(0, pos) + "\t" + src.substr(pos);
				elm.selectionStart = pos + 1;
				elm.selectionEnd = pos + 1;
			}
			if( e_.keyCode == 13 )
			{
				e_.preventDefault();


				let elm = $(this).get()[0];
				let src = elm.value;
				let pos = elm.selectionStart;
				let pre_line = src.substr(0, pos);
				let indent = "";

				//	前行のインデントを数える
				if( pre_line != "" )
				{
					let cr_pos = pre_line.lastIndexOf("\n");
					if( cr_pos >= 0 )
					{
						let last_line = pre_line.substring(cr_pos + 1);
						let space_cnt = last_line.match(/^( *)/)[0].length;
						let tab_cnt = last_line.match(/^(\t*)/)[0].length;
						if( space_cnt > tab_cnt )
						{
							for( let i = 0; i < space_cnt; i++ ) indent += " ";
						}
						else if( tab_cnt > space_cnt )
						{
							for( let i = 0; i < tab_cnt; i++ ) indent += "\t";
						}
					}
				}

				elm.value = pre_line + "\r\n" + indent + src.substr(pos);
				elm.selectionStart = pos + 1 + indent.length;
				elm.selectionEnd = pos + 1 + indent.length;

				let lines = $(this).val().split("\n");
				let line_nos = "";
				for( let i = 0; i < lines.length; i++ ) line_nos += (i + 1) + "\r\n";
				g.editor_line_nos.val(line_nos);
			}
		})
		.on('scroll', () =>
		{
			g.editor_line_nos.scrollTop(g.editor_lines.scrollTop());
		})
		;

	//	編集ボタン押下時
	$('#btn_edit').on('click', () =>
	{
		//	オーバーライドソースを取得して編集モードへ
		let path = $('.line.file.selected').attr('path');
		let component_index = $('#apply_hist div.selected').attr('index');
		let component_name = g.current_hist[component_index].component;

		ajax.post
		(
			g.actions.ajax_get_component_src,
			{
				path : path,
				component_name : component_name
			},
			data_ =>
			{
				let type = data_[0];
				if( type == "removed" )
				{
					ui.dialog.popup_dialog('削除指示のファイル', '削除指定されたファイルです : ' + path);
				}
				else if( type == "notfound" )
				{
					ui.dialog.popup_error('エラー', 'not found component source : ' + path);
				}
				else
				{
					let disp_name = data_[1];
					let lines = data_[2];
					g.editor_lines.val(lines.join("\r\n")).focus();

					let line_nos = "";
					for( let i = 0; i < lines.length; i++ ) line_nos += (i + 1) + "\r\n";
					g.editor_line_nos.val(line_nos);

					g.editor_title.text("File: " + disp_name);
					ui.dialog.popup('#editor',
					{
						'#btn_editor_close' : null,
						'#btn_editor_save' : () =>
						{
							ajax.post
							(
								g.actions.ajax_save_component_src,
								{
									path : path,
									type : type,
									component_name : component_name,
									body : g.editor_lines.val()
								},
								data_ =>
								{
									ui.toast.add('saved component src : ' + path);

									//	差分の再取得
									redraw_detail(path);
								},
								(code_, msg_) =>
								{
									ui.dialog.popup_error('エラー', 'failed to save component source : ' + path);
								}
							);
						}
					});
				}
			},
			(code_, msg_) =>
			{
				ui.dialog.popup_error('エラー', 'failed to get component source : ' + path);
			}
		);
	});

	//	差分スクロールのハンドル
	ui.dragger.create('#diff_scroll_handle').on_move((param_, ax_, ay_, x_, y_)=>
	{
		//	スクロール領域の高さ
		let scroll_height = g.elm_diff_scroll.height();
		let table_height = g.elm_panel_center.find('table').height();
		if( scroll_height === undefined || table_height === undefined ) return;

		//	差分スクロール領域とコード領域の割合
		let ratio = table_height / scroll_height;
		let scroll_val = parseFloat(g.elm_panel_center.scrollTop()) + (ay_ * ratio);
		g.elm_panel_center.scrollTop(scroll_val);
	});
}

//------------------------------------------------------------------------------
//	高さ調整
//------------------------------------------------------------------------------
function adjust_height()
{
	let panel_components = $('#apply_hist');

	let win_height = window.innerHeight;
	let panel_top = panel_components.offset().top;
	let comp_hei = panel_components.outerHeight();
	let margin = 20;
	let result = win_height - panel_top - comp_hei - margin;

	$('#panel_tree').css('height', result + "px");
	g.elm_panel_center.css('height', (result - 1) + "px");
	$('#splitter_bar').css('height', result + "px");

	//	差分スクロールバーの高さ調整
	g.elm_diff_scroll.css('height', result + "px");

	//	画面一杯使いたいので、フッターを消す
	$('#content').css('margin-bottom', '0');
	$('#footer').addClass('hide');

	init_diff_scroll();
	resize_diff_scroll();

	//	コード領域スクロールをdiffスクロール領域に連動する
	g.elm_panel_center.scroll( () => resize_diff_scroll() );
}

//------------------------------------------------------------------------------
//	差分スクロールバー調整
//------------------------------------------------------------------------------
function init_diff_scroll()
{
	//	一旦クリア
	g.elm_diff_scroll.find('.diff').remove();

	//	スクロール領域の高さ
	let scroll_height = g.elm_diff_scroll.height();
	if( scroll_height === undefined ) return;

	//	スクロール領域の1行あたりの高さ
	let line_height = 100 / g.current_line_diffs.length;

	//	差分がある行を計算
	let elm_preline = null;
	let pretype = "";
	let precnt = 0;

	for( let i = 0; i < g.current_line_diffs.length; i++ )
	{
		let type = g.current_line_diffs[i];
		if( type == "" ) continue;

		if( pretype == type && elm_preline !== null )
		{
			precnt++;
			elm_preline.css('height', precnt * line_height + "%");
			continue;
		}

		//	差分ボックスを追加
		elm_preline = 
			$('<div class="diff ' + type + '"></div>')
				.css('top', (i * line_height) + "%")
				.css('height', line_height + "%")
				.appendTo(g.elm_diff_scroll)
				;
		pretype = type;
		precnt = 1;
	}
}

function resize_diff_scroll()
{
	//	スクロール領域の高さ
	let scroll_height = g.elm_diff_scroll.height();
	let table_height = g.elm_panel_center.find('table').height();
	if( scroll_height === undefined || table_height === undefined ) return;

	//	スクロール領域の1行あたりの高さ
	let line_height_scroll = 100 / g.current_line_diffs.length;

	//	コード表示部の1行あたりの高さ
	let line_height_code = table_height / g.current_line_diffs.length;

	//	1画面への表示量
	//	テーブルにpadding=10x2=20があるので、scroll_heightから20px減らして計算する
	let line_per_screen = parseInt((scroll_height - 20) / line_height_code) + 1;

	//	コード領域のスクロール位置をハンドル位置に適用する
	let top_line_no = parseInt(g.elm_panel_center.scrollTop()) / line_height_code;
	let handle_top = top_line_no * line_height_scroll;
	g.elm_diff_scroll_handle.css('top', handle_top + "%");

	//	ハンドルの高さを上記の行数分にする
	let handle_height = (line_per_screen * line_height_scroll);
	if( handle_height + handle_top > 100 ) handle_height = 100 - handle_top;
	g.elm_diff_scroll_handle.css('height', handle_height + "%");
}

//------------------------------------------------------------------------------
//	ファイルの詳細表示（左ツリーからファイルクリック）
//------------------------------------------------------------------------------
function redraw_detail(path_)
{
	ajax.post
	(
		g.actions.ajax_diff,
		{path : path_},
		(data_) =>
		{
			if( data_.length <= 0 ) return;

			let hist = $('#apply_hist').empty().append($('<div class="label">layers: </div>'));
			array_each(data_, (dat, index) =>
			{
				$('<div></div>')
					.append(
						$('<div class="name">' + index + ". " + (dat.component=='' ? 'default' : dat.component) + '</div>')
					)
					.append(
						$('<div class="logic">' + dat.logic + '</div>')
					)
					.attr('index', index)
					.on('click', function()
					{
						redraw_diff($(this).attr('index'));
					})
					.appendTo(hist)
					;
			});
			g.current_hist = data_;
			redraw_diff(data_.length - 1);

			adjust_height();
		},
		(code_, msg_) =>
		{
			ui.toast.add_error('エラー', 'failed to get diff : ' + msg_);
		}
	);
}

//------------------------------------------------------------------------------
//	コード差分表示
//------------------------------------------------------------------------------
function redraw_diff(component_index_)
{
	if( component_index_ < 0 || component_index_ >= g.current_hist.length ) return;
	let diff = get_object_item(g.current_hist[component_index_], 'diff', null);
	let logic = get_object_item(g.current_hist[component_index_], 'logic', null);

	$('#apply_hist > div.selected').removeClass('selected');
	$('#apply_hist > div[index="' + component_index_ + '"]').addClass('selected');

	let preview_area = g.elm_panel_center.empty();
	let preview_tbl = $('<table></table>').appendTo(preview_area);
	let preview_tbody = $('<tbody></tbody>').appendTo(preview_tbl);

	if( logic == "override" || logic == "new" || logic == "reset" )
	{
		$('#btn_edit').removeClass('hide');
	}
	else
	{
		$('#btn_edit').addClass('hide');
	}

	let line_diffs = [];

	//	diffがないなら、ボディ表示
	if( diff == null )
	{
		let body = g.current_hist[component_index_].body;
		for( let i = 0; i < body.length; i++ )
		{
			let line = htmlspecialchars(body[i]);

			line = line.replaceAll("\t", '<font class="tab">\t</font>');
			line = line.replaceAll("　", '<font class="zsp">　</font>');
			line += line == "" ? '<font class="hcr"></font>' : '<font class="cr"></font>';

			preview_tbody.append
			(
				$('<tr class="line"></tr>')
					.append($('<td class="no">' + (i + 1) + '</td>'))
					.append($('<td class="type"> </td>'))
					.append($('<td class="text"></td>').html(line))
			);
			line_diffs.push('');
		}
	}

	//	コード部の描画
	else
	{
		for( let i = 0; i < diff.length; i++ )
		{
			var html_line = diff[i];
			var type = html_line.substr(0,1);
			var type_class = "";
			if( type === "+" ) type_class = "add";
			else if( type === "-" ) type_class = "del";

			let line = htmlspecialchars(html_line.substr(1));

			line = line.replaceAll("\t", '<font class="tab">\t</font>');
			line = line.replaceAll("　", '<font class="zsp">　</font>');
			line += line == "" ? '<font class="hcr"></font>' : '<font class="cr"></font>';

			preview_tbody.append
			(
				$('<tr class="line '+type_class+'"></tr>')
					.append($('<td class="no">' + (i + 1) + '</td>'))
					.append($('<td class="type">' + type + '</td>'))
					.append($('<td class="text"></td>').html(line))
			);
			line_diffs.push(type_class);
		}
	}
	g.current_line_diffs = line_diffs;
}
