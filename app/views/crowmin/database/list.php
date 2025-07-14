<?php $include("database_header.php"); ?>

<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	$(".show_detail").on('click', function()
	{
		$(this).prev().css('display', 'none');
		$(this).css('display', 'none');
		$(this).next().css('display', 'block');
		$(this).next().next().css('display', 'block');
	});
	$(".hide_detail").on('click', function()
	{
		$(this).prev().prev().css('display', 'inline');
		$(this).prev().css('display', 'inline-block');
		$(this).css('display', 'none');
		$(this).next().css('display', 'none');
	});
});
</script>


<div class="page_title">
<?php
	echo $design->name;
	if( $design->logical_name != "" )
	{
		echo '<font style="color:#888;font-weight:normal;font-size:14px;"> : '.$design->logical_name.'</font>';
	}
?>
</div>

<table id="rows">

 <tr>
  <td class="pager top" colspan="<?= count($design->fields) ?>">
<?php
		echo ''
			.'<div class="info">'
				.'全'.$pager->get_total().'件中、'
				.$pager->get_start_index()
				.'件目 ～ '
				.$pager->get_end_index()
				.' 件目を表示しています '
				.'<div class="ui_btn blue small btn_add">＋追加</div>'
			.'</div>'
			;
		echo '<div class="nos">';

		//	先頭ページ
		if( $pager->get_all_page() > 1 )
		{
			$link = crow::make_url_self
			([
				"table"	=> crow_request::get('table'),
				"page"	=> 1,
			]);
			echo '<a href="'.$link.'" style="margin-right:10px;">先頭</a>';
		}

		//	途中ページ
		foreach( $pager->get_page_nos() as $no )
		{
			$link = crow::make_url_self
			([
				"table"	=> crow_request::get('table'),
				"page"	=> $no,
			]);
			if( $no == $pager->get_page() )
				echo '<a class="current" href="'.$link.'">'.$no.'</a>';
			else
				echo '<a href="'.$link.'">'.$no.'</a>';
		}

		//	末尾ページ
		if( $pager->get_all_page() > 1 )
		{
			$link = crow::make_url_self
			([
				"table"	=> crow_request::get('table'),
				"page"	=> $pager->get_last_page(),
			]);
			echo '<a href="'.$link.'" style="margin-left:10px;">末尾</a>';
		}

		echo '</div>';
?>
  </td>
 </tr>

 <tr>
  <th></th>
  <?php foreach( $design->fields as $field ) : ?>
  <th style="white-space:nowrap"><?= $field->name.($field->logical_name=="" ? "" : "<br><font style='color:#888;font-size:10px;font-weight:normal;'>".$field->logical_name."</font>") ?></th>
  <?php endforeach; ?>
 </tr>

<!--
 <tr>
  <th colspan="<?= count($design->fields) ?>">
   <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="12px" height="12px" viewBox="0 0 200 200" enable-background="new 0 0 200 200" xml:space="preserve">
    <g id="layer_3">
     <line fill="none" stroke="#000000" stroke-width="40" stroke-miterlimit="10" x1="124.242" y1="124.186" x2="180.248" y2="180.191"/>
    </g>
    <g id="layer_2">
     <circle fill="none" stroke="#000000" stroke-width="20" stroke-miterlimit="10" cx="78.009" cy="77.953" r="65.42"/>
    </g>
   </svg>
  </th>
 </tr>
-->

