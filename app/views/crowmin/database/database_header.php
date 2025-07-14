<?php $include("header.php"); ?>
<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	$('#table_filter').on('keyup', function()
	{
		var keyword = $(this).val();
		$('#pane_left .table').each(function()
		{
			if( $(this).text().indexOf(keyword) >= 0 )
				$(this).css('display', 'block');
			else
				$(this).css('display', 'none');
		});
	});
});
</script>
<table id="pane_frame">
 <tr>
  <td id="pane_left">

   <?php /**** メインメニュー ****/ ?>
   <div class="row section">menu</div>
<?php
	$menus = array
	(
		"index"		=> "デザイン",
		"lang"		=> "ローカライズ",
		"query"		=> "クエリ",
		"backup"	=> "バックアップ",
	);
	foreach( $menus as $key => $menu )
	{
		$url = crow::make_url_action($key);
		if( $key == $active )
			echo '<a class="row item active link" href="'.$url.'">'.$menu.'</a>';
		else
			echo '<a class="row item link" href="'.$url.'">'.$menu.'</a>';
	}
?>

   <?php /**** テーブルへのリンク ****/ ?>
   <div class="row section">tables</div>
    <div>
     <div><input type="text" id="table_filter" autocomplete="off" style="width:100%;padding:4px;margin:6px 0;border:none;" placeholder="フィルタ"></div>
    </div>
<?php
	//	デザインとDBに不一致がない場合のみリンク可とする
	foreach( $diff as $diff_item )
	{
		$url = crow::make_url_action('list', array('table'=>$diff_item['name']));

		if( $diff_item['exists']=="both" && $diff_item['field_comp']===true )
		{
			if( $active=="list" && $design->name==$diff_item['name'] )
			{
				echo '<a class="table row item active link" href="'.$url.'">'.$diff_item['name'].'</a>';
			}
			else
			{
				echo '<a class="table row item link" href="'.$url.'">'.$diff_item['name'].'</a>';
			}
		}
		else
		{
			echo '<div class="table row disable">'.$diff_item['name'].'</div>';
		}
	}
?>

  </td>
  <td id="pane_right">
