<?php $include("header.php"); ?>
<?php $include("components_header.php"); ?>


<?php /**** div class="ui_heading">Components viewer</div ****/ ?>

<?php /**** コンポーネントリスト ****/ ?>
<div id="apply_hist" class="ui_panel layout_horizon full_horizon padding radius shadow margin_bottom" style="min-height:47px"></div>

<?php /**** メイン ****/ ?>
<div id="panel_main" class="ui_panel layout_horizon_stretch full ui_splitter transparent">
 <div id="panel_tree" class="ui_panel full_vertical">
<?php

	function echo_dir($dir_, $items_, $path_, &$dir_id_)
	{
		foreach( $dir_ as $name => $val )
		{
			//	ディレクトリライン出力
			echo ''
				.'<div class="line dir ui_panel noshrink layout_horizon" dir_id="'.$dir_id_.'" path="'.$path_.$name.'">'
					.'<div class="icon expander" dir_id="'.$dir_id_.'">[D]</div>'
					.'<div>'.$name.'</div>'
					.'<div class="spacer"></div>'
					.'<div class="create_link">＋ Create</div>'
				.'</div>'
				.'<div class="child" dir_id="'.$dir_id_.'">'
				;
			$dir_id_++;

			//	サブディレクトリ出力
			if( count($val) > 0 ) echo_dir($val, $items_, $path_.$name."/", $dir_id_);

			//	ファイル一覧の出力
			foreach( $items_ as $item )
			{
				if( $item['dir'] != $path_.$name ) continue;

				$file_stat = '';
				$icon= '';
				if( $item['stat'] == "mod" )
				{
					$file_stat = "changed mod";
					$icon = '';//'M:';
				}
				else if( $item['stat'] == "add" )
				{
					$file_stat = "changed add";
					$icon = '';//'A:';
				}
				else if( $item['stat'] == "del" )
				{
					$file_stat = "changed del";
					$icon = '';//'D:';
				}

				echo ''
					.'<div class="line file '.$file_stat.' ui_panel noshrink layout_horizon" path="'.$item['dir']."/".$item['name'].'">'
						.'<div class="icon">'.$icon.'</div>'
						.'<div>'.$item['name'].'</div>'
					.'</div>'
					;
			}

			//	ディレクトリラインの終端
			echo '</div>';
		}
	}
	$dir_id = 0;
	echo_dir($hierarchy, $items, "", $dir_id);

?>
 </div>
 <div id="splitter_bar" class="ui_panel full_vertical">&nbsp;</div>
 <div id="panel_center" class="ui_panel spacer" style="overflow-y:auto"></div>
 <div id="diff_scroll"><div id="diff_scroll_handle"></div></div>
 <button id="btn_edit" class="hide">Edit</button>
</div>

<div id="editor" class="ui_dialog full_vertical full_horizon">
 <div class="box">
  <div class="body">
   <div class="src_head">
    <div class="ui_panel transplarent layout_horizon full_horizon full_vertical">
     <div class="disp_name">File : xxxx@override.php</div>
     <div class="spacer"></div>
     <button class="ui_button done small" id="btn_editor_save">Save</button>
     <button class="ui_button close small" id="btn_editor_close">Close</button>
    </div>
   </div>
   <div class="src_body ui_panel layout_horizon full_horizon">
    <textarea class="line_nos" readonly spellcheck="false"></textarea>
    <textarea class="lines spacer" spellcheck="false"></textarea>
   </div>
  </div>
 </div>
</div>

<div style="position:fixed; top:0; bottom:0; left:0; right:0; overflow:hidden; background:#fff" id="loading"></div>

<script nonce="<?= crow_response::nonce() ?>">

<?php /**** ページ制御 ****/ ?>
$(function()
{
	init(
	{
		actions : <?= crow::get_module_urls_as_json() ?>,
		current_hist : []
	});
});
</script>


<?php $include("components_footer.php"); ?>
<?php $include("footer.php"); ?>
