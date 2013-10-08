<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

use fkooman\OAuth\Client\ClientConfig;
use fkooman\OAuth\Client\SessionStorage;
use fkooman\OAuth\Client\Callback;
use fkooman\OAuth\Client\AuthorizeException;

use Guzzle\Http\Client;

$clientConfig = new ClientConfig($config['client']);

try {
    $tokenStorage = new SessionStorage();
    $httpClient = new Client();
    $cb = new Callback("php-voot-client", $clientConfig, $tokenStorage, $httpClient);
    $cb->handleCallback($_GET);

    header("HTTP/1.1 302 Found");
    header(sprintf("Location: %s", $config['base_uri']));
    exit;
} catch (AuthorizeException $e) {
    // this exception is thrown by Callback when the OAuth server returns a
    // specific error message for the client, e.g.: the user did not authorize
    // the request
    die(sprintf("ERROR: %s, DESCRIPTION: %s", $e->getMessage(), $e->getDescription()));
} catch (Exception $e) {
    // other error, these should never occur in the normal flow
    die(sprintf("ERROR: %s", $e->getMessage()));
}
