<?php
/*

	DBフィールド定義

*/
class crow_db_design_field
{
	//	フィールド名
	public $name;

	//	型
	public $type;

	//	サイズ（varchar/varcrypt用）
	public $size;

	//	デフォルト値
	public $default_value;

	//	デフォルトコード
	public $default_code;

	//	プライマリーキー？
	public $primary_key = false;

	//	オートインクリメント？
	public $auto_increment = false;

	//	null許可？
	public $nullable = false;

	//	重複不可フィールド？
	public $unique = false;

	//	削除フラグのフィールドか？（1なら削除として扱う）
	public $deleted = false;

	//	必須パラメータか？
	public $must = false;

	//	範囲バリデーション
	public $valid_range_from = false;
	public $valid_range_to = false;

	//	正規表現バリデーション
	public $valid_regexp = false;

	//	半角バリデーション
	public $valid_charcase = false;

	//	受け取る定数パターン
	public $const_array = [];

	//	定数の論理名
	public $const_logical_names = [];

	//	IDとして参照する先のテーブル
	public $refer_table = false;
	//	IDとして参照する場合の、参照先が削除されたときの振る舞い("zero" or "trash")
	public $refer_remove = false;

	//	標準ソートフィールド？
	public $order = false;
	public $order_vector = "";

	//	論理名
	public $logical_name = "";

	//	備考
	public $remark = "";
}
?>
