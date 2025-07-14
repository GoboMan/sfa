<?php
/*

	DBページャー

*/
class crow_db_pager
{
	//--------------------------------------------------------------------------
	//	SQLのオブジェクトからページャー作成
	//
	//	引数には crow_db_sql のインスタンスを指定する。
	//	get_rows()による現在ページの一覧は、テーブルモデルのインスタンス一覧を取得できる。
	//--------------------------------------------------------------------------
	public static function create_with_obj( $sql_obj_ )
	{
		$instance = new self();
		$instance->m_sql_is_obj = true;
		$instance->m_sql = $sql_obj_;
		$instance->m_table_name = $sql_obj_->m_from;
		return $instance;
	}

	//--------------------------------------------------------------------------
	//	SQLを直接指定してページャー作成
	//
	//	引数には生のクエリを指定する。
	//	get_rows()による現在ページの一覧は、連想配列の一覧を取得する。
	//	ただし、引数の $table_name_ にテーブル名を指定すれば、
	//	テーブルのモデルインスタンスの一覧として取得するようになる。
	//--------------------------------------------------------------------------
	public static function create_with_query( $query_, $table_name_=false )
	{
		$instance = new self();
		$instance->m_sql_is_obj = false;
		$instance->m_sql = $query_;
		$instance->m_table_name = $table_name_;
		return $instance;
	}

	//--------------------------------------------------------------------------
	//	ページ分割実行前の設定
	//--------------------------------------------------------------------------

	//	カウント用クエリを指定する
	//	通常は自動で作成されるが、うまくいかないときに本メソッドで指定する。
	//	"cnt"というフィールドに行数が取れるようなSQLを指定すればよい。
	public function set_count_query( $sql_ )
	{
		$this->m_count_query = $sql_;
		return $this;
	}

	//	1ページに包含するデータ（行）の数
	public function set_row_per_page( $value_ )
	{
		$this->m_row_per_page = $value_ > 0 ? $value_ : 1;
		return $this;
	}

	//	現在のページ番号（1～の値で指定）
	public function set_page_no( $value_ )
	{
		$this->m_page = $value_ > 0 ? $value_ : 1;
		return $this;
	}

	//	現在のページから前後に何ページのリンクを作成するか
	//	0ページ以下になる場合など、はみ出る場合は前後にその分延びる。
	public function set_page_range( $value_ )
	{
		$this->m_page_range = $value_ > 0 ? $value_ : 3;
		return $this;
	}

	//	大きくジャンプするリンクを前後に設置するか？
	//	設置する場合は $enable_ に true を指定して、ジャンプするページの量を $range_ に指定する。
	public function set_wide_link( $enable_, $range_ = 10 )
	{
		$this->m_wide_link_enable = $enable_;
		$this->m_wide_link_range = $range_ > 0 ? $range_ : 10;
		return $this;
	}


