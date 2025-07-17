<?php $include('header.php'); ?>

<div class="ui_panel transparent layout_horizon_top padding_vertical full"> 

 <?php $include("sidebar.php") ?>

 <div class="ui_panel layout_vertical_left full padding_large">
  <h1 class="ui_heading">業界一覧</h1>
 
  <?php /* heading */ ?>
  <div class="ui_panel transparent layout_horizon padding_vertical"> 
    <div class="spacer"></div>
   <button id="create_btn" class="ui_button warn">新規登録</button>
  </div>

  <?php /* 企業一覧 Table */ ?>
  <table id="buisness_table" class="ui_list">
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
      <tr class="border clickable hover"
       industry_id = "<?= $row->industry_id ?>"
       name = "<?= $row->name ?>"
      >
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

<?php /**** Create・Edit dialog ****/ ?>
<div id="create_dlg" class="ui_dialog">
 <div>
  <div class="header">新規管理者作成</div>
  <div class="body">
   <div class="ui_panel layout_horizon padding_xlarge">
    <div class="margin_right" style="white-space:nowrap;">業界名</div>
    <div class="margin full_horizon"><input type="text" class="ui_text full_horizon" name="name"></div>
    </div>
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