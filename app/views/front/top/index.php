<!DOCTYPE html>
<html>
<head>
 <?= crow::get_default_head_tag() ?>
</head>
<body>


<div class="ui_panel shadow dark padding full_horizon">
 ui.icss スタイル確認ページ
</div>

<?php /**** ヘッダ ***/ ?>
<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">h1に ui_heading 指定</h1>
 <h2 class="ui_heading">h2に ui_heading 指定</h2>
 <h3 class="ui_heading">h3に ui_heading 指定</h3>
 <div class="ui_panel border margin_bottom code">
  &lt;h1 class="<b>ui_heading</b>"&gt;タイトル&lt;/h1&gt;
  &lt;h2 class="<b>ui_heading</b>"&gt;タイトル&lt;/h2&gt;
  &lt;h3 class="<b>ui_heading</b>"&gt;タイトル&lt;/h3&gt;
 </div>
</div>

<?php /**** テーブル ***/ ?>
<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">テーブル : ui_list</h1>
 <div class="ui_panel">※ ui_table に full 指定で横幅と高さ一杯。full_horizon 指定で横幅一杯、full_vertical 指定で高さ一杯</div>
 <div class="ui_panel">※ th に sortable 指定で順番変更可能指定、asc/descで方向指定</div>
 <div class="ui_panel">※ th/td に nodata 指定でデータなしを表現（colspan結合する）、trに指定で行全体をデータなし表現とする</div>
 <div class="ui_panel">※ th/td に clickable 指定でクリック可能表現、trに指定で全カラムクリック可能表現</div>
 <div class="ui_panel">※ th/td に nowrap 指定で折り返しなし指定、trに指定で全カラム折り返しなし指定</div>
 <div class="ui_panel">※ th/td に min 指定で最小表示且つ折り返しなし指定、trに指定で全カラム最小表示且つ折り返しなし指定</div>
 <div class="ui_panel">※ borderはth/tdに付与するとカラム単位、trに指定すると指定行の小th/td全てが対象となる。</div>
 <table class="ui_list full">
  <thead>
   <tr class="border">
    <th class="sortable asc">並び替え可能 : sortable で矢印、asc/descで方向</th>
    <th>通常カラム</th>
    <th class="min">横幅最小 : min</th>
   </tr>
  </thead>
  <tbody>
   <tr>
    <td>データA</td>
    <td>データB</td>
    <td class="min">A</td>
   </tr>
   <tr>
    <td>データA</td>
    <td class="border">tdにborder指定</td>
    <td class="min">AB</td>
   </tr>
   <tr class="border">
    <td>trにborder指定</td>
    <td>データB</td>
    <td class="min">AB</td>
   </tr>
   <tr class="clickable border">
    <td>クリック可能 : tr にclickable</td>
    <td class="clickable">td に付与するとカラム単位</td>
    <td class="min">QQ</td>
   </tr>
   <tr>
    <td class="nodata border" colspan=3>データなし行 : tdにnodata、trに指定で全カラム対象</td>
   </tr>
   <tr>
    <td class="border_vertical">垂直方向 (上下) のみ枠線 : border_vertical</td>
    <td class="border_vertical">trに指定すると行全体になる</td>
    <td>枠線なし</td>
   </tr>
   <tr>
    <td class="border_horizon">水平方向 (左右) のみ枠線なし : noborder_horizon</td>
    <td class="border_horizon">trに指定すると行全体になる</td>
    <td>枠線なし</td>
   </tr>
  </tbody>
 </table>

 <div class="ui_panel border shadow margin_top_xlarge">
  <div class="ui_props inner">
   <div class="group">
    <div class="title">テーブルボーダーの種類</div>
    <div class="prop"><div>border</div><div>全行</div></div>
    <div class="prop"><div>border_vertical</div><div>垂直方向の辺 (上下)</div></div>
    <div class="prop"><div>border_horizon</div><div>水平方向の辺 (左右)</div></div>
    <div class="prop"><div>border_top</div><div>上辺</div></div>
    <div class="prop"><div>border_right</div><div>右辺</div></div>
    <div class="prop"><div>border_bottom</div><div>下辺</div></div>
    <div class="prop"><div>border_left</div><div>左辺</div></div>
    <div class="prop"><div>border_not_top</div><div>上辺以外の3辺</div></div>
    <div class="prop"><div>border_not_right</div><div>右辺以外の3辺</div></div>
    <div class="prop"><div>border_not_bottom</div><div>下辺以外の3辺</div></div>
    <div class="prop"><div>border_not_left</div><div>左辺以外の3辺</div></div>
   </div>
  </div>
 </div>
