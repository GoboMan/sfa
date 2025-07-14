<?php
/*

	crow mail


	送信例）
		crow_mail::create()
			->name( 'from name' )
			->from( 'from_addr@mail' )
			->to( 'to_addr@mail' )
			->subject( 'タイトル' )
			->body( '内容' )
			->send()
			;

		通常 crow_mail::create() で取得したインスタンスは
		crow_config の mail.protocol に従ったインスタンスとなる
		crow_config で指定していないプロトコルを指定してインスタンスを取得する場合には、
		create_with_protocol() でインスタンスを作成する

		SMTP指定の場合には、PHPMaler を使用することとし、
		https://github.com/PHPMailer/PHPMailer/tree/master/src
		にある、ファイルを全て必要とする。
		engine/vendor/PHPMailer/src/PHPMailer.php のように配置する

	メールテンプレートの利用）
		subject()とbody()での内容の代わりに、template()を使うと、
		定義済みメールファイルを元にメッセージを作成することができる
		crow_mail::create()
			->name( 'from name' )
			->from( 'from_addr@mail' )
			->to( 'to_addr@mail' )
			->template( 'テンプレート名', 置換配列 )
			->send()
			;

		テンプレートは、
			[CROW_PATH]app/assets/mail/_common_/定義名.tpl
			[CROW_PATH]app/assets/mail/ロール名/定義名.tpl
		のように配置する。
		_common_とロール配下のどちらにも同じ名前のテンプレートがある場合、
		ロール名で定義したテンプレートが採用される

		1行目がタイトル、2行目以降を内容として扱い、
		文中の「%置換キー%」の部分は置換配列で指定された文字列で置換される。

		例）[CROW_PATH]app/assets/mail/front/regist.tpl
		-----------------------------
		「%SYSTEM_NAME%」登録完了のご案内
		%NAME% 様
		システムへの登録が完了しました。
		-----------------------------

		PHP側
		-----------------------------
		crow_mail::create()
			->name('system')
			->name('system@mail')
			->to('user@mail')
			->template('regist', ["SYSTEM_NAME"=>"テストシステム", "NAME"=>"藤崎"])
			->send()
			;
		-----------------------------

	※ AWS SES で送信する場合の注意点

		SESでの1回での送信最大件数はto/cc/bcc総合で 50 件となり、
		1件ずつの送信が推奨となっている
		それとは別に、24時間と秒間あたりの最大送信数もSES設定値として存在している

		最大数の設定値に関しては get_aws_quota() で取得できるため
		利用側で送信タイミングを調整すること。

		不正アドレスや受信拒否など、メールが届かないことが全体の5%を超えた場合に
		awsから管理者へ警告メールが届くようになる。10%を超えるとSES自体が停止してしまい、
		復旧するためにサポートセンターに改善内容を送る必要がある
		これを未然に防ぐために、メールアドレスの有効性をアプリ側で事前にチェックするのが望ましい。
		また、送信に失敗した一覧を get_aws_rejected_list() で取得できるので、
		送信後や定期バッチなどでチェックして、そのアドレスに対しては
		以降送信しないためのフラグを立てておくなどの対策がとれる。

		SES利用時には、AWS-SDKが必要になる。
		インストール方法
		$ cd [CROW_PATH/engine/]
		$ php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
		$ php composer-setup.php
		$ php -r "unlink('composer-setup.php');"
		$ sudo php ./composer.phar require aws/aws-sdk-php

		サプレッションリストの運用ルールについて
		・SES側にメアドは残しておかないと結局カウント上がるリスクしかないので、前提としてAWS側からは消さない。
		・できれば時間バッチ、最悪日次バッチで最新サプレッションリストを1000～1万件（件数はconfig）を日時指定で取得する。
		・WEB側のテーブルにそれらを保持する
		・WEBからメール送信の都度、そのテーブルに含まれるかチェックする
		・WEBに保持する最大件数を別途configに記載（1万でも十分な気はするが抱えるユーザ数による）

*/
class crow_mail
{
	//--------------------------------------------------------------------------
	//	作成
	//--------------------------------------------------------------------------
	public static function create()
	{
		$protocol = crow_config::get('mail.protocol', 'local');
		switch($protocol)
		{
			case 'local': return self::create_with_local();
			case 'smtp': return self::create_with_smtp();
			case 'aws': return self::create_with_aws();
		}
		return self::create_with_local();
	}

	//	プロトコル指定 : ローカル
	//	通常のメール送信
	public static function create_with_local()
	{
		$inst = new self();
		$inst->m_protocol = "local";
		$inst->m_mimetype = crow_config::get('mail.mimetype', 'text/plain');
		return $inst;
	}

	//	プロトコル指定 : SMTP
	//	アクセス情報を引数に渡す、authを指定する場合は"true"/"false"を文字列で指定する
	public static function create_with_smtp( $server_ = false, $port_ = "587", $auth_ = false, $user_ = false, $pw_ = false )
	{
		$inst = new self();
		$inst->m_protocol = "smtp";
		$inst->m_mimetype = crow_config::get('mail.mimetype', 'text/plain');
		$inst->m_smtp_host = $server_ !== false ? $server_ : crow_config::get('mail.smtp.host');
		$inst->m_smtp_port = $port_ !== false ? $port_ : crow_config::get('mail.smtp.port');
		$inst->m_smtp_auth = $auth_ !== false ? $auth_ : crow_config::get('mail.smtp.auth');
		$inst->m_smtp_user = $user_ !== false ? $user_ : crow_config::get('mail.smtp.user');
		$inst->m_smtp_pw = $pw_ !== false ? $pw_ : crow_config::get('mail.smtp.password');
		return $inst;
	}

