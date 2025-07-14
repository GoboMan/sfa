<?php $include("header.php"); ?>
<?php $include("s3filer_header.php"); ?>

<div id="env">
 <span>現在のプロファイル：</span>
 <select class="ui_select" id="profile" name="profile">
  <?= crow_html::make_option_tag($profile_names, $profile) ?>
 </select>
 <span>バケット：</span>
 <input type="text" class="ui_text" id="bucket" name="bucket" value="<?= $bucket ?>">
 <span></span>
 <button class="ui_button done" id="btn_change_profile">変更</button>
</div>

<div id="path">
 <input type="text" class="ui_text" name="path" value="<?= $path ?>">
 <div class="prompt">Path : </div>
</div>

<form id="uploader" method="post" action="<?= crow::make_url_action('upload') ?>" enctype="multipart/form-data">
 <?= crow::get_csrf_hidden_action('upload') ?>
 <input type="hidden" name="path" value="<?= crow_html::escape_js($path) ?>">
 <input type="file" id="upload_file" name="upload_file">
 <button class="ui_button done" style="display:none">アップロード</button>
</form>

<table id="data">
 <tr>
  <th class="noborder-left">name</th>
  <th class="center noborder-right">size</th>
  <th class="center noborder-left"></th>
  <th class="min">update</th>
  <th class="min">class</th>
  <th class="min">owner</th>
  <th class="min">etag</th>
  <th class="min noborder-right"></th>
 </tr>

 <?php /**** 親ディレクトリへのパス ****/ ?>
 <?php if( $path != "/" ) : ?>
  <tr class="dir">
   <td colspan=8 class="noborder-left noborder-right" path="<?= crow_html::escape_js(crow_storage::extract_dirpath(substr($path,0,strlen($path)-1))) ?>">..</td>
  </tr>
 <?php endif; ?>

 <?php /**** サブディレクトリ一覧 ****/ ?>
 <?php if( count($dirs) > 0 ) : ?>
  <?php foreach($dirs as $dir) : ?>
   <tr class="dir">
    <td colspan=8 class="noborder-left noborder-right" path="<?= crow_html::escape_js($dir) ?>"><?= $dir ?></td>
   </tr>
  <?php endforeach; ?>
 <?php endif; ?>

 <?php /**** ファイル一覧 ****/ ?>
 <?php if( count($files) > 0 ) : ?>
  <?php foreach($files as $file) : ?>
   <tr class="file">
    <td class="noborder-left"><?= $file['name'] ?></td>
    <td class="min right noborder-right">
     <?= number_format($file['size']) ?>
    </td>
    <td class="min center noborder-left">
     <button class="button_download" path="<?= crow_html::escape_js($path.$file['name']) ?>"></button>
    </td>
    <td class="min"><?= date('Y/m/d H:i:s', $file['update']) ?></td>
    <td class="min"><?= $file['class'] ?></td>
    <td class="min"><?= $file['owner'] ?></td>
    <td class="min"><span title="<?= $file['etag'] ?>"><?= strlen($file['etag']) > 13 ? substr($file['etag'], 0, 10)."..." : $file['etag'] ?></span></td>
    <td class="min center noborder-right">
     <button class="button_trash" path="<?= crow_html::escape_js($path.$file['name']) ?>"></button>
    </td>
   </tr>
  <?php endforeach; ?>
 <?php endif; ?>
</table>

<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	init_page(
	{
		"path" : "<?= $path ?>",
		"url_self" : "<?= crow::make_url_self() ?>",
		"url_change_profile" : "<?= crow::make_url_action('ajax_change_profile') ?>",
		"url_download" : "<?= crow::make_url_action('download') ?>",
		"url_trash" : "<?= crow::make_url_action('trash') ?>",
		"buckets" : JSON.parse('<?= json_encode($buckets) ?>'),
		"csrf_change_profile_key" : '<?= crow::get_csrf_key_action("ajax_change_profile") ?>',
		"csrf_change_profile_val" : '<?= crow::get_csrf_val_action("ajax_change_profile") ?>'
	});
});
</script>


<?php $include("s3filer_footer.php"); ?>
<?php $include("footer.php"); ?>
