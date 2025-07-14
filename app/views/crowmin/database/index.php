<?php $include("database_header.php"); ?>

<div class="page_title">デザイン</div>

<table id="tables">

 <?php foreach( $diff as $diff_item ) : ?>

  <?php /**** デザインとDB両方に存在する場合 ****/ ?>
  <?php if( $diff_item['exists']=="both" ) : ?>
  <tr>
   <?php if( $diff_item['field_comp']===true ) : ?>
   <th class="ok left" colspan="11" style="border-right-width:0;"><?= $diff_item['name'] ?> :: OK</th>
   <td class="ok" style="border-left-width:0;">
    <button class="ui_btn small blue link" href="<?= crow::make_url_action("list", array("table"=>$diff_item['name'])) ?>">表示</button>
   </td>
   <?php else : ?>
   <th class="ng left" colspan="12"><?= $diff_item['name'] ?> :: 仕様と一致しません</th>
   <?php endif; ?>
  </tr>
  <tr>
   <th class="left">field</th>
   <th class="left">type</th>
   <th>size</th>
   <th>pk</th>
   <th>ai</th>
   <th>unique</th>
   <th>del</th>
   <th>must</th>
   <th>range</th>
   <th>case</th>
   <th>order</th>
   <th></th>
  </tr>
  <?php foreach( $diff_item['field'] as $field ) : ?>
   <?php if( $field['same']===true ) : ?>
   <tr class="field_same">
    <td class="left"><?= $field['name'] ?></td>
    <td class="left"><?= $field['design']->type ?></td>
    <td class="right"><?= $field['design']->size==0 ? "" : $field['design']->size ?></td>
    <td><?= $field['design']->primary_key ? "○" : "" ?></td>
    <td><?= $field['design']->auto_increment ? "○" : "" ?></td>
    <td><?= $field['design']->unique ? "○" : "" ?></td>
    <td><?= $field['design']->deleted ? "○" : "" ?></td>
    <td><?= $field['design']->must ? "○" : "" ?></td>
    <td>
     <?php
		if( $field['design']->valid_range_from !== false || $field['design']->valid_range_to !== false )
		{
			echo $field['design']->valid_range_from !== false ? $field['design']->valid_range_from : '';
			echo ':';
			echo $field['design']->valid_range_to !== false ? $field['design']->valid_range_to : '';
		}
     ?>
    </td>
    <td>
     <?php
		if( $field['design']->valid_charcase !== false ) echo $field['design']->valid_charcase;
     ?>
    </td>
    <td>
     <?php
		if( $field['design']->order )
		{
			echo $field['design']->order_vector=="desc" ?
				"desc" : "asc";
		}
     ?>
    </td>
    <td></td>
   </tr>
   <?php else : ?>
   <tr class="field_differ">
    <td class="left"><?= $field['name'] ?></td>
    <?php if( isset($field['cur']) ) : ?>
    <td class="left" colspan="10">仕様に存在しないフィールドが、現行DBに存在しています</td>
    <td>
     <button class="ui_btn small green link" href="<?= crow::make_url_action("delete_field", array("table"=>$diff_item['name'], "field"=>$field['name'])) ?>">削除</button>
    </td>
    <?php elseif( $field['diff']['exists']===false ) : ?>
    <td class="left" colspan="10">現行DBにフィールドが存在しません</td>
    <td>
     <button class="ui_btn small green link" href="<?= crow::make_url_action("create_field", array("table"=>$diff_item['name'], "field"=>$field['name'])) ?>">作成</button>
    </td>
    <?php else : ?>
    <td class="left" colspan="10">現行DBのフィールドが仕様の型/サイズと一致しません</td>
    <td>
     <button class="ui_btn small green link" href="<?= crow::make_url_action("restore_field", array("table"=>$diff_item['name'], "field"=>$field['name'])) ?>">修復</button>
    </td>
    <?php endif; ?>
   </tr>
   <?php endif; ?>
  <?php endforeach; ?>


  <?php /**** DBにのみ存在する場合 ****/ ?>
  <?php elseif( $diff_item['exists']=="current" ) : ?>
  <tr>
   <th class="ng left" colspan="12"><?= $diff_item['name'] ?> :: 仕様に存在しないテーブルです</th>
  </tr>
  <tr>
   <td class="center" colspan="12">
    <button class="ui_btn small green link" href="<?= crow::make_url_action("drop_table", array("table" => $diff_item["name"])) ?>">テーブル削除</button>
   </td>
  </tr>


  <?php /**** デザインにのみ存在する場合 ****/ ?>
  <?php elseif( $diff_item['exists']=="design" ) : ?>
  <tr>
   <th class="ng left" colspan="12"><?= $diff_item['name'] ?> :: テーブルが作成されていません</th>
  </tr>
  <tr>
   <td class="center" colspan="12">
    <button class="ui_btn small green link" href="<?= crow::make_url_action("create_table", array("table" => $diff_item["name"])) ?>">テーブル作成</button>
   </td>
  </tr>

  <?php endif; ?>

  <?php /**** インデックス ****/ ?>
  <?php if( isset($diff_item['indexes']) && count($diff_item['indexes']) > 0 ) : ?>
  <tr>
   <td colspan="11">
    <table width="100%">
     <tr>
      <th class="left">インデックス名</th>
      <th class="left">フィールド</th>
      <th>ユニーク</th>
      <th colspan=2></th>
     </tr>
     <?php foreach( $diff_item['indexes'] as $index ) : ?>
      <tr>
       <td class="left"><?= $index['name'] ?></td>
       <td class="left"><?= implode(", ", $index['cols']) ?></td>
       <td><?= $index['unique']==1 ? "○" : "" ?></td>
       <?php if( $index['compare']=="same" ) : ?>
        <td class="left ok" colspan=2>OK</td>
       <?php elseif( $index['compare']=="def_only" ) : ?>
        <td class="left ng">定義に存在しますが現行DBにありません</td>
        <td class="ng"><button class="ui_btn small green link" href="<?= crow::make_url_action("create_index", array("table"=>$diff_item['name'], "name"=>$index['name'])) ?>">作成</button></td>
       <?php elseif( $index['compare']=="differ_unq" ) : ?>
        <td class="left ng">ユニーク指定が異なります</td>
        <td class="ng"><button class="ui_btn small green link" href="<?= crow::make_url_action("restore_index", array("table"=>$diff_item['name'], "name"=>$index['name'])) ?>">修復</button></td>
       <?php elseif( $index['compare']=="differ_def" || $index['compare']=="differ_cols" ) : ?>
        <td class="left ng">対象カラムの指定が異なります</td>
        <td class="ng"><button class="ui_btn small green link" href="<?= crow::make_url_action("restore_index", array("table"=>$diff_item['name'], "name"=>$index['name'])) ?>">修復</button></td>
       <?php elseif( $index['compare']=="current_only" ) : ?>
        <td class="left ng">現行DBに存在しますが定義にありません</td>
        <td class="ng"><button class="ui_btn small green link" href="<?= crow::make_url_action("delete_index", array("table"=>$diff_item['name'], "name"=>$index['name'])) ?>">削除</button></td>
       <?php endif; ?>
      </tr>
     <?php endforeach; ?>
    </table>
   </td>
  </tr>
  <?php endif; ?>

 <?php endforeach; ?>

</table>

<?php $include("database_footer.php"); ?>