</div>

<?php /**** プロパティテーブル ***/ ?>
<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">プロパティテーブル : 最初のdivをキー、二つ目以降を値として扱う</h1>
 <div class="ui_panel border code">
  &lt;div class="<b>ui_props</b>"&gt;
      &lt;div class="<b>prop</b>"&gt;
          &lt;div&gt;キー&lt;/div&gt;
          &lt;div&gt;値&lt;/div&gt;
      &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_props margin_top">
  <div class="prop">
   <div>キー</div>
   <div>値</div>
  </div>
  <div class="prop">
   <div>キー</div>
   <div>値</div>
  </div>
  <div class="prop">
   <div>キー</div>
   <div>値</div>
  </div>
 </div>
</div>

<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">プロパティテーブル : clickable でマウスホバーへの反応</h1>
 <div class="ui_panel border code">
  &lt;div class="ui_props"&gt;
      &lt;div class="prop <b>clickable</b>"&gt;
          &lt;div&gt;キー&lt;/div&gt;
          &lt;div&gt;値&lt;/div&gt;
      &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_props margin_top">
  <div class="prop clickable">
   <div>キー</div>
   <div>値</div>
  </div>
  <div class="prop clickable">
   <div>キー</div>
   <div>値</div>
  </div>
  <div class="prop clickable">
   <div>キー</div>
   <div>値</div>
  </div>
 </div>
</div>

<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">プロパティテーブル : group でグルーピング</h1>
 <div class="ui_panel border code">
  &lt;div class="ui_props"&gt;
      &lt;div class="<b>group</b>"&gt;
          &lt;div class="<b>title</b>"&gt;グループA&lt;/div&gt;
          &lt;div class="prop"&gt;
              &lt;div&gt;キー&lt;/div&gt;
              &lt;div&gt;値&lt;/div&gt;
          &lt;/div&gt;
      &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_props margin_top">
  <div class="group">
   <div class="title">グループA</div>
   <div class="prop clickable">
    <div>キー</div>
    <div>値</div>
   </div>
   <div class="prop clickable">
    <div>キー</div>
    <div>値</div>
   </div>
  </div>
  <div class="group">
   <div class="title">グループB</div>
   <div class="prop clickable">
    <div>キー</div>
    <div>値</div>
   </div>
  </div>
 </div>
</div>

<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">プロパティテーブル : パネル内にpadding無しで埋め込む場合は、inner 指定で上下のボーダーを消す</h1>

 <div>※ inner 指定なし版 : プロパティテーブル上下のボーダーとui_panelのボーダーが被ってしまう</div>
 <div class="ui_panel border margin code">
  &lt;div class="ui_panel shadow border margin"&gt;
    &lt;div class="ui_props"&gt;
        &lt;div class="prop"&gt;
            &lt;div&gt;キー&lt;/div&gt;
            &lt;div&gt;値&lt;/div&gt;
        &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel shadow border margin">
  <div class="ui_props">
   <div class="group">
    <div class="title">グループA</div>
    <div class="prop clickable">
     <div>キー</div>
     <div>値</div>
    </div>
    <div class="prop clickable">
     <div>キー</div>
     <div>値</div>
    </div>
   </div>
   <div class="group">
    <div class="title">グループB</div>
    <div class="prop clickable">
     <div>キー</div>
     <div>値</div>
    </div>
   </div>
  </div>
 </div>

 <div>※ inner 指定あり版 : プロパティテーブルの上下のボーダーがなくなり、ui_panelと被らない</div>
 <div class="ui_panel border margin code">
  &lt;div class="ui_panel shadow border margin"&gt;
    &lt;div class="ui_props <b>inner</b>"&gt;
        &lt;div class="prop"&gt;
            &lt;div&gt;キー&lt;/div&gt;
            &lt;div&gt;値&lt;/div&gt;
        &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel shadow border margin">
  <div class="ui_props inner">
   <div class="group">
    <div class="title">グループA</div>
    <div class="prop clickable">
     <div>キー</div>
     <div>値</div>
    </div>
    <div class="prop clickable">
     <div>キー</div>
     <div>値</div>
    </div>
   </div>
   <div class="group">
    <div class="title">グループB</div>
    <div class="prop clickable">
     <div>キー</div>
     <div>値</div>
    </div>
   </div>
  </div>
 </div>