	//	プロトコル指定 : AWS
	//	crow_configのawsの設定キー（aws.default.key の "default" の部分）を指定する
	public static function create_with_aws( $aws_config_key_ = "default" )
	{
		$inst = new self();
		$inst->m_protocol = "aws";
		$inst->m_mimetype = crow_config::get('mail.mimetype', 'text/plain');
		$inst->m_aws_key = $aws_config_key_;
		return $inst;
	}

	//--------------------------------------------------------------------------
	//	送信者名
	//--------------------------------------------------------------------------
	public function name( $name_ = null )
	{
		if( is_null($name_) ) return $this->m_name;
		$this->m_name = $name_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	送信元アドレス
	//--------------------------------------------------------------------------
	public function from( $addr_ = null )
	{
		if( is_null($addr_) ) return $this->m_from;
		$this->m_from = $addr_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	送信先アドレス、複数の送信先を指定するには配列でアドレスを渡す
	//--------------------------------------------------------------------------
	public function to( $addr_ = null )
	{
		if( is_null($addr_) ) return $this->m_to;
		$this->m_to = $addr_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	件名
	//--------------------------------------------------------------------------
	public function subject( $subject_ = null )
	{
		if( is_null($subject_) ) return $this->m_subject;
		$this->m_subject = $subject_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	本文
	//--------------------------------------------------------------------------
	public function body( $body_ = null )
	{
		if( is_null($body_) ) return $this->m_body;
		$this->m_body = $body_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	mimetype
	//--------------------------------------------------------------------------
	public function mimetype( $mimetype_ = null )
	{
		if( is_null($mimetype_) ) return $this->m_mimetype;
		$this->m_mimetype = $mimetype_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	テンプレートから件名と本文を指定する
	//--------------------------------------------------------------------------
	public function template( $template_name_, $replace_map_ )
	{
		//	対象のテンプレートを見つける
		$fname = CROW_PATH."app/assets/mail/".crow_request::get_role_name()."/".$template_name_.".tpl";
		if( is_file($fname) === false )
		{
			$fname = CROW_PATH."app/assets/mail/_common_/".$template_name_.".tpl";
			if( is_file($fname) === false )
			{
				crow_log::notice("undefined mail template : ".$template_name_);
				return $this;
			}
		}

		//	テンプレート読み込み
		$lines = file($fname);
		if( count($lines) > 0 )
		{
			$this->m_subject = trim($lines[0]);
			$this->m_body = "";
			for( $i=1; $i<count($lines); $i++ )
				$this->m_body .= trim($lines[$i])."\n";
		}

		//	置換マップの適用
		if( count($replace_map_) > 0 )
		{
			foreach( $replace_map_ as $key => $val )
			{
				$this->m_subject = str_replace("%".$key."%", $val, $this->m_subject);
				$this->m_body = str_replace("%".$key."%", $val, $this->m_body);
			}
		}

		return $this;
	}

	//--------------------------------------------------------------------------
	//	ヘッダ
	//--------------------------------------------------------------------------
	public function header( $header_ = null )
	{
		if( is_null($header_) ) return $this->m_header;
		$this->m_header = $header_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	パラメータ
	//--------------------------------------------------------------------------
	public function param( $param_ = null )
	{
		if( is_null($param_) ) return $this->m_param;
		$this->m_param = $param_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	添付ファイル（キーがファイル名、値がファイルパスの連想配列を指定）
	//--------------------------------------------------------------------------
	public function files( $files_ = null )
	{
		if( is_null($files_) ) return $this->m_files;
		$this->m_files = $files_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	添付データ（キーがファイル名、値が生データの連想配列を指定）
	//--------------------------------------------------------------------------
	public function rawfiles( $rawfiles_ = null )
	{
		if( is_null($rawfiles_) ) return $this->m_rawfiles;
		$this->m_rawfiles = $rawfiles_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	CC（アドレスの配列で指定）
	//--------------------------------------------------------------------------
	public function cc( $addr_arr_ = null )
	{
		if( is_null($addr_arr_) ) return $this->m_cc;
		$this->m_cc = $addr_arr_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	BCC（アドレスの配列で指定）
	//--------------------------------------------------------------------------
	public function bcc( $addr_arr_ = null )
	{
		if( is_null($addr_arr_) ) return $this->m_bcc;
		$this->m_bcc = $addr_arr_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	Reply-To（アドレスの配列で指定）
	//--------------------------------------------------------------------------
	public function reply_to( $addr_arr_ = null )
	{
		if( is_null($addr_arr_) ) return $this->m_reply_to;
		$this->m_reply_to = $addr_arr_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	返信先アドレス
	//--------------------------------------------------------------------------
	public function return_path( $addr_ = null )
	{
		if( is_null($addr_) ) return $this->m_return_path;
		$this->m_return_path = $addr_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	boundary
	//--------------------------------------------------------------------------
	public function boundary( $boundary_ = null )
	{
		if( is_null($boundary_) ) return $this->m_boundary;
		$this->m_boundary = $boundary_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	送信先ホワイトリストの登録/取得
	//
	//	ホワイトリストを登録すると、そのリストに一致する送信先にのみ送信されるようになる
	//	indexや、各ロールのモジュールベースクラスなどで登録するような想定
	//	configのmail.sendはtrueである必要あり
	//
	//	引数には通過させたいメールアドレスの配列を指定する
	//	@より前を「*」にすると、ドメイン全てを許可とする。
	//	例）「*@gmail.com」を指定するとgmail.com宛ての送信を全て許可する
	//--------------------------------------------------------------------------
	public static function whitelist( $mail_addresses_ = null )
	{
		if( is_null($mail_addresses_) === true ) return self::$m_whitelist;
		self::$m_whitelist = $mail_addresses_;
	}
	public static function clear_whitelist()
	{
		self::$m_whitelist = false;
	}

	//--------------------------------------------------------------------------
	//	送信ログの出力
	//	force 指定で設定値によらず強制出力が可能
	//--------------------------------------------------------------------------
	public static function output_log( $instance_, $force_ = false )
	{
		if( $force_ === true || crow_config::get('log.mail', "false") == "true" )
		{
			crow_log::log_with_name( 'mail', "\n"
				.$instance_->build_header()
				."To: ".(is_array($instance_->m_to) ? implode(", ", $instance_->m_to) : $instance_->m_to)."\n"
				."Subject: ".$instance_->m_subject."\n\n"
				.$instance_->build_body()
			);
		}
	}

	//--------------------------------------------------------------------------
	//	送信
	//--------------------------------------------------------------------------
	public function send()
	{
		//	boundaryがセットされていなければ作成する
		if( strlen($this->m_boundary) <= 0 )
			$this->m_boundary = crow_utility::random_str();

		//	ログ出力指定があればログに出す
		self::output_log($this);

		//	実際には送信しない指定があれば送信しない
		$send = crow_config::get('mail.send', "true");
		if( $send !== "true" ) return true;

		//	whitelistが指定されている場合、一致しないメールアドレスに対しては送信しない
		if( self::$m_whitelist !== false )
		{
			if( is_array($this->m_to) === true )
			{
				$new_to = [];
				foreach( $this->m_to as $to )
				{
					if( in_array($to, self::$m_whitelist) === false )
					{
						$at = strpos($to, "@");
						if( $at === false || in_array("*".substr($to, $at), self::$m_whitelist) === false )
							continue;
					}
					$new_to[] = $to;
				}
				if( count($new_to) <= 0 ) return true;

				$this->m_to = $new_to;
			}
			else if( in_array($this->m_to, self::$m_whitelist) === false )
			{
				$at = strpos($this->m_to, "@");
				if( $at === false || in_array("*".substr($this->m_to, $at), self::$m_whitelist) === false )
					return true;
			}
		}

		mb_language( "ja" );
		mb_internal_encoding( "UTF-8" );

		//	ローカル指定
		if( $this->m_protocol == "local" )
		{
			$result = mail
			(
				 is_array($this->m_to) ? implode(",", $this->m_to) : $this->m_to,
				 mb_encode_mimeheader( $this->m_subject, "UTF-8", "B" ),
				 $this->build_body(),
				 $this->build_header(),
				 $this->build_param()
			);
			$this->set_last_error(0, $result === false ? $this->get_last_error()['message'] : '');
			return $result;
		}
		//	SMTP指定
		else if( $this->m_protocol == "smtp" )
		{
			//	PHPMailerを使用して送信。PHPMailerを読み込んでいなければ読み込む
			if( class_exists('PHPMailer') === false )
			{
				require_once(CROW_PATH.'engine/vendor/PHPMailer/src/PHPMailer.php');
				require_once(CROW_PATH.'engine/vendor/PHPMailer/src/SMTP.php');
				require_once(CROW_PATH.'engine/vendor/PHPMailer/src/POP3.php');
				require_once(CROW_PATH.'engine/vendor/PHPMailer/src/Exception.php');
				//エラーになるので以下はrequireしない
				//require_once(CROW_PATH.'engine/vendor/PHPMailer/src/OAuth.php');
				//require_once(CROW_PATH.'engine/vendor/PHPMailer/src/OAuthTokenProvider.php');
			}

			//	送信単位で、インスタンス作成
			$this->m_handle = new PHPMailer\PHPMailer\PHPMailer();
			$this->m_handle->isSMTP();
			$this->m_handle->Host = $this->m_smtp_host;
			$this->m_handle->Port = $this->m_smtp_port;

			//	To
			if( is_array($this->m_to) === true )
			{
				foreach( $this->m_to as $to )
					$this->m_handle->AddAddress($to);
			}
			else
			{
				$this->m_handle->AddAddress($this->m_to);
			}

			//	Cc
			if( is_array($this->m_cc) === true )
			{
				foreach( $this->m_cc as $cc )
					$this->m_handle->AddCC($cc);
			}
			else if( $this->m_cc !== "" )
			{
				$this->m_handle->AddCC($this->m_cc);
			}

			//	Bcc
			if( is_array($this->m_bcc) === true )
			{
				foreach( $this->m_bcc as $bcc )
					$this->m_handle->AddBCC($bcc);
			}
			else if( $this->m_bcc !== "" )
			{
				$this->m_handle->AddBCC($this->m_bcc);
			}

			// Reply-To
			if( is_array($this->m_reply_to) === true )
			{
				$this->m_handle->clearReplyTos();
				foreach( $this->m_reply_to as $reply_to )
					$this->m_handle->AddReplyTo($reply_to);
			}
			else if( $this->m_reply_to !== "" )
			{
				$this->m_handle->clearReplyTos();
				$this->m_handle->AddReplyTo($this->m_reply_to);
			}

			// Return-Path
			if( $this->m_return_path !== "" )
			{
				$this->m_handle->Sender = $this->m_return_path;
			}

			//	From
			$this->m_handle->From = $this->m_from;
			$this->m_handle->FromName = $this->m_name;

			//	Subject, Body
			$this->m_handle->Subject = $this->m_subject;
			$this->m_handle->Body = $this->m_body;
			$this->m_handle->CharSet = 'utf-8';

			//	Attachments
			if( count($this->m_files) > 0 )
			{
				foreach( $this->m_files as $file_name => $file_path )
				{
					if( ! file_exists( $file_path ) )
					{
						crow_log::warning( 'メールに添付するファイルが見つかりません:'.$file_path );
						continue;
					}

					$this->m_handle->addAttachment($file_path, $file_name);
				}
			}
			if( count($this->m_rawfiles) > 0 )
			{
				foreach( $this->m_rawfiles as $file_name => $file_data )
				{
					$this->m_handle->addStringAttachment($file_data, $file_name);
				}
			}

			//	認証指定
			$is_auth = $this->m_smtp_auth !== "false";
			$this->m_handle->SMTPAuth = $is_auth;
			if( $is_auth === true )
			{
				$this->m_handle->Username = $this->m_smtp_user;
				$this->m_handle->Password = $this->m_smtp_pw;
				$this->m_handle->SMTPSecure = $this->m_smtp_auth;
				$this->m_handle->SMTPOptions =
				[
					'ssl' =>
					[
						'verify_peer' => false,
						'verify_peer_name' => false,
					]
				];
			}

			//	エラー時に情報を拾う
			$this->m_handle->SMTPDebug = 2;
			$this->m_handle->Debugoutput = function($str_, $level_)
			{
				if( strpos(strtolower($str_), 'smtp error') !== false )
				{
					crow_log::notice('smtp error [debug_level:'.$level_.']: '.$str_);
					return false;
				}
			};

			//	送信
			$ret = $this->m_handle->Send();
			if( ! $ret )
			{
				$this->set_last_error(0, $this->m_handle->ErrorInfo);
				crow_log::notice('failed to send smtp : '.$this->m_handle->ErrorInfo);
				return false;
			}
			return $ret;
		}
		//	AWS指定
		else if( $this->m_protocol == "aws" )
		{
			//	ses 初期化
			$this->init_ses();

			//	パラメータ作成と送信
			$result = false;
			$mail_info = $this->build_aws_mail();
			try
			{
				//	添付ファイル有無でメソッドが異なる
				if( count($this->m_files) > 0 || count($this->m_rawfiles) > 0 ) $result = $this->m_handle->sendRawEmail($mail_info);
				else $result = $this->m_handle->sendEmail($mail_info);
			}
			catch( \Aws\Ses\Exception\SesException $e_ )
			{
				$code = $e_->getStatusCode();
				$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
				$this->set_last_error($code, $msg);

				$err = "failed to send mail to ".implode(",", $mail_info['Destination']['ToAddresses']).", ".$msg;
				crow_log::notice($err);
				return false;
			}
			catch( \Aws\Exception\AwsException $e_ )
			{
				$code = $e_->getStatusCode();
				$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
				$this->set_last_error($code, $msg);

				$err = "failed to send mail to ".implode(",", $mail_info['Destination']['ToAddresses']).", ".$msg;
				crow_log::notice($err);
				return false;
			}
			catch( Exception $e_ )
			{
				$code = $e_->getCode();
				$msg = $e_->getMessage();
				$this->set_last_error($code, $msg);

				$err = "failed to send mail to ".implode(",", $mail_info['Destination']['ToAddresses']).", ".$msg;
				crow_log::notice($err);
				return false;
			}
			return $result;
		}
	}

	//--------------------------------------------------------------------------
	//	最後のエラー詳細を取得
	//--------------------------------------------------------------------------

	//	プロトコルによって返却コードが異なるので注意すること。
	//	phpとsmtpによるメールの場合は常に0が返却される。
	public function get_last_error_code()
	{
		return $this->m_error_code;
	}

	//	エラーメッセージ取得
	public function get_last_error()
	{
		return $this->m_error_msg;
	}

	//--------------------------------------------------------------------------
	//	AWS SES での送信上限値と現在値を取得
	//
	//	返却は下記連想配列となる
	//		Max24HourSend		: 24時間内での最大送信数
	//		MaxSendRate			: 秒間あたりの送信最大数
	//		SentLast24Hours		: 24時間内で実際に送信された回数
	//--------------------------------------------------------------------------
	public function get_aws_quota()
	{
		//	ses 初期化
		$this->init_ses();

		//	設定値と状態取得
		$result = false;
		try
		{
			$result = $this->m_handle->getSendQuota();
			if( $result instanceof \Aws\Result )
				$result = $result->toArray();
		}
		catch( \Aws\Exception\AwsException $e_ )
		{
			$code = $e_->getStatusCode();
			$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get quota, ".$code." : ".$msg);
			return false;
		}
		catch( Exception $e_ )
		{
			$code = $e_->getStatusCode();
			$msg = $e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get quota, ".$e_->getMessage());
			return false;
		}
		return $result;
	}

	//--------------------------------------------------------------------------
	//	AWS SES での送信不能になった一覧を取得
	//	APIコールになるため、頻繁に呼ばないように使用側で調整すること
	//
	//	$start_date_	: タイムスタンプ
	//	$end_date_		: タイムスタンプ
	//	
	//	返却は次の形の配列とする。
	//	[
	//		[
	//			"addr"      => メールアドレス
	//			"reason"    => "bounce" or "complaint"
	//			"timestamp" => 検知した最終時刻のタイムスタンプ(秒)
	//		],
	//	]
	//--------------------------------------------------------------------------
	public function get_aws_rejected_list( $start_date_ = false, $end_date_  = false )
	{
		//	ses 初期化
		$this->init_ses_v2();

		//	1000件ずつ取得
		$addrs = [];
		$opts =
		[
			"PageSize" => 1000,
		];
		if( $start_date_ !== false )
		{
			$opts["StartDate"] = $start_date_;
		}
		if( $end_date_ !== false )
		{
			$opts["EndDate"] = $end_date_;
		}

		try
		{
			$next_token = false;
			while(1)
			{
				if( $next_token !== false ) $opts['NextToken'] = $next_token;
				$result = $this->m_handle_v2->ListSuppressedDestinations($opts);
				if( $result instanceof \Aws\Result )
					$result = $result->toArray();

				$summaries = isset($result['SuppressedDestinationSummaries']) === true ?
					$result['SuppressedDestinationSummaries'] : [];
				if( count($summaries) <= 0 ) break;

				foreach( $summaries as $row )
				{
					//	念のためオブジェクト判定をしておく
					$timestamp =
						($row['LastUpdateTime'] instanceof \Aws\Api\DateTimeResult) ?
						$row['LastUpdateTime']->getTimestamp() : intval($row['LastUpdateTime'])
						;

					$addrs[$row['EmailAddress']] =
					[
						"addr" => $row['EmailAddress'],
						"reason" => $row['Reason'],
						"timestamp" => $timestamp
					];
				}

				if( isset($result['NextToken']) === false ) break;
				$next_token = $result['NextToken'];
			}
		}
		catch( \Aws\SesV2\Exception\SesV2Exception $e_ )
		{
			$code = $e_->getStatusCode();
			$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get rejected list, ".$code." : ".$msg." / ".$e_->getMessage());
			return false;
		}
		catch( Exception $e_ )
		{
			$code = $e_->getCode();
			$msg = $e_->getMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get rejected list, ".$code." : ".$msg);
			return false;
		}

		return array_values($addrs);
	}

	//--------------------------------------------------------------------------
	//	AWS SES での送信不能リストにメールアドレスが存在するか確認する
	//	
	//	APIの結果は
	//	送信成功の場合はリストに存在しないので404でfalse返却
	//	送信失敗の場合は連想配列で詳細情報が取得できる
	//	[
	//		"SuppressedDestination" => 
	//		[
	//			"EmailAddress" => "xxx",
	//			"Reason" => "BOUNCE|COMPLAINT",
	//			"LastUpdateTime" => Aws\Api\DateTimeResult Object
	//			"Attributes" => ["MessageId" => "xxx", "FeedbackId" => "xxx"]
	//		]
	//	];
	//
	//	@return array | false 
	//--------------------------------------------------------------------------
	public function get_aws_rejected_addr( $addr_ )
	{
		//	ses 初期化
		$this->init_ses_v2();

		try
		{
			$result = $this->m_handle_v2->getSuppressedDestination(
			[
				"EmailAddress" => $addr_
			]);
			if( $result instanceof \Aws\Result )
				$result = $result->toArray();

			return $result["SuppressedDestination"];
		}
		catch( \Aws\SesV2\Exception\SesV2Exception $e_ )
		{
			$code = $e_->getStatusCode();
			if( $code == 404 )
			{
				return false;
			}
			$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get rejected addr, ".$code." : ".$msg);
			return false;
		}
		catch( \Aws\Exception\AwsException $e_ )
		{
			$code = $e_->getStatusCode();
			if( $code == 404 )
			{
				return false;
			}
			$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get rejected addr, ".$code." : ".$msg);
			return false;
		}
		catch( Exception $e_ )
		{
			$code = $e_->getCode();
			$msg = $e_->getMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get rejected addr, ".$code." : ".$msg);
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	AWS SES でのエラーレートのCloudWatchメトリクスからの取得
	//	
	//	$type_ = BounceRate | ComplaintRate
	//	
	//	おおよそ15分に一回更新される
	//	返却はメトリクス最新データポイントのAverageの値
	//	バウンス率:推奨5%未満。10%超過で確認中ステータスに自動変更
	//	苦情率:推奨0.1%未満。0.5%超過で確認中ステータスに自動変更
	//--------------------------------------------------------------------------
	public function get_aws_error_rates( $type_ = false )
	{
		//	cloudwatch 初期化
		$this->init_cloudwatch();

		//	設定値と状態取得
		$now = time();
		$ts_start = $now - 60*30;
		$ts_end = $now;
		$start_date = str_replace(' ', 'T', date('Y-m-d H:i:s+09:00', $ts_start));
		$end_date = str_replace(' ', 'T', date('Y-m-d H:i:s+09:00', $ts_end));
		$period = 60;
		// $stat = ['Average','Maximum','Minimum','Sum','SampleCount']からaverageを選択
		$stat = 'Average';

		$result = false;
		try
		{
			$param =
			[
				"StartTime" => $start_date,
				"EndTime" => $end_date,
				'MetricDataQueries' => [],
			];

			//	BounceRateの取得設定
			$metric_bounce_rate =
			[
				'Id' => 'bounce_rate',
				'MetricStat' =>
				[
					'Metric' =>
					[
						"Namespace" =>  "AWS/SES",
						"MetricName" => "Reputation.BounceRate",
					],
					"Period" => $period,
					"Stat" => "Average",
				],
			];

			//	ComplaintRateの取得設定
			$metric_complaint_rate =
			[
				'Id' => 'complaint_rate',
				'MetricStat' =>
				[
					'Metric' =>
					[
						"Namespace" =>  "AWS/SES",
						"MetricName" => "Reputation.ComplaintRate",
					],
					"Period" => $period,
					"Stat" => $stat,
				],
			];

			$ret = [];
			if( $type_ === false )
			{
				$param['MetricDataQueries'][] = $metric_bounce_rate;
				$param['MetricDataQueries'][] = $metric_complaint_rate;
				$ret['bounce_rate'] = [];
				$ret['complaint_rate'] = [];
			}
			else
			{
				if( in_array('bounce_rate', $type_) === true )
				{
					$param['MetricDataQueries'][] = $metric_bounce_rate;
					$ret['bounce_rate'] = [];
				}
				if( in_array('complaint_rate', $type_) === true )
				{
					$param['MetricDataQueries'][] = $metric_complaint_rate;
					$ret['complaint_rate'] = [];
				}
			}

			$result = $this->m_handle_cloudwatch->getMetricData($param);
			if( $result instanceof \Aws\Result )
				$result = $result->toArray();

			if( isset($result['MetricDataResults']) === false
				|| count($result['MetricDataResults']) <= 0
			){
				return false;
			}

			foreach( $result['MetricDataResults'] as $data )
			{
				$id = $data['Id'];
				if( isset($ret[$id]) === true )
				{
					$ret[$id] = sprintf('%.5f', $data['Values'][0]) * 100;
				}
			}

			return $ret;
		}
		catch( \Aws\CloudWatch\Exception\CloudWatchException $e_ )
		{
			$code = $e_->getStatusCode();
			$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get ".$type_.", ".$code." : ".$msg);
			return false;
		}
		catch( \Aws\Exception\AwsException $e_ )
		{
			$code = $e_->getStatusCode();
			$msg = $e_->getAwsErrorCode()." : ".$e_->getAwsErrorMessage();
			$this->set_last_error($code, $msg);
			crow_log::notice("failed to get ".$type_.", ".$code." : ".$msg);
			return false;
		}
		catch( Exception $e_ )
		{
			crow_log::notice("failed to get ".$type_.$e_->getMessage());
			return false;
		}

		return $result;
	}

	//--------------------------------------------------------------------------
	//	AWS SES でのバウンスレートの取得
	//--------------------------------------------------------------------------
	public function get_aws_bounce_rate()
	{
		return $this->get_aws_error_rates(['bounce_rate']);
	}

	//--------------------------------------------------------------------------
	//	AWS SES での苦情レートの取得
	//--------------------------------------------------------------------------
	public function get_aws_complaint_rate()
	{
		return $this->get_aws_error_rates(['complaint_rate']);
	}

	//--------------------------------------------------------------------------
	//	AWS SES 送信内容構築
	//--------------------------------------------------------------------------
	private function build_aws_mail()
	{
		$mail_info = [];

		//	添付ファイルなしの場合
		if( count($this->m_files) <= 0 && count($this->m_rawfiles) <= 0 )
		{
			$mail_info =
			[
				//	送信元
				"Source" => $this->build_from(),
				//	宛先
				"Destination" =>
				[
					"ToAddresses" => is_array($this->m_to) ? $this->m_to : [$this->m_to],
					"CcAddresses" => is_array($this->m_cc) ? $this->m_cc : [$this->m_cc],
					"BccAddresses" => is_array($this->m_bcc) ? $this->m_bcc : [$this->m_bcc],
				],
				// Reply-To
				"ReplyToAddresses" => is_array($this->m_reply_to) ? $this->m_reply_to : [$this->m_reply_to],
				//	メッセージ
				"Message" =>
				[
					//	件名
					"Subject" =>
					[
						"Data" => $this->m_subject,
						"Charset" => 'utf-8',
					],
					//	本文
					"Body" =>
					($this->m_mimetype === 'text/html'
						?
							[
								"Html" =>
								[
									"Data" => $this->m_body,
									"Charset" => 'utf-8'
								]
							]
						:
							[
								"Text" =>
								[
									"Data" => $this->m_body,
									"Charset" => 'utf-8'
								]
							]
					)
				],
			];
			// Return-Path
			if( $this->m_return_path !== "" )
			{
				$mail_info["ReturnPath"] = $this->m_return_path;
			}
		}

		//	添付ファイルありの場合
		else
		{
			$rawdata = ''
				.$this->build_header()
				."To: ".(is_array($this->m_to) ? implode(", ", $this->m_to) : $this->m_to)."\n"
				."Subject: ".mb_encode_mimeheader($this->m_subject, "UTF-8", "B")."\n\n"
				.$this->build_body()
				;

			$mail_info =
			[
				//	送信元
				"Source" => $this->m_from,
				//	送信先
				"Destination" =>
				[
					"ToAddresses" => is_array($this->m_to) ? $this->m_to : [$this->m_to],
					"CcAddresses" => is_array($this->m_cc) ? $this->m_cc : [$this->m_cc],
					"BccAddresses" => is_array($this->m_bcc) ? $this->m_bcc : [$this->m_bcc],
				],
				//	生ボディ
				"RawMessage" =>
				[
					"Data" => $rawdata,
				],
			];
		}

		return $mail_info;
	}

	//--------------------------------------------------------------------------
	//	本文の構築
	//--------------------------------------------------------------------------
	private function build_body()
	{
		$body = $this->m_body;
		if( count($this->m_files) <= 0 && count($this->m_rawfiles) <= 0 ) return $body;

		//	ファイル添付
		$body = ""
			."--".$this->m_boundary."\n"
			."Content-Type: ".$this->m_mimetype."; charset=\"UTF-8\"\n"
			."Content-Transfer-Encoding: 8bit\n"
			."\n"
			.$body."\n"
			;

		foreach( $this->m_files as $file_name => $file_path )
		{
			if( ! file_exists( $file_path ) )
			{
				crow_log::warning( 'メールに添付するファイルが見つかりません:'.$file_path );
				continue;
			}

			$content	= "application/octet-stream";
			$filename	= mb_encode_mimeheader( $file_name, "UTF-8", "B" );

			$body .= ''
				."--".$this->m_boundary."\n"
				."Content-Type: ".$content."; charset=\"UTF-8\"; name=\"".$filename."\"\n"
				."Content-Transfer-Encoding: base64\n"
				."Content-Disposition: attachment; filename=\"".$filename."\"\n"
				."\n"
				.chunk_split( base64_encode(file_get_contents($file_path)) )."\n"
				;
		}
		foreach( $this->m_rawfiles as $file_name => $file_data )
		{
			$content	= "application/octet-stream";
			$filename	= mb_encode_mimeheader( $file_name, "UTF-8", "B" );

			$body .= ''
				."--".$this->m_boundary."\n"
				."Content-Type: ".$content."; charset=\"UTF-8\"; name=\"".$filename."\"\n"
				."Content-Transfer-Encoding: base64\n"
				."Content-Disposition: attachment; filename=\"".$filename."\"\n"
				."\n"
				.chunk_split( base64_encode($file_data) )."\n"
				;
		}
		$body .= '--'.$this->m_boundary.'--';
		return $body;
	}

	//--------------------------------------------------------------------------
	//	fromの構築
	//--------------------------------------------------------------------------
	private function build_from()
	{
		$from = "";
		if( strlen($this->m_name) <= 0 ) $from .= $this->m_from;
		else
		{
			$from .= mb_encode_mimeheader($this->m_name, "UTF-8", "B")." <".$this->m_from.">";
		}
		return $from;
	}

	//--------------------------------------------------------------------------
	//	CCの構築
	//--------------------------------------------------------------------------
	private function build_cc()
	{
		$cc = "";
		if( is_array($this->m_cc) === true )
		{
			if( count($this->m_cc) > 0 )
			{
				$cc .= "Cc: ".implode(",", $this->m_cc)."\r\n";
			}
		}
		else if ( $this->m_cc !== "" )
		{
			$cc .= "Cc: ".$this->m_cc."\r\n";
		}
		return $cc;
	}

	//--------------------------------------------------------------------------
	//	BCCの構築
	//--------------------------------------------------------------------------
	private function build_bcc()
	{
		$bcc = "";
		if( is_array($this->m_bcc) === true )
		{
			if( count($this->m_bcc) > 0 )
			{
				$bcc .= "Bcc: ".implode(",", $this->m_bcc)."\r\n";
			}
		}
		else if ( $this->m_bcc !== "" )
		{
			$bcc .= "Bcc: ".$this->m_bcc."\r\n";
		}
		return $bcc;
	}

	//--------------------------------------------------------------------------
	//	ヘッダの構築
	//--------------------------------------------------------------------------
	private function build_header()
	{
		$header = "";

		//	デフォルト
		$header .= "X-Mailer: PHP".phpversion()."\r\n";
		$header .= "From: " . $this->build_from() . "\r\n";
		$header .= "Return-Path: " . $this->build_from() . "\r\n";
		$header .= $this->build_cc();
		$header .= $this->build_bcc();
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Transfer-Encoding: 8bit\r\n";
		if( count($this->m_files) <= 0 && count($this->m_rawfiles) <= 0 ){
			$header .= "Content-Type: ".$this->m_mimetype."; charset=\"UTF-8\"\n";
		}
		else {
			$header .= "Content-Type: multipart/mixed; boundary=\"" . $this->m_boundary . "\"\n";
		}

		//	ユーザ定義
		$header .= $this->m_header;
		return $header;
	}

	//--------------------------------------------------------------------------
	//	パラメータの構築
	//--------------------------------------------------------------------------
	private function build_param()
	{
		$param = '';
		$param .= "-f ".$this->m_from;
		$param .= $this->m_param;
		return $param;
	}

	//--------------------------------------------------------------------------
	//	AWS SES ハンドル初期化、初期化済みの場合は何もしない
	//--------------------------------------------------------------------------
	private function init_ses()
	{
		if( $this->m_handle === false )
		{
			//	AWS SDK を使用して送信。読み込んでいなければ読み込む
			if( class_exists('SesClient') === false )
			{
				require_once(CROW_PATH.'engine/vendor/autoload.php');
			}

			$profile_info =
			[
				"credentials"	=>
				[
					"key"		=> crow_config::get("aws.".$this->m_aws_key.".key"),
					"secret"	=> crow_config::get("aws.".$this->m_aws_key.".secret")
				],
				"region"		=> crow_config::get("aws.".$this->m_aws_key.".region"),
				"version"		=> crow_config::get("aws.".$this->m_aws_key.".version")
			];

			//	proxy設定があれば追加
			$proxy_http = crow_config::get_if_exists("aws.".$this->m_aws_key.".proxy.http", '');
			if( strlen($proxy_http) > 0 )
			{
				if( isset($profile_info['http']) === false )
				{
					$profile_info['http'] = ['proxy'=>[]];
				}
				$profile_info['http']['proxy']['http'] = $proxy_http;
			}
			$proxy_https = crow_config::get_if_exists("aws.".$this->m_aws_key.".proxy.https", '');
			if( strlen($proxy_https) > 0 )
			{
				if( isset($profile_info['http']) === false )
				{
					$profile_info['http'] = ['proxy'=>[]];
				}
				$profile_info['http']['proxy']['https'] = $proxy_https;
			}

			$this->m_handle = Aws\Ses\SesClient::factory($profile_info);
		}
		return $this->m_handle;
	}

	//--------------------------------------------------------------------------
	//	AWS SES V2用のハンドル初期化、初期化済みの場合は何もしない
	//--------------------------------------------------------------------------
	private function init_ses_v2()
	{
		if( $this->m_handle_v2 === false )
		{
			//	AWS SDK を使用して送信。読み込んでいなければ読み込む
			if( class_exists('SesV2Client') === false )
			{
				require_once(CROW_PATH.'engine/vendor/autoload.php');
			}

			$profile_info =
			[
				"credentials"	=>
				[
					"key"		=> crow_config::get("aws.".$this->m_aws_key.".key"),
					"secret"	=> crow_config::get("aws.".$this->m_aws_key.".secret")
				],
				"region"		=> crow_config::get("aws.".$this->m_aws_key.".region"),
				"version"		=> crow_config::get("aws.".$this->m_aws_key.".version")
			];

			//	proxy設定があれば追加
			$proxy_http = crow_config::get_if_exists("aws.".$this->m_aws_key.".proxy.http", '');
			if( strlen($proxy_http) > 0 )
			{
				if( isset($profile_info['http']) === false )
				{
					$profile_info['http'] = ['proxy'=>[]];
				}
				$profile_info['http']['proxy']['http'] = $proxy_http;
			}
			$proxy_https = crow_config::get_if_exists("aws.".$this->m_aws_key.".proxy.https", '');
			if( strlen($proxy_https) > 0 )
			{
				if( isset($profile_info['http']) === false )
				{
					$profile_info['http'] = ['proxy'=>[]];
				}
				$profile_info['http']['proxy']['https'] = $proxy_https;
			}

			$this->m_handle_v2 = Aws\SesV2\SesV2Client::factory($profile_info);
		}
		return $this->m_handle_v2;
	}

	//--------------------------------------------------------------------------
	//	AWS CloudWatch用のハンドル初期化、初期化済みの場合は何もしない
	//--------------------------------------------------------------------------
	private function init_cloudwatch()
	{
		if( $this->m_handle_cloudwatch === false )
		{
			//	AWS SDK を使用して送信。読み込んでいなければ読み込む
			if( class_exists('CloudWacthClient') === false )
			{
				require_once(CROW_PATH.'engine/vendor/autoload.php');
			}

			$profile_info =
			[
				"credentials"	=>
				[
					"key"		=> crow_config::get("aws.".$this->m_aws_key.".key"),
					"secret"	=> crow_config::get("aws.".$this->m_aws_key.".secret")
				],
				"region"		=> crow_config::get("aws.".$this->m_aws_key.".region"),
				"version"		=> crow_config::get("aws.".$this->m_aws_key.".version")
			];

			//	proxy設定があれば追加
			$proxy_http = crow_config::get_if_exists("aws.".$this->m_aws_key.".proxy.http", '');
			if( strlen($proxy_http) > 0 )
			{
				if( isset($profile_info['http']) === false )
				{
					$profile_info['http'] = ['proxy'=>[]];
				}
				$profile_info['http']['proxy']['http'] = $proxy_http;
			}
			$proxy_https = crow_config::get_if_exists("aws.".$this->m_aws_key.".proxy.https", '');
			if( strlen($proxy_https) > 0 )
			{
				if( isset($profile_info['http']) === false )
				{
					$profile_info['http'] = ['proxy'=>[]];
				}
				$profile_info['http']['proxy']['https'] = $proxy_https;
			}

			$this->m_handle_cloudwatch = Aws\CloudWatch\CloudWatchClient::factory($profile_info);
		}
		return $this->m_handle_cloudwatch;
	}

	//--------------------------------------------------------------------------
	//	エラーセット
	//--------------------------------------------------------------------------
	private function set_last_error($code_, $msg_)
	{
		$this->m_error_code = $code_;
		$this->m_error_msg = $msg_;
		return $this;
	}

	//	private
	private $m_handle		= false;
	private $m_handle_v2	= false;
	private $m_handle_cloudwatch = false;
	private $m_protocol		= "";
	private $m_mimetype		= "text/plain";
	private $m_smtp_host	= "";
	private $m_smtp_port	= "";
	private $m_smtp_auth	= "";
	private $m_smtp_user	= "";
	private $m_smtp_pw		= "";
	private $m_aws_key		= "";
	private $m_name			= "";
	private $m_from			= "";
	private $m_to			= "";
	private $m_subject		= "";
	private $m_body			= "";
	private $m_cc			= [];
	private $m_bcc			= [];
	private $m_reply_to		= [];
	private $m_return_path	= "";
	private $m_header		= "";
	private $m_param		= "";
	private $m_files		= [];
	private $m_rawfiles		= [];
	private $m_boundary		= "";
	private $m_error_code	= 0;
	private $m_error_msg	= "";

	//	whitelist
	private static $m_whitelist = false;
}
?>
