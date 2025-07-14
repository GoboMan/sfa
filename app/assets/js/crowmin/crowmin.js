//------------------------------------------------------------------------------
//	URL指定でジャンプ
//------------------------------------------------------------------------------
function jump( $to_ )
{
	document.location = $to_;
}

var g_popup_id = 0;

//------------------------------------------------------------------------------
//	通常メッセージのポップアップ
//------------------------------------------------------------------------------
function popup_msg(
	title_,		//	ポップアップのタイトル
	msg_,		//	メッセージ
	func_		//	OK押下時の処理（省略可）
){
	//	グレーパネル作成
	gray_panel = $('<div class="popup_gray" id="popup_gray_'+g_popup_id+'"></div>')
		.appendTo( $('body') );

	//	ポップアップパネル作成
	popup_panel = $('<div class="popup_panel" id="popup_panel_'+g_popup_id+'"></div>')
		.appendTo( $('body') );

	//	タイトル部分作成
	$('<div class="title">'+title_+'</div>').appendTo( popup_panel );

	//	ボディ部分作成
	$('<div class="body">'+msg_+'</div>').appendTo( popup_panel );

	//	閉じるボタン作成
	btn_area = $('<div class="btn_area"></div>')
		.appendTo( popup_panel );

	$('<button class="ui_btn blue">閉じる</button>')
		.data('id', g_popup_id)
		.click(function(){
			$('#popup_gray_' +$(this).data('id')).remove();
			$('#popup_panel_'+$(this).data('id')).remove();
			if( func_ ) func_();
		}).appendTo( btn_area ).focus();

	$(window).on('keydown', function(ev){
		//	「Esc」キー押下時
		if( ev.which == 27 )
		{
			$('#popup_gray_' +(g_popup_id-1)).remove();
			$('#popup_panel_'+(g_popup_id-1)).remove();
		}
	});

	//	センター寄せ
	popup_panel.css( 'margin', (-(popup_panel.outerHeight()/2))+'px 0 0 '+(-(popup_panel.outerWidth()/2))+'px' );

	//	IDを更新しておく
	g_popup_id++;
}


//------------------------------------------------------------------------------
//	エラーメッセージのポップアップ
//------------------------------------------------------------------------------
function popup_error(
	title_,		//	ポップアップのタイトル
	error_,		//	エラー
	func_		//	OK押下時の処理（省略可）
){
	//	グレーパネル作成
	gray_panel = $('<div class="popup_gray" id="popup_gray_'+g_popup_id+'"></div>')
		.appendTo( $('body') );

	//	ポップアップパネル作成
	popup_panel = $('<div class="popup_panel" id="popup_panel_'+g_popup_id+'"></div>')
		.appendTo( $('body') );

	//	タイトル部分作成
	$('<div class="title">'+title_+'</div>').appendTo( popup_panel );

	//	ボディ部分作成
	$('<div class="body">'+error_+'</div>').appendTo( popup_panel );

	//	閉じるボタン作成
	btn_area = $('<div class="btn_area"></div>')
		.appendTo( popup_panel );

	$('<button class="ui_btn blue">閉じる</button>')
		.data('id', g_popup_id)
		.click(function(){
			$('#popup_gray_' +$(this).data('id')).remove();
			$('#popup_panel_'+$(this).data('id')).remove();
			if( func_ ) func_();
		}).appendTo( btn_area ).focus();

	$(window).on('keydown', function(ev){
		//	「Esc」キー押下時
		if( ev.which == 27 )
		{
			$('#popup_gray_' +(g_popup_id-1)).remove();
			$('#popup_panel_'+(g_popup_id-1)).remove();
		}
	});

	//	センター寄せ
	popup_panel.css( 'margin', (-(popup_panel.outerHeight()/2))+'px 0 0 '+(-(popup_panel.outerWidth()/2))+'px' );

	//	IDを更新しておく
	g_popup_id++;
}


//------------------------------------------------------------------------------
//	確認のポップアップ
//------------------------------------------------------------------------------
function popup_confirm(
	title_,		//	ポップアップのタイトル
	msg_,		//	メッセージ
	btn1_,		//	YESの文言
	btn2_,		//	NOの文言
	func_		//	OK押下時の処理（省略可）
){
	//	グレーパネル作成
	gray_panel = $('<div class="popup_gray" id="popup_gray_'+g_popup_id+'"></div>')
		.appendTo( $('body') );

	//	ポップアップパネル作成
	popup_panel = $('<div class="popup_panel" id="popup_panel_'+g_popup_id+'"></div>')
		.appendTo( $('body') );

	//	タイトル部分作成
	$('<div class="title">'+title_+'</div>').appendTo( popup_panel );

	//	ボディ部分作成
	$('<div class="body">'+msg_+'</div>').appendTo( popup_panel );

	//	ボタンエリア作成
	btn_area = $('<div class="btn_area"></div>')
		.appendTo( popup_panel );

	//	OKボタン作成
	$('<button class="ui_btn green">'+btn1_+'</button>')
		.css('margin-right','4px')
		.data('id', g_popup_id)
		.click(function(){
			$('#popup_gray_' +$(this).data('id')).remove();
			$('#popup_panel_'+$(this).data('id')).remove();
			if( func_ ) func_();
		}).appendTo( btn_area ).focus();

	//	cancelボタン作成
	$('<button class="ui_btn red">'+btn2_+'</button>')
		.css('margin-left','4px')
		.data('id', g_popup_id)
		.click(function(){
			$('#popup_gray_' +$(this).data('id')).remove();
			$('#popup_panel_'+$(this).data('id')).remove();
		}).appendTo( btn_area ).focus();

	$(window).on('keydown', function(ev){
		//	「Esc」キー押下時
		if( ev.which == 27 )
		{
			$('#popup_gray_' +(g_popup_id-1)).remove();
			$('#popup_panel_'+(g_popup_id-1)).remove();
		}
	});

	//	センター寄せ
	popup_panel.css( 'margin', (-(popup_panel.outerHeight()/2))+'px 0 0 '+(-(popup_panel.outerWidth()/2))+'px' );

	//	IDを更新しておく
	g_popup_id++;
}
