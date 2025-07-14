<?php
/*

	AWS Cognito Condition Model

	cognito でのユーザ検索に使用するパラメータをオブジェクトにしたもの
	where() で属性の条件を指定できるが、cognito の仕様上、カラム条件は一つのみとなる
	使用例は crow_cognito を参照

	whereで使用できる属性は
		- username(ユーザー名)
		- email(Eメールアドレス)
		- phone_number(電話番号)
		- name(名前)
		- given_name(名)
		- family_name(姓)
		- preferred_username(希望するユーザー名)
		- cognito:user_status(確認ステータス)
		- status(ステータス)
		- sub(ユーザーID(sub))

	使用例
	$cond = crow_cognito_cond::create_list_users_cond()
		->target("attr1", "attr2")

		//	cognitoの仕様の条件はひとつしか指定できない
		//	完全一致
		->where("attr1", "xxx")

		//	前方一致
		->where("attr1, "^=", "xxx")

		//	条件はひとつしか指定できない
		//	ステータスが外接ユーザかどうか
		->where_status("=", crow_cognito_core::STATUS_XXXX)
		->where_status("!=", crow_cognito_core::STATUS_XXXX)

		//	件数
		->limit(3)
		//	検索用パラメータ配列に変換
		->build()
		;

*/
class crow_cognito_cond
{
	//--------------------------------------------------------------------------
	//	list_users実行用条件オブジェクト取得
	//--------------------------------------------------------------------------
	public static function create_list_users_cond()
	{
		$obj = new self();
		$obj->m_command = "list_users";
		return $obj;
	}

	//--------------------------------------------------------------------------
	//	パラメータビルド
	//--------------------------------------------------------------------------
	public function build()
	{
		if( $this->m_command==="list_users" )
		{
			$filter = "";
			if( $this->m_where_key !== "" ||
				$this->m_where_cond !== "" ||
				$this->m_where_val !== ""
			){
				$filter = $this->m_where_key.$this->m_where_cond.'"'.$this->m_where_val.'"';
			}
			return 
			[
				"target" => $this->m_target,
				"filter" => $filter,
				"limit" => $this->m_limit,
				"status" => $this->m_status,
				"status_cond" => $this->m_status_cond,
			];
		}
		return [];
	}

	//--------------------------------------------------------------------------
	//	list_users実行用条件の取得属性設定
	//--------------------------------------------------------------------------
	public function target( /* attr1, attr2, ... */ )
	{
		$this->m_target = func_get_args();
		return $this;
	}

	//--------------------------------------------------------------------------
	//	list_users実行用条件の取得条件設定
	//--------------------------------------------------------------------------
	public function where( /* attr1, attr2, ... */ )
	{
		$args = func_get_args();
		if( count($args) === 2 )
		{
			$this->m_where_key = $args[0];
			$this->m_where_cond = "=";
			$this->m_where_val = $args[1];
		}
		else if( count($args) === 3 )
		{
			if( in_array($args[1], ["=","^="]) === false )
			{
				return false;
			}
			$this->m_where_key = $args[0];
			$this->m_where_cond = $args[1];
			$this->m_where_val = $args[2];
		}
		return $this;
	}

	//--------------------------------------------------------------------------
	//	list_users実行用条件の取得件数設定
	//--------------------------------------------------------------------------
	public function limit( $limit_ )
	{
		$this->m_limit = $limit_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	list_users実行用条件のステータス絞り込み設定
	//	CognitoAPI側では条件を複数指定できないので取得後に条件一致を確認して返却する
	//--------------------------------------------------------------------------
	public function where_status( $status_cond_, $status_ )
	{
		$this->m_status_cond = $status_cond_;
		$this->m_status = $status_;
		return $this;
	}

	private $m_command = "";
	private $m_target = [];
	private $m_where_key = "";
	private $m_where_cond = "";
	private $m_where_val = "";
	private $m_limit = 0;
	private $m_status = "";
	private $m_status_cond = "";
}
?>
