/*

	ページャ

*/
//------------------------------------------------------------------------------
//	properties
//------------------------------------------------------------------------------
<props>
{
	module			: null,
	total			: null,
	start_index		: null,
	row_per_page	: null,
	prev_page_no	: null,
	next_page_no	: null,
}
</props>

//------------------------------------------------------------------------------
//	html part
//------------------------------------------------------------------------------
<template>
 <div class="pager" ref="pager">
  <div class="ui_panel transparent layout_horizon full_horizon padding_vertical">
   <div class="ui_pager">
    <div class="label">
     <span>{{ total }}</span>件中、<span>{{ start_index }}</span>件目～<span>{{ row_per_page }}</span>件を表示
    </div>
    <div class="links">
     <button ref="prev" class="prev disabled" title="前のxx件" disabled></button>
     <button ref="next" class="next disabled" title="次のxx件" disabled></button>
    </div>
   </div>
  </div>
 </div>
</template>

//------------------------------------------------------------------------------
//	style
//------------------------------------------------------------------------------
<style>
.ui_pager
{
	.links
	{
		margin-left : 20px;

		> button
		{
			border-radius : $radius_button;
			width : 32px;
			height : 32px;
			cursor : pointer;

			&.prev
			{
				background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNSIgaGVpZ2h0PSIxMiIgdmlld0JveD0iMCAwIDE1IDEyIj48cGF0aCBmaWxsPSIjNjE2NzczIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik00LjEyIDcuMjIzbDIuNjY0IDIuNjk1Yy40NzIuNDczLjQ3NSAxLjI0Mi4wMDggMS43Mi0uNDcuNDgtMS4yMzUuNDgzLTEuNzEuMDA2TC4zNTEgNi44NkExLjIyNiAxLjIyNiAwIDAgMSAuMzUgNS4xNDJMNS4wNzUuMzYyQTEuMTk4IDEuMTk4IDAgMCAxIDYuNzgzLjM1NWwuMDgzLjA5MmMuMzg4LjQ3Ni4zNjQgMS4xOC0uMDc2IDEuNjNMNC4xMSA0Ljc4OGg5LjY4NGMuNjY3IDAgMS4yMDYuNTQ2IDEuMjA2IDEuMjE3IDAgLjY3Mi0uNTQgMS4yMTgtMS4yMDYgMS4yMTh6Ii8+PC9zdmc+');
				background-size : 100% 100%;
				background-repeat : no-repeat;
				background-position : center;
				border : none;
			}
			&.next
			{
				background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNSIgaGVpZ2h0PSIxMiIgdmlld0JveD0iMCAwIDE1IDEyIj48cGF0aCBmaWxsPSIjNjE2NzczIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMC44OCA3LjIyM0w4LjIxNiA5LjkxOGExLjIyNyAxLjIyNyAwIDAgMC0uMDA4IDEuNzJjLjQ3LjQ4IDEuMjM1LjQ4MyAxLjcxLjAwNmw0LjczLTQuNzg0Yy40NjktLjQ3NC40Ny0xLjI0My4wMDEtMS43MThMOS45MjUuMzYyQTEuMTk4IDEuMTk4IDAgMCAwIDguMjE3LjM1NWwtLjA4My4wOTJhMS4yMjggMS4yMjggMCAwIDAgLjA3NiAxLjYzbDIuNjggMi43MTJIMS4yMDZBMS4yMSAxLjIxIDAgMCAwIDAgNi4wMDVjMCAuNjcyLjU0IDEuMjE4IDEuMjA2IDEuMjE4eiIvPjwvc3ZnPg==');
				background-size : 100% 100%;
				background-repeat : no-repeat;
				background-position : center;
				border : none;
			}
		}
	}
}
</style>

//------------------------------------------------------------------------------
//	init
//------------------------------------------------------------------------------
<init>
{
	//	ページャ情報をバインド
	let module = self.prop('module');
	dbc.bind(module + '_pager', 'total', self, 'total');
	dbc.bind(module + '_pager', 'start_index', self, 'start_index');
	dbc.bind(module + '_pager', 'row_per_page', self, 'row_per_page');
	dbc.bind(module + '_pager', 'prev_page_no', self, 'prev_page_no');
	dbc.bind(module + '_pager', 'next_page_no', self, 'next_page_no');
}
</init>

//------------------------------------------------------------------------------
//	ready
//------------------------------------------------------------------------------
<ready>
{
	//	prev, nextボタン制御
	let page_cols = ['prev', 'next'];

	//	外部から渡されたmodule名を元にURLを生成
	//	memo : ajax_get_pageは、ページャを使用するモジュールでは共通のアクションにする。
	let module = self.prop('module');
	let url = g[module + '_actions'].ajax_get_page;

	array_each(page_cols, (col_) =>
	{
		self.jq(col_).on('click', function()
		{
			let page_no = self.prop(col_ + '_page_no');

			ajax.post
			(
				url,
				{ page_no : page_no },
				(data_) =>
				{
					let data = JSON.parse(data_);

					let new_rows = data.rows;
					let total = data.total;
					let start_index = data.start_index;
					let row_per_page = data.row_per_page;
					let prev_page_no = data.prev_page_no;
					let next_page_no = data.next_page_no;

					let vp = viewpart_find_by_name('scene_' + module);

					//	一覧をクリアする
					vp.remove_pref('row');

					//	一覧を更新する
					if( Object.keys(new_rows).length > 0 )
					{
						//	一覧を埋め込む
						vp.create_children_and_append("row", new_rows, "rows");

						//	dbcにデータを追加、既にある場合は上書きする
						dbc.merge_list(module + '_list', new_rows);
					}
					else
					{
						//	nodata用のviewpartを埋め込む
						vp.create_child_and_append("row_nodata", {}, "rows");
					}

					//	ページャ情報を更新する
					dbc.set(module + '_pager', 'total', total);
					dbc.set(module + '_pager', 'start_index', start_index);
					dbc.set(module + '_pager', 'row_per_page', row_per_page);
					dbc.set(module + '_pager', 'prev_page_no', prev_page_no);
					dbc.set(module + '_pager', 'next_page_no', next_page_no);
				},
				(code_, msg_) =>
				{
					console.log(code_, msg_);
				}
			);
		});
	});

}
</ready>

//------------------------------------------------------------------------------
//	watch
//------------------------------------------------------------------------------
<watch>
{
	next_page_no(old_, new_)
	{
		//	次のページがない場合は、ボタンを無効化する
		if( new_ == false )
		{
			self.jq('next').prop('disabled', true);
			self.jq('next').addClass('disabled');
		}
		else
		{
			self.jq('next').prop('disabled', false);
			self.jq('next').removeClass('disabled');
		}
	},
	prev_page_no(old_, new_)
	{
		if( new_ == false )
		{
			self.jq('prev').prop('disabled', true);
			self.jq('prev').addClass('disabled');
		}
		else
		{
			self.jq('prev').prop('disabled', false);
			self.jq('prev').removeClass('disabled');
		}
	},
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


}
</method>

//------------------------------------------------------------------------------
//	recv
//------------------------------------------------------------------------------
<recv>
{
}
</recv>
