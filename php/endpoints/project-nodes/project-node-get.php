<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodeGet
{
    private $pdo;
    private $user;
    private $urlParams;
    private $resParams;

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

        return $this->readProjectNode($this->generateSql());
    }

    private function validateParams() : Response
    {
        // Check the project exists and the user owns it
        try
        {
            $project = api_get_project($this->pdo, $this->resParams["projectId"]);

            if ($project === null)
                return new Response(404);
            else if ($project["userId"] !== $this->user["userId"])
                return new Response(403);
            else return new Response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function readProjectNode(array $data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);

            if (count($query) === 0)
                return new Response(404);

            $query[0]["isActive"] = (bool)$query[0]["isActive"];
            
            if (array_key_exists("report", $this->urlParams) &&
                $this->urlParams["report"] === "true")
            {
                // Move the keys from the reports table into a currentReport sub-object
                foreach ($query[0] as $key => $value)
                {
                    if (starts_with($key, "b_"))
                    {
                        $query[0]["latestReport"][substr($key, 2)] = $value;
                        unset($query[0][$key]);
                    }
                }

                if ($query[0]["latestReport"]["reportId"] === null)
                    $query[0]["latestReport"] = null;
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
        $sql = "SELECT projectNodes.*";

        if (array_key_exists("report", $this->urlParams) &&
            $this->urlParams["report"] === "true")
        {
            $sql .= ", reportId b_reportId, time b_time, airt b_airt, relh b_relh, batv b_batv";
        }

        $sql .= ", (endAt IS NULL OR NOW() < endAt) isActive FROM projectNodes ";

        if (array_key_exists("report", $this->urlParams) &&
            $this->urlParams["report"] === "true")
        {
            $sql .= " LEFT JOIN (SELECT * FROM reports) b ON b.reportId = projectNodes.latestReportId";
        }
        
        $sql .= " WHERE projectNodes.projectId = ? AND projectNodes.nodeId = ?";

        $values = [$this->resParams["projectId"], $this->resParams["nodeId"]];
        return [$sql, $values];
    }
}