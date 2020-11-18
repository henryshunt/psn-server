<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodesGet
{
    private $pdo;
    private $user;
    private $resParams;
    private $urlParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function response(array $resParams) : Response
    {
        $this->resParams = $resParams;
        $this->urlParams = $_GET;

        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->readProjectNodes($this->generateSql());
    }

    private function validateParams() : Response
    {
        $validator = V
            ::key("mode", V::in(["active", "completed"], true), false)
            ->key("report", V::in(["true", "false"], true), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        // Check the project exists and the user owns it
        try
        {
            $project = api_get_project($this->pdo, $this->resParams["projectId"]);

            if ($project === null)
                return new Response(404);
            else if ($project["userId"] !== $this->user["userId"])
                return new Response(403);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }

        return new Response(200);
    }

    private function readProjectNodes(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            if (!array_key_exists("mode", $this->urlParams))
            {
                for ($i = 0; $i < count($query); $i++)
                    $query[$i]["isActive"] = (bool)$query[$i]["isActive"];
            }

            // Ensure each node has a currentReport attribute
            if (array_key_exists("report", $this->urlParams) &&
                $this->urlParams["report"] === "true")
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

            return (new Response(200))->setBody($query);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function generateSql() : array
    {
        $sql = "SELECT projectNodes.nodeId,
                    (SELECT macAddress FROM nodes WHERE nodeId = projectNodes.nodeId) macAddress,
                    (SELECT name FROM nodes WHERE nodeId = projectNodes.nodeId) name,
                    location, startAt, endAt, `interval`, batchSize";

        if (array_key_exists("report", $this->urlParams) &&
            $this->urlParams["report"] === "true")
        {
            $sql .= ", reportId b_reportId, time b_time, airt b_airt, relh b_relh, batv b_batv";
        }

        if (!array_key_exists("mode", $this->urlParams))
            $sql .= ", (endAt IS NULL OR NOW() < endAt) isActive";

        $sql .= " FROM projectNodes";

        if (array_key_exists("report", $this->urlParams) &&
            $this->urlParams["report"] === "true")
        {
            $sql .= " LEFT JOIN (SELECT * FROM reports) b ON b.reportId = projectNodes.latestReportId";
        }

        $sql .= " WHERE projectNodes.projectId = ?";

        if (array_key_exists("mode", $this->urlParams))
        {
            if ($this->urlParams["mode"] === "active")
                $sql .= " AND (endAt IS NULL OR NOW() < endAt)";
            else $sql .= " AND endAt IS NOT NULL AND NOW() >= endAt";
        }

        $sql .= " ORDER BY location";

        $values = [$this->resParams["projectId"]];
        return [$sql, $values];
    }
}