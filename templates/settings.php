<div id="belco-setup" class="wrap" ng-app="belco.setup">
  <div ng-controller="SetupController as setup" ng-if="ready">
  <?php if (!$api_key) : ?>
    <div ng-init="setup.apiKey='<?php echo wp_generate_password(48, false); ?>'">
      <form ng-submit="setup.login()" class="belco-setup-login" ng-if="!loggedIn">
        <h2>Log in with your Belco account to finish the installation</h2>
        
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
      
      <form ng-submit="setup.setup()" class="belco-setup" ng-if="loggedIn">
        <h2>Connect your Belco account to finish the installation</h2>
        
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
          <button type="submit" class="button button-primary">Connect account</button> or <a href="#" ng-click="setup.logout()">log in with another account</a>
        </p>
      </form>
    </div>
  <?php else: ?>
    <h2>Settings</h2>
    <form method="post" action="options.php"> 
      <?php @settings_fields('wp_belco'); ?>
      <?php @do_settings_fields('wp_belco'); ?>

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
        <tr valign="top">
          <th scope="row"><label for="belco_api_key">API key</label></th>
          <td><span><?php echo $api_key; ?></span></td>
        </tr>
      </table>
      <p class="submit">
        <button type="submit" class="button button-primary" ng-click="setup.disconnect()">Disconnect account</button>
      </p>
    </form>
  <?php endif; ?>
</div>