<?php $include('header.php'); ?>

<div class="ui_panel transparent layout_horizon_top padding_vertical full"> 

 <?php $include("sidebar.php") ?>

 <div class="ui_panel transparent layout_vertical_left full padding_horizon">
  <div class="ui_panel transparent full_horizon">
   <h1 class="ui_heading">ポジション一覧</h1>
  </div>

  <div class="ui_panel transparent layout_horizon padding_vertical">
   <div id="create_btn" class="ui_button warn">新規登録</div>
   <div class="spacer"></div>
  </div>

  <table id="positions_table" class="ui_list">
   <thead>
    <tr>
     <th class="min">ID</th>
     <th>ポジション名</th>
     <th>ポジション類義語</th>
     <th class="min">編集</th>
    </tr>
   </thead>

   <tbody>
    <?php if(count($rows) > 0): ?>
     <?php foreach($rows as $row): ?>
      <tr class="clickable hover border">
       <td class="min"><?= $row->position_id ?></td>
       <td><?= $row->name ?></td>
       <td><?= $row->synonyms ?></td>
       <td class="min">
        <button class="ui_button small done edit_btn"
         position_id="<?= $row->position_id ?>"
         name="<?= $row->name ?>"
         synonyms="<?= $row->synonyms ?>"
        >変更</button>
        <button class="ui_button small danger delete_btn"
         position_id="<?= $row->position_id ?>"
        >削除</button>
       </td>
      </tr>
     <?php endforeach; ?>
    <?php else: ?>
     <tr class="border nodata">
      <td colspan="4">データがありません。</td>
     </tr>
    <?php endif; ?>
   </tbody>
  </table>
 </div>
</div>


<?php /**** Create dialog ****/ ?>
<div id="create_dlg" class="ui_dialog full_horizon">
 <div>
  <div class="header">新規ポジション追加</div>
  <div id="input_table_body" class="body">
   <div class="ui_panel layout_vertical padding_top padding_horizon first_row">
    <div class="margin_horizon" style="white-space:nowrap;">ポジション名</div>
    <div class="margin full_horizon">
      <input type="text" class="ui_text full_horizon" name="name">
    </div>
    <div class="margin_horizon" style="white-space:nowrap;">類義語</div>
    <div class="margin full_horizon">
      <textarea class="ui_text full_horizon" name="synonyms"></textarea>
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


<?php /**** Edit dialog ****/ ?>
<div id="edit_dlg" class="ui_dialog">
 <div>
  <div class="header">ポジションの変更</div>
  <div class="body">
   <table class="ui_list full_horizon">
    <tr class="border_bottom">
     <td>ポジション名</td>
     <td><input type="text" class="ui_text" name="name"></td>
    </tr>
    <tr>
     <td>類義語</td>
     <td><input type="text" class="ui_text" name="synonyms"></td>
    </tr>
   </table>
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

