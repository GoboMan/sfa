;
var g = {opt:{}};

//------------------------------------------------------------------------------
//	ページ初期化
//------------------------------------------------------------------------------
function page_init( opt_ )
{
	g.opt = opt_;

	//	追加ボタン押下時
	$('.btn_add').on('click', function()
	{
		popup_addedit_show();
	});
}


//------------------------------------------------------------------------------
//	追加/編集ポップアップ
//------------------------------------------------------------------------------

//	追加で表示
function popup_addedit_show()
{
	//	内容クリア
	for( var i=0; i<g.opt.columns.length; i++ )
	{
		var col = g.opt.columns[i];
		if( col.const == "1" )
		{
		}
		//	座標の場合
		else if( col.type == "geometry" )
		{
			$("#popup_form select[name="+col.name+"_lat]").val('');
			$("#popup_form select[name="+col.name+"_lng]").val('');
		}
		//	二値の場合
		else if( col.type == "boolean" )
		{
			$("#popup_form input[name="+col.name+"]").prop("checked", false);
		}
		//	テキストの場合
		else if( col.type == "text" )
		{
			$("#popup_form textarea[name="+col.name+"]").val('');
		}
		//	それ以外
		else
		{
			$("#popup_form input[name="+col.name+"]").val('');
		}
	}
	$('#popup_record_id').attr('name', g.opt.primary_key).val("0");
	$('#popup_title').html("追加");
	$('#popup_btn_done').html("追加");
	$('#popup_form .pw_info').css('display', 'none');

	$('#popup_gray_form').css('display','block');
	var popup_panel = $('#popup_form').css('display','block');
	popup_panel.css( 'margin',
		(-(popup_panel.outerHeight()/2))+'px 0 0 '+(-(popup_panel.outerWidth()/2))+'px'
	);
}

//	編集で表示
function popup_addedit_show_with( key_, primary_id_ )
{
	var src_tr = $('tr[key='+key_+']');
	for( var i=0; i<g.opt.columns.length; i++ )
	{
		var col = g.opt.columns[i];

		//	const時はselectbox選択
		if( col.const == "1" )
		{
			$("#popup_form select[name="+col.name+"]").val(
				$("[name="+col.name+"]", src_tr).val()
			);
		}
		//	座標の場合
		else if( col.type == "geometry" )
		{
			$("#popup_form select[name="+col.name+"_lat]").val(
				$("[name="+col.name+"_lat]", src_tr).val()
			);
			$("#popup_form select[name="+col.name+"_lng]").val(
				$("[name="+col.name+"_lng]", src_tr).val()
			);
		}
		//	二値の場合
		else if( col.type == "boolean" )
		{
			var cur = $("[name="+col.name+"]", src_tr).val();
			if( cur == "0" )
			{
				$("#popup_form input[name="+col.name+"]").prop("checked", false);
			}
			else
			{
				$("#popup_form input[name="+col.name+"]").prop("checked", true);
			}
		}
		//	テキストの場合
		else if( col.type == "text" )
		{
			$("#popup_form textarea[name="+col.name+"]").val(
				$("[name="+col.name+"]", src_tr).val()
			);
		}
		//	それ以外
		else
		{
			$("#popup_form input[name="+col.name+"]").val(
				$("[name="+col.name+"]", src_tr).val()
			);
		}
	}
	$('#popup_record_id').attr('name', g.opt.primary_key).val(primary_id_);
	$('#popup_title').html("編集");
	$('#popup_btn_done').html("変更");
	$('#popup_form .pw_info').css('display', 'block');

	$('#popup_gray_form').css('display','block');
	var popup_panel = $('#popup_form').css('display','block');
	popup_panel.css( 'margin',
		(-(popup_panel.outerHeight()/2))+'px 0 0 '+(-(popup_panel.outerWidth()/2))+'px'
	);
}

//	非表示
function popup_addedit_hide()
{
	$('#popup_gray_form').css('display','none');
	$('#popup_form').css('display','none');
}

//	キャンセル
function btn_addedit_cancel()
{
	popup_addedit_hide();
}

//	実行
function btn_addedit_done()
{
//	btn_addedit_cancel();

	var params =
	{
		crow_db_table_name : g.opt.table_name
	};
	var id = $('#popup_record_id').val();
	if( id != "0" ) params[$('#popup_record_id').attr('name')] = id;

	for( var i=0; i<g.opt.columns.length; i++ )
	{
		var col = g.opt.columns[i];
		if( col.name == g.opt.primary_key ) continue;

		if( col.type == "geometry" )
		{
			params[col.name+"_lat"] = $('#popup_form [name='+col.name+'_lat]').val();
			params[col.name+"_lng"] = $('#popup_form [name='+col.name+'_lng]').val();
		}
		else if( col.type == "boolean" )
		{
			if( $('#popup_form [name='+col.name+']').prop("checked") )
				params[col.name] = "1";
			else
				params[col.name] = "0";
		}
		else
		{
			params[col.name] = $('#popup_form [name='+col.name+']').val();
		}
	}

	if( id=="0" ) params[g.opt.csrf_add.key] = g.opt.csrf_add.val;
	else params[g.opt.csrf_edit.key] = g.opt.csrf_edit.val;

	$.ajax(
	{
		url : id=="0" ? g.opt.url_add : g.opt.url_edit,
		type : "post",
		dataType : "json",
		data : params,
		success : function(json_)
		{
			if( json_.r != "100" )
			{
				if( id == "0" )
					popup_error("エラー : "+json_.r, "レコードの追加に失敗しました。<br>"+json_.d);
				else
					popup_error("エラー : "+json_.r, "レコードの変更に失敗しました。<br>"+json_.d);
				return;
			}
			document.location = g.opt.url_self + "&page=" + g.opt.page;
		},
		error : function(req_, stat_, error_)
		{
			popup_error("エラー", "<b>"+stat_+"</b><br>"+error_);
		}
	});
}

//------------------------------------------------------------------------------
//	削除
//------------------------------------------------------------------------------
function confirm_delete( id_ )
{
	popup_confirm
	(
		"削除確認",
		g.opt.primary_key+"="+id_+"のデータを本当に削除しますか？",
		"削除する",
		"キャンセル",
		function()
		{
			var params = {table:g.opt.table_name, id:id_};
			params[g.opt.csrf_delete.key] = g.opt.csrf_delete.val;

			$.ajax(
			{
				url : g.opt.url_delete,
				type : "post",
				dataType : "json",
				data : params,
				success : function(json_)
				{
					if( json_.r != "100" )
					{
						popup_error("エラー : "+json_.r, "レコードの削除に失敗しました。<br>"+json_.d);
						return;
					}
					document.location = g.opt.url_self + "&page=" + g.opt.page;
				},
				error : function(req_, stat_, error_)
				{
					popup_error("エラー", "<b>"+stat_+"</b><br>"+error_);
				}
			});
		}
	);
}

