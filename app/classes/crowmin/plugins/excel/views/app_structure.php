<?php $include("header.php"); ?>
<table id="pane_frame">
 <tr>
  <td id="pane_left">

   <?php /**** メインメニュー ****/ ?>
   <div class="row section">menu</div>
<?php
	$active = crow_request::get_action_name();
	$menus = array
	(
		"index"			=> "DB定義書",
		"app_structure"	=> "ファイル構成書",
	);
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

   <?php /**** ファイル構成書 ****/ ?>
   <div id="excel_header">
    <div class="page_title">ファイル構成書</div>
     <div class="btn_area">
      <a class="ui_btn blue" href="<?= crow::make_url_action("download_app_structure") ?>">ダウンロード</a>
     </div>
     <div>ファイル構成をExcelで出力します</div>
    </div>
   </div>

  </td>
 </tr>
</table>
