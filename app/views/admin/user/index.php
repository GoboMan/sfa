<?php $include("header.php"); ?>

<?php /**** ユーザ登録 ****/ ?>
<div class="ui_panel transparent margin">
 <button id="btn_create" class="ui_button info">ユーザ登録</button>
</div>

<?php /**** 一覧テーブル ****/ ?>
<div class="ui_panel border shadow margin">
 <table id="rows" class="ui_list full_horizon">
  <tr>
   <th>ID</th>
   <th>ログインID</th>
   <th>氏名</th>
   <th>シンボル</th>
   <th>性別</th>
   <th>登録日</th>
   <th>操作</th>
  </tr>

  <?php /**** データがない場合 ****/ ?>
  <?php if( count($rows) <= 0 ) : ?>
   <tr>
    <td colspan=7 class="nodata">ユーザは登録されていません</td>
   </tr>

  <?php /**** データがある場合は一覧出力 ****/ ?>
  <?php else : ?>
   <?php foreach( $rows as $row ) : ?>
    <tr class="data" data_id="<?= $row->user_id ?>">
     <td><?= $row->user_id ?></td>
     <td><?= $row->login_id ?></td>
     <td><?= $row->name ?></td>
     <td><?= $row->slug ?></td>
     <td><?= $row->gender_str() ?></td>
     <td><?= date('Y年n月j日 H:i', $row->create_date) ?></td>
     <td class="right min">
      <input type="hidden" name="login_id" value="<?= $row->login_id ?>">
      <input type="hidden" name="name" value="<?= $row->name ?>">
      <input type="hidden" name="slug" value="<?= $row->slug ?>">
      <input type="hidden" name="gender" value="<?= $row->gender ?>">
      <button class="ui_button info small btn_edit" data_id="<?= $row->user_id ?>">編集</button>
      <button class="ui_button info small btn_password" data_id="<?= $row->user_id ?>">パスワード変更</button>
      <button class="ui_button warn small btn_delete" data_id="<?= $row->user_id ?>">削除</button>
     </td>
    </tr>
   <?php endforeach; ?>

   <?php /**** ページャー ****/ ?>
   <tr id="pager_row">
    <td colspan=7>
     <div class="ui_pager">
      <div class="label">
       <?= $pager->get_total() ?>件中、<?= $pager->get_start_index() ?>件目 ～ <?= count($pager->get_rows()) ?>件を表示
      </div>
      <div class="links">
       <?php if( $pager->get_prev_page() === false ) : ?>
        <div class="prev disabled" title="前の<?= $pager->get_row_per_page() ?>件"></div>
       <?php else : ?>
        <a href="<?= crow::make_url_self() ?>?page=<?= $pager->get_prev_page() ?>" class="prev" title="前の<?= $pager->get_row_per_page() ?>件"></a>
       <?php endif; ?>
       <?php if( $pager->get_next_page() === false ) : ?>
        <div class="next disabled" title="次の<?= $pager->get_row_per_page() ?>件"></div>
       <?php else : ?>
        <a href="<?= crow::make_url_self() ?>?page=<?= $pager->get_next_page() ?>" class="next" title="次の<?= $pager->get_row_per_page() ?>件"></a>
       <?php endif; ?>
      </div>
     </div>
    </td>
   </tr>

  <?php endif; ?>
 </table>
</div>

<?php /**** 登録ダイアログ ****/ ?>
<div class="ui_dialog" id="dlg_create">
 <div>
  <div class="header">ユーザ登録</div>
  <div class="body">
   <div class="ui_props inner">
    <div class="prop">
     <div>ログインID</div>
     <div><input type="text" class="ui_text" name="login_id"></div>
    </div>
    <div class="prop">
     <div>パスワード</div>
     <div><input type="password" class="ui_text" name="login_pw"></div>
    </div>
    <div class="prop">
     <div>パスワード (確認)</div>
     <div><input type="password" class="ui_text" name="login_pw_confirm"></div>
    </div>
    <div class="prop">
     <div>氏名</div>
     <div><input type="text" class="ui_text" name="name"></div>
    </div>
    <div class="prop">
     <div>シンボル</div>
     <div><input type="text" class="ui_text" name="slug"></div>
    </div>
    <div class="prop">
     <div>性別</div>
     <div><select class="ui_select" name="gender"><?= crow_html::make_option_tag(model_user::get_gender_map()) ?></select></div>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button close">閉じる</button>
   <button class="ui_button done">登録する</button>
  </div>
 </div>
</div>

<?php /**** 編集ダイアログ ****/ ?>
<div class="ui_dialog" id="dlg_edit">
 <input type="hidden" name="data_id" value="">
 <div>
  <div class="header">ユーザ編集</div>
  <div class="body">
   <div class="ui_props inner">
    <div class="prop">
     <div>ログインID</div>
     <div><input type="text" class="ui_text" name="login_id"></div>
    </div>
    <div class="prop">
     <div>氏名</div>
     <div><input type="text" class="ui_text" name="name"></div>
    </div>
    <div class="prop">
     <div>シンボル</div>
     <div><input type="text" class="ui_text" name="slug"></div>
    </div>
    <div class="prop">
     <div>性別</div>
     <div><select class="ui_select" name="gender"><?= crow_html::make_option_tag(model_user::get_gender_map()) ?></select></div>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button close">閉じる</button>
   <button class="ui_button done">更新する</button>
  </div>
 </div>
</div>

<?php /**** パスワード変更ダイアログ ****/ ?>
<div class="ui_dialog" id="dlg_password">
 <input type="hidden" name="data_id" value="">
 <div>
  <div class="header">パスワード変更</div>
  <div class="body">
   <div class="ui_props inner">
    <div class="prop">
     <div>ログインID</div>
     <div name="login_id"></div>
    </div>
    <div class="prop">
     <div>パスワード</div>
     <div><input type="password" class="ui_text" name="login_pw"></div>
    </div>
    <div class="prop">
     <div>パスワード (確認)</div>
     <div><input type="password" class="ui_text" name="login_pw_confirm"></div>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button close">閉じる</button>
   <button class="ui_button done">更新する</button>
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
		reload_url : "<?= crow::make_url_self(['page' => crow_request::get('page',1)]) ?>",
	});
});
</script>

<?php $include("footer.php"); ?>
