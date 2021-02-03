<?php
namespace Psn\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;


class PreMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler) : Response
    {
        $config = load_configuration(__DIR__ . "/../../config.json");

        if ($config === false)
            return (new Response())->withStatus(500);
                
        try
        {
            $pdo = database_connect($config["databaseHost"], $config["databaseName"],
                $config["databaseUsername"], $config["databasePassword"]);
        }
        catch (Exception $ex)
        {
            return (new Response())->withStatus(500);
        }

        return $handler->handle($request->withAttribute("config", $config)
            ->withAttribute("pdo", $pdo));
    }
}