</div>

<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">プロパティテーブル : 複数カラム</h1>
 <div class="margin">下記のように複数カラムを並べることは可能だが、縦に揃わなくなるため、この場合は素直に ui_table を使った方がよい</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_props"&gt;
      &lt;div class="prop"&gt;
          &lt;div&gt;キー&lt;/div&gt;
          <b>&lt;div&gt;カラム1&lt;/div&gt;</b>
          <b>&lt;div&gt;カラム2&lt;/div&gt;</b>
      &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_props">
  <div class="prop clickable">
   <div>セバスチャン</div>
   <div>A型</div>
   <div>東京都</div>
   <div>10歳</div>
   <div>|←揃わない</div>
   <div class="fill"></div>
   <div class="right"><button class="ui_button info small">編集</button><div class="ui_button warn small">削除</div></div>
  </div>
  <div class="prop clickable">
   <div>ボブ</div>
   <div>B型</div>
   <div>パリ</div>
   <div>20歳</div>
   <div>|←揃わない</div>
   <div class="fill"></div>
   <div class="right"><button class="ui_button info small">編集</button><button class="ui_button warn small">削除</button></div>
  </div>
  <div class="prop clickable">
   <div>アリス</div>
   <div>O型</div>
   <div>ブルックリン</div>
   <div>30歳</div>
   <div>|←揃わない</div>
   <div class="fill"></div>
   <div class="right"><button class="ui_button info small">編集</button><button class="ui_button warn small">削除</button></div>
  </div>
 </div>
</div>

<?php /**** タブコントロール、コード例 ***/ ?>
<div class="ui_panel border shadow padding margin">
 <h1 class="ui_heading">タブコントロール</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="<b>ui_tab</b>"&gt;
    &lt;div <b>for="tab1"</b> class="<b>selected</b>"&gt;タブ1&lt;/div&gt;
    &lt;div <b>for="tab2"</b> &gt;タブ2&lt;/div&gt;
  &lt;/div&gt;
  &lt;div <b>id="tab1"</b> class="<b>ui_tab_body selected</b>"&gt;
    タブ1の内容
  &lt;/div&gt;
  &lt;div <b>id="tab2"</b> class="<b>ui_tab_body</b>"&gt;
    タブ2の内容
  &lt;/div&gt;
 </div>
</div>

<?php /**** タブコントロール ***/ ?>
<div class="ui_tab">
 <div for="tab1" id="tabsel1" class="selected">ボタン類</div>
 <div for="tab2" id="tabsel2">カレンダー</div>
 <div class="spacer"></div>
 <div class="controls">
  <button class="ui_button small info">追加</button>
  <button class="ui_button small warn">削除</button>
 </div>
</div>

