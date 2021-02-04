<?php
namespace Psn\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpNotFoundException;


class ProjectPatchAction
{
    private $request;
    private $pdo;
    private $user;
    private $resArgs;
    private $jsonArgs;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->pdo = $request->getAttribute("pdo");
        $this->user = $request->getAttribute("user");
        $this->resArgs = $args;

        $this->checkProjectExists();
        $this->validateJsonArgs();
        $this->updateProject();
        return $response;
    }

    private function checkProjectExists(): void
    {
        try
        {
            $sql = "SELECT 1 FROM projects WHERE projectId = ? AND userId = ? LIMIT 1";
            $values = [$this->resArgs["projectId"], $this->user["userId"]];
            $query = database_query($this->pdo, $sql, $values);

            if (count($query) === 0)
                throw new HttpNotFoundException($this->request);
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, $ex->getMessage(), $ex);
        }
    }

    private function validateJsonArgs(): void
    {
        $json = json_decode(file_get_contents("php://input"));
        if (gettype($json) !== "object")
            throw new HttpBadRequestException($this->request, "Invalid JSON object supplied");

        $json = filter_keys((array)$json, ["name", "description"]);
        if (count($json) === 0)
            throw new HttpBadRequestException($this->request, "No JSON attributes supplied");

        $validator = V
            ::key("name", V::stringType()->length(1, 128), false)
            ->key("description", V::anyOf(
                V::nullType(), V::stringType()->length(1, 255)), false);

        try
        {
            $validator->check($json);
            $this->jsonArgs = $json;
        }
        catch (ValidationException $ex)
        {
            throw new HttpBadRequestException($this->request, $ex->getMessage());
        }
    }

    private function updateProject(): void
    {
        try
        {
            $values = $this->jsonArgs;
            $sql = "UPDATE projects SET " . sql_update_string(array_keys($values)) .
                " WHERE projectId = ? LIMIT 1";
            $values["projectId"] = $this->resArgs["projectId"];

            database_query($this->pdo, $sql, array_values($values));
        }
        catch (\PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1062 &&
                strpos($ex->errorInfo[2], "for key 'userId_name'") !== false)
            {
                throw new HttpBadRequestException($this->request, "'name' is not unique within user");
            }
            else
            {
                throw new HttpInternalServerErrorException($this->request, $ex->getMessage(), $ex);
            }
        }
    }
}