<?php
/*

	DBテーブル定義

*/
class crow_db_design_table
{
	//	テーブル名
	public $name;

	//	プライマリキーのフィールド名
	//	複数のプライマリーキーがある場合は、フィールド名の配列になる。
	public $primary_key = false;

	//	削除フラグのフィールド名
	public $deleted = false;

	//	オーダーフィールド名
	public $order = false;

	//	オーダー順
	public $order_vector = false;

	//	オプション
	public $options = [];

	//	論理名
	public $logical_name = "";

	//	備考
	public $remark = "";

	//	インデックス
	public $indexes = [];
	public $indexes_with_unique = [];

	//	自分を参照しているテーブル/フィールドの一覧
	//	キーは参照元テーブル名。
	//	値は実際に参照している[フィールド名, 削除種別]の配列とする
	//	つまり、次のようなイメージ
	//	[テーブル名] = [[フィールド名, 削除種別], ...]
	public $referrers = [];

	//--------------------------------------------------------------------------
	//	フィールドリスト
	//
	//	連想配列
	//	key:フィールド名
	//	val:crow_design_field
	//--------------------------------------------------------------------------
	public $fields = [];
}
?>
