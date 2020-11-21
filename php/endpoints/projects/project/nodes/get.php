<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodesGet extends Endpoint
{
    public function response() : Response
    {
        $validation = checkProjectAccess($this->pdo,
            $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;


        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->readProjectNodes($this->generateSql());
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

    private function readProjectNodes(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            for ($i = 0; $i < count($query); $i++)
            {
                move_prefixed_keys($query[$i], "r_", "latestReport");

                if ($query[$i]["latestReport"]["reportId"] === null)
                    $query[$i]["latestReport"] = null;
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
                    pn.nodeId,
                    pn.location,
                    pn.startAt,
                    pn.endAt,
                    pn.`interval`,
                    pn.batchSize,
                    r.reportId r_reportId,
                    r.time r_time,
                    r.airt r_airt,
                    r.relh r_relh,
                    r.batv r_batv
                
                FROM projectNodes pn
                    LEFT JOIN reports r ON r.reportId = pn.latestReportId
                    
                WHERE pn.projectId = ?";

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