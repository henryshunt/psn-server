<?php
namespace Psn\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpUnauthorizedException;


class ProjectsPostAction
{
    private $request;
    private $pdo;
    private $user;
    private $response;
    private $jsonArgs;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->pdo = $request->getAttribute("pdo");
        $this->user = $request->getAttribute("user");
        $this->response = $response;

        $this->validateJsonArgs();
        return $this->createProject();
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
            ::key("name", V::stringType()->length(1, 128))
            ->key("description", V::anyOf(
                V::nullType(), V::stringType()->length(1, 255)), false);

        try
        {
            $validator->check($json);
            $this->jsonArgs = $json;
        }
        catch (ValidationException $ex)
        {
            throw new HttpBadRequestException($this->request, $ex->getMessage(), $ex);
        }
    }

    private function createProject(): Response
    {
        try
        {
            $values = $this->jsonArgs;
            $values["userId"] = $this->user["userId"];
            $sql = "INSERT INTO projects " . sql_insert_string(array_keys($values));
            database_query($this->pdo, $sql, array_values($values));

            $this->response->getBody()->write(
                json_encode(["projectId" => $this->pdo->lastInsertId()]));
            return $this->response->withHeader("Content-Type", "application/json");
        }
        catch (\PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1452 &&
                strpos($ex->errorInfo[2], "FOREIGN KEY (`userId`)") !== false)
            {
                throw new HttpUnauthorizedException($this->$request, 
                    "user does not exist", $ex);
            }
            else if ($ex->errorInfo[1] === 1062 &&
                strpos($ex->errorInfo[2], "for key 'userId_name'") !== false)
            {
                throw new HttpBadRequestException($this->request,
                    "'name' is not unique within user");
            }
            else throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }
}