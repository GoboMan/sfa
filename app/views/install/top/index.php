<?php $include("header.php"); ?>

<div class="ui_panel transparent padding">

 <div class="ui_heading ui_panel layout_horizon full_horizon">
  <div class="ui_panel">system install</div>
  <div class="spacer"></div>
  <a class="ui_panel" href="<?= crow::make_url('auth', 'logout') ?>">[LOGOUT]</a>
 </div>

 <?php if( isset($error) === true ) : ?>
  <div class="ui_panel radius shadow padding margin_bottom" style="color:#f00;font-weight:600;"><?= $error ?></div>
 <?php endif; ?>
 <?php if( isset($msg) === true ) : ?>
  <div class="ui_panel radius shadow padding margin_bottom" style="color:#0a0;font-weight:600;"><?= $msg ?></div>
 <?php endif; ?>

 <form method="post" action="<?= crow::make_url_self() ?>">
  <?= crow::get_csrf_hidden() ?>
  <input type="password" name="exec_pw" class="ui_text" placeholder="パスワード">
  <button type="submit" class="ui_button warn">実行する</button>
 </form>

</div>

<?php $include("footer.php"); ?>
