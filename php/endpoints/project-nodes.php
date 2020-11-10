<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_project_nodes_get($projectId)
{
    // ----- Validation
    $validator = V
        ::key("mode", V::anyOf(V::identical("active"), V::identical("completed")), false)
        ->key("report", V::anyOf(V::identical("true"), V::identical("false")), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    $sql = "SELECT *";

    if (isset($_GET["report"]) && $_GET["report"] === "true")
    {
        $sql .= ", (SELECT reportId FROM reports WHERE projectId = projectNodes.projectId AND nodeId = projectNodes.nodeId " .
            "ORDER BY time DESC LIMIT 1) AS latestReportId";
    }

    if (isset($_GET["mode"]) && $_GET["mode"] === "active")
    {
        $sql .= " FROM projectNodes WHERE projectId = ? " .
            "AND (startAt > NOW() OR endAt IS NULL OR NOW() BETWEEN startAt AND endAt) ORDER BY location";
    }
    else if (isset($_GET["mode"]) && $_GET["mode"] === "completed")
    {
        $sql .= " FROM projectNodes WHERE projectId = ? " .
            "AND NOT (startAt > NOW() OR endAt IS NULL OR NOW() BETWEEN startAt AND endAt) ORDER BY location";
    }
    else $sql .= " FROM projectNodes WHERE projectId = ?";

    // ----- Query execution
    try
    {
        global $pdo;
        $query = database_query($pdo, $sql, [$projectId]);

        // Get the latest report data for each node
        if (isset($_GET["report"]) && $_GET["report"] === "true")
        {
            $sql = "SELECT * FROM reports WHERE reportId = ?";

            for ($i = 0; $i < count($query); $i++)
            {
                if ($query[$i]["latestReportId"] !== null)
                {
                    $query2 = database_query($pdo, $sql, [$query[$i]["latestReportId"]]);

                    if (count($query2) > 0)
                        $query[$i]["latestReport"] = $query2[0];
                    else $query[$i]["latestReport"] = null;
                }
                else $query[$i]["latestReport"] = null;
                
                unset($query[$i]["latestReportId"]);
            }
        }

        return (new Response(200))->setBody(json_encode($query));
    }
    catch (PDOException $ex)
    {
        return (new Response(500))->setError($ex->getMessage());
    }
}