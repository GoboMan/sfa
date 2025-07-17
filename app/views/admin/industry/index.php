<?php $include('header.php'); ?>

<div class="ui_panel transparent layout_horizon_top padding_vertical full"> 

 <?php $include("sidebar.php") ?>

 <div class="ui_panel layout_vertical_left full padding_large">
  <h1 class="ui_heading">業界一覧</h1>
 

  <?php /* heading */ ?>
  <div class="ui_panel transparent layout_horizon padding_vertical"> 
   <div class="spacer"></div>
   <button id="create_btn" class="ui_button warn">新規登録</button>
  </div>

  <?php /* Todo Table */ ?>
  <table id="todo_table" class="ui_list">
   <thead>
    <tr>
     <th class="min">ID</th>
     <th>Name</th>
     <th class="min">Due Date</th>
     <th class="min">Edit</th>
     <th class="min">Check</th>
    </tr>
   </thead>

   <tbody>
    <?php usort($rows, function($a, $b)
    {
     return $a->due_date - $b->due_date;
    }); ?>
    <?php foreach($rows as $row): ?>
     <tr class="border clickable hover <?= $row->is_completed === true ? 'hide' : ''?>"
      todo_id = "<?= $row->todo_id ?>"
      name = "<?= $row->name ?>"
      due_date = "<?= date('Y-m-d', $row->due_date) ?>"
     >
      <td class="min"><?= $row->todo_id ?></td>
      <td><?= $row->name ?></td>
      <td class="min"><?= date('Y-m-d', $row->due_date) ?></td>
      <td class="min">
       <button class="ui_button small done edit_btn" 
        todo_id="<?= $row->todo_id ?>"
        name="<?= $row->name ?>"
        due_date="<?= date('Y-m-d', $row->due_date) ?>">EDIT</button>
       <button class="ui_button small danger delete_btn"
        todo_id="<?= $row->todo_id ?>">DELETE</button>
      </td>
      <td class="min">
       <button class="ui_checkbox checkbox_btn" todo_id="<?= $row->todo_id?>"></button>
      </td>
     </tr>
    <?php endforeach; ?>
   </tbody>
  </table>
 </div>
</div>

<?php $include('footer.php'); ?>