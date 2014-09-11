<div id="belco-settings" class="wrap">
  <?php if ($installed) : ?>
    <div>
			<h2>Settings</h2>

	    <form id="belco-settings-form" action="options.php" method="post" class="belco-setup">
	      <?php @settings_fields('wp_belco'); ?>
	      <?php @do_settings_fields('wp_belco'); ?>
			
				<input type="hidden" name="belco_api_key">
				<input type="hidden" name="belco_api_secret">
				<input type="hidden" name="belco_host">

		    <p class="submit">
		      <button type="submit" class="button button-primary">Disconnect account</button>
		    </p>
			</form>
		</div>
  <?php endif; ?>
</div>