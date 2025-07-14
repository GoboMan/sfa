<?php $include("header.php"); ?>

<table id="pane_frame">
 <tr>
  <td id="pane_left">

   <?php /**** メインメニュー ****/ ?>
   <div class="row section">menu</div>
<?php
	$active = crow_request::get_action_name();
	$menus =
	[
		"index"			=> "DB定義書",
		"app_structure"	=> "ファイル構成書",
	];
	foreach( $menus as $key => $menu )
	{
		$url = crow::make_url_action($key);
		if( $key == $active )
			echo '<div class="row item active link" href="'.$url.'">'.$menu.'</div>';
		else
			echo '<div class="row item link" href="'.$url.'">'.$menu.'</div>';
	}
?>

  </td>
  <td id="pane_right">

   <?php /**** DB定義書 ****/ ?>
   <form id="excel_wrap" action="<?= crow::make_url_path('/excel/download/') ?>" method="post">
    <?= crow::get_csrf_hidden('excel', 'download') ?>

    <?php /*** 上 ***/ ?>
    <div id="excel_header">
     <div class="page_title">DB定義書出力</div>
     <div class="btn_area">
      <button id="download" class="ui_btn blue">ダウンロード</button>
     </div>
     <div>ダウンロード後に画面を更新すると履歴表示も最新になります。</div>
    </div>

    <div id="excel_block_box">

     <?php /*** 左 ***/ ?>
     <div class="excel_block left">

      <?php /*** ドキュメント設定  ***/ ?>
      <div id="excel_info_area">
       <table>
        <tr class="pane_title"><th colspan="2" class="table_header">ページヘッダー設定</th></tr>

        <?php foreach( $page_header_rows as $key => $row ) : ?>
         <tr>
          <td class="cols first"><?= $row["name"] ?></td>
          <td class="cols second">
           <input type="textbox" name="<?= $key ?>" class="input_box prop" value="<?= $row["val"] ?>">
          </td>
         </tr>
        <?php endforeach; ?>
<?php /* 動かないので一旦
        <tr>
         <td colspan="2" class="cols first">
          <div id="prop_area">
           <span class="prop">Excelプロパティ詳細設定</span>
           <span class="open">+</span>
          </div>
         </td>
        </tr>
*/ ?>
        <?php foreach( $property_rows as $key => $row ) : ?>
         <tr class="hidden_prop hidden">
          <td class="cols first"><?= $row["name"] ?></td>
          <td class="cols second">
           <input type="textbox" name="<?= $key ?>" class="input_box prop" value="<?= $row["val"] ?>">
          </td>
         </tr>
        <?php endforeach; ?>

       </table>
      </div>

      <?php /*** 履歴設定  ***/ ?>
      <div id="excel_history_area">
       <table>
        <tr class="pane_title"><th colspan="4" class="table_header">改版履歴</th></tr>
        <tr>
         <th class="cols fourth col_message" colspan="4">
          <textarea name="new_message" placeholder="新規更新内容を記入" class="input_box hist"></textarea>
         </th>
        </tr>
        <tr>
         <th class="cols second col_date header">更新日時</th>
         <th class="cols fourth col_message header">更新内容</th>
         <th colspan="2"></th>
        </tr>
        <?php if( count($history_rows) > 0 ) : ?>
         <?php foreach( $history_rows as $i => $history_row ) : ?>
          <?php list($hist_no, $hist_date, $hist_msg) = $history_row; ?>
          <tr class="hist_row" id="row_<?= $hist_no ?>">
           <td id="date_<?= $hist_no ?>" class="cols second date"><?= date("Y/m/d",strtotime($hist_date)) ?></td>
           <td id="msg_<?= $hist_no ?>" class="cols fourth message">
            <p class="current_msg"><?= nl2br($hist_msg, false) ?></p>
            <?php /* 動かないので一旦 <textarea id="update_msg_<?= $hist_no ?>" class="update_msg hidden" name="update_msg_<?= $hist_no ?>"><?= $hist_msg ?></textarea> */ ?>
           </td>
           <?php /* 動かないので一旦 <td class="cols edit"><div class="ui_btn green edit">編集</div></td> */ ?>
           <?php /* 動かないので一旦 <td class="cols delete"><div class="ui_btn red delete">削除</div></td> */ ?>
          </tr>
         <?php endforeach; ?>
        <?php else : ?>
         <tr>
          <th class="cols fourth col_message" colspan="4">改版履歴がありません</th>
         </tr>
        <?php endif; ?>
       </table>
      </div>
     </div>

     <?php /*** 右 ***/ ?>
     <div class="excel_block right">
      <div id="excel_tables_area">
       <table>
        <tr class="pane_title"><th colspan="3" class="table_header">テーブル一覧(出力するテーブルを選択) <?= count($table_rows) ?>テーブル</th></tr>
        <tr>
         <th class="cols first col_check header"><input type="checkbox" id="check_all" name="check_all" checked><span>選択</span></th>
         <th class="cols second col_physical header">物理名</th>
         <th class="cols third col_logical header">論理名</th>
        </tr>
        <?php foreach( $table_rows as $name => $table_row ) : ?>
        <tr class="table_row">
         <td id="check_<?= $name ?>" class="cols first col_check"><input type="checkbox" name="checked_<?= $name ?>" class="check_box" checked></td>
         <td id="physical_<?= $name ?>" class="cols second col_physical"><?= $table_row["physical"] ?></td>
         <td id="logical_<?= $name ?>" class="cols third col_logical"><?= $table_row["logical"] ?></td>
        </tr>
        <?php endforeach; ?>
       </table>
      </div>
     </div>

    <?php /*** flexbox閉じ ***/ ?>
    </div>
   </form>

  </td>
 </tr>
</table>