<?php /**** タブ1 ****/ ?>
<div id="tab1" class="ui_tab_body selected">
 <h1 class="ui_heading">テキストボックス : ui_text</h1>
 <div class="ui_panel margin_bottom_large">
  <input type="text" class="ui_text" placeholder="通常">
  <input type="text" class="ui_text" placeholder="readonly指定" readonly>
  <input type="text" class="ui_text" placeholder="disabled指定" disabled>
  <button class="ui_button done">ボタンと並べてみる</button>
  <select class="ui_select done"><option>セレクトと並べてみる</option></select>
 </div>
 <h1 class="ui_heading">検索ボックス : ui_search</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="<b>ui_search</b>"&gt;
    &lt;input type="text" class="ui_text" placeholder="キーワードで検索"&gt;
    &lt;button&gt;&lt;/button&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel margin_bottom_large">
  <div class="ui_search">
   <input type="text" class="ui_text" placeholder="キーワードで検索">
   <button></button>
  </div>
 </div>
 <h1 class="ui_heading">ボタン : ui_button</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;button class="<b>ui_button done</b>"&gt;決定ボタン&lt;/button&gt;
 </div>
 <div class="ui_panel margin_vertical_large">
  <button class="ui_button done">決定</button>
  <button class="ui_button cancel">キャンセル</button>
  <button class="ui_button info">情報</button>
  <button class="ui_button warn">警告</button>
  <button class="ui_button danger">危険</button>
  <button class="ui_button done" disabled>非活性</button>
 </div>

 <h1 class="ui_heading">ボタン小 : ui_button and small</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;button class="<b>ui_button done small</b>"&gt;決定ボタン&lt;/button&gt;
 </div>
 <div class="ui_panel margin_vertical_large">
  <button class="ui_button done small">決定</button>
  <button class="ui_button cancel small">キャンセル</button>
  <button class="ui_button info small">情報</button>
  <button class="ui_button warn small">警告</button>
  <button class="ui_button danger small">危険</button>
  <button class="ui_button done small" disabled>非活性</button>
 </div>

 <h1 class="ui_heading">アイコンボタン : ui_button and icon</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;button class="<b>ui_button done icon icon_star</b>"&gt;決定&lt;/button&gt;
 </div>
 <div class="ui_panel margin_vertical_xlarge">
  <button class="ui_button done icon icon_star">決定</button>
  <button class="ui_button cancel icon icon_star">キャンセル</button>
  <button class="ui_button done small icon icon_star">決定</button>
  <button class="ui_button cancel small icon icon_star">キャンセル</button>
 </div>

 <h1 class="ui_heading">折り返しを許可するボタン : ui_button and wrap_allow</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;button class="<b>ui_button done wrap_allow</b>"&gt;ながいボタン&lt;/button&gt;
 </div>
 <div class="ui_panel">※ ただしコンテナ要素による（親のui_panelが layout_horizon 系なら折り返される）</div>
 <div class="ui_panel layout_horizon margin">
  <button class="ui_button done wrap_allow">なっがいボタンですあいうえおかきくけこ</button>
  <button class="ui_button done wrap_allow">なっがいボタンですあいうえおかきくけこ</button>
  <button class="ui_button done wrap_allow">なっがいボタンですあいうえおかきくけこ</button>
  <button class="ui_button done wrap_allow">なっがいボタンですあいうえおかきくけこ</button>
 </div>


</div>

<?php /**** タブ2 ****/ ?>
<div id="tab2" class="ui_tab_body">
 <h1 class="ui_heading">タブ２</h1>
 <div class="ui_panel">
  <div>テストタブ２</div>
 </div>
</div>

<div class="ui_panel border padding margin">
 <h1 class="ui_heading">スイッチ : ui_switch</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;input type="checkbox" class="ui_switch"&gt;
  &lt;input type="checkbox" class="ui_switch" checked&gt;
 </div>
 <div class="ui_panel">
  <input type="checkbox" class="ui_switch">
  <input type="checkbox" class="ui_switch" checked>
 </div>

 <h1 class="ui_heading margin_top_large">チェックボックス : ui_checkbox、ラジオボタン : ui_radio</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;input type="radio" class="ui_radio"&gt;
  &lt;input type="radio" class="ui_radio" checked&gt;
  &lt;input type="checkbox" class="ui_checkbox"&gt;
  &lt;input type="checkbox" class="ui_checkbox" checked&gt;
 </div>
 <div class="ui_panel">
  <input type="radio" class="ui_radio" name="val" checked> aaa
  <input type="radio" class="ui_radio" name="val"> bbbb
  <input type="checkbox" class="ui_checkbox" name="chk1" checked> aaa
  <input type="checkbox" class="ui_checkbox" name="chk2"> bbbb
 </div>

 <h1 class="ui_heading margin_top_large">セレクト : ui_select</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;select class="ui_select"&gt;
      &lt;option value="1"&gt;選択肢A&lt;/option&gt;
      &lt;option value="2"&gt;選択肢B&lt;/option&gt;
  &lt;/select&gt;
 </div>
 <div class="ui_panel">
  <select class="ui_select">
   <option value="1">選択肢AAAA</option>
   <option value="2">選択肢B</option>
  </select>
  <select class="ui_select" disabled>
   <option value="1">選択肢AAAA</option>
   <option value="2">選択肢B</option>
  </select>
 </div>

 <h1 class="ui_heading margin_top_large">ページャー : ui_pager</h1>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_pager"&gt;
   &lt;div class="label"&gt;
    &lt;span&gt;xx&lt;/span&gt;件中、&lt;span&gt;xx&lt;/span&gt;件目～&lt;span&gt;xx&lt;/span&gt;件を表示
   &lt;/div&gt;
   &lt;div class="links"&gt;
    &lt;a href="" class="prev disabled" title="前のxx件"&gt;&lt;/a&gt;
     &lt;a href=""&gt;1&lt;/a&gt;
     &lt;a href=""&gt;2&lt;/a&gt;
     &lt;a href="" class="active"&gt;3&lt;/a&gt;
     &lt;a href=""&gt;4&lt;/a&gt;
     &lt;a href=""&gt;5&lt;/a&gt;
    &lt;a href="" class="next" title="次のxx件"&gt;&lt;/a&gt;
   &lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel">
  <div class="ui_pager">
   <div class="label">
    <span>xx</span>件中、<span>xx</span>件目～<span>xx</span>件を表示
   </div>
   <div class="links">
    <a href="" class="prev disabled" title="前のxx件"></a>
    <div class="pages"></div>
    <a href="" class="next" title="次のxx件"></a>
   </div>
  </div>
 </div>
 <div class="ui_panel">
  <div class="ui_pager">
   <div class="label">
    <span>xx</span>件中、<span>xx</span>件目～<span>xx</span>件を表示
   </div>
   <div class="links">
    <a href="" class="prev disabled" title="前のxx件"></a>
    <div class="pages">
     <a href="">1</a>
     <a href="">2</a>
     <a href="" class="active">3</a>
     <a href="">4</a>
     <a href="">5</a>
    </div>
    <a href="" class="next" title="次のxx件"></a>
   </div>
  </div>
 </div>

