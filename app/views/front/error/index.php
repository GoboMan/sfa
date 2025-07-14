<!DOCTYPE html>
<html lang="ja">
<head>
 <title>システムエラー</title>
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
  <h1>システムのエラーによりページが表示できません。</h1>

  <?php if( crow_config::get('error.home.url', '') != '' ) : ?>
   <h2>次のステップ</h2>
   <hr>
   <h3>ホームページに戻る</h3>
   <p><a href="<?= crow_config::get('error.home.url') ?>">ホームページ</a>から探し直してみましょう。</p>
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
