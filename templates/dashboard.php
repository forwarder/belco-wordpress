<div id="belco-container">
	<iframe src="//<?php echo BELCO_HOST; ?><?php echo $page; ?>" id="belco-frame"></iframe>
</div>
<?php if (!$installed) : ?>
	<form id="belco-install-form" action="options.php" method="post" style="display: none;">
	  <?php @settings_fields('wp_belco'); ?>
	  <?php @do_settings_fields('wp_belco'); ?>
	
		<input type="hidden" name="belco_api_secret" value="<?php echo wp_generate_password(48, false); ?>')">">
	</form>
<?php endif; ?>