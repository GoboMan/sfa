//------------------------------------------------------------------------------
//	ヒストリ制御
//------------------------------------------------------------------------------

//	ヒストリ初期化
//	pop時にハンドラがキックされる、その時の引数はpush_histに渡したext_params_となる
function hist_init( pop_handler_ )
{
	window.addEventListener("popstate", function(e_)
	{
		if( e_.state != null ) pop_handler_(e_.state);
		else history.back();
	});
}

//	ヒストリに状態保存
function push_hist( url_, ext_params_ )
{
	history.pushState(ext_params_, null, url_);
}

//------------------------------------------------------------------------------
//	配列/またはオブジェクトに対してイテレート実行する
//
//	let arr = [1, 11, 22];
//	array_each(arr, function(i){ console.log(i*2); });
//
//	→ "2", "22", "44" が出力される
//
//	渡す関数の第二引数で配列のインデックスやオブジェクトのキーを受け取れる
//	array_each(arr, function(i, j){ console.log(i*j); });
//
//	→ "0", "11", "44" が出力される
//
//	途中でループを停止するためには、指定した無名関数が false を返却すればよい。
//	array_each(arr, function(i){ if(i > 10) return false; console.log(i); }
//
//	→ "1" が出力される
//
//------------------------------------------------------------------------------
function array_each(array_, func_)
{
	//	配列のループ
	if( Array.isArray(array_) )
	{
		for( let index = 0; index < array_.length; index++ )
		{
			if( func_(array_[index], index) === false ) break;
		}
	}
	//	オブジェクトのループ
	else if( array_ )
	{
		let keys = Object.keys(array_);
		for( let index = 0; index < keys.length; index++ )
		{
			if( func_(array_[keys[index]], keys[index]) === false ) break;
		}
	}
}

//	同期版
async function array_each_async(array_, func_)
{
	//	配列のループ
	if( Array.isArray(array_) )
	{
		for( let index = 0; index < array_.length; index++ )
		{
			if( await func_(array_[index], index) === false ) break;
		}
	}
	//	オブジェクトのループ
	else if( array_ )
	{
		let keys = Object.keys(array_);
		for( let index = 0; index < keys.length; index++ )
		{
			if( await func_(array_[keys[index]], keys[index]) === false ) break;
		}
	}
}

//------------------------------------------------------------------------------
//	配列/またはオブジェクトから要素を検索する、検索ロジックを関数で指定する
//
//	let arr = [{uid:1, name:"taro"}, {uid:2, name:"hanako"}, {uid:3, name:"ichiro"}];
//	let found_item = array_find(arr, function(i){ return i.uid == 1; } );
//	if( found_item !== null ) console.log(found_item.name);
//
//	→ "taro" が出力される
//
//	アロー式で書くと、
//	array_find(arr, i => i.uid == 1);
//	となる。
//	要素を一つ発見した段階で処理を終了する。
//
//	※array_each()同様、受け取り側の第二引数でインデックスやキーを取れる
//
//------------------------------------------------------------------------------
function array_find(array_, func_)
{
	//	配列のループ
	if( Array.isArray(array_) )
	{
		for( let index = 0; index < array_.length; index++ )
		{
			let item = array_[index];
			if( func_(item, index) === true ) return item;
		}
	}
	//	オブジェクトのループ
	else if( array_ )
	{
		let keys = Object.keys(array_);
		for( let index = 0; index < keys.length; index++ )
		{
			let item = array_[keys[index]];
			if( func_(item, keys[index]) === true ) return item;
		}
	}
	return null;
}

//	複数要素の検索、使い方はarray_find()と同じで、返却が配列となる
function array_find_all(array_, func_)
{
	let items = [];
	array_each(array_, function(item_, index_)
	{
		if( func_(item_, index_) === true )
			items.push(item_);
	});
	return items;
}

//	同期版
async function array_find_async(array_, func_)
{
	//	配列のループ
	if( Array.isArray(array_) )
	{
		for( let index = 0; index < array_.length; index++ )
		{
			let item = array_[index];
			if( await func_(item, index) === true ) return item;
		}
	}
	//	オブジェクトのループ
	else if( array_ )
	{
		let keys = Object.keys(array_);
		for( let index = 0; index < keys.length; index++ )
		{
			let item = array_[keys[index]];
			if( await func_(item, keys[index]) === true ) return item;
		}
	}
	return null;
}
async function array_find_all_async(array_, func_)
{
	let items = [];
	await array_each(array_, async function(item_, index_)
	{
		if( await func_(item_, index_) === true )
			items.push(item_);
	});
	return items;
}