</div>


<?php /*** ダイアログ ***/ ?>
<div class="ui_panel border shadow margin padding">
 <h1 class="ui_heading">ダイアログ</h1>
 <div class="ui_panel">
  <button id="btn_popup_msg" class="ui_button info">通常メッセージ</button>
  <button id="btn_popup_err" class="ui_button info">エラーメッセージ</button>
  <button id="btn_popup_custom" class="ui_button info">カスタムポップアップ</button>
  <button id="btn_popup_custom_h" class="ui_button info">横幅一杯ポップアップ</button>
  <button id="btn_popup_custom_vh" class="ui_button info">縦横一杯ポップアップ</button>
 </div>

 <div class="ui_panel margin_top_large">JSでダイアログのポップアップ</div>
 <div class="ui_panel border margin_bottom code">
     //  メッセージ表示
     ui.dialog.popup_message('タイトル', 'メッセージ');

     //  メッセージ表示＆閉じるボタン押下時のコールバック指定
     ui.dialog.popup_message('タイトル', 'メッセージ', function(){});

     //  エラー表示
     ui.dialog.popup_message('タイトル', 'エラー');

     //  確認ダイアログ
     ui.dialog.popup_confirm(
         'タイトル',
         '確認メッセージ',
         function()
         {
             //  YESのコールバック
         },
         function()
         {
             //  NOのコールバック
         }
     );

     //  ローディングパネルをかぶせる
     ui.dialog.popup_loading();

 </div>

 <div class="ui_panel margin_top_large">カスタムダイアログは下記の構成で作成する</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_dialog" id="test_dialog"&gt;
      &lt;div&gt;
          &lt;div class="header"&gt;タイトル&lt;/div&gt;
          &lt;div class="body"&gt;
              ....ダイアログ中身....
          &lt;/div&gt;
          &lt;div class="footer"&gt;
              &lt;ui_button close&gt;閉じる&lt;/button&gt;
              &lt;ui_button done&gt;登録&lt;/button&gt;
          &lt;/div&gt;
      &lt;/div&gt;
  &lt;/div&gt;
 </div>

 <div class="ui_panel margin_top_large">JSでカスタムダイアログを表示</div>
 <div class="ui_panel border margin_bottom code">
     //  カスタムダイアログの表示
     var dlg = ui.dialog.popup('#test_dialog');

     //  閉じる制御
     $('#test_dialog .ui_button.close').on('click', function()
     {
         dlg.close();
     });
 </div>

</div>


