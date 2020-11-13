<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_project_nodes_get($projectId)
{
    global $pdo;

    // ----- Validation
    $validator = V
        ::key("mode", V::in(["active", "completed"], true), false)
        ->key("report", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // Check the project exists
    require_once __DIR__ . "/project.php";
    $project = api_project_get($projectId);

    if ($project->getStatus() !== 200)
        return $projectId;

    // ----- Query generation
    $sql = "SELECT projectNodes.nodeId,
                (SELECT macAddress FROM nodes WHERE nodeId = projectNodes.nodeId) macAddress,
                (SELECT name FROM nodes WHERE nodeId = projectNodes.nodeId) name";

    if (isset($_GET["report"]) && $_GET["report"] === "true")
    {
        $sql .= ", location, startAt, endAt, `interval`, batchSize, reportId b_reportId, time b_time,
                    airt b_airt, relh b_relh, batv b_batv";
    }
    else $sql .= ", location, startAt, endAt, `interval`, batchSize";

    if (!isset($_GET["mode"]))
        $sql .= ", (endAt IS NULL OR NOW() < endAt) isActive";

    $sql .= " FROM projectNodes";

    if (isset($_GET["report"]) && $_GET["report"] === "true")
        $sql .= " LEFT JOIN (SELECT * FROM reports) b ON b.reportId = projectNodes.latestReportId";

    $sql .= " WHERE projectNodes.projectId = ?";

    if (isset($_GET["mode"]) && $_GET["mode"] === "active")
        $sql .= " AND (endAt IS NULL OR NOW() < endAt)";
    else if (isset($_GET["mode"]) && $_GET["mode"] === "completed")
        $sql .= " AND endAt IS NOT NULL AND NOW() >= endAt";

    $sql .= " ORDER BY location";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql, [$projectId]);

        if (!isset($_GET["mode"]))
        {
            for ($i = 0; $i < count($query); $i++)
                $query[$i]["isActive"] = (bool)$query[$i]["isActive"];
        }

        // Ensure each node has a currentReport attribute
        if (isset($_GET["report"]) && $_GET["report"] === "true")
        {
            for ($i = 0; $i < count($query); $i++)
            {
                // Move the keys from the reports table into a currentReport sub-object
                foreach ($query[$i] as $key => $value)
                {
                    if (starts_with($key, "b_"))
                    {
                        $query[$i]["latestReport"][substr($key, 2)] = $value;
                        unset($query[$i][$key]);
                    }
                }

                if ($query[$i]["latestReport"]["reportId"] === null)
                    $query[$i]["latestReport"] = null;
            }
        }

        return (new Response(200))->setBody(json_encode($query));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_project_nodes_post($projectId)
{
    global $pdo;

    // ----- Validation section
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) !== "object")
        return (new Response(400))->setError("Invalid JSON object supplied");

    $json = (array)$json;

    $validator = V
        ::key("nodeId", V::intType()->digit()->min(0)->max(MYSQL_MAX_INT))
        ->key("location", V::stringType()->length(1, 128))
        ->key("endAt", V::stringType()->dateTime("Y-m-d H:i:s"))
        ->key("interval", V::in([1, 2, 5, 10, 15, 20, 30], true))
        ->key("batchSize", V::intType()->digit()->min(0)->max(MYSQL_MAX_TINYINT));

    try { $validator->check($json); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    $json = filter_attributes_allowed($json,
        ["nodeId", "location", "endAt", "interval", "batchSize"]);

    // Check if the node is already active
    require_once __DIR__ . "/node.php";
    $_GET["project"] = "true";
    $node = api_node_get($json["nodeId"]);

    if ($node->getStatus() === 404)
        return (new Response(400))->setError("Node does not exist");
    else if ($node->getStatus() !== 200)
        return $node;
    
    if ($node["currentProject"] !== null)
        return (new Response(400))->setError("Node is already active");

    // ----- Query execution
    try
    {
        $sql = "INSERT INTO projectNodes (projectId, nodeId, location, endAt, `interval`, batchSize)
                    VALUES (?, ?, ?, ?, ?, ?)";
        
        $values = [$projectId, $json["nodeId"], $json["location"], $json["endAt"],
            $json["interval"], $json["batchSize"]];

        $query = database_query($pdo, $sql, $values);
        return new Response(200);
    }
    catch (PDOException $ex)
    {
        if ($ex->errorInfo[1] === 1452 &&
            strpos($ex->errorInfo[2], "FOREIGN KEY (`projectId`)") !== false)
        {
            return (new Response(400))->setError("Project does not exist");
        }
        else if ($ex->errorInfo[1] === 1452 &&
            strpos($ex->errorInfo[2], "FOREIGN KEY (`nodeId`)") !== false)
        {
            return (new Response(400))->setError("Node does not exist");
        }
        else if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'projectId_location'") !== false)
        {
            return (new Response(400))->setError("location is not unique within project");
        }
        else return new Response(500);
    }
}