<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodeReportsGet extends Endpoint
{
    public function response() : Response
    {
        $validation = checkProjectAccess(
            $this->pdo, $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->checkProjectNodeExists();
        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->readReports($this->generateSql());
    }

    private function checkProjectNodeExists() : Response
    {
        try
        {
            $projectNode = api_get_project_node($this->pdo,
                $this->resParams["projectId"], $this->resParams["nodeId"]);

            if ($projectNode === null)
                return new Response(404);
            else return new response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function validateUrlParams() : Response
    {
        $validator = V
            ::key("mode", V::in(["latest", "chart"], true), true)
            ->key("limit", V::digit()->min(1)->max(MYSQL_MAX_INT), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        if (keyExistsMatches("mode", "latest", $this->urlParams) &&
            !array_key_exists("limit", $this->urlParams))
        {
            return (new Response(400))->setError("limit is required when mode=latest");
        }

        return new Response(200);
    }

    private function readReports($data) : Response
    {
        try
        {
            $query = database_query($this->pdo, $data[0], $data[1]);
            return (new response(200))->setBody($query);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function generateSql() : array
    {
        if (keyExistsMatches("mode", "chart", $this->urlParams))
        {
            $test = $this->urlParams["columns"];
            $sql = "SELECT
                        time AS x,
                        $test AS y

                    FROM reports
                        WHERE projectId = ? AND nodeId = ?
                    LIMIT 720";

            $values = [$this->resParams["projectId"], $this->resParams["nodeId"]];
        }
        else
        {
            $sql = "SELECT
                        reportId,
                        time,
                        airt,
                        relh,
                        batv

                    FROM reports
                        WHERE projectId = ? AND nodeId = ?
                    ORDER BY time DESC LIMIT ?";

            $values = [$this->resParams["projectId"], $this->resParams["nodeId"],
                $this->urlParams["limit"]];
        }

        return [$sql, $values];
    }
}