<?php /*** テストポップアップ ***/ ?>
<div class="ui_dialog" id="custom_dialog">
 <div>
  <div class="header">カスタムダイアログ</div>
  <div class="body">
   <div class="ui_props">
    <div class="prop">
     <div>名前</div>
     <div><input type="text" class="ui_text"></div>
    </div>
    <div class="prop">
     <div>住所</div>
     <div><input type="text" class="ui_text"></div>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button cancel">閉じる</button>
   <button class="ui_button done">登録</button>
  </div>
 </div>
</div>

<?php /*** テストポップアップ ***/ ?>
<div class="ui_dialog full_horizon" id="custom_dialog_h">
 <div>
  <div class="header">横幅一杯</div>
  <div class="body">
   <div class="ui_props">
    <div class="prop">
     <div>名前</div>
     <div><input type="text" class="ui_text"></div>
    </div>
    <div class="prop">
     <div>住所</div>
     <div><input type="text" class="ui_text"></div>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button cancel">閉じる</button>
   <button class="ui_button done">登録</button>
  </div>
 </div>
</div>

<?php /*** テストポップアップ ***/ ?>
<div class="ui_dialog full_vertical full_horizon" id="custom_dialog_vh">
 <div>
  <div class="header">高さと横幅一杯</div>
  <div class="body">
   <div class="ui_props">
    <div class="prop">
     <div>名前</div>
     <div><input type="text" class="ui_text"></div>
    </div>
    <div class="prop">
     <div>住所</div>
     <div><input type="text" class="ui_text"></div>
    </div>
   </div>
  </div>
  <div class="footer">
   <button class="ui_button cancel">閉じる</button>
   <button class="ui_button done">登録</button>
  </div>
 </div>
</div>

