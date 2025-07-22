<?php $include("header.php"); ?>

<div class="ui_panel transparent padding">

 <h1 class="ui_heading">SFA ユーザーリスト</h1>
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
    <th class="min">UserID</th>
    <th>氏名</th>
	<th>メールアドレス</th>
    <th class="min">ログインID</th>
    <th class="min">パスワード</th>
	<th>作成日</th>
    <th class="min">編集</th>
   </tr>
  </thead>

  <tbody>
   <?php foreach($rows as $row): ?>
    <tr class="border clickable hover">
     <td class="min"><?= $row->user_id ?></td>
     <td><?= $row->name ?></td>
     <td><?= $row->email ?></td>
     <td class="min"><?= $row->login_id ?></td>
     <td class="min">****</td>
     <td class="min"><?= date('Y-m-d', $row->create_at) ?></td>
     <td class="min">
      <button class="ui_button small done edit_btn" 
       user_id="<?= $row->user_id ?>"
       name="<?= $row->name ?>"
       email="<?= $row->email ?>"
       login_id="<?= $row->login_id ?>"
       login_pw="<?= $row->login_pw ?>"
       create_at="<?= date('Y-m-d', $row->create_at) ?>"
      >変更</button>
      <button class="ui_button small danger delete_btn"
       user_id="<?= $row->user_id ?>">削除</button>
     </td>
    </tr>
   <?php endforeach; ?>
  </tbody>
 </table>


 <?php /**** Create dialog ****/ ?>
 <div id="create_dlg" class="ui_dialog">
  <div>
   <div class="header">新規ユーザー登録</div>
   <div class="body">
    <table class="ui_list full_horizon">
     <tr class="border_bottom">
      <td class="min">
       <div class="ui_panel layout_vertical_left ">
        <div>氏名</div>
        <div><input class="ui_text" type="text" name="name"></div>
       </div>
      </td>
      <td class="min">
       <div class="ui_panel layout_vertical_left ">
        <div>メールアドレス</div>
        <div><input class="ui_text" type="text" name="email"></div>
       </div>
      </td>
     </tr>
     <tr class="border_bottom">
      <td class="min">
       <div class="ui_panel layout_vertical_left ">
        <div>ログインID</div>
        <div><input class="ui_text" type="text" name="login_id"></div>
       </div>
      </td>
      <td class="min">
       <div class="ui_panel layout_vertical_left ">
        <div>パスワード</div>
        <div><input class="ui_text" type="password" name="login_pw"></div>
       </div>
      </td>
     </tr>
     <tr>
      <td colspan="2">
       <div class="ui_panel layout_horizon padding_right_xlarge">
        <div class="margin_right" style="white-space:nowrap;">作成日</div>
        <div class="margin full_horizon"><input type="date" class="ui_text full_horizon" name="create_at"></div>
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

 <?php /**** Edit dialog ****/ ?>
 <div id="edit_dlg" class="ui_dialog">
  <div>
   <div class="header">新規ユーザー登録</div>
   <div class="body">
    <table class="ui_list full_horizon">
     <tr class="border_bottom">
      <td class="min">
       <div class="ui_panel layout_vertical_left ">
        <div>氏名</div>
        <div><input class="ui_text" type="text" name="name"></div>
       </div>
      </td>
      <td class="min">
       <div class="ui_panel layout_vertical_left ">
        <div>メールアドレス</div>
        <div><input class="ui_text" type="text" name="email"></div>
       </div>
      </td>
     </tr>
     <tr class="border_bottom">
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

</div>


<?php /**** ページ制御 ****/ ?>
<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	init(
	{
		actions : <?= crow::get_module_urls_as_json() ?>,
	});
});
</script>

<?php $include("footer.php"); ?>