	//--------------------------------------------------------------------------
	//	ページ分割実行
	//
	//	$html_escape_ を true にすると現在ページ一覧取得時に内容がHTMLエスケープされる。
	//	失敗時は false、成功時は自インスタンスを返却
	//--------------------------------------------------------------------------
	public function build( $html_escape_ = false )
	{
		//	クエリ作成
		$cnt_query = "";
		$query = "";
		if( $this->m_sql_is_obj )
		{
			$query = $this->m_sql->limit( ($this->m_page - 1) * $this->m_row_per_page, $this->m_row_per_page )->build();
			$cnt_query = $this->m_count_query === false ?
				$this->m_sql->limit()->target("count(*) as cnt")->build() : $this->m_count_query;
		}
		else
		{
			$dbtype = crow_config::get('db.type', 'mysqli');

			if( $this->m_count_query === false )
			{
				$lower = strtolower($this->m_sql);
				if( substr($lower,0,6) != "select" )
				{
					crow_log::warning( "cant build db_pager : not select query : ".$this->m_sql );
					return false;
				}

				//	SQLServerの場合は、ORDER BY 句が必須
				if( $dbtype == "sqlserver" )
				{
					if( strpos($lower,"order by")===false )
					{
						crow_log::warning( "cant build db_pager : 'order by' is not included : ".$this->m_sql );
						return false;
					}
				}

				//	ターゲットを count(*) に置換
				$pos = strpos($lower, " from ");
				if( $pos === false )
				{
					crow_log::warning( "cant build db_pager : 'from' is not included : ".$this->m_sql );
					return false;
				}
				$cnt_query = "select count(*) as cnt ".substr( $this->m_sql, $pos );

				//	order by があれば削除する
				$pos = strpos( strtolower($cnt_query), "order by" );
				if( $pos !== false )
					$cnt_query = substr( $cnt_query, 0, $pos );
			}
			else
			{
				$cnt_query = $this->m_count_query;
			}

			//	行取得クエリ作成（DB種別により異なる）
			if( $dbtype == "mysqli" )
			{
				$query = $this->m_sql." limit "
					.($this->m_page - 1) * $this->m_row_per_page.","
					.$this->m_row_per_page
					;
			}
			else if( $dbtype == "postgres" )
			{
				$query = $this->m_sql." limit "
					.$this->m_row_per_page." offset "
					.($this->m_page - 1) * $this->m_row_per_page
					;
			}
			else if( $dbtype == "sqlserver" )
			{
				$query = $this->m_sql." offset ".($this->m_page - 1) * $this->m_row_per_page." rows"
					." fetch next ".$this->m_row_per_page
					." rows only "
					;
			}
			else
			{
				crow_log::warning( "crow_db_pager is not supported dbtype:".$dbtype );
			}
		}

		//	トータル件数取得
		$this->m_total = 0;
		$hdb = crow::get_hdb_reader();
		$rset = $hdb->query($cnt_query);
		if( ! $rset )
		{
			crow_log::warning( "failed to count query at crow_db_pager:".$cnt_query );
			return false;
		}
		if( $rset->num_rows() > 0 )
		{
			$row = $rset->get_row();
			$this->m_total = $row['cnt'];
		}


		//	現在ページの一覧取得
		$rset = $hdb->query( $query );
		if( ! $rset )
		{
			crow_log::warning( "failed to list query at crow_db_pager:".$query );
			return false;
		}
		if( $this->m_table_name === false )
		{
			$this->m_rows = $rset->num_rows() > 0 ?
				$rset->get_rows($html_escape_) : [];
		}
		else {
			$model = "model_".$this->m_table_name;
			$this->m_rows = $rset->num_rows() > 0 ?
				$model::create_array_from_record($rset->get_rows($html_escape_)) : [];
		}

		//	ページ数計算
		$this->m_all_pagenum = intval($this->m_total / $this->m_row_per_page);
		if( ($this->m_total % $this->m_row_per_page) != 0 ) $this->m_all_pagenum++;

		//	リンク計算
		$this->m_page_nos = [];
		$min = $this->m_page - $this->m_page_range;
		$max = $this->m_page + $this->m_page_range;
		if( $min < 1 )
		{
			$max += 1 - $min;
			$min = 1;
		}
		if( $max > $this->m_all_pagenum )
		{
			$min -= $max - $this->m_all_pagenum;
			$max = $this->m_all_pagenum;
			if( $min < 1 ) $min = 1;
		}
		for( $i=$min; $i<=$max; $i++ ) $this->m_page_nos[$i] = $i;

		//	ワイドリンク
		if( $this->m_wide_link_enable )
		{
			$this->m_wide_link_prev = $min - $this->m_wide_link_range;
			if( $this->m_wide_link_prev < 1 )
				$this->m_wide_link_prev = 1;

			$this->m_wide_link_next = $max + $this->m_wide_link_range;
			if( $this->m_wide_link_next > $this->m_all_pagenum )
				$this->m_wide_link_next = $this->m_all_pagenum;
		}

		//	分割成功
		$this->m_built = true;
		return $this;
	}


	//--------------------------------------------------------------------------
	//	分割結果取得（ build() 後に取得可能になる ）
	//--------------------------------------------------------------------------

	//	現在ページ番号
	public function get_page()
	{
		return $this->m_page;
	}

	//	次ページ番号
	public function get_next_page()
	{
		if( $this->m_page + 1 > $this->m_all_pagenum ) return false;
		return $this->m_page + 1;
	}

	//	前ページ番号（ない場合には、falseが返却される）
	public function get_prev_page()
	{
		if( $this->m_page <= 1 ) return false;
		return $this->m_page - 1;
	}

	//	最後のページ番号
	public function get_last_page()
	{
		return intval
		(
			($this->get_total() + ($this->get_row_per_page()-1)) / $this->get_row_per_page()
		);
	}

	//	前のワイドリンク番号取得
	public function get_wide_prev_page()
	{
		return $this->m_wide_link_enable ? $this->m_wide_link_prev : 0;
	}

	//	次のワイドリンク番号取得
	public function get_wide_next_page()
	{
		return $this->m_wide_link_enable ? $this->m_wide_link_next : 0;
	}

	//	ページ番号リスト取得
	public function get_page_nos()
	{
		return $this->m_page_nos;
	}

	//	全ページ数取得
	public function get_all_page()
	{
		return $this->m_all_pagenum;
	}

	//	１ページあたりの表示最大件数
	public function get_row_per_page()
	{
		return $this->m_row_per_page;
	}

	//	現在ページの行一覧
	public function get_rows()
	{
		return $this->m_rows;
	}

	//	全件数
	public function get_total()
	{
		return $this->m_total;
	}

	//	現在行一覧の、最初のインデックス
	public function get_start_index()
	{
		if( count($this->m_rows) <= 0 ) return 0;
		return ($this->m_page-1) * $this->m_row_per_page + 1;
	}

	//	現在行一覧の、最後のインデックス
	public function get_end_index()
	{
		if( count($this->m_rows) <= 0 ) return 0;
		return $this->get_start_index() + count($this->m_rows) - 1;
	}


	//	private
	private $m_sql_is_obj = false;
	private $m_sql = "";
	private $m_page = 1;
	private $m_row_per_page = 10;
	private $m_page_range = 3;
	private $m_wide_link_enable = false;
	private $m_wide_link_range = 10;
	private $m_table_name = false;

	private $m_built = false;
	private $m_rows = [];
	private $m_page_nos = [];
	private $m_total = 0;
	private $m_all_pagenum = 0;
	private $m_count_query = false;
	private $m_wide_link_prev = 0;
	private $m_wide_link_next = 0;
}
?>
