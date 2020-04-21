<?php
require_once("helpers.php");
require_once("config.php");

$config = new Config();
if (!$config->load_config("../../config.ini"))
    die("Configuration error");
$db_connection = database_connection($config);
if (!$db_connection) die("Database error");

$session = try_loading_session($db_connection);
if ($session === FALSE) die("Session error");
if ($session === NULL)
{
    header("Location: ../../");
    exit();
}


// Delete the login session from the database
$QUERY = "DELETE FROM login_sessions WHERE session_id = ?";
$result = query_database($db_connection, $QUERY, [$_COOKIE["session"]]);
if ($result === FALSE) die("Logout error");

setcookie("session", "", time() - 3600, "/"); // Delete cookie

// If account is on Microsoft Graph then redirect to logout URL
if (strpos($session["user_id"], "@") !== FALSE)
    header("Location: https://login.microsoftonline.com/common/oauth2/v2.0/logout?post_logout_redirect_uri=http%3A%2F%2Flocalhost%2Fpsn-server%2Flogin.php");
else header("Location: ../../login.php");