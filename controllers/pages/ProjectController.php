<?php
namespace App\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class ProjectPage
{
    private $request;
    private $response;
    private $args;

    private $project;
    private $activeNodes = [];
    private $completedNodes = [];

    public function __invoke(Request $request, Response $response, array $args) : Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        $this->readProject($this->generateSql());
        $this->readProjectNodes($this->generateSql2());

        $data = [
            "username" => $this->request->getAttribute("user")["username"],
            "projectId" => $this->args["projectId"],
            "project" => $this->project,
            "activeNodes" => $this->activeNodes,
            "completedNodes" => $this->completedNodes
        ];
        
        return Twig::fromRequest($request)
            ->render($response, "ProjectView.twig", $data);
    }

    private function readProject(array $data)
    {
        $query = database_query($this->request->getAttribute("pdo"), $data[0], $data[1]);

        if (count($query) > 0)
        {
            $this->project["name"] = $query[0]["name"];

            if ($query[0]["description"] !== null)
                $this->project["description"] = $query[0]["description"];
            else $this->project["description"] = "No Description Available";

            $this->project["isActive"] = (bool)$query[0]["isActive"];
        }
    }

    private function readProjectNodes(array $data)
    {
        try
        {
            $query = database_query($this->request->getAttribute("pdo"), $data[0], $data[1]);

            foreach ($query as $node)
            {
                $newNode;
                $newNode["nodeId"] = $node["nodeId"];
                $newNode["location"] = $node["location"];

                if ($node["isActive"] === 1)
                {
                    if ($node["r_reportId"] !== null)
                    {
                        $reportTime = \DateTime::createFromFormat("Y-m-d H:i:s", $node["r_time"]);
                        $newNode["reportTime"] = "Latest Report on " . $reportTime->format('d/m/Y \a\t H:i');

                        if ($node["r_airt"] !== null)
                            $newNode["latestReport"]["airt"] = $node["r_airt"] . "°C";
                        else $newNode["latestReport"]["airt"] = "None";

                        if ($node["r_relh"] !== null)
                            $newNode["latestReport"]["relh"] = $node["r_relh"] . "%";
                        else $newNode["latestReport"]["relh"] = "None";
                    }
                    else
                    {
                        $newNode["reportTime"] = "No Latest Report";
                        $newNode["latestReport"] = null;
                    }
                    
                    array_push($this->activeNodes, $newNode);
                }
                else array_push($this->completedNodes, $newNode);
            }
        }
        catch (PDOException $ex)
        {
            error_log($ex);
        }
    }

    private function generateSql() : array
    {
        $sql = "SELECT
                    p.userId,
                    p.name,
                    p.description,
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
                    
                WHERE p.projectId = ?";

        $values = [$this->args["projectId"]];
        return [$sql, $values];
    }

    private function generateSql2() : array
    {
        $sql = "SELECT
                    pn.nodeId,
                    pn.location,
                    pn.startedAt,
                    pn.endAt,
                    pn.`interval`,
                    pn.batchSize,
                    r.reportId r_reportId,
                    r.time r_time,
                    r.airt r_airt,
                    r.relh r_relh,
                    r.batv r_batv,
                    (endAt IS NULL OR NOW() < endAt) isActive
                
                FROM projectNodes pn
                    LEFT JOIN reports r ON r.reportId = pn.latestReportId
                    
                WHERE pn.projectId = ?
                ORDER BY location";

        $values = [$this->args["projectId"]];
        return [$sql, $values];
    }
}