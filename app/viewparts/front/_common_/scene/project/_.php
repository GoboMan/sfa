/*

	projectシーン

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	show_workforce_table	: false,
	show_create_panel		: false,

	scalefull_width			: 0,

	//	jqオブジェクト
	jq_body_wrapper			: null,
	jq_create_project_panel	: null,
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="project ui_panel transparent layout_vertical_left full padding" ref="project">
  <div class="ui_panel transparent layout_horizon full_horizon margin_vertical">
   <div class="spacer"></div>
   <button class="ui_button small margin_right" ref="btn_toggle_workforce_table">人材リスト表示</button>
   <button class="ui_button info small" ref="btn_show_create_panel">+ 案件登録</button>
  </div>

  <div class="ui_panel transparent layout_horizon_top full_horizon">
   <?php /**** 案件テーブル ****/ ?>
   [[table pref="project_table"]]

   <?php /**** 人材テーブル ****/ ?>
   [[scene_workforce_table pref="workforce_table"]]

  </div>

  <?php /**** プロジェクト作成パネル ****/ ?>
  [[create pref="create_project_panel"]]
 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.ui_list
{
	tr
	{
		th
		{
			padding : 3px 5px;
			font-size : 0.9em;
		}
		td
		{
			padding : 5px;
			font-size : 0.8em;
		}
	}
}

button
{
	&.show_workforce_table
	{
		background-color : #00ff00;
		color : #000;
	}
}
</style>

//------------------------------------------------------------------------------
//	init
//------------------------------------------------------------------------------
<init>
{
	//	パーツ内で使用する、jqオブジェクト
	self.prop('jq_body_wrapper', viewpart_find_by_name('root').jq('body_wrapper'));
	self.prop('jq_create_project_panel', self.pref('create_project_panel').jq('create_project_panel'));
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
		self.adjust_create_panel_size();

		//	パネル表示
		if( self.prop('show_create_panel') === true )
		{
			self.prop('jq_create_project_panel').css('display', 'block').stop().animate({ width : self.prop('scalefull_width') }, 200);
		}
		else
		{
			self.prop('jq_create_project_panel').css('display', 'none');
		}
	});

	//	人材テーブル表示
	self.jq('btn_toggle_workforce_table').on('click', function()
	{
		self.prop('show_workforce_table', !self.prop('show_workforce_table'));

		if( self.prop('show_workforce_table') === true )
		{
			self.jq('btn_toggle_workforce_table').addClass('show_workforce_table');

			let vp_workforce_table = self.pref('workforce_table');
			vp_workforce_table.prop('shown', true);
		}
		else
		{
			self.jq('btn_toggle_workforce_table').removeClass('show_workforce_table');

			let vp_workforce_table = self.pref('workforce_table');
			vp_workforce_table.prop('shown', false);
		}
	});

	//	案件作成パネル表示
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
		return g.url_base;
	},

	//	タイトル取得
	scene_title()
	{
		return '案件一覧';
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
	adjust_create_panel_size()
	{
		self.prop('scalefull_width', self.prop('jq_body_wrapper').width());
	},

	//	プロジェクト作成パネル表示
	show_create_panel(anim_ = true)
	{
		//	サイズ調整
		self.adjust_create_panel_size();

		//	パネル表示
		if( anim_ === true )
		{
			if( self.prop('show_create_panel') === false )
			{
				self.prop('jq_create_project_panel').css('display', 'block').stop().animate({ width : self.prop('scalefull_width') }, 200);
			}
		}
		else
		{
			self.prop('jq_create_project_panel').css('display', 'block');
		}

		self.prop('show_create_panel', true);
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
