/*

	ajax 制御


	レスポンスからCSRF値を自動更新する機能を持つため、各アクション毎に初回だけCSRF値を渡せばよい。
	成功と失敗のコールバックを指定することができる。
	失敗というのは通信の失敗と、正常コード以外が返却された場合を指す。
	正常コードは、json_.r でサーバから返却された値とする。


	使い方

		//	現在モジュールのURLとCSRFのリストを取得
		var actions = <?= crow::get_module_urls_as_json() ?>;

		//	1. "create"というアクションへpostで送信
		params = {"name" : "test data", "gender" : "male"};
		ajax.post(actions.create, params);

		//	2. 送信した後に成功時コールバックをとる例
		params = {"name" : "test data", "gender" : "male"};
		ajax.post(actions.create, params, function(data_)
		{
			console.log('succeed');
		});

		//	3. 送信した後に成功/失敗時コールバックをとる例
		params = {"name" : "test data", "gender" : "male"};
		ajax.post
		(
			actions.create, params,

			//	正常コールバック、data_ には json_.d が入ってくる
			function(data_)
			{
				console.log('succeed');
			},

			//	失敗コールバック
			//	・errcode_ は、json_.r で返却された値、msg_は、json_.d で返却された値とする。
			function(code_, data_)
			{
				console.log('failed : ' + data_);
			}
		);

		※上記までの第一引数に指定している "actions.create" は、下記のような連想配列が指定されている
		{
			"url"	: アクションのURL,
			"key"	: CSRFのキー,
			"val"	: CSRFの値
		}

		つまり、それらを手動で指定すると自由なアクションへの通信が可能になる。
		下記はその例。

		//	crow::get_module_urls_as_json() の値ではなく
		//	個別に URL、CRSF key、CSRF val を指定する例
		var url = "<?= crow::make_url_action('create') ?>";
		var csrf_key = "<?= crow::get_csrf_key_action('ajax_create') ?>";
		var csrf_val = "<?= crow::get_csrf_val_action('ajax_create') ?>";

		ajax.post
		(
			{"url":url, "key":csrf_key, "val":csrf_val},
			params,

			//	正常コールバック、data_ には json_.d が入ってくる
			function(data_)
			{
				console.log('succeed');
			},

			//	失敗コールバック
			//	・errcode_ は、json_.r で返却された値、msg_は、json_.d で返却された値とする。
			function(code_, data_)
			{
				console.log('failed : ' + data_);
			}
		);


		■ getについて。

		getについてはCSRFのチェックは不要なのだが、
		postとインタフェースを合わせるために、第一引数にはpostと同様に配列を指定する。
		ただしその要素は "url" しか見ないため、 "key"と"val"についてはなくてもよい。

		例）crow::get_module_urls_as_json()を使う場合
		var actions = <?= crow::get_module_urls_as_json() ?>;
		ajax.get( {"url" : actions.list} );

		例）手動指定の場合
		ajax.get( {"url" : "<?= make_url_action('list') ?>"} );


		■ crowで作られたajax以外の通信を行う場合

		crowで作られたアクションではないURLへアクセスする際は
		各送信メソッドの noparse_ 引数に true を指定する。
		すると返却値のパースを行われず、成功レスポンスの引数は返却値そのものとなり、
		失敗レスポンスの引数は code_ は HTTPレスポンスコードとなり、data_ にはメッセージが渡るようになる
		また、この場合限定で、datatype_ 指定も有効になる。
		datatype_ には "json", "xml", "html", "script", "jsonp", "auto" を指定可能。
		デフォルトは "auto" で、この場合は応答のMIMEタイプにより自動で推測される

*/

//	正常コード
var AJAX_CODE_SUCCESS = 100;

