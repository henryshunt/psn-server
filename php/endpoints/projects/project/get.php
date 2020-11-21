<?php
class EndpointProjectGet extends Endpoint
{
    public function response() : Response
    {
        return $this->readProject($this->generateSql());
    }

    private function readProject(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            if (count($query) > 0)
            {
                // Check the user owns the project
                if ($query[0]["userId"] !== $this->user["userId"])
                    return new Response(403);

                unset($query[0]["userId"]);
                $query[0]["isActive"] = (bool)$query[0]["isActive"];

                if ($query[0]["nodeCount"] === null)
                {
                    $query[0]["nodeCount"] = 0;
                    unset($query[0]["startAt"]);
                    unset($query[0]["endAt"]);
                }

                return (new Response(200))->setBody($query[0]);
            }
            else return new Response(404);
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
                    p.userId,
                    p.name,
                    p.description,
                    p.createdAt,
                    pn.startAt,
                    pn.endAt,
                    pn.nodeCount,
                    (pn.nodeCount IS NOT NULL AND (pn.endAt IS NULL OR NOW() < pn.endAt)) isActive

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
                    
                WHERE p.projectId = ?";

        $values = [$this->resParams["projectId"]];
        return [$sql, $values];
    }
}