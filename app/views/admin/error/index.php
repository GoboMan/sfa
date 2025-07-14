<?php $include("header_nologin.php"); ?>

<div class="ui_panel border shadow margin_xlarge padding_xlarge">
 <h1 class="ui_heading">エラー</h1>
 <div class="ui_panel">
  <div class="ui_panel margin_bottom_large"><?= crow_request::get('error', "システムでエラーが発生しました") ?></div>
  <div class="ui_panel"><a class="ui_button done" href="<?= crow::make_url() ?>">トップページ</a></div>
 </div>
</div>

<?php $include("footer_nologin.php"); ?>
