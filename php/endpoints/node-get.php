<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodeGet
{
    private $pdo;
    private $user;
    private $restParams;
    private $urlParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function response(array $restParams) : Response
    {
        $this->restParams = $restParams;
        $this->urlParams = $_GET;

        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        if (array_key_exists("project", $this->urlParams) &&
            $this->urlParams["project"] === "true" && !$this->user["privNodes"])
        {
            return (new Response(403))->setError(
                "Only privileged users can read the project info for a node");
        }

        return $this->readNode($this->generateSql());
    }

    private function validateParams() : Response
    {
        $validator = V::key("project", V::in(["true", "false"], true), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        return new Response(200);
    }

    private function readNode(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            if (count($query) === 0)
                return new Response(404);

            if (array_key_exists("project", $this->urlParams) &&
                $this->urlParams["project"] === "true")
            {
                // Move the keys from the projectNodes table into a project sub-object
                foreach ($query[0] as $key => $value)
                {
                    if (starts_with($key, "b_"))
                    {
                        $query[0]["currentProject"][substr($key, 2)] = $value;
                        unset($query[0][$key]);
                    }
                }

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
        if (array_key_exists("macAddress", $this->restParams))
            $idOrMac = "macAddress";
        else $idOrMac = "nodeId";

        $sql = "SELECT nodes.*";

        if (array_key_exists("project", $this->urlParams) &&
            $this->urlParams["project"] === "true")
        {
            $sql .= ", projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                        `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                    FROM nodes
                        LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                            ON b.nodeId = nodes.nodeId WHERE nodes.$idOrMac = ?";
        }
        else $sql .= " FROM nodes WHERE $idOrMac = ?";

        $values = [$this->restParams[$idOrMac]];
        return [$sql, $values];
    }
}