<?php
	foreach( $rows as $row )
	{
		$key = $design->name."_".$row->{$design->primary_key};
		echo '<tr key="'.$key.'">';
		echo '<td style="white-space:nowrap;">'
			.'<button class="ui_btn green small popup_addedit_btn" key="'.$key.'" val="'.$row->{$design->primary_key}.'">編集</button> '
			.'<button class="ui_btn red small confirm_delete_btn" val="'.$row->{$design->primary_key}.'">削除</button></td>'
			;
		foreach( $design->fields as $field )
		{
			echo '<td>';
			if( count($field->const_array) > 0 )
			{
				$found = false;
				foreach( $field->const_array as $const_key => $const_val )
				{
					if( $row->{$field->name} == $const_val )
					{
						$found = true;
						echo $const_key." (".$const_val.")";
						break;
					}
				}
				if( $found === false )
				{
					if( $field->type == "bit" )
					{
						$bits = $row->{$field->name."_unpack"}();
						$symbols = $row->{"get_".$field->name."_symbol_vals"}();
						$outs = [];
						foreach( $bits as $bit )
							$outs[] = isset($symbols[$bit]) ?
								($symbols[$bit]."(".$bit.")") :
								("unknown bit(".$bit.")")
								;
						echo implode(", ", $outs);
					}
					else
					{
						echo "unmatch const val (".$row->{$field->name}.")";
					}
				}

				echo '<input type="hidden" name="'.$field->name.'" value="'.$row->{$field->name}.'">';
			}
			else if( $field->type == "geometry" )
			{
				echo $row->{$field->name."_lat"}.", ".$row->{$field->name."_lng"};

				echo '<input type="hidden" name="'.$field->name.'_lat" value="'.$row->{$field->name."_lat"}.'">';
				echo '<input type="hidden" name="'.$field->name.'_lng" value="'.$row->{$field->name."_lng"}.'">';
			}
			else if( $field->type == "boolean" )
			{
				echo $row->{$field->name} ? 'true' : 'false';
				echo '<input type="hidden" name="'.$field->name.'" value="'.($row->{$field->name} ? "1" : "0").'">';
			}
			else if( $field->type == "password" )
			{
				echo '****';
			}
			else if( $field->type == "unixtime" )
			{
				$datestr = $row->{$field->name} == 0 ? "" : date("Y/m/d H:i:s",$row->{$field->name});
				echo date("Y/m/d H:i:s",$row->{$field->name});
				echo '<input type="hidden" name="'.$field->name.'" value="'.$datestr.'">';
			}
			else if( $field->type == "datetime" )
			{
				$datestr = $row->{$field->name} == 0 ? "" : date("Y/m/d H:i:s",$row->{$field->name});
				echo date("Y-m-d H:i:s",$row->{$field->name});
				echo '<input type="hidden" name="'.$field->name.'" value="'.$datestr.'">';
			}
			else
			{
				if( strlen($row->{$field->name}) > 128 )
				{
					echo ""
						."<span>".substr($row->{$field->name},0,128)." ...</span>"
						."<div class='show_detail'>more</div>"
						."<div class='hide_detail' style='display:none;'>hide</div>"
						."<textarea style='display:none;' cols=60 rows=8 readonly>".$row->{$field->name}."</textarea>"
						;
				}
				else
				{
					echo $row->{$field->name};
				}

				if( $field->type == "text" )
				{
					echo '<textarea style="display:none" name="'.$field->name.'">'.$row->{$field->name}.'</textarea>';
				}
				else
				{
					echo '<input type="hidden" name="'.$field->name.'" value="'.$row->{$field->name}.'">';
				}
			}
			echo '</td>';
		}
		echo '</tr>';
	}
?>

 <tr>
  <td class="pager bottom" style="text-align:left" colspan="<?= count($design->fields) ?>">
<?php
		echo '<div class="nos">';

		//	先頭ページ
		if( $pager->get_all_page() > 1 )
		{
			$link = crow::make_url_self
			([
				"table"	=> crow_request::get('table'),
				"page"	=> 1,
			]);
			echo '<a href="'.$link.'" style="margin-right:10px;">先頭</a>';
		}

		//	途中ページ
		foreach( $pager->get_page_nos() as $no )
		{
			$link = crow::make_url_self
			([
				"table"	=> crow_request::get('table'),
				"page"	=> $no,
			]);
			if( $no==$pager->get_page() )
				echo '<a class="current" href="'.$link.'">'.$no.'</a>';
			else
				echo '<a href="'.$link.'">'.$no.'</a>';
		}

		//	末尾ページ
		if( $pager->get_all_page() > 1 )
		{
			$link = crow::make_url_self
			([
				"table"	=> crow_request::get('table'),
				"page"	=> $pager->get_last_page(),
			]);
			echo '<a href="'.$link.'" style="margin-left:10px;">末尾</a>';
		}

		echo '</div>';
		echo ''
			.'<div class="info">'
				.'全'.$pager->get_total().'件中、'
				.$pager->get_start_index()
				.'件目 ～ '
				.$pager->get_end_index()
				.' 件目を表示しています '
				.'<div class="ui_btn blue small btn_add">＋追加</div>'
			.'</div>'
			;
