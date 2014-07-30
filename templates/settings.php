<div class="wrap">
    <h2>Options</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('wp_belco'); ?>
        <?php @do_settings_fields('wp_belco'); ?>

        <table class="form-table">  
            <tr valign="top">
                <th scope="row"><label for="setting_api">API key</label></th>
                <td><input type="text" name="setting_api" id="setting_api" value="<?php echo get_option('setting_api'); ?>" /></td>
            </tr>
        </table>

        <?php @submit_button(); ?>
    </form>
</div>