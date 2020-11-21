<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodeGet extends Endpoint
{
    public function response() : Response
    {
        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        if (keyExistsMatches("project", "true", $this->urlParams) &&
            !$this->user["privNodes"])
        {
            return (new Response(403))->setError(
                "Only privileged users can read the project info for a node");
        }

        return $this->readNode($this->generateSql());
    }

    private function validateUrlParams() : Response
    {
        $validator = V::key("project", V::in(["true", "false"], true), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        if (array_key_exists("macAddress", $this->resParams))
            $this->resParams["macAddress"] = strtolower($this->resParams["macAddress"]);

        return new Response(200);
    }

    private function readNode(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            if (count($query) === 0)
                return new Response(404);

            if (keyExistsMatches("project", "true", $this->urlParams))
            {
                move_prefixed_keys($query[0], "pn_", "currentProject");

                if ($query[0]["currentProject"]["projectId"] === null)
                    $query[0]["currentProject"] = null;
            }

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
        if (array_key_exists("macAddress", $this->resParams))
        {
            $idOrMac = "macAddress";
            $selectIdOrMac = "nodeId";
        }
        else
        {
            $idOrMac = "nodeId";
            $selectIdOrMac = "macAddress";
        }

        if (keyExistsMatches("project", "true", $this->urlParams))
        {
            $sql = "SELECT
                        n.$selectIdOrMac,
                        n.name,
                        pn.projectId pn_projectId,
                        pn.location pn_location,
                        pn.startedAt pn_startedAt,
                        pn.endAt pn_endAt,
                        pn.interval pn_interval,
                        pn.batchSize pn_batchSize,
                        pn.latestReportId pn_latestReportId
            
                    FROM nodes n
                        LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) pn
                            ON pn.nodeId = n.nodeId
                    
                    WHERE n.$idOrMac = ?";
        }
        else
        {
            $sql = "SELECT
                        $selectIdOrMac,
                        name,
                    FROM nodes
                    
                    WHERE $idOrMac = ?";
        }

        $values = [$this->resParams[$idOrMac]];
        return [$sql, $values];
    }
}