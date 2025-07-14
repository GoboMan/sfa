<?php
/*

	curl 実行結果

*/
class crow_curl_response
{
	//--------------------------------------------------------------------------
	//	レスポンス情報取得
	//--------------------------------------------------------------------------

	//	レスポンスボディ取得
	public function body()
	{
		return $this->m_body;
	}

	//	レスポンスヘッダ取得
	public function headers()
	{
		return $this->m_headers;
	}

	//	レスポンスクッキー取得
	public function cookies()
	{
		return $this->m_cookies;
	}

	//	レスポンスコード取得
	public function code()
	{
		return $this->m_code;
	}

	//	クッキーに指定のキーが存在するか？
	public function exists_cookie_key( $key_ )
	{
		return count($this->m_cookies) <= 0 ?
			false : array_key_exists($key_, $this->m_cookies);
	}

	//	タイムアウトしたかどうか (true/false)
	public function timeout()
	{
		return $this->m_timeout;
	}

	//	レスポンスサマリを連想配列で取得
	public function info()
	{
		return $this->m_info;
	}

	//--------------------------------------------------------------------------
	//	初期化、crow_curlから実行される
	//--------------------------------------------------------------------------
	public function __construct( $raw_, $info_, $errno_ )
	{
		$lines = explode("\n", $raw_);
		$found_separate = false;
		$headers = [];
		$bodies = [];
		$cookies = [];
		$continue_cnt = 0;
		foreach( $lines as $line )
		{
			if( substr($line, -1) == "\r" )
				$line = substr($line, 0, strlen($line)-1);
			if( $found_separate === false )
			{
				if( strpos($line, "HTTP/1.1 100") === 0 )
				{
					$continue_cnt++;
					continue;
				}

				if( $line == "" )
				{
					$continue_cnt--;
					if( $continue_cnt < 0 )
					{
						$found_separate = true;
					}
					continue;
				}

				$pos = strpos($line, ":");
				if( $pos !== false )
				{
					$key = trim(substr($line, 0, $pos));
					$val = trim(substr($line, $pos+1));
					$headers[$key] = $val;
					if( strtolower($key) == "set-cookie" )
					{
						$cookies[] = $val;
					}
				}
			}
			else
			{
				$bodies[] = $line;
			}
		}

		//	cookie分解
		if( count($cookies) > 0 )
		{
			foreach( $cookies as $cookie )
			{
				$cookie_items = explode(";", $cookie);
				$keyvals = [];
				$cookie_key = false;
				foreach( $cookie_items as $item )
				{
					$pos = strpos($item, "=");
					if( $pos === false )
					{
						$keyvals[trim($item)] = false;
					}
					else
					{
						$key = trim(substr($item, 0, $pos));
						if( $cookie_key === false ) $cookie_key = $key;
						$keyvals[$key] = trim(substr($item, $pos + 1));
					}
				}
				$this->m_cookies[$cookie_key] = $keyvals;
			}
		}

		$this->m_headers = $headers;
		$this->m_body = implode("\n", $bodies);
		$this->m_info = $info_;
		$this->m_code = isset($this->m_info['http_code']) ?
			$this->m_info['http_code'] : "";
		$this->m_timeout =
			$this->m_code==0 && $errno_ == CURLE_OPERATION_TIMEDOUT;
	}

	//	結果データ
	private $m_headers = [];
	private $m_cookies = [];
	private $m_body = "";
	private $m_code = "";
	private $m_timeout = false;
	private $m_info = false;
}

?>
