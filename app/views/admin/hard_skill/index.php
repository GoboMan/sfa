<?php $include('header.php'); ?>

<div class="ui_panel transparent layout_horizon_top padding_vertical full"> 

 <?php $include("sidebar.php") ?>

 <div class="ui_panel transparent layout_vertical_left full padding_horizon">
  <div class="ui_panel transparent full_horizon">
   <h1 class="ui_heading">ハードスキル一覧</h1>
  </div>

  <?php /* heading */ ?>
  <div class="ui_panel transparent layout_horizon padding_vertical"> 
   <div class="spacer"></div>
   <button id="create_btn" class="ui_button warn">新規登録</button>
  </div>

  <?php /* 企業一覧 Table */ ?>
  <table id="companies_table" class="ui_list">
   <thead>
    <tr>
     <th class="min">ID</th>
     <th>ハードスキル名</th>
     <th>類義語</th>
     <th>スキル種類</th>
     <th class="min">編集</th>
    </tr>
   </thead>

   <tbody>
    <?php if(count($rows) > 0): ?>
     <?php foreach($rows as $row): ?>
      <tr class="border clickable hover">
       <td class="min"><?= $row->hard_skill_id ?></td>
       <td><?= $row->name ?></td>
       <td><?= $row->synonyms ?></td>
       <td><?= model_hard_skill::get_skill_type_map()[$row->skill_type] ?></td>
       <td class="min">
        <button class="ui_button small done edit_btn" 
         hard_skill_id="<?= $row->hard_skill_id ?>"
         name="<?= $row->name ?>"
         synonyms="<?= $row->synonyms ?>"
         skill_type="<?= $row->skill_type ?>"
         >変更</button>
        <button class="ui_button small danger delete_btn"
         hard_skill_id="<?= $row->hard_skill_id ?>"
         >削除</button>
       </td>
      </tr>
     <?php endforeach; ?>
    <?php else: ?>
     <tr class="border nodata">
      <td colspan="5">データがありません。</td>
     </tr>
    <?php endif; ?>
   </tbody>
  </table>
 </div>
</div>

<?php /**** Create dialog ****/ ?>
<div id="create_dlg" class="ui_dialog full_horizon">
 <div>
  <div class="header">新規ハードスキル追加</div>
  <div id="input_table_body" class="body">
   <div class="ui_panel layout_horizon padding_top padding_horizon first_row">
    <div class="margin_horizon" style="white-space:nowrap;">スキル名</div>
    <div class="margin full_horizon">
      <input type="text" class="ui_text full_horizon" name="name">
    </div>
    <div class="margin_horizon" style="white-space:nowrap;">類義語</div>
    <div class="margin full_horizon">
      <input type="text" class="ui_text full_horizon" name="synonyms">
    </div>
    <div class="margin_horizon" style="white-space:nowrap;">スキル種別</div>
    <div class="margin full_horizon">
      <select name="skill_type" class="ui_select full_horizon">
       <?= crow_html::make_option_tag(model_hard_skill::get_skill_type_map()) ?>
      </select>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button small close">キャンセル</button>
   <button class="ui_button small info">+追加</button>
   <button class="ui_button small done">完了</button>
  </div>
 </div>
</div>

<?php /**** input_row_template　****/ ?>
<div id="input_row_template">
 <div class="ui_panel layout_horizon padding_horizon input_row">
  <div class="margin_horizon" style="white-space:nowrap;">スキル名</div>
  <div class="margin full_horizon">
   <input type="text" class="ui_text full_horizon" name="name">
  </div>
  <div class="margin_horizon" style="white-space:nowrap;">類義語</div>
  <div class="margin full_horizon">
   <input type="text" class="ui_text full_horizon" name="synonyms">
  </div>
  <div class="margin_horizon" style="white-space:nowrap;">スキル種別</div>
  <div class="margin full_horizon">
   <select name="skill_type" class="ui_select">
    <?= crow_html::make_option_tag(model_hard_skill::get_skill_type_map()) ?>
   </select>
  </div>
 </div>
</div>


<?php /**** Edit dialog ****/ ?>
<div id="edit_dlg" class="ui_dialog full_horizon">
 <div>
  <div class="header">ハードスキルの変更</div>
  <div id="input_table_body" class="body">
   <div class="ui_panel layout_horizon padding_top first_row">
    <div class="margin_horizon" style="white-space:nowrap;">スキル名</div>
    <div class="margin full_horizon">
     <input type="text" class="ui_text full_horizon" name="name">
    </div>
    <div class="margin_horizon" style="white-space:nowrap;">類義語</div>
    <div class="margin full_horizon">
     <input type="text" class="ui_text full_horizon" name="synonyms">
    </div>
    <div class="margin_horizon" style="white-space:nowrap;">スキル種別</div>
    <div class="margin full_horizon">
     <select name="skill_type" class="ui_select">
      <?= crow_html::make_option_tag(model_hard_skill::get_skill_type_map()) ?>
     </select>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button small close">キャンセル</button>
   <button class="ui_button small done">完了</button>
  </div>
 </div>
</div>

<?php /**** Add script tag to connect to the javascript　****/ ?>
<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	init(
	{
		actions : <?= crow::get_module_urls_as_json() ?>,
	});
});
</script> 



<?php $include('footer.php'); ?>

