<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectsGet
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

        return $this->readProjects($this->generateSql());
    }

    private function validateParams() : Response
    {
        $validator = V::key("mode", V::in(["active", "completed"], true), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        return new Response(200);
    }

    private function readProjects(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            // If we're not getting the active projects (which always have at least 1 node)
            // then perform some cleanup of the data
            if (!array_key_exists("mode", $this->urlParams) ||
                $this->urlParams["mode"] !== "active")
            {
                for ($i = 0; $i < count($query); $i++)
                {
                    // If we're not also getting the completed projects...
                    if (!array_key_exists("mode", $this->urlParams))
                        $query[$i]["isActive"] = (bool)$query[$i]["isActive"];

                    if ($query[$i]["nodeCount"] === null)
                    {
                        $query[$i]["nodeCount"] = 0;
                        unset($query[$i]["startAt"]);
                        unset($query[$i]["endAt"]);
                    }
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
        $sql = "SELECT projects.projectId, name, description, createdAt, startAt, endAt, nodeCount";

        if (!isset($this->urlParams["mode"]))
            $sql .= ", (nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)) isActive";

        $sql .= " FROM projects
                    LEFT JOIN (SELECT projectId, MIN(startAt) startAt, MAX(endAt) endAt, COUNT(*) nodeCount
                        FROM projectNodes GROUP BY projectId) b
                    ON b.projectId = projects.projectId WHERE userId = ?";

        if (array_key_exists("mode", $this->urlParams))
        {
            if ($this->urlParams["mode"] === "active")
                $sql .= " AND nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)";
            else $sql .= " AND nodeCount IS NULL OR (endAt IS NOT NULL AND NOW() >= endAt)";
        }
        
        $sql .= " ORDER BY name";

        $values = [$this->user["userId"]];
        return [$sql, $values];
    }
}