<?php /*** パネル ***/ ?>
<div class="ui_panel border shadow padding margin">
 <div class="ui_heading">パネル</div>
 <div>※ 配置の基本要素。レイアウトに使ったり、要素のコンテナに使ったり。</div>
 <div class="ui_panel border margin_top_xlarge margin_bottom code">
  &lt;div class="ui_panel"&gt;通常のパネル&lt;&gt;
 </div>
 <div class="ui_panel">通常のパネル</div>

 <div class="ui_panel border margin_top_xlarge margin_bottom code">
  &lt;div class="ui_panel <b>border</b>"&gt;枠線付きのパネル&lt;&gt;
 </div>
 <div class="ui_panel border">枠線付きのパネル</div>

 <div class="ui_panel border margin_top_xlarge margin_bottom code">
  &lt;div class="ui_panel <b>border padding</b>"&gt;枠線とパディング付きのパネル&lt;&gt;
 </div>
 <div class="ui_panel border padding">枠線とパディング付きのパネル</div>

 <div class="ui_panel border margin_top_xlarge margin_bottom code">
  &lt;div class="ui_panel <b>border padding shadow</b>"&gt;枠線とパディングと影付きのパネル&lt;&gt;
 </div>
 <div class="ui_panel border padding shadow">枠線とパディングと影付きのパネル</div>

 <div class="ui_panel border margin_top_xlarge margin_bottom code">
  &lt;div class="ui_panel <b>border padding shadow margin</b>"&gt;枠線とパディングとマージンと影付きのパネル&lt;&gt;
 </div>
 <div class="ui_panel border padding shadow margin">枠線とパディングとマージンと影付きのパネル</div>

 <div class="ui_panel border margin_top_xlarge margin_bottom code">
  &lt;div class="ui_panel border padding <b>code</b>"&gt;コードを表示するパネル&lt;&gt;
 </div>
 <div class="ui_panel border code padding sample">	//	コード表示用のパネル
	public function sum( $left_, $right_ )
	{
		return $left_ + $right_;
	}</div>

 <div class="ui_panel border shadow margin_top_xlarge">
  <div class="ui_props inner">
   <div class="group">
    <div class="title dark">ui_panel に付与できるオプション</div>
    <div class="prop"><div>transparent</div><div>背景を透明にする</div></div>
    <div class="prop"><div>dark</div><div>ダーク配色（$col_dark_back、$col_dark_fore）にする</div></div>
    <div class="prop"><div>light</div><div>ライト配色（$col_light_back、$col_light_fore）にする</div></div>
    <div class="prop"><div>border</div><div>枠線を付与</div></div>
    <div class="prop"><div>shadow</div><div>影を付与</div></div>

    <div class="group">
     <div class="title">パディング系 (大量にあるが、法則がある)</div>
     <div class="prop"><div>padding</div><div>パディングを付与</div></div>
     <div class="prop"><div>padding_small</div><div>小さなパディングを付与</div></div>
     <div class="prop"><div>padding_large</div><div>大きなパディングを付与</div></div>
     <div class="prop"><div>padding_xlarge</div><div>最大のパディングを付与</div></div>
     <div class="prop"><div>padding_horizon</div><div>水平方向（左右）にパディングを付与</div></div>
     <div class="prop"><div>padding_horizon_small</div><div>水平方向（左右）に小さなパディングを付与</div></div>
     <div class="prop"><div>padding_horizon_large</div><div>水平方向（左右）に大きなパディングを付与</div></div>
     <div class="prop"><div>padding_horizon_xlarge</div><div>水平方向（左右）に最大のパディングを付与</div></div>
     <div class="prop"><div>padding_vertical</div><div>垂直方向（上下）にパディングを付与</div></div>
     <div class="prop"><div>padding_vertical_small</div><div>垂直方向（上下）に小さなパディングを付与</div></div>
     <div class="prop"><div>padding_vertical_large</div><div>垂直方向（上下）に大きなパディングを付与</div></div>
     <div class="prop"><div>padding_vertical_xlarge</div><div>垂直方向（上下）に最大のパディングを付与</div></div>

     <div class="prop"><div>padding_top</div><div>上方向にパディングを付与</div></div>
     <div class="prop"><div>padding_top_small</div><div>上方向に小さなパディングを付与</div></div>
     <div class="prop"><div>padding_top_large</div><div>上方向に大きなパディングを付与</div></div>
     <div class="prop"><div>padding_top_xlarge</div><div>上方向に最大のパディングを付与</div></div>
     <div class="prop"><div>...略...</div><div>各方向についても同様</div></div>

     <div class="prop"><div>padding_not_top</div><div>上方向以外の3辺にパディングを付与</div></div>
     <div class="prop"><div>padding_not_top_small</div><div>上方向以外の3辺に小さなパディングを付与</div></div>
     <div class="prop"><div>padding_not_top_large</div><div>上方向以外の3辺に大きなパディングを付与</div></div>
     <div class="prop"><div>padding_not_top_xlarge</div><div>上方向以外の3辺に最大のパディングを付与</div></div>
     <div class="prop"><div>...略...</div><div>各方向についても同様</div></div>
    </div>

    <div class="group">
     <div class="title">レイアウト系 (下部にサンプルあり)</div>
     <div class="prop"><div>layout_horizon</div><div>子要素を横方向に整列する</div></div>
     <div class="prop"><div>layout_horizon_top</div><div>子要素を横方向に整列し、上下を上端に合わせる</div></div>
     <div class="prop"><div>layout_horizon_bottom</div><div>子要素を横方向に整列し、上下を下端に合わせる</div></div>
     <div class="prop"><div>layout_horizon_baseline</div><div>子要素を横方向に整列し、上下をベースラインに合わせる</div></div>
     <div class="prop"><div>layout_horizon_stretch</div><div>子要素を横方向に整列し、上下を枠にフィットするように引き延ばす</div></div>
     <div class="prop"><div>layout_vertical</div><div>子要素を縦方向に整列する</div></div>
     <div class="prop"><div>layout_vertical_left</div><div>子要素を縦方向に整列し、左右を左端に合わせる</div></div>
     <div class="prop"><div>layout_vertical_right</div><div>子要素を縦方向に整列し、左右を右端に合わせる</div></div>
     <div class="prop"><div>layout_vertical_baseline</div><div>子要素を縦方向に整列し、左右をベースラインに合わせる</div></div>
     <div class="prop"><div>layout_vertical_stretch</div><div>子要素を縦方向に整列し、左右を枠にフィットするように引き延ばす</div></div>
    </div>
   </div>
  </div>
 </div>
</div>


