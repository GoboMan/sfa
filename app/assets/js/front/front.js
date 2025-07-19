function change_unixts_to_ymd(unixts_)
{
	let date = new Date(unixts_ * 1000);
	let year = date.getFullYear();
	let month = date.getMonth() + 1;
	let day = date.getDate();
	return `${year}-${month}-${day}`;
}

function adjust_entity_create_panel_size()
{
	//	パネルの幅を調整
	g.scalefull_width = g.jq_body_wrap.width();

	//	パネルの高さを調整
	// g.jq_create_entity_panel.height(g.jq_body_wrap.height() - 10 * 2);
}

function collect_input_data(viewpart_, selector_)
{
	let topelm = viewpart_.jq(selector_);
	let datas = {};
	array_each(topelm.find('input, textarea, select').get(), elm_ =>
	{
		let jq_elm = $(elm_);
		let name = jq_elm.attr('name');
		let value = jq_elm.val();
		let type = jq_elm.attr('type');
		if( type == 'checkbox' )
		{
			if( jq_elm.attr('value') === undefined )
				datas[name] = jq_elm.prop('checked') ? true : false;
		}
		else if( type == 'radio' )
		{
			if( jq_elm.prop('checked') ) datas[name] = value;
		}
		else datas[name] = value;
	});
	return datas;
}