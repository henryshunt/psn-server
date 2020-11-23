<?php
require_once "vendor/autoload.php";
require_once "php/helpers.php";
require_once "php/response.php";
require_once "php/endpoint.php";

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

$user = api_authenticate($pdo);


$router = new AltoRouter();
$router->setBasePath($_SERVER["SCRIPT_NAME"]);
$router->addMatchTypes(["mac" => "([a-f0-9]{2}:){5}[a-f0-9]{2}"]);

$router->map("GET", "/nodes", "nodes/get.php+EndpointNodesGet");
$router->map("POST", "/nodes", "nodes/post.php+EndpointNodesPost");
$router->map("GET", "/nodes/[i:nodeId]", "nodes/node/get.php+EndpointNodeGet");
$router->map("GET", "/nodes/[mac:macAddress]", "nodes/node/get.php+EndpointNodeGet");
$router->map("PATCH", "/nodes/[i:nodeId]", "nodes/node/patch.php+EndpointNodePatch");
$router->map("DELETE", "/nodes/[i:nodeId]", "nodes/node/delete.php+EndpointNodeDelete");

$router->map("GET", "/projects", "projects/get.php+EndpointProjectsGet");
$router->map("POST", "/projects", "projects/post.php+EndpointProjectsPost");
$router->map("GET", "/projects/[i:projectId]",
    "projects/project/get.php+EndpointProjectGet");
$router->map("PATCH", "/projects/[i:projectId]",
    "projects/project/patch.php+EndpointProjectPatch");
$router->map("DELETE", "/projects/[i:projectId]",
    "projects/project/delete.php+EndpointProjectDelete");

$router->map("GET", "/projects/[i:projectId]/nodes",
    "projects/project/nodes/get.php+EndpointProjectNodesGet");
$router->map("POST", "/projects/[i:projectId]/nodes",
    "projects/project/nodes/post.php+EndpointProjectNodesPost");
$router->map("GET", "/projects/[i:projectId]/nodes/[i:nodeId]",
    "projects/project/nodes/node/get.php+EndpointProjectNodeGet");
$router->map("PATCH", "/projects/[i:projectId]/nodes/[i:nodeId]",
    "projects/project/nodes/node/patch.php+EndpointProjectNodePatch");
$router->map("DELETE", "/projects/[i:projectId]/nodes/[i:nodeId]",
    "projects/project/nodes/node/delete.php+EndpointProjectNodeDelete");

$router->map("GET", "/projects/[i:projectId]/nodes/[i:nodeId]/reports",
    "projects/project/nodes/node/reports/get.php+EndpointProjectNodeReportsGet");
$router->map("POST", "/projects/[i:projectId]/nodes/[mac:macAddress]/reports",
    "projects/project/nodes/node/reports/post.php+EndpointProjectNodeReportsPost");


$match = $router->match();

if ($match)
{
    $target = explode("+", $match["target"]);
    require_once "php/endpoints/" . $target[0];

    $endpoint = new $target[1]($pdo, $user, $match["params"]);
    api_respond($endpoint->response());
}
else api_respond(new Response(404));