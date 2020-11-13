<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_project_node_get($projectId, $nodeId)
{
    global $pdo;

    // ----- Validation
    $validator = V::key("report", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    $sql = "SELECT location, startAt, endAt, `interval`, batchSize";

    if (isset($_GET["report"]) && $_GET["report"] === "true")
        $sql .= ", reportId b_reportId, time b_time, airt b_airt, relh b_relh, batv b_batv";

    $sql .= ", (endAt IS NULL OR NOW() < endAt) isActive FROM projectNodes ";

    if (isset($_GET["report"]) && $_GET["report"] === "true")
        $sql .= " LEFT JOIN (SELECT * FROM reports) b ON b.reportId = projectNodes.latestReportId";
    
    $sql .= " WHERE projectNodes.projectId = ? AND projectNodes.nodeId = ?";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql, [$projectId, $nodeId]);

        if (count($query) === 0)
            return new Response(404);

        $query[0]["isActive"] = (bool)$query[0]["isActive"];
        
        if (isset($_GET["report"]) && $_GET["report"] === "true")
        {
            // Move the keys from the reports table into a currentReport sub-object
            foreach ($query[0] as $key => $value)
            {
                if (starts_with($key, "b_"))
                {
                    $query[0]["latestReport"][substr($key, 2)] = $value;
                    unset($query[0][$key]);
                }
            }

            if ($query[0]["latestReport"]["reportId"] === null)
                $query[0]["latestReport"] = null;
        }

        return (new Response(200))->setBody(json_encode($query[0]));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_project_node_delete($projectId, $nodeId)
{
    global $pdo;

    try
    {
        $sql = "DELETE FROM projectNodes WHERE projectId = ? AND nodeId = ?";
        $affected = query_database_affected($pdo, $sql, [$projectId, $nodeId]);
        return new Response($affected > 0 ? 200 : 404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}