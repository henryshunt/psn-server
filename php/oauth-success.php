<?php
require_once("../../vendor/autoload.php");
require_once("helpers.php");
require_once("config.php");

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;

if (!isset($_GET["code"])) die("URL error");
$config = new Config();
if (!$config->load_config("../../config.ini"))
    die("Configuration error");
$db_connection = database_connection($config);
if (!$db_connection) die("Database error");

$session = try_loading_session($db_connection);
if ($session === FALSE) die("Session error");
if ($session !== NULL)
{
    header("Location: ../../");
    exit();
}


$oauthClient = new GenericProvider(
[
    "clientId" => $config->get_oauth_client_id(),
    "clientSecret" => $config->get_oauth_client_secret(),
    "redirectUri" => $config->get_oauth_redirect_url(),
    "urlAuthorize" => $config->get_oauth_authorise_url(),
    "urlAccessToken" => $config->get_oauth_access_token_url(),
    "urlResourceOwnerDetails" => $config->get_oauth_resource_owner_url(),
    "scopes" => $config->get_oauth_scopes()
]);

try
{
    // Request token to access user data
    $accessToken = $oauthClient->getAccessToken("authorization_code",
        ["code" => $_GET["code"]]);

    // Request user data using access token
    $graph = new Graph();
    $graph->setAccessToken($accessToken->getToken());
    $user = $graph->createRequest("GET", "/me")->setReturnType(User::class)
        ->execute();
    
    // Create new session using user's email address
    new_login_session($db_connection,
        $user->getUserPrincipalName(), $config->get_session_timeout());
    header("Location: ../../");
    exit();
}
catch (IdentityProviderException $e) { die("OAuth error"); }