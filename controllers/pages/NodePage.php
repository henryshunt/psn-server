<?php
namespace App\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class NodePage
{
    private $request;
    private $response;
    private $args;
    private $pdo;
    private $user;

    private $project;
    private $node;

    public function __invoke(Request $request, Response $response, array $args) : Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        $this->pdo = $this->request->getAttribute("pdo");
        $this->user = $this->request->getAttribute("user");

        $this->readProjectNode();

        $viewData =
        [
            "user" => $this->user,
            "project" => $this->project,
            "node" => $this->node
        ];
        
        return Twig::fromRequest($request)
            ->render($response, "pages/node.twig", $viewData);
    }

    private function readProjectNode(): void
    {
        try
        {
            $sql = $this->readProjectNodeSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            if (count($query) === 0)
                throw new HttpNotFoundException($this->request, $ex);

            $this->project["name"] = "Part of the '" . $query[0]["p_name"] . "' session.";
            $this->node["location"] = $query[0]["location"];


            $startedAt = \DateTime::createFromFormat("Y-m-d H:i:s", $query[0]["startedAt"]);
            $options = "From " . $startedAt->format("d/m/Y H:i");

            if ($query["endAt"] !== null)
            {
                $endAt = \DateTime::createFromFormat("Y-m-d H:i:s", $query[0]["endAt"]);
                $options .= " to " . $endAt->format("d/m/Y H:i");
            }
            else $options .= ", indefinitely";

            $options .= ". Reporting every " . $query[0]["interval"] . " minutes.";
            $this->node["options"] = $options;

            $this->node["isActive"] = (bool)$query[0]["isActive"];
        }
        catch (PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }

    private function readProjectNodeSql(): array
    {
        $sql = "SELECT
                    pn.location,
                    pn.startedAt,
                    pn.endAt,
                    pn.interval,
                    pn.batchSize,
                    (pn.endAt IS NULL OR NOW() < pn.endAt) isActive,
                    p.name p_name,
                    p.description p_description,
                    n.macAddress n_macAddress,
                    n.name n_name

                FROM projectNodes pn
                    LEFT JOIN projects p ON p.projectId = pn.projectId
                    LEFT JOIN nodes n ON n.nodeId = pn.nodeId

                WHERE pn.projectId = ?
                    AND pn.nodeId = ?
                    AND p.userId = ?";

        $values = [$this->args["projectId"], $this->args["nodeId"], $this->user["userId"]];
        return [$sql, $values];
    }
}