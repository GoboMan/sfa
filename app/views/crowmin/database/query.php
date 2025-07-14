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


<div class="page_title">クエリ</div>


<form id="query_form" method="post" action="<?= crow::make_url_self() ?>">
 <?= crow::get_csrf_hidden() ?>
 <textarea class="query" name="query" placeholder="please input query"><?= $query ?></textarea>
 <div class="btn_area" style="text-align:left"><button class="ui_btn green" type="submit">実行</button></div>
</form>


<?php /**** select 結果表示 ****/ ?>
<?php if( $query_type == "select" ) : ?>

 <div class="page_title">結果</div>

 <?php if( count($rows) <= 0 ) : ?>
  <div class="norows">no rows</div>

 <?php else: ?>
  <table class="result_set">
   <tr>
    <?php foreach( $columns as $col ) : ?>
    <th><?= $col ?></th>
    <?php endforeach; ?>
   </tr>

   <?php foreach( $rows as $row ) : ?>
   <tr>
<?php
	foreach( $columns as $col )
	{
		if( strlen($row[$col]) > 128 )
		{
			echo ""
				."<td>"
					."<span>".substr($row[$col],0,128)." ...</span>"
					."<div class='show_detail'>more</div>"
					."<div class='hide_detail' style='display:none;'>hide</div>"
					."<textarea style='display:none;' cols=60 rows=8 readonly>".$row[$col]."</textarea>"
				."</td>"
				;
		}
		else
		{
			echo '<td>'.$row[$col].'</td>';
		}
	}
?>
   </tr>
   <?php endforeach; ?>
  </table>
 <?php endif; ?>


<?php /**** select 以外結果表示 ****/ ?>
<?php elseif( $query_type == "not select" ) : ?>

 <div class="page_title">結果</div>

 <?php if( $result===true ) : ?>
  <div class="result_ok">query succeeded</div>
 <?php else : ?>
  <div class="result_ng">query failed : <?= $err_msg ?></div>
 <?php endif; ?>

<?php endif; ?>


<?php $include("database_footer.php"); ?>
