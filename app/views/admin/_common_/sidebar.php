<div class="clickable hover sidebar ui_panel layout_vertical_left">
 <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel_top 
  <?= $url === crow::make_url('industry') ? 'active' : '' ?> ">
   <a class="margin_left" href="<?= crow::make_url('industry') ?>">業界マスタ</a>
 </div>
 <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel
  <?= $url === crow::make_url('position') ? 'active' : '' ?> ">
   <a class="margin_left" href="<?= crow::make_url('position') ?>">ポジション</a>
 </div>
 <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel
  <?= $url === crow::make_url('skill') ? 'active' : '' ?> ">
   <a class="margin_left" href="<?= crow::make_url('skill') ?>">スキル</a>
 </div>
 <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel
  <?= $url === crow::make_url('skill') ? 'active' : '' ?> ">
   <a class="margin_left" href="<?= crow::make_url('language') ?>">言語</a>
 </div>
</div>