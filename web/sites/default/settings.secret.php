<?php

/**
 * @file
 * Loads local Auth0 credentials from ignored files.
 */

$local_credentials = __DIR__ . '/settings.credentials.php';
if (is_readable($local_credentials)) {
  require $local_credentials;
  return;
}

$auth0_secret_file = dirname($app_root) . '/.auth0-client-secret';
if (is_readable($auth0_secret_file)) {
  $auth0_secret = trim((string) file_get_contents($auth0_secret_file));
  if ($auth0_secret !== '') {
    $config['openid_connect.client.auth0']['settings']['client_secret'] = $auth0_secret;
  }
}