//	ajax管理
var ajax =
{
	//--------------------------------------------------------------------------
	//	GETリクエスト
	//--------------------------------------------------------------------------
	get : function( url_, params_, callback_success_, callback_failed_, noparse_ = false, datatype_ = "json" )
	{
		let params = JSON.parse(JSON.stringify(params_));
		let noparse = noparse_;
		let opts =
		{
			url : url_.url,
			type : "get",
			data : params,
			success : function(json_)
			{
				//	パースなし指定ならそのまま返却
				if( noparse === true )
				{
					if( callback_success_ != null )
						callback_success_(json_);
					return;
				}

				//	フォーマットチェック。エラー時は エラコード = 0 とする
				if( json_.r == undefined || json_.d == undefined )
				{
					if( callback_failed_ != null )
						callback_failed_(0, "フォーマットエラー");
					return;
				}

				//	成功コードであること
				if( json_.r != AJAX_CODE_SUCCESS )
				{
					if( callback_failed_ != null )
						callback_failed_(json_.r, json_.d);
					return;
				}

				//	成功
				if( callback_success_ != null )
					callback_success_(json_.d);
			},
			error : function(req_, stat_, error_)
			{
				//	パースなし指定ならHTTPレスポンスコードを返却
				if( noparse === true )
				{
					if( callback_failed_ != null )
						callback_failed_(req_.status, error_.message);
					return;
				}

				//	通信失敗時は エラーコード = 0 とする
				if( callback_failed_ != null )
					callback_failed_(0, "通信エラー");
			}
		};

		if( datatype_ != "auto" ) opts.dataType = datatype_;
		$.ajax(opts);
	},

	//--------------------------------------------------------------------------
	//	POSTリクエスト
	//--------------------------------------------------------------------------
	post : function( url_, params_, callback_success_, callback_failed_, noparse_ = false, datatype_ = "json" )
	{
		let params = JSON.parse(JSON.stringify(params_));
		let noparse = noparse_;

		//	CSRF指定、パースありの場合のみ
		if( noparse_ === false )
		{
			if( ajax.m.csrf[url_.url] == undefined )
				params[url_.key] = url_.val;
			else
				params[ajax.m.csrf[url_.url][0]] = ajax.m.csrf[url_.url][1];
		}

		//	実行
		let url = url_.url;
		let opts =
		{
			url : url,
			type : "post",
			data : params,
			success : function(json_)
			{
				//	パースなし指定ならそのまま返却
				if( noparse === true )
				{
					if( callback_success_ != null )
						callback_success_(json_);
					return;
				}

				//	フォーマットチェック。エラー時は エラコード = 0 とする
				if( json_.r == undefined || json_.d == undefined )
				{
					if( callback_failed_ != null )
						callback_failed_(0, "フォーマットエラー");
					return;
				}

				//	CSRF更新
				if( json_.csrf != undefined && json_.csrf.key != undefined && json_.csrf.val != undefined )
					ajax.m.csrf[url] = [json_.csrf.key, json_.csrf.val];

				//	成功コードであること
				if( json_.r != AJAX_CODE_SUCCESS )
				{
					if( callback_failed_ != null )
						callback_failed_(json_.r, json_.d);
					return;
				}

				//	成功
				if( callback_success_ != null )
					callback_success_(json_.d);
			},
			error : function(req_, stat_, error_)
			{
				//	パースなし指定ならHTTPレスポンスコードを返却
				if( noparse === true )
				{
					if( callback_failed_ != null )
						callback_failed_(req_.status, error_.message);
					return;
				}

				//	通信失敗時は エラーコード = 0 とする
				if( callback_failed_ != null )
					callback_failed_(0, "通信エラー");
			}
		};

		if( datatype_ != "auto" ) opts.dataType = datatype_;
		$.ajax(opts);
	},

	//--------------------------------------------------------------------------
	//	ファイル複数添付ありのPOSTリクエスト
	//--------------------------------------------------------------------------
	post_with_files : function( url_, params_, callback_success_, callback_failed_, noparse_ = false, datatype_ = "json" )
	{
		//	formdata作成
		let fd = new FormData();
		for( key in params_ ) fd.append(key, params_[key]);

		//	CSRF指定、パースありの場合のみ
		if( noparse_ === false )
		{
			if( ajax.m.csrf[url_.url] == undefined )
				fd.append(url_.key, url_.val);
			else
				fd.append(ajax.m.csrf[url_.url][0], ajax.m.csrf[url_.url][1]);
		}

		//	実行
		let noparse = noparse_;
		let url = url_.url;
		let opts =
		{
			url : url,
			type : "post",
			enctype : 'multipart/form-data',
			data : fd,
			processData : false,
			contentType : false,
			cache : false,
			success : function(json_)
			{
				//	パースなし指定ならそのまま返却
				if( noparse === true )
				{
					if( callback_success_ != null )
						callback_success_(json_);
					return;
				}

				//	フォーマットチェック。エラー時は エラコード = 0 とする
				if( json_.r == undefined || json_.d == undefined )
				{
					if( callback_failed_ != null )
						callback_failed_(0, "フォーマットエラー");
					return;
				}

				//	CSRF更新
				if( json_.csrf != undefined && json_.csrf.key != undefined && json_.csrf.val != undefined )
					ajax.m.csrf[url] = [json_.csrf.key, json_.csrf.val];

				//	成功コードであること
				if( json_.r != AJAX_CODE_SUCCESS )
				{
					if( callback_failed_ != null )
						callback_failed_(json_.r, json_.d);
					return;
				}

				//	成功
				if( callback_success_ != null )
					callback_success_(json_.d);
			},
			error : function(req_, stat_, error_)
			{
				//	パースなし指定ならHTTPレスポンスコードを返却
				if( noparse === true )
				{
					if( callback_failed_ != null )
						callback_failed_(req_.status, error_.message);
					return;
				}

				//	通信失敗時は エラーコード = 0 とする
				if( callback_failed_ != null )
					callback_failed_(0, "通信エラー");
			}
		};

		if( datatype_ != "auto" ) opts.dataType = datatype_;
		$.ajax(opts);
	},

	//--------------------------------------------------------------------------
	//	単一のファイルを添付するPUTリクエスト
	//--------------------------------------------------------------------------
	put_with_file : function( url_, file_, callback_success_, callback_failed_, noparse_ = false, datatype_ = "json" )
	{
		let url = url_;
		let noparse = noparse_;
		let opts =
		{
			url : url,
			type : "put",
			data : file_,
			contentType: file_.type,
			cache : false,
			processData: false,
			success : function(json_)
			{
				//	パースなし指定ならそのまま返却
				if( noparse === true )
				{
					if( callback_success_ != null )
						callback_success_(json_);
					return;
				}

				//	フォーマットチェック。エラー時は エラコード = 0 とする
				if( json_.r == undefined || json_.d == undefined )
				{
					if( callback_failed_ != null )
						callback_failed_(0, "フォーマットエラー");
					return;
				}

				//	成功コードであること
				if( json_.r != AJAX_CODE_SUCCESS )
				{
					if( callback_failed_ != null )
						callback_failed_(json_.r, json_.d);
					return;
				}

				//	成功
				if( callback_success_ != null )
					callback_success_(json_.d);
			},
			error : function(req_, stat_, error_)
			{
				//	パースなし指定ならHTTPレスポンスコードを返却
				if( noparse === true )
				{
					if( callback_failed_ != null )
						callback_failed_(req_.status, error_.message);
					return;
				}

				//	通信失敗時は エラーコード = 0 とする
				if( callback_failed_ != null )
					callback_failed_(0, "通信エラー");
			}
		};

		if( datatype_ != "auto" ) opts.dataType = datatype_;
		$.ajax(opts);
	},

	//	private, CSRFのキャッシュ
	m :
	{
		"csrf" : {}
	}
};
