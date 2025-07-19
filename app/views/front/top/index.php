<!DOCTYPE html>
<html>
<head>
 <?= crow::get_default_head_tag() ?>
</head>
<body>

<?php /**** viewpart 出力 ****/ ?>
<?= crow_viewpart::html() ?>

<script nonce="<?= crow_response::nonce() ?>">
var g =
{
	<?php /**** 各モジュール内のアクションを取得 ****/ ?>
	project_actions		: <?= crow::get_module_urls_as_json('project') ?>,
	workforce_actions	: <?= crow::get_module_urls_as_json('workforce') ?>,
	entity_actions		: <?= crow::get_module_urls_as_json('entity') ?>,

	access_path			: "<?= crow_request::get_route_path() ?>",
	scenes				: null,
	root				: null,
	url_base			: "<?= crow::make_url() ?>",
};

g.root = viewpart_activate("root");


</script>

</body>
</html>
