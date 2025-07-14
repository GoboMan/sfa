<?php
/*

	PHPにおけるPDF生成ユーティリティ

	----------------------------------------------------------------------------

	Headless Chromeを用いてHTMLからPDFを出力する。環境へのChrome本体のインストー
	ルとcomposerによる `chrome-php/chrome` のインストールが必要。拡張することで
	そのほかのPDF出力ハンドルにも対応可能。


	1. テンプレート指定でPDF生成

	[CROW_PATH]assets/pdf/[role]/xxxxx.phpをテンプレートとして指定してPDFを生成
	する。置換文字列はテンプレート内に `[[NAME]]` のように記述しておき、第二引数
	に連想配列指定する。テンプレートはHTMLの `<body>` 部のみを記述すればよい。
	CSSは[CROW_PATH]assets/pdf/[role]/xxxxx.cssが自動的に読みこまれる。

	```php
	htmltopdf::create()
		->name("file_name")
		->template("xxxxx",
			[
				"NAME" => "guest",
				"AGE" => 20,
			])
		->add_opts(
			[
				"marginTop" => 20,
				"marginBottom" => 20,
				"marginLeft" => 20,
				"marginRight" => 20,
			])
		->output_download();
	```

	2. HTMLを直接指定してPDF生成

	```php
	htmltopdf::create()
		->name("file_name")
		->html(
			'
			<div class="box">
			 <table>
			  <tr>
			   <td>データ1</td>
			   <td>データ2</td>
			  </tr>
			 </table>
			</div>
			'
		)
		->css(
			'
			.box
			{
				background-color: gray;
				font-size: 20px;
			}
			'
		)
		->output_download();
	```

*/

use HeadlessChromium\BrowserFactory;

//	ライブラリ読み込み
require_once(CROW_PATH."engine/vendor/autoload.php");

class htmltopdf
{
	//--------------------------------------------------------------------------
	//	インスタンス生成
	//--------------------------------------------------------------------------
	public static function create()
	{
		$pdf_handle = crow_config::get("pdf.handle", "chrome");
		switch( $pdf_handle )
		{
			//	他のPDF生成ハンドルを使用する場合に備えたswitch
			case "chrome" : return self::create_with_chrome();
		}
		return self::create_with_chrome();
	}

	//	Headless Chromeを使用したPDF生成
	public static function create_with_chrome()
	{
		$inst = new self();
		$inst->m_handle = "chrome";
		return $inst;
	}

