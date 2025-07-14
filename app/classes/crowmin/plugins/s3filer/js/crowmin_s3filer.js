var g_opt = {};

function init_page( opts_ )
{
	g_opt = opts_;

	//	プロファイル選択でデフォルトバケット変更
	$('#profile').change(function(e_)
	{
		$('#bucket').val(g_opt.buckets[$(this).val()]);
	});

	//	プロファイル変更
	$('#btn_change_profile').on('click', function()
	{
		var args =
		{
			"profile" : $('#profile').val(),
			'bucket' : $('#bucket').val()
		};
		args[g_opt.csrf_change_profile_key] = g_opt.csrf_change_profile_val;
		$.ajax(
		{
			url : g_opt.url_change_profile,
			type : 'post',
			dataType : 'json',
			data : args,
			success : function(json_)
			{
				g_opt.csrf_change_profile_key = json_.csrf.key;
				g_opt.csrf_change_profile_val = json_.csrf.val;

				if( json_.r != 100 )
				{
					console.log('ajax error, code = ' + json_.r + ", reason = " + json_.d);
					popup_error('エラー', json_.d);
					return;
				}
				popup_msg('メッセージ', '現在のプロファイルとバケットを変更しました', function()
				{
					document.location = g_opt.url_self;
				});
			},
			error : function(req_, stat_, error_)
			{
				popup_error('通信エラー', error_);
			}
		});
	});

	//	パス部分でエンター押下するとパス移動
	$('#path input').keyup(function(e_)
	{
		if( e_.keyCode == 13 )
		{
			document.location = g_opt.url_self + "?path="
				+ encodeURIComponent($(this).val());
		}
	});

	//	ディレクトリクリック
	$('tr.dir td').on('click', function()
	{
		document.location = g_opt.url_self + "?path="
			+ encodeURIComponent($(this).attr('path'));
	});

	//	ダウンロード
	$('.button_download').on('click', function()
	{
		document.location = g_opt.url_download + "?path="
			+ encodeURIComponent($(this).attr('path'));
	});

	//	削除
	$('.button_trash').on('click', function()
	{
		var path = $(this).attr('path');
		popup_confirm( "削除確認", "ファイル「" + path + "」を本当に削除しますか？", "はい", "いいえ", function()
		{
			document.location = g_opt.url_trash + "?path="
				+ encodeURIComponent(path) + "&ret=" + encodeURIComponent(g_opt.path);
		});
	});

	//	アップロードファイル選択時
	$('#upload_file').change(function()
	{
		$('#uploader button').css('display', 'inline-block');
	});
}
