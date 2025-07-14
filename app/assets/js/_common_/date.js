/*

	日時関連のユーティリティ

	ビューの頭で、グローバル変数「g.formats.date」で下記のような文言マップをセットしておくこと

	例）header.php

		var g =
		{
			... 各種グローバル変数 ...
			formats : []
		};

		//	日付関連を追加
		//	#yearが年、#monthが月、#dayが日、#hourが時間、#minを分で置換する

		g.formats.date =
		{
			//	年月日
			year_month_day : "#year-#month-#day",

			//	時分
			hour_min : "#hour-#min",

			//	たった今
			now : "now",

			//	x分前
			mins_ago : "minutes ago",

			//	x時間前
			hours_ago : "hours ago",

			//	x日前
			days_ago : "days ago",

			//	xヶ月前
			months_ago : "months ago",

			//	x年前
			years_ago : "years ago",
		};
*/

//------------------------------------------------------------------------------
//	Dateオブジェクトからカレンダーで扱える文字列への変更 (YYYY-MM-DD)
//------------------------------------------------------------------------------
function date_to_calstr(date_, sep_ = "-")
{
	return "" +
		date_.getFullYear() + sep_ + 
		strpad_zero(date_.getMonth() + 1, 2) + sep_ +
		strpad_zero(date_.getDate(), 2)
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから文字列への変更 (YYYY-MM-DD)
//	月日は2桁でゼロ埋めされる
//------------------------------------------------------------------------------
function date_to_yyyymmdd(date_)
{
	return g.formats.date.year_month_day
		.replace('#year', date_.getFullYear())
		.replace('#month', strpad_zero(date_.getMonth() + 1, 2))
		.replace('#day', strpad_zero(date_.getDate(), 2))
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから文字列への変更 (YYYY-M-D)
//	月日はゼロ埋めなし
//------------------------------------------------------------------------------
function date_to_yyyymd(date_)
{
	return g.formats.date.year_month_day
		.replace('#year', date_.getFullYear())
		.replace('#month', date_.getMonth() + 1)
		.replace('#day', date_.getDate())
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから文字列への変更 (YYYY-M)
//	月はゼロ埋めなし
//------------------------------------------------------------------------------
function date_to_yyyym(date_)
{
	return g.formats.date.year_month
		.replace('#year', date_.getFullYear())
		.replace('#month', date_.getMonth() + 1)
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから文字列への変更 (YYYY)
//------------------------------------------------------------------------------
function date_to_yyyy(date_)
{
	return g.formats.date.year
		.replace('#year', date_.getFullYear())
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから文字列への変更 (M)
//------------------------------------------------------------------------------
function date_to_m(date_)
{
	return g.formats.date.month
		.replace('#month', date_.getMonth() + 1)
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから文字列への変更 (M-D)
//	月日はゼロ埋めなし
//------------------------------------------------------------------------------
function date_to_md(date_)
{
	return g.formats.date.month_day
		.replace('#month', date_.getMonth() + 1)
		.replace('#day', date_.getDate())
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから時間文字列への変更 (HH:MM)
//	時間部分は2桁でゼロ埋めされる
//------------------------------------------------------------------------------
function date_to_hhmm(date_)
{
	return g.formats.date.hour_min
		.replace('#hour', strpad_zero(date_.getHours(), 2))
		.replace('#min', strpad_zero(date_.getMinutes(), 2))
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから時間文字列への変更 (H:MM)
//	時間部分はゼロ埋めなし
//------------------------------------------------------------------------------
function date_to_hmm(date_)
{
	return g.formats.date.hour_min
		.replace('#hour', date_.getHours())
		.replace('#min', strpad_zero(date_.getMinutes(), 2))
		;
}

//------------------------------------------------------------------------------
//	時間文字列 00:00:00（House:Min:Sec） から秒数への変換
//------------------------------------------------------------------------------
function hms_to_sec(hms_)
{
	let parts = hms_.split(":");
	if( parts.length >= 3 )
	{
		return
			parseInt(parts[0]) * 3600 +
			parseInt(parts[1]) * 60 +
			parseInt(parts[2])
			;
	}
	return 0;
}

//------------------------------------------------------------------------------
//	秒数から 00:00:00（Hour:Min:Sec） の文字列へ変換
//------------------------------------------------------------------------------
function sec_to_hms(sec_)
{
	let sec = parseInt(sec_);
	return
		strpad_zero(parseInt(sec / 3600), 2) + ":" +
		strpad_zero(parseInt((sec % 3600) / 60 ), 2) + ":" +
		strpad_zero(parseInt(sec % 60), 2)
		;
}

//------------------------------------------------------------------------------
//	秒数から 00:00（Min:Sec） の文字列へ変換
//------------------------------------------------------------------------------
function sec_to_ms(sec_)
{
	let sec = parseInt(sec_);
	return
		strpad_zero(parseInt(sec / 60), 2) + ":" +
		strpad_zero(parseInt(sec % 60), 2)
		;
}

//------------------------------------------------------------------------------
//	たった今の文字列を作成
//------------------------------------------------------------------------------
function date_to_now()
{
	return g.formats.date.now;
}

//------------------------------------------------------------------------------
//	YYYY-MM-DD 文字列から Date オブジェクトへ変換
//	※ HTMLカレンダーコントロールからの入力チェック用
//------------------------------------------------------------------------------
function yyyymmdd_to_date(str_, sep_ = "-")
{
	let parts = str_.split(sep_);
	return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
}

//------------------------------------------------------------------------------
//	YYYY-M-D 文字列から Date オブジェクトへ変換
//------------------------------------------------------------------------------
function yyyymd_to_date(str_, sep_ = "-")
{
	return yyyymmdd_to_date(str_, sep_);
}

//------------------------------------------------------------------------------
//	YYYY-MM-DD HH:ii:ss 文字列から Date オブジェクトへ変換
//	※ HTMLカレンダーコントロールからの入力チェック用
//------------------------------------------------------------------------------
function yyyymmddhhiiss_to_date(str_, sep_ = "-")
{
	let parts = str_.split(" ");
	let date = parts[0];
	let time = parts[1];
	let parts_date = date.split(sep_);
	let parts_time = time.split(":");
	return new Date(parseInt(parts_date[0]), parseInt(parts_date[1]) - 1, parseInt(parts_date[2]), parseInt(parts_time[0]), parseInt(parts_time[1]),parseInt(parts_time[2]));
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから短縮文字列を取得する（1/2 3:04）
//	現在と年が異なる場合には年も出力される
//------------------------------------------------------------------------------
function date_to_yyyymdhmm(date_)
{
	let now = new Date();
	let format = now.getFullYear() == date_.getFullYear() ?
		g.formats.date.month_day_hour_min :
		g.formats.date.year_month_day_hour_min;

	return format
		.replace('#year', date_.getFullYear())
		.replace('#month', date_.getMonth() + 1)
		.replace('#day', date_.getDate())
		.replace('#hour', date_.getHours())
		.replace('#min', strpad_zero(date_.getMinutes(), 2))
		;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトの時分秒を捨てたオブジェクトを返却する
//------------------------------------------------------------------------------
function clear_time_from_date(date_)
{
	let date = new Date(date_);
	date.setHours(0);
	date.setMinutes(0);
	date.setSeconds(0);
	date.setMilliseconds(0);
	return date;
}

//------------------------------------------------------------------------------
//	Dateオブジェクトから現在時刻との差分を短縮時間文字列で取得する
//------------------------------------------------------------------------------
function date_to_shortstr(date_)
{
	let now = new Date();
	let diff = now.getTime() - date_;
	diff = Math.floor(diff / 1000);

	//	24時間未満
	if( diff < 3600 * 24 )
	{
		//	たった今
		if( diff < 60 )
		{
			return date_to_now();
		}

		//	x分前
		if( diff < 60 * 60 )
		{
			return g.formats.date.mins_ago
				.replace('#min', Math.floor((diff / 60)) % 60);
		}

		//	x時間前
		return g.formats.date.hours_ago
			.replace('#hour', Math.floor(diff / 3600));
	}

	//	24時間以上
	else
	{
		let days = Math.floor(diff / (60*60*24));

		//	31日まではx日前
		if( days <= 31 )
		{
			return g.formats.date.days_ago
				.replace('#day', days);
		}

		//	12か月まではxヶ月前
		//	年月の差から計算する
		let diff_months = (now.getFullYear() * 12 + now.getMonth()) - (date_.getFullYear() * 12 + date_.getMonth());
		let diff_years = Math.floor(diff_months / 12);
		if( diff_years <= 0 || diff_months < 12 )
		{
			return g.formats.date.months_ago
				.replace('#month', diff_months);
		}

		//	それ以外はx年前
		return g.formats.date.years_ago
			.replace('#year', diff_years);
	}
}

//------------------------------------------------------------------------------
//	Dateオブジェクトをphpのdate()と同じようにフォーマット
//------------------------------------------------------------------------------
function date_format( date_, format_ = "Y/m/d H:i:s" )
{
	let dest = format_;
	dest = dest.replaceAll('y', ("" + date_.getFullYear()).substr(2));
	dest = dest.replaceAll('Y', date_.getFullYear());
	dest = dest.replaceAll('n', date_.getMonth() + 1);
	dest = dest.replaceAll('m', strpad_zero(date_.getMonth() + 1, 2));
	dest = dest.replaceAll('j', date_.getDate());
	dest = dest.replaceAll('d', strpad_zero(date_.getDate(), 2));
	dest = dest.replaceAll('G', date_.getHours());
	dest = dest.replaceAll('H', strpad_zero(date_.getHours(), 2));
	dest = dest.replaceAll('i', strpad_zero(date_.getMinutes(), 2));
	dest = dest.replaceAll('s', strpad_zero(date_.getSeconds(), 2));
	return dest;
}
