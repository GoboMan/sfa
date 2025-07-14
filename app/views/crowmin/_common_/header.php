<!DOCTYPE html>
<html>
 <head>
  <?= crow::get_default_head_tag() ?>
  <title>crowmin</title>
 </head>
<body>
<?php /***** 通知があるなら、ポップアップで表示 *****/ ?>
<script nonce="<?= crow_response::nonce() ?>" type="text/javascript">
$(function()
{
	<?php if( isset($error) === true ) : ?>
		popup_error('エラー', '<?= $error ?>');
	<?php elseif( isset($msg) === true ) : ?>
		popup_msg('メッセージ', '<?= $msg ?>');
	<?php endif; ?>

	$('.link').on('click', function()
	{
		document.location = $(this).attr("href");
	});
});
</script>

 <div id="header">crowmin - crow メンテ</div>
 <div id="menu">
<?php
	$menus = array( 'top', 'sandbox', 'database' );
	$menus = array_merge($menus, $plugins);
	foreach( $menus as $menu )
	{
		if( crow_request::get_module_name() == $menu )
		{
			echo '<a class="link active" href="'.crow::make_url($menu).'");">'
				.$menu.'</a>';
		}
		else
		{
			echo '<a class="link" href="'.crow::make_url($menu).'");">'
				.$menu.'</a>';
		}
	}
?>
  <a class="link" href="<?= crow::make_url('auth', 'logout') ?>">LOGOUT</a>
 </div>
 <div id="content">
