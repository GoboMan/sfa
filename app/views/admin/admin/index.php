<?php $include('header.php'); ?>

<h1 class="ui_heading">管理者リスト</h1>
<div class="ui_panel transparent layout_horizon full_horizon">
 <div class="spacer"></div>
</div>

<?php /**** heading ****/ ?>
<div class="ui_panel transparent layout_horizon padding_vertical"> 
 <button id="create_btn" class="ui_button warn">新規登録</button>
 <div class="spacer"></div>
</div>

<?php /**** 管理者一覧 Table ****/ ?>
<table id="admin_table" class="ui_list">
 <thead>
  <tr>
   <th class="min">ID</th>
   <th>氏名</th>
   <th class="min">ログインID</th>
   <th class="min">パスワード</th>
   <th class="min">編集</th>
  </tr>
 </thead>

 <tbody>
  <?php foreach($rows as $row): ?>
   <tr class="border clickable hover">
    <td class="min"><?= $row->admin_id ?></td>
    <td><?= $row->name?></td>
    <td class="min"><?= $row->login_id ?></td>
    <td class="min">****</td>
    <td class="min">
     <button class="ui_button small done edit_btn" 
      admin_id="<?= $row->admin_id ?>"
      name="<?= $row->name ?>"
      login_id="<?= $row->login_id ?>"
      login_pw="<?= $row->login_pw ?>"
     >変更</button>
     <button class="ui_button small danger delete_btn"
      admin_id="<?= $row->admin_id ?>">削除</button>
    </td>
   </tr>
  <?php endforeach; ?>
 </tbody>
</table>

<?php /**** Create・Edit dialog ****/ ?>
<div id="create_dlg" class="ui_dialog">
 <div>
  <div class="header">新規管理者作成</div>
  <div class="body">
   <table class="ui_list full_horizon">
    <tr class="border_bottom">
      <td>氏名</td>
      <td><input type="text" class="ui_text" name="name"></td>
    </tr>
    <tr>
     <td class="min">
      <div class="ui_panel layout_vertical_left ">
       <div>ログインID</div>
       <div><input class="ui_text" type="text" name="login_id"></div>
      </div>
     </td>
     <td class="min">
      <div class="ui_panel layout_vertical_left ">
       <div>パスワード変更</div>
       <div><input class="ui_text" type="password" name="login_pw"></div>
      </div>
     </td>
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