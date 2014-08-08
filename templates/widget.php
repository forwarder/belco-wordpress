<div id="belco-widget" ng-app="belco.widget">        
  <div ng-if="!connected">
    Connecting...
  </div>       
        
  <div ng-controller="LoginController as login" ng-if="!user">
    <form ng-submit="login.submit()">
      <p>
        <label for="username">Username</label><input type="text" name="belco_username" ng-model="login.username">
      </p>
      <p>
        <label for="password">Password</label><input type="password" name="belco_password" ng-model="login.password">
      </p>
      <button type="submit">Log in</button>
    </form>
  </div>

  <div ng-if="user && ready">
    <div class="belco-utilities" ng-controller="StatusController as status" ng-show="connected">
      <p>Welkom {{user | displayName}}</p>
    </div>

    <div ng-controller="LogoutController as logout">
      <form ng-submit="logout.submit()">
        <button type="submit">Log out</button>
      </form>
    </div>

  </div>
      
</div>