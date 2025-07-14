<?php $include("database_header.php"); ?>

<div class="page_title">ローカライズ</div>


<form id="query_form" method="post" action="<?= crow::make_url_self() ?>">
 <?= crow::get_csrf_hidden() ?>
 <div style="padding-bottom:10px;"><button class="ui_btn green" type="submit">言語ファイル作成</button></div>
 <div>※「app/assets/lang/ja/_common_/db.txt」「app/assets/lang/en/_common_/db.txt」に出力されます。</div>
 <div>※同名で既存のファイルが存在していた場合でも上書きします</div>
</form>


<?php $include("database_footer.php"); ?>
