<?php
namespace App\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as IRequest;
use Psr\Http\Message\ResponseInterface as IResponse;
use Slim\Psr7\Response as Response;
use Slim\Views\Twig;
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;


class ProjectsPage
{
    private $request;
    private $response;
    private $pdo;
    private $user;

    private $activeProjects = [];
    private $completedProjects = [];
    private $json;

    public function __invoke(IRequest $request, IResponse $response, array $args) : IResponse
    {
        $this->request = $request;
        $this->response = $response;
        $this->pdo = $this->request->getAttribute("pdo");
        $this->user = $this->request->getAttribute("user");

        if ($request->getMethod() === "GET")
            return $this->get();
        else if ($request->getMethod() === "POST")
            return $this->post();
        else return (new Response())->withStatus(404);
    }


    private function get() : Response
    {
        $response = $this->readProjects();
        if ($response->getStatusCode() !== 200)
            return $response;

        $data =
        [
            "user" => $this->user,
            "activeProjects" => $this->activeProjects,
            "completedProjects" => $this->completedProjects
        ];
        
        return Twig::fromRequest($this->request)
            ->render($this->response, "pages/projects.twig", $data);
    }

    private function readProjects() : Response
    {
        try
        {
            $sql = $this->readProjectsSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            foreach ($query as $project)
            {
                $newProject;
                $newProject["projectId"] = $project["projectId"];
                $newProject["name"] = $project["name"];

                if ($project["nodeCount"] === null)
                    $newProject["nodeCount"] = "0 Sensor Nodes";
                else if ($project["nodeCount"] === 1)
                    $newProject["nodeCount"] = "1 Sensor Node";
                else $newProject["nodeCount"] = $project["nodeCount"] . " Sensor Nodes";

                if ($project["nodeCount"] !== null)
                {
                    $startedAt = \DateTime::createFromFormat("Y-m-d H:i:s", $project["startedAt"]);
                    $newProject["dateRange"] = "From " . $startedAt->format("d/m/Y");

                    if ($project["endAt"] !== null)
                    {
                        $endAt = \DateTime::createFromFormat("Y-m-d H:i:s", $project["endAt"]);
                        $newProject["dateRange"] .= " to " . $endAt->format("d/m/Y");
                    }
                    else $newProject["dateRange"] .= ", indefinitely";
                }
                else $newProject["dateRange"] = "";

                if ($project["isActive"] === 1)
                    array_push($this->activeProjects, $newProject);
                else array_push($this->completedProjects, $newProject);
            }

            return (new Response())->withStatus(200);
        }
        catch (\PDOException $ex)
        {
            return (new Response())->withStatus(500);
        }
    }

    private function readProjectsSql() : array
    {
        $sql = "SELECT
                    p.projectId,
                    p.name,
                    pn.startedAt,
                    pn.endAt,
                    pn.nodeCount,
                    (pn.nodeCount IS NOT NULL AND (pn.endAt IS NULL OR NOW() < pn.endAt)) isActive
                
                FROM projects p
                    LEFT JOIN (
                        SELECT
                            projectId,
                            MIN(startedAt) startedAt,
                            MAX(endAt) endAt,
                            COUNT(*) nodeCount

                        FROM projectNodes
                            GROUP BY projectId
                    ) pn ON pn.projectId = p.projectId
                
                WHERE userId = ?
                ORDER BY p.name";

        $values = [$this->user["userId"]];
        return [$sql, $values];
    }


    private function post() : Response
    {
        $response = $this->loadJson();
        if ($response->getStatusCode() !== 200)
            return $response;

        return $this->createProject();
    }

    private function loadJson() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return withJson(400, ["error" => "Invalid JSON object supplied"]);

        $json = filter_keys((array)$json, ["name", "description"]);

        if (count($json) === 0)
            return withJson(400, ["error" => "No JSON attributes supplied"]);

        $validator = V
            ::key("name", V::stringType()->length(1, 128))
            ->key("description", V::anyOf(
                V::nullType(), V::stringType()->length(1, 255)), false);

        try { $validator->check($json); }
        catch (ValidationException $ex)
        {
            return withJson(400, ["error" => $ex->getMessage()]);
        }

        $this->json = $json;
        return new Response(200);
    }

    private function createProject() : Response
    {
        try
        {
            $values = $this->json;
            $values["userId"] = $this->user["userId"];

            $sql = "INSERT INTO projects " . sql_insert_string(array_keys($values));
            database_query($this->pdo, $sql, array_values($values));

            return withJson(200, ["projectId" => $this->pdo->lastInsertId()]);
        }
        catch (\PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1452 &&
                strpos($ex->errorInfo[2], "FOREIGN KEY (`userId`)") !== false)
            {
                return withJson(401);
            }
            else if ($ex->errorInfo[1] === 1062 &&
                strpos($ex->errorInfo[2], "for key 'name'") !== false)
            {
                return withJson(400, ["error" => "name is not unique within user"]);
            }
            else return withJson(500);
        }
    }
}