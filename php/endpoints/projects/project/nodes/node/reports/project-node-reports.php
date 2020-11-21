<?php

function api_project_node_reports_post($projectId, $macAddress)
{
    global $pdo;

    // ----- Validation
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) !== "object")
        return (new Response(400))->setError("Invalid JSON object supplied");

    $json = (array)$json;

    $json["time"] = str_replace("T", " ", $json["time"]);
    $json["time"] = str_replace("Z", "", $json["time"]);

    try
    {
        $sql = "SELECT nodeId, startAt, endAt FROM projectNodes
                    WHERE projectId = ? AND nodeId = (SELECT nodeId FROM nodes WHERE macAddress = ?)";

        $query = database_query($pdo, $sql, [$projectId, $macAddress]);

        if (count($query) === 0)
            return new Response(404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }

    $projectStart = date_create_from_format("Y-m-d H:i:s", $query[0]["startAt"]);
    $projectEnd = $query[0]["endAt"];
    if ($projectEnd !== null)
        date_create_from_format("Y-m-d H:i:s", $query[0]["endAt"]);
    $reportTime = date_create_from_format("Y-m-d H:i:s", $json["time"]);

    if ($projectEnd !== null && new DateTime() >= $projectEnd)
        return (new Response(400))->setError("time outside time range of project");

    // ----- Query execution
    try
    {
        $sql = "INSERT INTO reports (projectId, nodeId, time, airt, relh, batv)
            VALUES (?, (SELECT nodeId FROM nodes WHERE macAddress = ?), ?, ?, ?, ?)";

        database_query($pdo, $sql, [$projectId, $macAddress,
            $json["time"], $json["airt"], $json["relh"], $json["batv"]]);

        $sql = "UPDATE projectNodes SET latestReportId = ? WHERE projectId = ? AND nodeId = ?";
        database_query($pdo, $sql, [$pdo->lastInsertId(), $projectId, $query[0]["nodeId"]]);
        return new Response(200);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}