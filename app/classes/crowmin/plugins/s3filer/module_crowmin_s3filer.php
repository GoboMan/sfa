<?php
/*

	S3 ファイラ

*/
class	module_crowmin_s3filer extends module_crowmin
{
	//--------------------------------------------------------------------------
	//	index
	//--------------------------------------------------------------------------
	public function action_index()
	{
		$config = crow_config::get_starts_with("aws.");
		$profiles = [];
		foreach( $config as $key => $val )
		{
			if( preg_match("/^aws\.(.+)\.key$/", $key, $m) == 1 )
			{
				$profiles[$m[1]] = $m[1];
			}
		}
		crow_response::set('profile_names', $profiles);

		//	セッションから現在の設定値を取得
		$sess = crow_session::get_instance();
		$sess_val = $sess->get_property('profile');
		$current_profile = $sess_val !== false ? $sess_val : 'default';
		$current_bucket = $sess->get_property('bucket');

		//	定義されているプロファイルとバケット一覧を取得
		$buckets = [];
		foreach( $profiles as $profile )
		{
			$buckets[$profile] = crow_config::get('aws.'.$profile.'.bucket');
		}
		crow_response::set('buckets', $buckets);

		//	設定プロファイル
		crow_response::set('profile', $current_profile);

		//	バケット
		$current_bucket = $current_bucket !== false ? $current_bucket : crow_config::get('aws.'.$current_profile.'.bucket', '');
		crow_response::set('bucket', $current_bucket);

		//	パス
		$path = crow_request::get('path', '/');
		if( substr($path, -1) != "/" ) $path .= "/";
		crow_response::set('path', $path);

		//	S3から取得
		$s3 = crow_storage::get_instance("s3", $current_bucket, $current_profile);
		$dirs_files = $s3->get_dirs_and_files($path);
		crow_response::set('dirs', $dirs_files[0]);
		crow_response::set('files', $dirs_files[1]);
	}

	//--------------------------------------------------------------------------
	//	プロファイル変更
	//--------------------------------------------------------------------------
	public function action_ajax_change_profile()
	{
		$i_profile = crow_request::get('profile', '');
		$i_bucket = crow_request::get('bucket', '');

		$sess = crow_session::get_instance();
		$sess->set_property('profile', $i_profile);
		$sess->set_property('bucket', $i_bucket);

		$this->exit_ok();
	}

	//--------------------------------------------------------------------------
	//	ダウンロード
	//--------------------------------------------------------------------------
	public function action_download()
	{
		$path = crow_request::get('path', '');

		//	セッションから現在の設定値を取得
		$sess = crow_session::get_instance();
		$current_profile = $sess->get_property('profile');
		$current_profile = $current_profile !== false ? $current_profile : 'default';

		$current_bucket = $sess->get_property('bucket');
		$current_bucket = $current_bucket !== false ? $current_bucket : crow_config::get('aws.'.$current_profile.'.bucket', '');

		//	S3から取得
		$s3 = crow_storage::get_instance("s3", $current_bucket, $current_profile);
		$s3->download($path);
		exit;
	}

	//--------------------------------------------------------------------------
	//	削除
	//--------------------------------------------------------------------------
	public function action_trash()
	{
		$path = crow_request::get('path', '');
		$return_path = crow_request::get('ret', '');

		//	セッションから現在の設定値を取得
		$sess = crow_session::get_instance();
		$sess_val = $sess->get_property('profile');
		$current_profile = $sess_val !== false ? $sess_val : 'default';

		$current_bucket = $sess->get_property('bucket');
		$current_bucket = $current_bucket !== false ? $current_bucket : crow_config::get('aws.'.$current_profile.'.bucket', '');

		//	削除
		$s3 = crow_storage::get_instance("s3", $current_bucket, $current_profile);
		if( $s3->remove($path) === false )
		{
			return crow::redirect_action_with_vars('index', ['error' => '削除に失敗しました'], ['path' => $return_path]);
		}
		return crow::redirect_action_with_vars('index', ['msg' => 'ファイルを削除しました'], ['path' => $return_path]);
	}

	//--------------------------------------------------------------------------
	//	アップロード
	//--------------------------------------------------------------------------
	public function action_upload()
	{
		$path = crow_request::get('path', '');
		if( substr($path, -1) != "/" ) $path .= "/";

		//	セッションから現在の設定値を取得
		$sess = crow_session::get_instance();
		$current_profile = $sess->get_property('profile');
		$current_profile = $current_profile !== false ? $current_profile : 'default';
		$current_bucket = $sess->get_property('bucket');
		$current_bucket = $current_bucket !== false ? $current_bucket : crow_config::get('aws.'.$current_profile.'.bucket', '');

		//	アップロード
		$s3 = crow_storage::get_instance("s3", $current_bucket, $current_profile);
		$data_path = $_FILES['upload_file']['tmp_name'];
		if( $s3->write_from_disk($data_path, $path.$_FILES['upload_file']['name']) === false )
		{
			return crow::redirect_action_with_vars('index', ['error' => 'アップロードに失敗しました'], ['path' => $path]);
		}
		return crow::redirect_action('index', ['path' => $path]);
	}
}


?>
