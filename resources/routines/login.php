<?php
date_default_timezone_set("UTC");
require_once("../../vendor/autoload.php");
require_once("helpers.php");
require_once("config.php");

use League\OAuth2\Client\Provider\GenericProvider;

if (!isset($_GET["type"])) die("URL error");
$config = new Config();
if (!$config->load_config("../../config.ini"))
    die ("Configuration error");
$db_connection = database_connection($config);
if (!$db_connection) die ("Database error");

$session = try_loading_session($db_connection);
if ($session === FALSE) die("Session error");
if ($session !== NULL)
{
    header("Location: ../../");
    exit();
}


switch ($_GET["type"])
{
    // Administrator account has read/write access to everything, plus some additional
    // functions such as adding nodes, etc.
    case "admin":
    {
        if (isset($_GET["password"]) &&
            $_GET["password"] === $config->get_admin_password() &&
            new_login_session($db_connection, "admin", $config->get_session_timeout()))
        {
            header("Location: ../../");
            exit();
        }
        else
        {
            header("Location: ../../login.php");
            exit();
        }

        break;
    }

    // Guest account has read access to everything
    case "guest":
    {
        if (isset($_GET["password"]) &&
            $_GET["password"] === $config->get_guest_password() &&
            new_login_session($db_connection, "guest", $config->get_session_timeout()))
        {
            header("Location: ../../");
            exit();
        }
        else
        {
            header("Location: ../../login.php");
            exit();
        }

        break;
    }

    // Authenticate using the specified OAuth details. For normal users
    case "oauth":
    {
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

        header("Location: " . $oauthClient->getAuthorizationUrl());
        exit();
        break;
    }
    
    default:
    {
        die("URL error");
        break;
    }
}