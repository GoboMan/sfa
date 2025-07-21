/*

	entityシーン

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	//	jqオブジェクト
	jq_root					: null,
	jq_body_wrapper			: null,
	jq_btn_wrapper			: null,
	jq_create_entity_panel	: null,
	jq_detail_entity_panel	: null,

	//	パネル表示サイズ
	scalefull_width			: 0,

	//	パネル表示状態
	show_create_panel		: false,
	show_detail_panel		: false,

}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="entity ui_panel transparent layout_vertical full padding" ref="entity">
  <div class="ui_panel transparent layout_horizon full_horizon padding_vertical">
   <div class="spacer"></div>
   <button class="ui_button info small" ref="btn_show_create_panel">+ 新規登録</button>
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

  <div class="spacer"></div>

  <?php /**** ページャ ****/ ?>
  [[pager pref="pager" module="entity"]]

  <?php /**** 取引先作成パネル ****/ ?>
  [[create pref="create_entity_panel"]]

  <?php /**** 取引先詳細パネル ****/ ?>
  [[detail pref="detail_entity_panel"]]
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
	//	パーツ内で使用する、jqオブジェクト
	self.prop('jq_root', viewpart_find_by_name('root').jq('root'));
	self.prop('jq_body_wrapper', viewpart_find_by_name('root').jq('body_wrapper'));
	self.prop('jq_btn_wrapper', viewpart_find_by_name('root').jq('btn_wrapper'));
	self.prop('jq_create_entity_panel', self.pref('create_entity_panel').jq('create_entity_panel'));
	self.prop('jq_detail_entity_panel', self.pref('detail_entity_panel').jq('detail_entity_panel'));
}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
	//	ブラウザサイズ変更時に、都度リサイズする
	let panel_cols = ['create', 'detail'];
	array_each(panel_cols, (col_) =>
	{
		window.addEventListener('resize', () =>
		{
			self.adjust_create_detail_panel_size();

			//	パネル表示
			if( self.prop('show_'+col_+'_panel') === true )
			{
				self.prop('jq_'+col_+'_entity_panel').css('display', 'block').stop().animate({ width : self.prop('scalefull_width') }, 200);
			}
			else
			{
				self.prop('jq_'+col_+'_entity_panel').css('display', 'none');
			}
		});
	});

	//	entity一覧取得して、viewにマウントする
	self.get_entity_rows_with_ajax();

	//	取引先作成パネル表示
	self.jq('btn_show_create_panel').on('click', () =>
	{
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
	adjust_create_detail_panel_size()
	{
		self.prop('scalefull_width', self.prop('jq_body_wrapper').width());
	},

	//	createパネル表示
	show_create_panel(anim_ = true)
	{
		//	サイズ調整
		self.adjust_create_detail_panel_size();

		//	パネル表示
		if( anim_ === true )
		{
			if( self.prop('show_create_panel') === false )
			{
				self.prop('jq_create_entity_panel').css('display', 'block').stop().animate({ width : self.prop('scalefull_width') }, 200);
			}
		}
		else
		{
			self.prop('jq_create_entity_panel').css('display', 'block');
		}

		self.prop('show_create_panel', true);
	},

	//	detailパネル表示
	show_detail_panel(anim_ = true)
	{
		//	サイズ調整
		self.adjust_create_detail_panel_size();

		//	パネル表示
		if( anim_ === true )
		{
			if( self.prop('show_detail_panel') === false )
			{
				self.prop('jq_detail_entity_panel').css('display', 'block').stop().animate({ width : self.prop('scalefull_width') }, 200);
			}
		}
		else
		{
			self.prop('jq_detail_entity_panel').css('display', 'block');
		}

		self.prop('show_detail_panel', true);
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
				let data = JSON.parse(data_);
				let entity_rows = data.rows;
				let pager_total = data.total;
				let pager_start_index = data.start_index;
				let pager_row_per_page = data.row_per_page;

				//	ページャ情報を設定する
				dbc.set('entity_pager', 'total', pager_total);
				dbc.set('entity_pager', 'start_index', pager_start_index);
				dbc.set('entity_pager', 'row_per_page', pager_row_per_page);
				dbc.set('entity_pager', 'prev_page_no', data.prev_page_no);
				dbc.set('entity_pager', 'next_page_no', data.next_page_no);

				if( Object.keys(entity_rows).length > 0 )
				{
					//	entity一覧を埋め込む
					self.create_children_and_append("row", entity_rows, "rows");

					//	dbcにデータを追加、既にある場合は上書きする
					dbc.set_list("entity_list", entity_rows);
				}
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
