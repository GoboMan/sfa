"use strict"

const AWS = require("aws-sdk");
const cognito = new AWS.CognitoIdentityServiceProvider();
const crypto = require("crypto");
const ALGO = "aes-256-cbc";
const IV_LENGTH = 16;

exports.handler = async (event, context, callback) =>
{
	const USER_POOL_ID = event.request.clientMetadata.user_pool_id;
	const CLIENT_ID = event.request.clientMetadata.client_id;
	const CRYPTO_KEY = event.request.clientMetadata.crypto_key;
	const UNIQUE_ID = event.request.clientMetadata.unique_id;

	const encrypt = function( text_ )
	{
		let iv = crypto.randomBytes(IV_LENGTH);
		let cipher = crypto.createCipheriv(ALGO, new Buffer(CRYPTO_KEY), iv);
		let encrypted = cipher.update(text_);
		encrypted = Buffer.concat([encrypted, cipher.final()]);
		return iv.toString('hex') + ':' + encrypted.toString('hex');
	};

	if( event.userPoolId != USER_POOL_ID )
		throw new Error("Invalid user_pool_id");

	if( ! CLIENT_ID || CLIENT_ID === "" )
		throw new Error("Invalid CLIENT_ID");

	if( ! CRYPTO_KEY || CRYPTO_KEY === "" )
		throw new Error("Invalid CRYPTO_KEY");

	if( ! UNIQUE_ID || UNIQUE_ID === "" )
		throw new Error("Invalid UNIQUE_ID");

	if( event.triggerSource === "CustomMessage_AdminCreateUser" )
	{
		const PASSWORD = event.request.clientMetadata.password;
		if( ! PASSWORD || PASSWORD === "" )
			throw new Error("Invalid password");

		var mail_subject = event.request.clientMetadata.mail_subject;
		var mail_message_base = event.request.clientMetadata.mail_body;
		var mail_message = mail_message_base.replace(/%EMAIL%/g, event.request.userAttributes.email).replace(/%PASSWORD%/g, PASSWORD);
		event.response.emailSubject = mail_subject;
		event.response.emailMessage = mail_message;
	}
	//	サインアップ時のメールアドレス認証
	else if( event.triggerSource === "CustomMessage_SignUp" )
	{
		const VERIFY_URL = event.request.clientMetadata.verify_url;
		const RESEND_CODE_URL = event.request.clientMetadata.resend_code_url;
		const PASSWORD = event.request.clientMetadata.password;
		if( ! VERIFY_URL || VERIFY_URL === "" )
			throw new Error("Invalid VERIFY_URL");

		if( ! PASSWORD || PASSWORD === "" )
			throw new Error("Invalid password");

		var params = 
		{
			email : event.request.userAttributes["email"],
			unique_id : UNIQUE_ID,
			password : PASSWORD
		};
		var params_json = JSON.stringify(params);
		var crypted_text = encrypt(params_json);
		const verify_url = VERIFY_URL + "?hash=" + crypted_text;

		var mail_subject = event.request.clientMetadata.mail_subject;
		var mail_message_base = event.request.clientMetadata.mail_body;
		var mail_message = mail_message_base
			.replace(/%VERIFY_URL%/g, verify_url)
			.replace(/%RESEND_CODE_URL%/g, RESEND_CODE_URL)
			.replace(/%PASSWORD%/g, PASSWORD)
			;
		event.response.emailSubject = mail_subject;
		event.response.emailMessage = mail_message;
	}
	//	認証コード発行
	else if( event.triggerSource === "CustomMessage_ResendCode" )
	{
		const VERIFY_URL = event.request.clientMetadata.verify_url;

		var verify_url = "";

		var params = 
		{
			email : event.request.userAttributes["email"],
			unique_id : UNIQUE_ID,
			password : ""
		};
		var params_json = JSON.stringify(params);
		var crypted_text = encrypt(params_json);
		verify_url = VERIFY_URL + "?hash=" + crypted_text;

		var mail_subject = event.request.clientMetadata.mail_subject;
		var mail_message_base = event.request.clientMetadata.mail_body;
		var mail_message = mail_message_base.replace(/%VERIFY_URL%/g, verify_url);
		event.response.emailSubject = mail_subject;
		event.response.emailMessage = mail_message;
	}
	//	パスワード忘れ時のコード発行
	else if( event.triggerSource === "CustomMessage_ForgotPassword" )
	{
		const RESET_URL = event.request.clientMetadata.reset_url;
		if( ! RESET_URL || RESET_URL === "" )
			throw new Error("Invalid RESET_URL");

		var params = 
		{
			unique_id : UNIQUE_ID,
		};
		var params_json = JSON.stringify(params);
		var crypted_text = encrypt(params_json);
		const reset_url = RESET_URL + "?hash=" + crypted_text;

		var mail_subject = event.request.clientMetadata.mail_subject;
		var mail_message_base = event.request.clientMetadata.mail_body;
		var mail_message = mail_message_base.replace(/%RESET_URL%/g, reset_url);
		event.response.emailSubject = mail_subject;
		event.response.emailMessage = mail_message;
	}
	//	ユーザ属性更新(メールアドレス)
	else if( event.triggerSource === "CustomMessage_UpdateUserAttribute" )
	{
		const VERIFY_URL = event.request.clientMetadata.verify_url;
		if( ! VERIFY_URL || VERIFY_URL === "" )
			throw new Error("Invalid VERIFY_URL");

		const current_email = event.request.userAttributes["custom:current_email"];
		const update_params = 
		{
			UserPoolId: event.userPoolId,
			Username: event.userName,
			UserAttributes: 
			[
				{
					Name: "email_verified",
					Value: "true",
				},
				{
					Name: "email",
					Value: current_email,
				},
			],
		};

		const result = await cognito
			.adminUpdateUserAttributes(update_params)
			.promise()
			.catch(error =>{
				throw error;
			});

		if( current_email === event.request.userAttributes.email )
			throw new Error("failed to prevent sending unnecessary verification code");

		var params = 
		{
			unique_id : UNIQUE_ID,
		};
		var params_json = JSON.stringify(params);
		var crypted_text = encrypt(params_json);
		const verify_url = VERIFY_URL + "?hash=" + crypted_text;

		var mail_subject = event.request.clientMetadata.mail_subject;
		var mail_message_base = event.request.clientMetadata.mail_body;
		var mail_message = mail_message_base.replace(/%VERIFY_URL%/g, verify_url);
		event.response.emailSubject = mail_subject;
		event.response.emailMessage = mail_message;
	}

	callback(null, event)
}