?>
  </td>
 </tr>
</table>

<?php /**** 追加編集ポップアップ ****/ ?>
<div id="popup_gray_form" class="popup_gray" style="display:none"></div>
<div id="popup_form" class="popup_panel" style="display:none">
 <div class="title" id="popup_title">追加/編集</div>
 <div class="body">
  <input type="hidden" id="popup_record_id" name="record_id" value="">
  <table class="popup_props">
<?php
	foreach( $design->fields as $field )
	{
		if( $field->name == $design->primary_key ) continue;

		echo '<tr>'
			.'<td style="max-width:200px;">'
				.$field->name.($field->must ? '<font style="color:red;font-size:12px;">※</font>': '')
				.($field->logical_name=="" ? "" : "<br><font style='color:#888;font-size:10px;font-weight:normal;'>".$field->logical_name."</font>")
			.'</td>'
			.'<td>'
			;

		if( count($field->const_array) > 0 )
		{
			echo '<select name="'.$field->name.'">'.
				crow_html::make_option_tag(array_flip($field->const_array))
				.'</select>';
		}
		else if( $field->type == "geometry" )
		{
			echo '<input type="text" name="'.$field->name.'_lat" style="width:400px;">';
			echo '<input type="text" name="'.$field->name.'_lng" style="width:400px;">';
		}
		else if( $field->type == "boolean" )
		{
			echo '<input type="checkbox" name="'.$field->name.'">';
		}
		else if( $field->type == "password" )
		{
			echo '<input type="password" name="'.$field->name.'" style="width:400px">';
			echo '<div class="pw_info">※変更しない場合は空欄のままにしてください</div>';
		}
		else if( $field->type == "text" )
		{
			echo '<textarea name="'.$field->name.'" style="width:400px;height:200px;"></textarea>';
		}
		else
		{
			echo '<input type="text" name="'.$field->name.'" style="width:400px">';
		}

		if( $field->remark != "" )
		{
			echo '<br><font style="color:#888;font-size:10px;">'.$field->remark.'</font>';
		}
		echo '</td></tr>';
	}
?>
  </table>
 </div>
 <div class="btn_area">
  <button class="ui_btn green addedit_done_btn" style="width:120px" id="popup_btn_done">変更</button>
  <button class="ui_btn red addedit_cancel_btn" style="width:120px">キャンセル</button>
 </div>
</div>

<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	let columns = [];
<?php
	foreach( $design->fields as $field )
	{
		$is_const = count($field->const_array) > 0 ? '1' : '0';
		echo "columns.push({name:'".$field->name."', type:'".$field->type."', const:'".$is_const."'});";
	}
?>

	$('.popup_addedit_btn').on('click', function()
	{
		let key = $(this).attr("key");
		let val = $(this).attr("val");
		popup_addedit_show_with(key, val);
	});
	$('.confirm_delete_btn').on('click', function()
	{
		confirm_delete($(this).attr("val"));
	});
	$('.addedit_done_btn').on('click', function()
	{
		btn_addedit_done();
	});
	$('.addedit_cancel_btn').on('click', function()
	{
		btn_addedit_cancel();
	});

	page_init(
	{
		url_self	: "<?= crow::make_url_self() ?>?table=<?= $design->name ?>",
		url_add		: "<?= crow::make_url('database', 'add') ?>",
		csrf_add	: {"key":"<?= crow::get_csrf_key_action('add') ?>", "val":"<?= crow::get_csrf_val_action('add') ?>"},
		url_edit	: "<?= crow::make_url('database', 'edit') ?>",
		csrf_edit	: {"key":"<?= crow::get_csrf_key_action('edit') ?>", "val":"<?= crow::get_csrf_val_action('edit') ?>"},
		url_delete	: "<?= crow::make_url('database', 'delete') ?>",
		csrf_delete	: {"key":"<?= crow::get_csrf_key_action('delete') ?>", "val":"<?= crow::get_csrf_val_action('delete') ?>"},
		table_name	: "<?= $design->name ?>",
		primary_key	: "<?= $design->primary_key ?>",
		columns		: columns,
		page		: <?= intval($pager->get_page()) ?>,
	});

});
</script>


<?php $include("database_footer.php"); ?>
