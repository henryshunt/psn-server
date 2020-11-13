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
$router->addMatchTypes(["mac" => "([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}"]);

$router->map("GET", "/nodes", "nodes.php/api_nodes_get");
$router->map("POST", "/nodes", "nodes.php/api_nodes_post");
$router->map("GET", "/nodes/[i:nodeId]", "node.php/api_node_get");
$router->map("GET", "/nodes/[mac:macAddress]", "node.php/api_node_mac_get");
$router->map("PATCH", "/nodes/[i:nodeId]", "node.php/api_node_patch");
$router->map("DELETE", "/nodes/[i:nodeId]", "node.php/api_node_delete");

$router->map("GET", "/projects", "projects.php/api_projects_get");
$router->map("POST", "/projects", "projects.php/api_projects_post");
$router->map("GET", "/projects/[i:projectId]", "project.php/api_project_get");
$router->map("PATCH", "/projects/[i:projectId]", "project.php/api_project_patch");
$router->map("DELETE", "/projects/[i:projectId]", "project.php/api_project_delete");

$router->map("GET", "/projects/[i:projectId]/nodes", "project-nodes.php/api_project_nodes_get");
$router->map("POST", "/projects/[i:projectId]/nodes", "project-nodes.php/api_project_nodes_post");
$router->map("GET",
    "/projects/[i:projectId]/nodes/[i:nodeId]", "project-node.php/api_project_node_get");
$router->map("PATCH",
    "/projects/[i:projectId]/nodes/[i:nodeId]", "project-node.php/api_project_node_patch");
$router->map("DELETE",
    "/projects/[i:projectId]/nodes/[i:nodeId]", "project-node.php/api_project_node_delete");

$router->map("POST", "/projects/[i:projectId]/nodes/[mac:macAddress]/reports",
    "project-node-reports.php/api_project_node_reports_post");

$match = $router->match();

if ($match)
{
    $target = explode("/", $match["target"]);
    require_once "php/endpoints/" . $target[0];

    // Validate any ID parameters in the URL and convert them to integers
    foreach ($match["params"] as $key => $value)
    {
        if (ends_with($key, "Id"))
        {
            if ((int)$value >= 0 && (int)$value <= MYSQL_MAX_INT)
                $match["params"][$key] = (int)$value;
            else api_respond(new Response(404));
        }
    }

    // Call the target function with the elements in the array as arguments
    $response = call_user_func_array($target[1], $match["params"]);

    api_respond($response);
}
else api_respond(new Response(404));