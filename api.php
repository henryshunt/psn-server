<?php
require_once "vendor/autoload.php";
require_once "php/helpers.php";

$config = load_configuration("config.json");
if ($config === false)
    api_respond(500);

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);
}
catch (Exception $ex) { api_respond(500); }


$router = new AltoRouter();
$router->setBasePath($_SERVER["SCRIPT_NAME"]);

$router->map("GET", "/test", "test.php#api_test_get");
$router->map("POST", "/tokens", "test.php#api_tokens_post");
$router->map("GET", "/sessions", "sessions.php#api_sessions_get");
$match = $router->match();

if ($match !== false)
{
    $parts = explode("#", $match["target"]);
    require_once "endpoints/" . $parts[0];

    // Call the target function with the elements in the array as individual arguments
    $response = call_user_func_array($parts[1], $match["params"]);
    api_respond($response[0], $response[1]);
}
else api_respond(404);