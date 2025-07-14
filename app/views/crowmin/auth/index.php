<?php $include("header_nologin.php"); ?>

<?php /***** ログインフォーム *****/ ?>
<div class="ui_panel layout_vertical full_vertical transparent">
 <div class="spacer"></div>
 <form class="ui_panel shadow" method="post" action="<?= crow::make_url_self() ?>">
  <?= crow::get_csrf_hidden() ?>
  <table class="ui_list">
   <tr>
    <th colspan=2>crowmin</th>
   </tr>
   <tr>
    <td>ログインID</td>
    <td><input type="text" class="ui_text" name="login_id" value="<?= crow_request::reflect('login_id', '') ?>"></td>
   </tr>
   <tr>
    <td>パスワード</td>
    <td><input type="password" class="ui_text" name="login_pw"></td>
   </tr>
   <tr>
    <td colspan=2 class="center"><button class="ui_button done" type="submit">ログイン</button></td>
   </tr>
  </table>
 </form>
 <div class="spacer"></div>
</div>

<?php /**** 表示制御 ****/ ?>
<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	<?php /**** エラー、メッセージの表示 ****/ ?>
	<?php if( isset($message) === true ) : ?>
		ui.dialog.popup_message("メッセージ", "<?= crow_html::escape_js($message) ?>");
	<?php endif; ?>
	<?php if( isset($error) === true ) : ?>
		ui.dialog.popup_error("エラー", "<?= crow_html::escape_js($error) ?>");
	<?php endif; ?>
});
</script>

<?php $include("footer_nologin.php"); ?>
