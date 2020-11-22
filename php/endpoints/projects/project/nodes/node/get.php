<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodeGet extends Endpoint
{
    public function response() : Response
    {
        $validation = checkProjectAccess(
            $this->pdo, $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->readProjectNode($this->generateSql());
    }

    private function readProjectNode(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            if (count($query) === 0)
                return new Response(404);

            $query[0]["isActive"] = (bool)$query[0]["isActive"];
            move_prefixed_keys($query[0], "p_", "project");
            move_prefixed_keys($query[0], "n_", "node");

            return (new Response(200))->setBody($query[0]);
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
                    pn.location,
                    pn.startedAt,
                    pn.endAt,
                    pn.interval,
                    pn.batchSize,
                    (pn.endAt IS NULL OR NOW() < pn.endAt) isActive,
                    p.name p_name,
                    p.description p_description,
                    n.macAddress n_macAddress,
                    n.name n_name

                FROM projectNodes pn
                    LEFT JOIN projects p ON p.projectId = pn.projectId
                    LEFT JOIN nodes n ON n.nodeId = pn.nodeId
                WHERE pn.projectId = ? AND pn.nodeId = ?";

        $values = [$this->resParams["projectId"], $this->resParams["nodeId"]];
        return [$sql, $values];
    }
}