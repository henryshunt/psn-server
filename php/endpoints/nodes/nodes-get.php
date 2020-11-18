<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodesGet
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

            // Ensure each node has a currentProject attribute
            if (isset($_GET["project"]) && $_GET["project"] === "true")
            {
                for ($i = 0; $i < count($query); $i++)
                {
                    // If inactive then there aren't any keys to move, so just set to null
                    if (isset($_GET["inactive"]) && $_GET["inactive"] === "true")
                        $query[$i]["currentProject"] = null;
                    else
                    {
                        // Move the keys from the projectNodes table into a currentProject sub-object
                        foreach ($query[$i] as $key => $value)
                        {
                            if (starts_with($key, "b_"))
                            {
                                $query[$i]["currentProject"][substr($key, 2)] = $value;
                                unset($query[$i][$key]);
                            }
                        }

                        if ($query[$i]["currentProject"]["projectId"] === null)
                            $query[$i]["currentProject"] = null;
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
            $sql = "SELECT * FROM nodes WHERE nodeId NOT IN
                    (SELECT nodeId FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt)";
        }
        else if (array_key_exists("project", $this->urlParams) &&
            $this->urlParams["project"] === "true")
        {
            $sql = "SELECT nodes.*, projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                        `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                    FROM nodes
                        LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                            ON b.nodeId = nodes.nodeId";
        }
        else $sql = "SELECT * FROM nodes";
    
        $sql .= " ORDER BY macAddress";
        return $sql;
    }
}