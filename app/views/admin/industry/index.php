<?php $include('header.php'); ?>

<div class="ui_panel transparent layout_horizon_top padding_vertical full"> 

 <?php $include("sidebar.php") ?>

 <div class="ui_panel transparent layout_vertical_left full padding_horizon">
  <div class="ui_panel transparent full_horizon">
   <h1 class="ui_heading">業界一覧</h1>
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
     <th>業界名</th>
     <th class="min">編集</th>
    </tr>
   </thead>

   <tbody>
    <?php usort($rows, function($a, $b)
    {
     return $a->due_date - $b->due_date;
    }); ?>
    <?php if(count($rows) > 0): ?>
     <?php foreach($rows as $row): ?>
      <tr class="border clickable hover">
       <td class="min"><?= $row->industry_id ?></td>
       <td><?= $row->name ?></td>
       <td class="min">
        <button class="ui_button small done edit_btn" 
         industry_id="<?= $row->industry_id ?>"
         name="<?= $row->name ?>"
         >変更</button>
        <button class="ui_button small danger delete_btn"
         industry_id="<?= $row->industry_id ?>"
         >削除</button>
       </td>
      </tr>
     <?php endforeach; ?>
    <?php else: ?>
     <tr class="border nodata">
      <td colspan="3">データがありません。</td>
     </tr>
    <?php endif; ?>
   </tbody>
  </table>
 </div>
</div>

<?php /**** Create dialog ****/ ?>
<div id="create_dlg" class="ui_dialog">
 <div>
  <div class="header">新規企業追加</div>
  <div id="input_table_body" class="body">
   <div class="ui_panel layout_horizon padding_top">
    <div class="margin_horizon" style="white-space:nowrap;">業界名</div>
    <div class="margin full_horizon input_row"><input type="text" class="ui_text full_horizon" name="name"></div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button small close">キャンセル</button>
   <button class="ui_button small info">+追加</button>
   <button class="ui_button small done">完了</button>

  </div>
 </div>
</div>

<!-- 
<div id="create_dlg" class="ui_dialog">
 <div>
  <div class="header">新規企業追加</div>
  <div class="body">
   <table class="ui_list full_horizon" id="admin_table">
    <thead>
     <tr class="border_bottom">
      <th>業界名</th>
     </tr>
    </thead>
    <tbody id="admin_table_body">
     <tr class="admin_row">
      <td><input type="text" class="ui_text" name="name"></td>
     </tr>
    </tbody>
   </table>

   <div class="padding_vertical">
    <button type="button" class="ui_button small info add_button">＋ 追加</button>
   </div>
  </div>
    
  <div class="footer">
   <button class="ui_button small close">キャンセル</button>
   <button class="ui_button small done">完了</button>
  </div>
 </div>
</div> 
-->


<?php /**** Edit dialog ****/ ?>
<div id="edit_dlg" class="ui_dialog">
 <div>
  <div class="header">新規企業追加</div>
  <div class="body">
   <div class="ui_panel layout_horizon padding_xlarge">
    <div class="margin_right" style="white-space:nowrap;">業界名</div>
    <div class="margin full_horizon"><input type="text" class="ui_text full_horizon" name="name"></div>
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

