//	Numberで扱える桁数を実数に指定したparseInt()がおかしな値を返却する
//	というJSの問題を解決するパッチ
//
//	例）parseInt(0.0000005)が5を返すのを、0が返るように修正するパッチ
//
parseInt_backup = null;
parseInt_override = function(num_, base_ = 10)
{
	if( num_ === "" || num_ === undefined || num_ === null ) return 0;
	return (
		(typeof num_) != "string" &&
		(num_ instanceof String) === false &&
		Math.abs(num_) < 1
	) ? 0 : parseInt_backup(num_, base_);
};
if( Number.parseInt == parseInt )
{
	parseInt_backup = Number.parseInt;
	Number.parseInt = parseInt_override;
	parseInt = parseInt_override;
}