//------------------------------------------------------------------------------
//	連想配列をキーでソートする
//------------------------------------------------------------------------------
function sort_array_with_key(assoc_)
{
	var pairs = Object.entries(assoc_);
	pairs.sort(function(p1, p2)
	{
		if( p1[0] < p2[0] ) return -1;
		if( p1[0] > p2[0] ) return 1;
		return 0;
	});
	return Object.fromEntries(pairs);
}

//------------------------------------------------------------------------------
//	配列を指定したカラムの値でソートする
//------------------------------------------------------------------------------
function sort_array_with_column(array_, key_, dir_ = "asc")
{
	let len = array_.length;
	let new_array = [];
	for( let i = 0; i < len; i++ )
		new_array.push(array_[i]);

	for( let i = 0; i < len - 1; i++ )
	{
		for( let j = i + 1; j < len; j++ )
		{
			if(
				(dir_ == "asc" && new_array[j][key_] < new_array[i][key_]) ||
				(dir_ == "desc" && new_array[j][key_] > new_array[i][key_])
			){
				let tmp = new_array[j];
				new_array[j] = new_array[i];
				new_array[i] = tmp;
			}
		}
	}
	return new_array;
}

//	降順
function sort_array_with_column_desc(array_, key_)
{
	return sort_array_with_column(array_, key_, "desc");
}

//------------------------------------------------------------------------------
//	配列を指定したロジックでソートする。
//	compare_に、function(a_, b_) の関数を渡し、結果として次の値を返却すること。
//		a_ < b_ の場合は -1
//		a_ > b_ の場合は 1
//		a_ == b_ の場合は 0
//------------------------------------------------------------------------------
function sort_array(array_, compare_, dir_ = "asc")
{
	let len = array_.length;
	let new_array = [];
	for( let i = 0; i < len; i++ )
		new_array.push(array_[i]);

	for( let i = 0; i < len - 1; i++ )
	{
		for( let j = i + 1; j < len; j++ )
		{
			if(
				(dir_ == "asc" && compare_(new_array[j][key_], new_array[i][key_]) < 0) ||
				(dir_ == "desc" && compare_(new_array[j][key_], new_array[i][key_]) > 0)
			){
				let tmp = new_array[j][key_];
				new_array[j][key_] = new_array[i][key_];
				new_array[i][key_] = tmp;
			}
		}
	}
	return new_array;
}

//	降順
function sort_array_desc(array_, compare_)
{
	return sort_array(array_, compare_, "desc");
}


//------------------------------------------------------------------------------
//	オブジェクトから指定したキーのアイテムを取得する
//	キーが存在しない場合は、default_ で指定した内容を取得する
//------------------------------------------------------------------------------
function get_object_item(object_, key_, default_)
{
	return object_ !== null && Object.keys(object_).indexOf(key_) >= 0 ?
		object_[key_] : default_;
}

//------------------------------------------------------------------------------
//	オブジェクトに指定したキーのアイテムがあるかチェックし、
//	あれば true を返却し、なければ false を返却する。
//
//	さらに、存在した場合には、第三引数に指定した処理を追加で実行する。
//	引数で指定する関数は、function(item_) として、存在した場合のアイテムを引数で受け取る。
//------------------------------------------------------------------------------
function exists_object_item(object_, key_, func_ = null)
{
	let item = get_object_item(object_, key_, null);
	if( func_ === null ) return item !== null;
	if( item !== null ) func_(item);
	return item !== null;
}

//	同期版
async function exists_object_item_async(object_, key_, func_ = null)
{
	let item = get_object_item(object_, key_, null);
	if( func_ === null ) return item !== null;
	if( item !== null ) await func_(item);
	return item !== null;
}

//------------------------------------------------------------------------------
//	URL指定でジャンプ
//------------------------------------------------------------------------------
function jump( $to_ )
{
	document.location = $to_;
}

