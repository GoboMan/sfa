<!DOCTYPE html>
<html lang="ja">
<head>
 <title>ブラウザのJavaScriptを有効にしてください</title>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <style>
	body
	{
		font-family: -apple-system, system-ui, sans-serif;
	}
	hr
	{
		width: 100%;
		height: 1px;
		border: 0;
		background: #ccc;
	}
	a
	{
		color: #0070c9;
		text-decoration: none;
	}
	a:hover
	{
		text-decoration: underline;
	}
	.container
	{
		max-width: 680px;
		margin: 0 auto;
	}
 </style>
</head>
<body>
<div class="container">
 <header>
  <h1>JavaScriptを有効にしてください</h1>

  <?php if( crow_config::get('error.site.name', '') != '' ) : ?>
   <p>申し訳ございませんが、<?= crow_config::get('error.site.name') ?>をご利用いただくにはJavaScriptを有効にする必要があります。</p>
  <?php else : ?>
   <p>申し訳ございませんが、当サイトをご利用いただくにはJavaScriptを有効にする必要があります。</p>
  <?php endif; ?>

  <p>お手数ですが、以下の手順に従ってお使いのブラウザでJavaScriptを有効にしていただくことで、当サイトをご利用いただけます。</p>
 </header>
 <section>
  <h2>ブラウザのJavaScriptを有効にする方法</h2>
  <hr>
  <h3>Google Chrome</h3>
  <ol>
   <li>右上の⋮メニューをクリックし、「設定」をクリックします。</li>
   <li>左側のメニューから「プライバシーとセキュリティ」をクリックします。</li>
   <li>「サイトの設定」をクリックします。</li>
   <li>「JavaScript」をクリックし、「サイトが JavaScript を使用できるようにする」を選択します。</li>
  </ol>
  <p><a href="https://support.google.com/admanager/answer/12654?hl=ja" target="_blank">詳細な手順を見る</a></p>
 </section>
 <hr>
 <section>
  <h3>Microsoft Edge</h3>
  <ol>
   <li>右上の⋯メニューをクリックし、「設定」をクリックします。</li>
   <li>左側のメニューから「Cookieとサイトのアクセス許可」をクリックします。</li>
   <li>「JavaScript」をクリックし、「許可 (推奨)」をオンにします。</li>
  </ol>
  <p><a href="https://support.microsoft.com/ja-jp/topic/windows-で-javascript-を有効にする方法-88d27b37-6484-7fc0-17df-872f65168279" target="_blank">詳細な手順を見る</a></p>
 </section>
 <hr>
 <section>
  <h3>MacのSafari</h3>
  <ol>
   <li>Safariアプリを開き、メニューバーから「Safari」＞「設定」と選択してから、「セキュリティ」をクリックします。</li>
   <li>「JavaScriptを有効にする」の項目を有効にしてWebサイトにJavaScriptを許可します。</li>
  </ol>
  <p><a href="https://support.apple.com/ja-jp/guide/safari/ibrw1074/mac" target="_blank">MacのSafariで「セキュリティ」設定を変更する</a></p>
 </section>
 <hr>
 <section>
  <h3>iPhone、iPad、Apple Vision ProのSafari</h3>
  <ol>
   <li>設定アプリを開き、「Safari」＞「詳細」と選択してします。</li>
   <li>「JavaScript」の項目を有効にしてWebサイトにJavaScriptを許可します。</li>
  </ol>
  <p><a href="https://support.apple.com/ja-jp/guide/iphone/-iphb3100d149/ios" target="_blank">iPhoneユーザガイド</a></p>
  <p><a href="https://support.apple.com/ja-jp/guide/ipad/-ipad54f3cde6/ipados" target="_blank">iPadユーザガイド</a></p>
  <p><a href="https://support.apple.com/ja-jp/guide/apple-vision-pro/tan1fd5a2748/visionos" target="_blank">Apple Vision Proユーザガイド</a></p>
 </section>
 <hr>
 <section>
  <h3>Mozilla Firefox</h3>
  <p>Mozilla公式のサポート情報をご参照ください。</p>
  <p><a href="https://support.mozilla.org/ja/kb/javascript-settings-for-interactive-web-pages" target="_blank">詳細な手順を見る</a></p>
 </section>
</div>
</body>
</html>
