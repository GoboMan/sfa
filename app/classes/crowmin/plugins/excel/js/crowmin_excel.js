

function init( args_ )
{
	//	フォーカス時色変更
	changeFocusColor(".input_box", "focused");

	//	全チェック
	checkAllTable("#check_all", ".check_box", "table_checked");

	//	プロパティ設定の展開
	openProp("#prop_area", ".open", "active", ".hidden_prop", "hidden");

	//	クリック時ハイライト
	clickHighlightClass(".ui_btn", "active");

	//	テーブル行チェック
	initCheckedColor(".check_box", ".table_row", "table_checked");
	changeClickColor(".table_row", "table_checked", ".checkbox");
	changeClickColor(".check_box", "table_checked", "");

	//	履歴編集
	editHistory(".ui_btn.edit", ".current_msg", ".update_msg");
	applyEditHistory(".update_msg", ".current_msg");

	//	履歴削除
	deleteHistory(".ui_btn.delete", ".hist_row", ".message", ".update_msg", "deleted");
}
function editHistory( selector_, current_, update_ )
{
	$(selector_).on("click", function()
	{
		$(this).parent().siblings(".message").children(current_).toggleClass("hidden");
		$(this).parent().siblings(".message").children(update_).toggleClass("hidden");
	});
}
function applyEditHistory(input_, target_)
{
	$(input_).on("input cut paste", function()
	{
		var input_text = $(this).val();
		$(this).text(input_text);
		$(this).siblings(target_).text(input_text);
	});
}

function deleteHistory( selector_, target_, siblings_target_, disabled_target_, delete_class_)
{
	$(selector_).on("click", function()
	{
		$(this).closest(target_).toggleClass(delete_class_);
		var disabled_selector = $(this).parent().siblings(siblings_target_).children(disabled_target_);
		var deleted = disabled_selector.attr("disabled");
		if( deleted )
			disabled_selector.attr("disabled", false);
		else
			disabled_selector.attr("disabled", true);
	});
}

//	テーブル行チェック
function changeClickColor( selector_, active_class_, check_class_ )
{
	$(selector_).on('click', function()
	{
		if( selector_===".table_row" )
		{
			var checked = $(this).children().children().prop("checked");
			if( checked )
				$(this).children().children().prop("checked", false);
			else
				$(this).children().children().prop("checked", true);

			$(this).toggleClass(active_class_);
		}
		else if( selector_===".check_box" )
		{
			var checked = $(this).prop("checked");
			if( checked )
				$(this).prop("checked", false);
			else
				$(this).prop("checked", true);

			$(this).parent().parent().toggleClass(active_class_);
		}
	});
}

//	フォーカス時色変更
function changeFocusColor( selector_, active_class_ )
{
	$(selector_).on('focus', function()
	{
		$(this).addClass(active_class_);
	});
	$(selector_).on('blur', function()
	{
		$(this).removeClass(active_class_);
	});
}
//	チェック時色変更
function initCheckedColor( selector_, target_, checked_class_ )
{
	$(selector_).each(function()
	{
		if( $(this).prop("checked") )
			$(this).closest(target_).addClass(checked_class_);
	});
}
//	全チェックON/OFF
function checkAllTable( all_selector_, check_selector_, active_class_ )
{
	$(all_selector_).on("click", function()
	{
		if( $(this).prop("checked") )
		{
			$(check_selector_).each(function()
			{
				$(this).prop("checked", true);
				$(this).closest("tr").addClass(active_class_);
			});
		}
		else
		{
			$(check_selector_).each(function()
			{
				$(this).prop("checked", false);
				$(this).closest("tr").removeClass(active_class_);
			});
		}
	});
}
//	プロパティ詳細のOPEN/CLOSE
function openProp( parent_, target_, active_class_, hidden_target_, hidden_class_ )
{
	$(parent_).on("click", function()
	{
		var is_opened = $(target_).hasClass(active_class_);
		if( is_opened )
		{
			$(target_).text("+");
			$(target_).removeClass(active_class_);
			$(hidden_target_).each(function()
			{
				$(hidden_target_).addClass(hidden_class_);
			});
		}
		else
		{
			$(target_).text("-");
			$(target_).addClass(active_class_);
			$(hidden_target_).each(function()
			{
				$(hidden_target_).removeClass(hidden_class_);
			});
		}
	});
}
//	クリック時ハイライト
function clickHighlightClass( selector_, active_class_ )
{
	$(selector_).each(function()
	{
		$(this).on('mousedown', function()
		{
			$(this).addClass(active_class_);
		});
		$(this).on('mouseup', function()
		{
			$(this).removeClass(active_class_);
		});
		$(this).on('mouseleave', function()
		{
			$(this).removeClass(active_class_);
		});
	});
}
