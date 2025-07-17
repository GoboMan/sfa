<!DOCTYPE html>
<html>
 <head>
  <?= crow::get_default_head_tag() ?>
 </head>
 <body>
  <div id="page" class="ui_panel layout_vertical_left full transparent">

   <?php /**** サイトヘッダ ****/ ?>
   <div id="header" class="ui_panel dark full_horizon padding">RA SFA管理画面</div>

   <?php /**** メニュー ****/ ?>
   <div id="menus" class="ui_panel layout_horizon padding_small full_horizon">
    <div class="item"><a href="<?= crow::make_url() ?>">トップ</a></div>
    <div class="item"><a href="<?= crow::make_url('user') ?>">ユーザ管理</a></div>
    <div class="item"><a href="<?= crow::make_url('admin') ?>">管理者管理</a></div>
    <div class="item"><a href="<?= crow::make_url('industry') ?>">登録</a></div>
    <div class="spacer"></div>
    <div class="item"><a href="<?= crow::make_url('auth', 'logout') ?>">ログアウト</a></div>
   </div>

   <?php /**** メインコンテンツ ****/ ?>
   <div id="content" class="spacer">