<?php /*** レイアウト（flex） ***/ ?>
<div class="ui_panel border shadow padding margin">
 <div class="ui_heading">ui_panel を使ったレイアウト例</div>
 <div class="ui_panel margin">layout_horizon</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_panel layout_horizon border margin"&gt;
      &lt;div class="red"&gt;aaa&lt;/div&gt;
      &lt;div class="green"&gt;ccc&lt;/div&gt;
      &lt;div class="purple"&gt;ddd&lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel layout_horizon border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_horizon and spacer</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_panel layout_horizon border margin"&gt;
      &lt;div class="red"&gt;aaa&lt;/div&gt;
      &lt;div class="green"&gt;ccc&lt;/div&gt;
      &lt;div class="spacer"&gt;&lt;/div&gt;
      &lt;div class="purple"&gt;ddd&lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel layout_horizon border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div class="spacer"></div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_horizon and spacer</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_panel layout_horizon border margin"&gt;
      &lt;div class="spacer"&gt;&lt;/div&gt;
      &lt;div class="red"&gt;aaa&lt;/div&gt;
      &lt;div class="green"&gt;ccc&lt;/div&gt;
      &lt;div class="purple"&gt;ddd&lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel layout_horizon border margin">
  <div class="spacer"></div>
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_horizon_top</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_panel layout_horizon_top border margin"&gt;
      &lt;div class="red"&gt;aaa&lt;/div&gt;
      &lt;div class="green"&gt;ccc&lt;/div&gt;
      &lt;div class="purple"&gt;ddd&lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel layout_horizon_top border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_horizon_bottom</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_panel layout_horizon_bottom border margin"&gt;
      &lt;div class="red"&gt;aaa&lt;/div&gt;
      &lt;div class="green"&gt;ccc&lt;/div&gt;
      &lt;div class="purple"&gt;ddd&lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel layout_horizon_bottom border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_horizon_stretch</div>
 <div class="margin_horizon">※一番大きな要素の高さに合わせて他の要素の高さが伸びる</div>
 <div class="ui_panel border margin_bottom code">
  &lt;div class="ui_panel layout_horizon_stretch border margin"&gt;
      &lt;div class="red"&gt;aaa&lt;/div&gt;
      &lt;div class="green"&gt;ccc&lt;/div&gt;
      &lt;div class="purple"&gt;ddd&lt;/div&gt;
  &lt;/div&gt;
 </div>
 <div class="ui_panel layout_horizon_stretch border margin">
  <div style="background-color:#f44; width:100px;">aaa<br>改行<br>改行</div>
  <div style="background-color:#5c5; width:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_horizon_baseline</div>
 <div class="margin_horizon">※テキストの下辺が揃う</div>
 <div class="ui_panel layout_horizon_baseline border margin">
  <div style="background-color:#f44; width:100px;font-size:40px;text-decoration:underline;">aaa</div>
  <div style="background-color:#5c5; width:120px;font-size:20px;text-decoration:underline;">ccc</div>
  <div style="background-color:#c8c; width:160px;font-size:8px;text-decoration:underline;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_vertical</div>
 <div class="ui_panel layout_vertical border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_vertical_left</div>
 <div class="ui_panel layout_vertical_left  border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_vertical_right</div>
 <div class="ui_panel layout_vertical_right  border margin">
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>

 <div class="ui_panel margin margin_top_large">layout_vertical_right and spacer</div>
 <div class="ui_panel layout_vertical_right  border margin" style="height:400px;">
  <div class="spacer"></div>
  <div style="background-color:#f44; width:100px; height:80px;">aaa</div>
  <div style="background-color:#5c5; width:120px; height:120px;">ccc</div>
  <div style="background-color:#c8c; width:160px; height:60px;">ddd</div>
 </div>
</div>

<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	$('#btn_popup_msg').on('click', function()
	{
		ui.dialog.popup_message('タイトル', 'メッセージです。');
	});
	$('#btn_popup_err').on('click', function()
	{
		ui.dialog.popup_error('タイトル', 'エラーメッセージです。');
	});
	$('#btn_popup_custom').on('click', function()
	{
		var dlg = ui.dialog.popup('#custom_dialog');
		$('#custom_dialog .ui_button').on('click', function()
		{
			dlg.close();
		});
	});
	$('#btn_popup_custom_h').on('click', function()
	{
		var dlg = ui.dialog.popup('#custom_dialog_h');
		$('#custom_dialog_h .ui_button').on('click', function()
		{
			dlg.close();
		});
	});
	$('#btn_popup_custom_vh').on('click', function()
	{
		var dlg = ui.dialog.popup('#custom_dialog_vh');
		$('#custom_dialog_vh .ui_button').on('click', function()
		{
			dlg.close();
		});
	});
});
</script>


</body>
</html>
