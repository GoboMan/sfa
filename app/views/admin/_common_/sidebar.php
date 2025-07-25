<div class="clickable hover sidebar ui_panel layout_vertical_left">

 <a class="block_link" href="<?= crow::make_url('industry') ?>">
  <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel_top 
   <?= $url === crow::make_url('industry') ? 'active' : '' ?>">
   <div class="margin_left">業界マスタ</div>
  </div>
 </a>

 <a class="block_link" href="<?= crow::make_url('position') ?>">
  <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel 
   <?= $url === crow::make_url('position') ? 'active' : '' ?>">
   <div class="margin_left">ポジション</div>
  </div>
 </a>

 <a class="block_link" href="<?= crow::make_url('soft_skill') ?>">
  <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel 
   <?= $url === crow::make_url('soft_skill') ? 'active' : '' ?>">
   <div class="margin_left">ソフトスキル</div>
  </div>
 </a>

 <a class="block_link" href="<?= crow::make_url('hard_skill') ?>">
  <div class="ui_panel layout_vertical_left padding_vertical full_horizon sidebar_panel 
   <?= $url === crow::make_url('hard_skill') ? 'active' : '' ?>">
   <div class="margin_left">ハードスキル</div>
  </div>
 </a>
 
</div>