<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectsGet extends Endpoint
{
    public function response() : Response
    {
        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->readProjects($this->generateSql());
    }

    private function validateUrlParams() : Response
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

            if (!keyExistsMatches("mode", "active", $this->urlParams))
            {
                for ($i = 0; $i < count($query); $i++)
                {
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
        $sql = "SELECT
                    p.projectId,
                    p.name,
                    p.description,
                    p.createdAt,
                    pn.startAt,
                    pn.endAt,
                    pn.nodeCount
                
                FROM projects p
                    LEFT JOIN (
                        SELECT
                            projectId,
                            MIN(startAt) startAt,
                            MAX(endAt) endAt,
                            COUNT(*) nodeCount

                        FROM projectNodes
                            GROUP BY projectId
                    ) pn ON pn.projectId = p.projectId
                
                WHERE userId = ?";

        if (array_key_exists("mode", $this->urlParams))
        {
            if ($this->urlParams["mode"] === "active")
                $sql .= " AND pn.nodeCount IS NOT NULL AND (pn.endAt IS NULL OR NOW() < pn.endAt)";
            else $sql .= " AND pn.nodeCount IS NULL OR (pn.endAt IS NOT NULL AND NOW() >= pn.endAt)";
        }
        
        $sql .= " ORDER BY p.name";

        $values = [$this->user["userId"]];
        return [$sql, $values];
    }
}