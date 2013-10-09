<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

use fkooman\OAuth\Client\ClientConfig;
use fkooman\OAuth\Client\SessionStorage;
use fkooman\OAuth\Client\Api;
use fkooman\OAuth\Client\Context;
use fkooman\OAuth\Client\Scope;

use fkooman\Guzzle\Plugin\BearerAuth\BearerAuth;
use fkooman\Guzzle\Plugin\BearerAuth\Exception\BearerErrorResponseException;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Log\MessageFormatter;
use Guzzle\Http\Client;

$clientConfig = new ClientConfig($config['client']);

$tokenStorage = new SessionStorage();

$log = new Logger('php-voot-client');
$log->pushHandler(new StreamHandler($config['log_file'], Logger::DEBUG));
$logPlugin = new LogPlugin(new PsrLogAdapter($log), MessageFormatter::DEBUG_FORMAT);

$httpClient = new Client();
$httpClient->addSubscriber($logPlugin);

$api = new Api("php-voot-client", $clientConfig, $tokenStorage, $httpClient);

$context = new Context(
    "john.doe@example.org",
    new Scope($config['scope'])
);

$accessToken = $api->getAccessToken($context);
if (false === $accessToken) {
    /* no valid access token available, go to authorization server */
    header("HTTP/1.1 302 Found");
    header("Location: " . $api->getAuthorizeUri($context));
    exit;
}

try {
    $client = new Client();
    $client->addSubscriber($logPlugin);
    $bearerAuth = new BearerAuth($accessToken->getAccessToken());
    $client->addSubscriber($bearerAuth);
    $response = $client->get($config['api_uri'])->send();
    header("Content-Type: application/json");
    echo $response->getBody();
} catch (BearerErrorResponseException $e) {
    if ("invalid_token" === $e->getBearerReason()) {
        // the token we used was invalid, possibly revoked, we throw it away
        $api->deleteAccessToken($context);
        $api->deleteRefreshToken($context);
        /* no valid access token available, go to authorization server */
        header("HTTP/1.1 302 Found");
        header("Location: " . $api->getAuthorizeUri($context));
        exit;
    }
    throw $e;
} catch (Exception $e) {
    die(sprintf('ERROR: %s', $e->getMessage()));
}
