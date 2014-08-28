<div id="belco-setup" class="wrap" ng-app="belco.setup">
  <div ng-controller="SetupController as setup" ng-if="ready">
  <?php if (!$api_key|| !$api_secret) : ?>
    <div>
			<h2>Finish your Belco installation</h2>
      <form ng-submit="setup.login()" class="belco-setup-login" ng-if="!loggedIn">				
        <h3>1. Log in with your Belco account</h3>
        
				<div class="error settings-error" ng-if="error"><p><strong>{{error}}</strong></p></div>
				
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="username">Username</label>
            </th>
            <td>
              <input type="text" name="belco_username" ng-model="setup.username" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="password">Password</label>
            </th>
            <td>
              <input type="password" name="belco_password" ng-model="setup.password" class="regular-text">
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" class="button button-primary">Log in</button>
        </p>
      </form>
      
      <form id="belco-setup-form" action="options.php" method="post" class="belco-setup" ng-if="loggedIn">
	      <?php @settings_fields('wp_belco'); ?>
	      <?php @do_settings_fields('wp_belco'); ?>
				
				<input type="hidden" name="belco_api_key" value="{{apiKey}}">
				<input type="hidden" name="belco_api_secret" value="{{apiSecret}}" ng-init="apiSecret='<?php echo wp_generate_password(48, false); ?>'">
				
        <h3>2. Connect your Belco account to finish the installation</h3>
        
				<div class="error settings-error" ng-if="error"><p><strong>{{error}}</strong></p></div>
				
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="organization">Organization</label>
            </th>
            <td>
              <span>{{organization.name}}</span>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="username">Logged in as</label>
            </th>
            <td>
              <span>{{user | displayName}}</span>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="button" class="button button-primary" ng-click="setup.setup()">Connect account</button> or <a href="#" ng-click="setup.logout()">log in with another account</a>
        </p>
      </form>
    </div>
  <?php else: ?>
    <h2>Settings</h2>
    <form ng-submit="setup.login()" class="belco-setup-login" ng-if="!loggedIn">				
      <h3>Log in with your Belco account</h3>
      
			<div class="error settings-error" ng-if="error"><p><strong>{{error}}</strong></p></div>
			
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="username">Username</label>
          </th>
          <td>
            <input type="text" name="belco_username" ng-model="setup.username" class="regular-text" autocomplete="off">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="password">Password</label>
          </th>
          <td>
            <input type="password" name="belco_password" ng-model="setup.password" class="regular-text">
          </td>
        </tr>
      </table>
      <p class="submit">
        <button type="submit" class="button button-primary">Log in</button>
      </p>
    </form>
    <form id="belco-setup-form" action="options.php" method="post" class="belco-setup" ng-show="loggedIn">
      <?php @settings_fields('wp_belco'); ?>
      <?php @do_settings_fields('wp_belco'); ?>
			
			<input type="hidden" name="belco_api_key" value="{{apiKey}}">
			<input type="hidden" name="belco_api_secret" value="{{apiSecret}}" ng-init="apiSecret='<?php echo wp_generate_password(48, false); ?>'">
	    <table class="form-table">
	      <tr>
	        <th scope="row">
	          <label for="organization">Organization</label>
	        </th>
	        <td>
	          <span>{{organization.name}}</span>
	        </td>
	      </tr>
	      <tr>
	        <th scope="row">
	          <label for="username">Logged in as</label>
	        </th>
	        <td>
	          <span>{{user | displayName}}</span>
	        </td>
	      </tr>
	    </table>
	    <p class="submit">
	      <button type="button" class="button button-primary" ng-click="setup.disconnect()">Disconnect account</button>
	    </p>
		</form>
  <?php endif; ?>
</div>