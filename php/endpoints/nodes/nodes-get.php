<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodesGet extends Endpoint
{
    public function response() : Response
    {
        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        if (array_key_exists("project", $this->urlParams) &&
            $this->urlParams["project"] === "true" && !$this->user["privNodes"])
        {
            return (new Response(403))->setError(
                "Only privileged users can read the project info for nodes");
        }

        return $this->readNodes($this->generateSql());
    }

    private function validateParams() : Response
    {
        $validator = V
            ::key("project", V::in(["true", "false"], true), false)
            ->key("inactive", V::in(["true", "false"], true), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        return new Response(200);
    }

    private function readNodes(string $sql) : Response
    {
        try
        {
            $query = database_query($this->pdo, $sql);

            if (array_key_exists("project", $this->urlParams) &&
                $this->urlParams["project"] === "true")
            {
                // Ensure each node has a currentProject attribute
                for ($i = 0; $i < count($query); $i++)
                {
                    if (array_key_exists("inactive", $this->urlParams) &&
                        $this->urlParams["inactive"] === "true")
                    {
                        $query[$i]["currentProject"] = null;
                    }
                    else
                    {
                        move_prefixed_keys($query[$i], "pn_", "currentProject");

                        if ($query[$i]["currentProject"]["projectId"] === null)
                            $query[$i]["currentProject"] = null;

                        move_prefixed_keys($query[$i], "r_", "latestReport");

                        if ($query[$i]["latestReport"]["reportId"] === null)
                            $query[$i]["latestReport"] = null;
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

    private function generateSql() : string
    {
        if (array_key_exists("inactive", $this->urlParams) &&
            $this->urlParams["inactive"] === "true")
        {
            $sql = "SELECT
                        nodeId,
                        macAddress,
                        name,
                        createdAt
                    FROM nodes

                    WHERE nodeId NOT IN
                        (SELECT nodeId FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt)";
        }
        else if (array_key_exists("project", $this->urlParams) &&
            $this->urlParams["project"] === "true")
        {
            $sql = "SELECT
                        n.nodeId,
                        n.macAddress,
                        n.name,
                        n.createdAt,
                        pn.projectId pn_projectId,
                        pn.location pn_location,
                        pn.startAt pn_startAt,
                        pn.endAt pn_endAt,
                        pn.interval pn_interval,
                        pn.batchSize pn_batchSize,
                        r.reportId r_reportId,
                        r.time r_time,
                        r.airt r_airt,
                        r.relh r_relh,
                        r.batv r_batv
                    
                    FROM nodes n
                        LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) pn
                            ON pn.nodeId = n.nodeId
                        LEFT JOIN reports r ON r.reportId = pn.latestReportId";
        }
        else
        {
            $sql = "SELECT
                        nodeId,
                        macAddress,
                        name,
                        createdAt
                    FROM nodes";
        }
    
        $sql .= " ORDER BY macAddress";
        return $sql;
    }
}