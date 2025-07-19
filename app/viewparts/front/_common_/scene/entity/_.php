/*

	entityシーン

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	shown_create_panel : false,
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="entity ui_panel transparent padding" ref="entity">
  <div class="ui_panel transparent layout_horizon full_horizon padding_vertical">
   <div class="spacer"></div>
   <button class="ui_button info" ref="btn_add">+ 新規登録</button>
  </div>

  <?php /**** テーブル ****/ ?>
  <table class="ui_list full_horizon border">
   <thead>
    <tr class="border">
     <th>企業名</th>
     <th class="min">RA営業担当</th>
     <th class="min">更新日時</th>
    </tr>
   </thead>
   <tbody ref="rows"></tbody>
  </table>

  <?php /**** 取引先作成パネル ****/ ?>
  [[create pref="create_entity_panel"]]
 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>

</style>

//------------------------------------------------------------------------------
//	init
//------------------------------------------------------------------------------
<init>
{
	//	パネル表示サイズ
	g.scalefull_width = 0;

	//	jqオブジェクト
	g.jq_root = viewpart_find_by_name('root').jq('root');
	g.jq_body_wrap = viewpart_find_by_name('root').jq('body_wrap');
	g.jq_btn_wrapper = viewpart_find_by_name('root').jq('btn_wrapper');
	g.jq_create_entity_panel = self.pref('create_entity_panel').jq('create_entity_panel');

}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
	//	ブラウザサイズ変更時に、都度リサイズする
	window.addEventListener('resize', () =>
	{
		adjust_entity_create_panel_size();

		//	パネル表示
		if( self.prop('shown_create_panel') === true )
		{
			g.jq_create_entity_panel.css('display', 'block').stop().animate({ width : g.scalefull_width }, 200);
		}
		else
		{
			g.jq_create_entity_panel.css('display', 'block');
		}
	});

	//	entity一覧取得して、viewにマウントする
	self.get_entity_rows_with_ajax();

	//	新規登録
	self.jq('btn_add').on('click', () =>
	{
		//	パネル表示
		self.show_create_panel();
	});

}
</ready>

//------------------------------------------------------------------------------
//	watch
//------------------------------------------------------------------------------
<watch>
{
}
</watch>

//------------------------------------------------------------------------------
//	method
//------------------------------------------------------------------------------
<method>
{
	//	復元パス取得
	scene_path()
	{
		return g.url_base + 'entity';
	},

	//	タイトル取得
	scene_title()
	{
		return '取引先一覧';
	},

	//	休止時
	scene_suspend()
	{
	},

	//	再開時
	scene_resume()
	{
	},

	//	破棄時
	scene_destroy()
	{
	},

	/*
		パネルサイズ関連
	*/
	//	createパネル表示
	show_create_panel(anim_ = true)
	{
		//	サイズ調整
		adjust_entity_create_panel_size();

		//	パネル表示
		if( anim_ === true )
		{
			if( self.prop('shown_create_panel') === false )
			{
				g.jq_create_entity_panel.css('display', 'block').stop().animate({ width : g.scalefull_width }, 200);
			}
		}
		else
		{
			g.jq_create_entity_panel.css('display', 'block');
		}

		self.prop('shown_create_panel', true);
	},

	//	entity一覧取得
	get_entity_rows_with_ajax()
	{
		ajax.post
		(
			g.entity_actions.ajax_get_rows,
			{},
			(data_) =>
			{
				//	取引先一覧を取得し、viewにマウントする
				let data = JSON.parse(data_);
				let entity_rows = data;

				//	取引先がある場合
				if( Object.keys(entity_rows).length > 0 )
				{
					//	entity一覧を埋め込む
					self.create_children_and_append("row", entity_rows, "rows");

					//	dbcにデータを追加、既にある場合は上書きする
					dbc.set_list("entities", entity_rows);
				}
				//	取引先がない場合
				else
				{
					//	nodata用のviewpartを埋め込む
					self.create_child_and_append("row_nodata", {}, "rows");
				}

			},
			(code_, msg_) =>
			{
				console.log(code_, msg_);
				ui.toast.add(msg_, 'error');
			}
		);
	},
}
</method>

//------------------------------------------------------------------------------
//	recv
//------------------------------------------------------------------------------
<recv>
{
}
</recv>
