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
	<?php /**** 自身のアクションはJS側のルータで使用するので、ajaxは専用モジュールに集約する ****/ ?>
	actions			: <?= crow::get_module_urls_as_json() ?>,

	access_path		: "<?= crow_request::get_route_path() ?>",
	scenes			: null,
	root			: null,
	url_base		: "<?= crow::make_url() ?>",
};

g.root = viewpart_activate("root");

</script>

</body>
</html>
