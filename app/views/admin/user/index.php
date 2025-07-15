<?php $include("header.php"); ?>


<?php /**** ページ制御 ****/ ?>
<script nonce="<?= crow_response::nonce() ?>">
$(function()
{
	init(
	{
		actions : <?= crow::get_module_urls_as_json() ?>,
	});
});
</script>

<?php $include("footer.php"); ?>
