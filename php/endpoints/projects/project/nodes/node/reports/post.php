<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodeReportsPost extends Endpoint
{
    private $jsonParams;
    private $projectNode;

    public function response() : Response
    {
        if (!$this->user["privNodes"])
            return (new Response(403))->setBody("Only privileged users can create reports");

        $validation = $this->checkProjectNodeExists();
        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->validateJsonParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->createReport();
    }

    private function checkProjectNodeExists() : Response
    {
        try
        {
            $this->pdo->exec("START TRANSACTION");

            $sql = "SELECT nodeId, startedAt, endAt FROM projectNodes
                        WHERE projectId = ? AND nodeId =
                            (SELECT nodeId FROM nodes WHERE macAddress = ?) FOR UPDATE";

            $query = database_query($this->pdo, $sql,
                [$this->resParams["projectId"], $this->resParams["macAddress"]]);

            if (count($query) > 0)
            {
                $this->projectNode = $query[0];
                return new Response(200);
            }
            else return new Response(404);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function validateJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = filter_keys((array)$json, ["time", "airt", "relh", "batv"]);

        if (count($json) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("time", V::stringType()->dateTime("Y-m-d H:i:s"))
            ->key("airt", V::anyOf(V::nullType(), V::floatType()))
            ->key("relh", V::anyOf(V::nullType(), V::floatType()))
            ->key("batv", V::anyOf(V::nullType(), V::floatType()));

        try { $validator->check($json); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        $this->jsonParams = $json;
        return $this->checkReportTimeInRange();
    }

    private function checkReportTimeInRange() : Response
    {
        $startedAt = DateTime::
            createFromFormat("Y-m-d H:i:s", $this->projectNode["startedAt"]);

        if ($this->projectNode["endAt"] !== null)
            $endAt = DateTime::createFromFormat("Y-m-d H:i:s", $this->projectNode["endAt"]);
        else $endAt = null;

        $reportTime = DateTime::createFromFormat("Y-m-d H:i:s", $this->jsonParams["time"]);

        if (new DateTime() < $startedAt)
            return (new Response(400))->setError("time older than node start time");
        else if ($endAt !== null && new DateTime() >= $endAt)
            return (new Response(400))->setError("time newer than node end time");
        else return new Response(200);
    }

    private function createReport() : Response
    {
        try
        {
            $values = $this->jsonParams;
            $values["projectId"] = $this->resParams["projectId"];
            $values["nodeId"] = $this->projectNode["nodeId"];

            $sql = "INSERT INTO reports " . sql_insert_string(array_keys($values));
            database_query($this->pdo, $sql, array_values($values));
            
            $sql = "UPDATE projectNodes SET latestReportId = ? WHERE projectId = ? AND nodeId = ?";
            
            database_query($this->pdo, $sql, [$this->pdo->lastInsertId(),
                $this->resParams["projectId"], $this->projectNode["nodeId"]]);

            $this->pdo->exec("COMMIT");

            return (new Response(200))->setBody(["reportId" => $this->pdo->lastInsertId()]);
        }
        catch (PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1062 &&
                strpos($ex->errorInfo[2], "for key 'nodeId'") !== false)
            {
                return (new Response(400))->setError("time is not unique within node");
            }
            else
            {
                error_log($ex);
                return new Response(500);
            }
        }
    }
}