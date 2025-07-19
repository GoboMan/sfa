<?php

class	module_front_project extends module_front
{
	public function action_index()
	{
	}

	public function action_ajax_get_rows()
	{
		$rows = model_project::create_array();
		app::exit_ok(json_encode($rows));
	}
}


?>
