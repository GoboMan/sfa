<?php $include("database_header.php"); ?>

<script nonce="<?= crow_response::nonce() ?>">
function btn_import($fname_)
{
	popup_confirm( "確認", "現行DBの内容は消えてしまいます。本当にインポートしますか？", "インポートする", "キャンセル", function()
	{
		var url = "<?= crow::make_url_action('import_exec') ?>?data="+$fname_;
		jump(url);
	});
}
function btn_delete($fname_)
{
	popup_confirm( "確認", "バックアップデータ「<b>"+$fname_+"</b>」を本当に削除しますか？", "削除する", "キャンセル", function()
	{
		var url = "<?= crow::make_url_action('backup_del') ?>?data="+$fname_;
		jump(url);
	});
}
$(function()
{
	$("#files .ui_btn.import").on('click', function()
	{
		btn_import($(this).attr("name"));
	});
	$("#files .ui_btn.delete").on('click', function()
	{
		btn_delete($(this).attr("name"));
	});
});
</script>


<div class="page_title">バックアップ</div>
<div id="backup_form">
 <form method="post" action="<?= crow::make_url_action('backup_exec') ?>">
  <?= crow::get_csrf_hidden_action('backup_exec') ?>
  <button class="ui_btn green">データベースのバックアップを実行</button>
  <div class="info">※ CROW_PATH/output/backup ディレクトリに書き込み権が必要です。</div>
 </form>
</div>


<div class="page_title">バックアップ一覧</div>
<table id="files">
 <tr>
  <th>日付</th>
  <th>ファイル名</th>
  <th>サイズ (bytes)</th>
  <th>操作</th>
 </tr>
 <?php foreach( $rows as $row ) : ?>
 <tr>
  <td><?= $row['date'] ?></td>
  <td><?= $row['name'] ?></td>
  <td class="right"><?= number_format($row['size']) ?></td>
  <td>
   <button class="ui_btn small red import" name="<?= $row['name'] ?>">インポート</button>
   <button class="ui_btn small red delete" name="<?= $row['name'] ?>">削除</button>
  </td>
 </tr>
 <?php endforeach; ?>
</table>


<?php $include("database_footer.php"); ?>
