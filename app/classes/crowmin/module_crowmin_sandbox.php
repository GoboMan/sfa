<?php

class	module_crowmin_sandbox extends module_crowmin
{
	//	コードの実行パスワード
	private static $m_exec_password = 'crow2017';

	//	index
	public function action_index()
	{
		$code = crow_request::get('code', '');

		crow_response::set('code', $code);
		crow_response::set('result', '');

		$need_pw = crow_config::get('crowmin.need_sandbox_pw') == "true";

		if( crow_request::is_post() )
		{
			if( $need_pw === true )
			{
				$pw = crow_request::get('pw', '');
				if( $pw != self::$m_exec_password )
				{
					crow_response::set('result', '実行パスワードが不正です');
					return;
				}
			}

			ob_start();
			eval($code);
			$result = ob_get_contents();
			ob_end_clean();

			crow_response::set('result', $result);
		}
	}
}


?>
