<?php

/**
 * @file
 * Example runtime settings for local Auth0 sign-in.
 *
 * Copy this file to settings.secret.php and add the client secret to
 * .auth0-client-secret in the project root.
 */

$auth0_secret_file = dirname($app_root) . '/.auth0-client-secret';
if (is_readable($auth0_secret_file)) {
  $auth0_secret = trim((string) file_get_contents($auth0_secret_file));
  if ($auth0_secret !== '') {
    $config['openid_connect.client.auth0']['settings']['client_secret'] = $auth0_secret;
  }
}