//------------------------------------------------------------------------------
//	0埋め用
//------------------------------------------------------------------------------
function strpad_zero( str_, len_ )
{
	var s = str_;
	if( s==undefined ) s = "";
	if( s==null ) s = "";
	return ('00000000' + s).slice(-1 * len_);
}

//------------------------------------------------------------------------------
//	HTMLエスケープ
//------------------------------------------------------------------------------
function htmlspecialchars( str_ )
{
	if( str_ === undefined || str_ === null ) return "";
	if( typeof str_ != "string" ) return str_;
	str_ = str_.replace(/&/g, "&amp;");
	str_ = str_.replace(/"/g, "&quot;");
	str_ = str_.replace(/'/g, "&#039;");
	str_ = str_.replace(/</g, "&lt;");
	str_ = str_.replace(/>/g, "&gt;");
	return str_;
}

//------------------------------------------------------------------------------
//	指定数字を3ケタ区切りのカンマを付与する
//	
//	例）toDecimal(123456);
//------------------------------------------------------------------------------
function number_format(number_)
{
	var num = String(number_).replace(/^(-?\d+)(\d{3})/, "$1,$2");
	if (num !== number_) {
		return number_format(num);
	}
	return num;
}

//------------------------------------------------------------------------------
//	数値の線形補間
//------------------------------------------------------------------------------
function lerp( from_, to_, value_ )
{
	return from_ + (to_ - from_) * value_;
}

//------------------------------------------------------------------------------
//	うるう年判定
//------------------------------------------------------------------------------
function is_leap_year( year_ )
{
	return (year_ % 4 == 0 && year_ % 100 != 0) || year_ % 400 == 0;
}

//------------------------------------------------------------------------------
//	指定した年月の日数を取得
//------------------------------------------------------------------------------
function get_days_in_month( month_, year_ )
{
	const days_in_month = [31, is_leap_year(year_) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	return days_in_month[month_ - 1];
}

//------------------------------------------------------------------------------
//	連想配列と、<select>のjQueryオブジェクトを指定して、
//	オプションの構築を行う
//	sort_keys_を指定すると、そのキーの順場に並び替えて表示する
//
//	オプショングループを指定する場合はdata_を二次元配列にして、opt_group_をtrueにする。
//	その場合のdata_の1次元目のキーが<optgroup>のラベルとなる
//		items =
//		{
//			"グループラベル" : {"アイテムキー" : "アイテム名", ...},
//			"グループラベル" : {"アイテムキー" : "アイテム名", ...}...
//		};
//	のような形となる。
//	オプショングループ自体の並びを指定する場合には、group_sort_labels_にラベルの配列を指定する。
//------------------------------------------------------------------------------
function make_options( select_obj_, data_, default_ = null, optgroup_ = false, sort_keys_ = null, group_sort_labels_ = null )
{
	select_obj_.empty();
	if( optgroup_ === true )
	{
		let grp_labels = group_sort_labels_ === null ? Object.keys(data_) : group_sort_labels_;
		for( let grp_index = 0; grp_index < grp_labels.length; grp_index++ )
		{
			let label = grp_labels[grp_index];
			if( Object.keys(data_).indexOf(label) < 0 ) continue;

			let opt_grp = $('<optgroup></optgroup>')
					.attr('label', label)
					.appendTo(select_obj_)
					;

			let item_keys = sort_keys_ === null ? Object.keys(data_[label]) : sort_keys_;
			for( let item_index = 0; item_index < item_keys.length; item_index++ )
			{
				let key = item_keys[item_index];
				if( Object.keys(data_[label]).indexOf(key) < 0 ) continue;

				let selected = default_ !== null && default_ == key;
				let opt = $('<option' + (selected ? ' selected' : '') + '></option>')
					.val(key)
					.text(data_[label][key])
					.appendTo(opt_grp)
					;
			}
		}
	}
	else
	{
		let item_keys = sort_keys_ === null ? Object.keys(data_) : sort_keys_;
		for( let item_index = 0; item_index < item_keys.length; item_index++ )
		{
			let key = item_keys[item_index];
			if( Object.keys(data_).indexOf(key) < 0 ) continue;

			let selected = default_ !== null && default_ == key;
			let opt = $('<option' + (selected ? ' selected' : '') + '></option>')
				.val(key)
				.text(data_[key])
				.appendTo(select_obj_)
				;
		}
	}
	return select_obj_;
}

//	未選択の項目があるオプション構築
function make_options_with_empty( select_obj_, data_, default_ = null, empty_key_ = 0, empty_text_ = "", optgroup_ = false, sort_keys_ = null, group_sort_labels_ = null )
{
	select_obj_.empty();
	if( optgroup_ === true )
	{
		let grp_labels = group_sort_labels_ === null ? Object.keys(data_) : group_sort_labels_;
		for( let grp_index = 0; grp_index < grp_labels.length; grp_index++ )
		{
			let label = grp_labels[grp_index];
			if( Object.keys(data_).indexOf(label) < 0 ) continue;

			let opt_grp = $('<optgroup></optgroup>')
					.attr('label', label)
					.appendTo(select_obj_)
					;

			if(true)
			{
				let opt = $('<option></option>')
					.val(empty_key_)
					.text(empty_text_)
					.appendTo(opt_grp)
					;
			}

			let item_keys = sort_keys_ === null ? Object.keys(data_[label]) : sort_keys_;
			for( let item_index = 0; item_index < item_keys.length; item_index++ )
			{
				let key = item_keys[item_index];
				if( Object.keys(data_[label]).indexOf(key) < 0 ) continue;
				let opt = $('<option></option>')
					.val(key)
					.text(data_[label][key])
					.appendTo(opt_grp)
					;
			}
		}

		if( default_ !== null ) select_obj_.val(default_);
		else select_obj_.val(empty_key_);
	}
	else
	{
		if(true)
		{
			let opt = $('<option></option>')
				.val(empty_key_)
				.text(empty_text_)
				.appendTo(select_obj_)
				;
		}

		let item_keys = sort_keys_ === null ? Object.keys(data_) : sort_keys_;
		for( let item_index = 0; item_index < item_keys.length; item_index++ )
		{
			let key = item_keys[item_index];
			if( Object.keys(data_).indexOf(key) < 0 ) continue;

			let opt = $('<option></option>')
				.val(key)
				.text(data_[key])
				.appendTo(select_obj_)
				;
		}

		if( default_ !== null ) select_obj_.val(default_);
		else select_obj_.val(empty_key_);
	}
	return select_obj_;
}

//------------------------------------------------------------------------------
//	開始数字と、カウント数を指定して、<option> の構築を行う
//------------------------------------------------------------------------------
function make_options_with_period( select_obj_, from_, count_, default_ = null, format_="%VAL%" )
{
	select_obj_.empty();
	for( let i = from_; i < from_ + count_; i++ )
	{
		let selected = default_ != null && default_ == i;
		$('<option' + (selected ? ' selected' : '') + '></option>')
			.val(i)
			.text(format_.replace("%VAL%", i))
			.appendTo(select_obj_)
			;
	}
	return select_obj_;
}

//------------------------------------------------------------------------------
//	クッキー保存
//		key_			: クッキー名
//		value_			: 値
//		expire_days_	: 有効期限 (デフォルトのnullはブラウザのセッションが終わるまで)
//------------------------------------------------------------------------------
function set_cookie(key_, value_, expire_days_ = null)
{
	const date = new Date();
	let expires = '';
	if( expire_days_ !== null )
	{
		date.setTime(date.getTime() + (expire_days_ * 24 * 60 * 60 * 1000));
		expires = 'expires=' + date.toUTCString();
	}
	document.cookie = key_ + '=' + value_ + ';' + expires + ';path=/';
}

//------------------------------------------------------------------------------
//	クッキー取得
//		key_	: クッキー名
//------------------------------------------------------------------------------
function get_cookie(key_)
{
	const key = key_ + '=';
	const cookies = decodeURIComponent(document.cookie).split(';');
	for( let cookie of cookies )
	{
		while( cookie.charAt(0) == ' ' )
		{
			cookie = cookie.substring(1);
		}
		if( cookie.indexOf(key) == 0 )
		{
			return cookie.substring(key.length, cookie.length);
		}
	}
	return '';
}

//------------------------------------------------------------------------------
//	クッキー削除
//		key_	: クッキー名
//------------------------------------------------------------------------------
function remove_cookie(key_)
{
	set_cookie(key_, '', -1);
}
