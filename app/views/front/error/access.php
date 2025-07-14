<!DOCTYPE html>
<html lang="ja">
<head>
 <title>ページの有効期限が切れました</title>
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
 <main>
  <h1>ページの有効期限が切れました</h1>
  <p>申し訳ございませんが、ページの有効期限が切れました。<br>お手数ですが、最初から操作をやり直してください。</p>

  <?php if( crow_config::get('error.home.url', '') != '' ) : ?>
   <h2>次のステップ</h2>
   <hr>
   <h3>最初から操作をやり直してみる</h3>
   <p>最初から操作しなおしてみましょう。<br><a href="<?= crow_config::get('error.home.url') ?>">ホームページに戻る</a></p>
  <?php endif; ?>

  <?php if( crow_config::get('error.support.name', '') != '' ) : ?>
   <hr>
   <h3>サポートにお問い合わせ</h3>
   <?php if( crow_config::get('error.support.url', '') != '' ) : ?>
    <p>このエラーが続く場合は、<a href="<?= crow_config::get('error.support.url') ?>"><?= crow_config::get('error.support.name') ?></a>までご連絡ください。</p>
   <?php else : ?>
    <p>このエラーが続く場合は、<?= crow_config::get('error.support.name') ?>までご連絡ください。</p>
   <?php endif; ?>
  <?php endif; ?>
 </main>
</div>
</body>
</html>
