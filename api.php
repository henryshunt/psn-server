<?php
require_once "vendor/autoload.php";
require_once "php/helpers.php";
require_once "php/response.php";

$config = load_configuration("config.json");
if ($config === false)
    api_respond(new Response(500));

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);
}
catch (Exception $ex)
{
    api_respond(new Response(500));
}

$userId = api_authenticate($pdo);

$router = new AltoRouter();
$router->setBasePath($_SERVER["SCRIPT_NAME"]);

$router->map("GET", "/nodes", "nodes.php/api_nodes_get");
$router->map("POST", "/nodes", "nodes.php/api_nodes_post");
$router->map("GET", "/projects", "projects.php/api_projects_get");

$match = $router->match();

if ($match)
{
    $target = explode("/", $match["target"]);
    require_once "php/endpoints/" . $target[0];

    // Call the target function with the elements in the array as arguments
    $response = call_user_func_array($target[1], $match["params"]);

    api_respond($response);
}
else api_respond(new Response(404));