<?php $include("header.php"); ?>

<div class="sandbox">

 <div class="page_title">サンドボックス</div>

 <div class="desc">crowのカーネルが読み込まれた状態でのPHPコードを試せます。実行するとechoやprintの結果が表示されます。</div>

 <form method="post" action="<?= crow::make_url_self() ?>">

  <?= crow::get_csrf_hidden() ?>

  <div class="code_box">
   <div class="head_area">PHPコード</div>
   <div class="code_area"><textarea class="code src" name="code" placeholder="例）echo crow::version();"><?= $code ?></textarea></div>
  </div>

  <div class="btn_area">
   <div class="caution">内容によってはシステムを破壊します。危険を承知で実行してください。</div>
   <?php if( crow_config::get('crowmin.need_sandbox_pw') == "true" ) : ?>
    <span>実行パスワード "crow2017"：</span>
    <input type="password" size=8 name="pw">
   <?php endif; ?>
   <button type="submit" class="ui_btn red">実行</button>
  </div>

  <div class="code_box">
   <table class="tab_area">
    <tr>
     <td class="tab selected">結果</td>
     <td class="span">&nbsp;</td>
    </tr>
   </table>
   <div class="code_area">
    <textarea class="code result" name="result" readonly><?= $result ?></textarea>
   </div>
  </div>

 </form>

</div>

<?php $include("footer.php"); ?>
