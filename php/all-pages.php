<?php
require_once __DIR__ . "/helpers.php";

$config = load_configuration(__DIR__ . "/../config.json");
if ($config === false)
    die("Configuration error");

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);
}
catch (Exception $ex)
{
    die("Database error");
}

if (isset($_COOKIE["session"]))
{
    $session = get_login_session($_COOKIE["session"], $pdo);

    if ($session === false)
        die("Session error");
}
else $session = null;

if (isset($loginPage) && $session !== null)
{
    header("Location: index.php");
    exit();
}
else if (!isset($loginPage) && $session === null)
{
    header("Location: login.php");
    exit();
}