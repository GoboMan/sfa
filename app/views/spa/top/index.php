<!DOCTYPE html>
<html>
<head>
 <?= crow::get_default_head_tag() ?>
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title></title>
 <meta name="keywords" content="">
 <meta name="description" content="">
 <meta name="robots" content="index, follow">
 <meta name="twitter:card" content="summary">
 <meta name="twitter:title" content="">
 <meta name="twitter:description" content="">
 <meta name="twitter:image" content="">
 <meta name="og:url" content="">
 <meta name="og:type" content="website">
 <meta name="og:locale" content="ja_JP">
 <meta name="og:title" content="">
 <meta name="og:description" content="">
 <meta name="og:image" content="">
 <meta name="og:site_name" content="">
 <link rel="icon" href="favicon.ico" type="image/x-icon" sizes="any">
 <link rel="icon" href="svg_icon.svg" type="image/svg+xml">
 <link rel="apple-touch-icon" href="apple-touch-icon.png">
 <link rel="manifest" href="manifest.webmanifest">
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
	url_base		: "<?= crow::make_url() ?>"
};
g.root = viewpart_activate("root");

</script>
</body>
</html>