	//--------------------------------------------------------------------------
	//	ファイル名指定
	//--------------------------------------------------------------------------
	public function name( $name_ )
	{
		$name = mb_substr($name_, -4, 4) === ".pdf" ? $name_ : $name_.".pdf";
		$this->m_name = rawurlencode($name);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	テンプレート指定
	//--------------------------------------------------------------------------
	public function template( $template_name_, $replace_map_ = [] )
	{
		//	対象のテンプレートを見つける
		$role = crow_request::get_role_name();
		$html_path = CROW_PATH."app/assets/pdf/".$role."/".$template_name_.".php";
		if( is_file($html_path) === false )
		{
			$html_path = CROW_PATH."app/assets/pdf/_common_/".$template_name_.".php";
			if( is_file($html_path) === false )
			{
				crow_log::notice("undefined html template : ".$template_name_);
				return $this;
			}
		}

		//	PHPテンプレートの読み込み
		ob_start();
		extract($replace_map_);
		include($html_path);
		$this->m_html = ob_get_clean();

		//	CSS構築
		$this->m_css = $this->get_css_code($template_name_);

		return $this;
	}

	//--------------------------------------------------------------------------
	//	CSSの構築
	//--------------------------------------------------------------------------
	public function get_css_code( $template_name_ )
	{
		//	キャッシュにあればそれを返却
		$cache_name = "css_pdf_".$template_name_;
		$source = crow_cache::load($cache_name);
		if( $source !== false ) return $source;
		$source = "";

		//	CSSパス構築
		$role = crow_request::get_role_name();
		$fnames = [];
		$css_paths =
		[
			CROW_PATH."engine/assets/css",
			CROW_PATH."app/assets/css/_common_",
			CROW_PATH."app/assets/css/".$role."/_common_",
		];
		foreach( $css_paths as $path )
		{
			$files = crow_storage::disk()->get_files($path, false, ["css","icss"]);
			foreach( $files as $file )
				$fnames[] = $file;
		}

		$css_paths =
		[
			CROW_PATH."app/assets/pdf/".$role."/".$template_name_.".icss",
			CROW_PATH."app/assets/pdf/".$template_name_.".icss",
		];
		foreach( $css_paths as $file )
		{
			if( ! is_file($file) ) continue;
			$fnames[] = $file;
		}

		//	require パスを作成しておく、優先順で。
		$require_paths =
		[
			CROW_PATH."app/assets/css/".$role."/",
			CROW_PATH."app/assets/css/".$role."/_common_/",
			CROW_PATH."app/assets/css/_common_/",
			CROW_PATH."engine/assets/css/",
		];

		//	ここまでで列挙したファイルを全て結合する
		foreach( $fnames as $css_path )
		{
			//	パーツがキャッシュ化されている場合はそちらを使用
			$part_name_hash = md5($css_path);
			$part_src = crow_cache::load($part_name_hash);
			if( $part_src === false )
			{
				$part_src = crow_css::compress($css_path, $require_paths);
				crow_cache::save($part_name_hash, $part_src);
			}
			$source .= $part_src;
		}
		return $source;
	}

	//--------------------------------------------------------------------------
	//	HTMLを指定
	//--------------------------------------------------------------------------
	public function html( $html_ )
	{
		$this->m_html = $html_;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	CSSを指定
	//--------------------------------------------------------------------------
	public function css( $css_ )
	{
		$this->m_css = crow_css::compress_css($css_);
		return $this;
	}

	//--------------------------------------------------------------------------
	//	オプション指定
	//--------------------------------------------------------------------------
	public function add_opt( $key_, $val_ )
	{
		$this->m_opts[$key_] = $val_;
		return $this;
	}

	public function add_opts( $arr_ )
	{
		foreach( $arr_ as $key => $val )
			$this->m_opts[$key] = $val;
		return $this;
	}

	//--------------------------------------------------------------------------
	//	PDFをビルド
	//--------------------------------------------------------------------------
	public function build()
	{
		if( $this->m_handle === "chrome" )
		{
			//	仮想Chromeブラウザの生成
			$browser_factory = new BrowserFactory();
			$browser = $browser_factory->createBrowser(["noSandbox" => true]);

			//	HTMLを構成
			$html = $this->m_html;
			if( strlen($this->m_css) > 0 )
				$html = "<head><style>".$this->m_css."</style></head><body>".$html."</body>";

			//	仮想ページにHTMLを描画
			$page = $browser->createPage();
			$page->setHtml($html);

			//	デフォルトのオプション
			$default_opts =
			[
				"printBackground" => true,
				"marginTop" => 0,
				"marginBottom" => 0,
				"marginLeft" => 0,
				"marginRight" => 0,
			];

			//	PDF生成
			$pdf = $page->pdf(array_merge($default_opts, $this->m_opts));
			return base64_decode($pdf->getBase64());
		}
		else
		{
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//	ブラウザ描画テスト
	//--------------------------------------------------------------------------
	public function output_test()
	{
		//	HTMLを構成
		$html = $this->m_html;
		if( strlen($this->m_css) > 0 )
			$html = "<head><style>".$this->m_css."</style></head><body>".$html."</body>";

		echo $html;
	}

	//--------------------------------------------------------------------------
	//	ブラウザ出力
	//--------------------------------------------------------------------------
	public function output_browser()
	{
		//	ヘッダ設定
		header("Content-Description: File Transfer");
		header("Content-Type: application/pdf");
		header("Content-Disposition: inline; filename*=UTF-8''".$this->m_name);
		header("Content-Transfer-Encoding: binary");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: public");

		//	PDF出力
		echo $this->build();
	}

	//--------------------------------------------------------------------------
	//	ダウンロード出力
	//--------------------------------------------------------------------------
	public function output_download()
	{
		//	ヘッダ設定
		header("Content-Description: File Transfer");
		header("Content-Type: application/pdf");
		header("Content-Disposition: attachment; filename*=UTF-8''".$this->m_name);
		header("Content-Transfer-Encoding: binary");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: public");

		//	PDF出力
		echo $this->build();
	}

	private $m_handle = "";
	private $m_name = "";
	private $m_html = "";
	private $m_css = "";
	private $m_opts = [];
};

?>
