<?php
include_once "../php/helpers.php";

if (!array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
{
    header("Location: ../login.php");
    exit();
}

// Remove the cookie first so if there's any error removing it from the
// database, the user will still be logged out
$token = $_COOKIE[SESSION_COOKIE_NAME];
setcookie(SESSION_COOKIE_NAME, "", time() - 1, SESSION_COOKIE_PATH);


$config = load_configuration("../config.json");

if ($config === false)
{
    error_log("Error loading configuration");
    header("Location: ../login.php");
    exit();
}

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);

    $sql = "DELETE FROM tokens WHERE token = ?";
    database_query($pdo, $sql, [$token]);
    
    header("Location: ../login.php");
}
catch (Exception $ex)
{
    error_log($ex);
    header("Location: ../login.php");
}