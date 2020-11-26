<?php
require_once "vendor/autoload.php";
require_once "php/helpers.php";
require_once "php/PreMiddleware.php";
require_once "php/AuthMiddleware.php";

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Routing\RouteCollectorProxy;


$app = AppFactory::create();
$app->setBasePath($_SERVER["SCRIPT_NAME"]);

$twig = Twig::create(__DIR__ . "/views", ["cache" => false]);
$basePath = rtrim(str_ireplace('index.php', '', $_SERVER["SCRIPT_NAME"]), '/');
$twig->getEnvironment()->addGlobal("assets", $basePath . "/resources");
$app->add(TwigMiddleware::create($app, $twig));


$app->group("", function (RouteCollectorProxy $base)
{
    $base->group("/projects", function (RouteCollectorProxy $projects)
    {
        $projects->group("/{projectId}", function (RouteCollectorProxy $project)
        {
            $project->get("", function ($request, $response, $args)
            {
                include_once "controllers/Pages/ProjectController.php";
                return (new \App\Controllers\Pages\ProjectPage())
                    ($request, $response, $args);
            })->setName("project");
        });
        
        $projects->get("", function ($request, $response, $args)
        {
            include_once "controllers/Pages/ProjectsController.php";
            return (new \App\Controllers\Pages\ProjectsPage())
                ($request, $response, $args);
        })->setName("projects");

        $projects->post("", function ($request, $response, $args)
        {
            
        });
    });

})->add(new AuthMiddleware());


$app->group("/auth", function (RouteCollectorProxy $auth)
{
    $auth->group("/login", function (RouteCollectorProxy $login)
    {
        $login->get("", function ($request, $response, $args)
        {
            include_once "controllers/pages/LoginController.php";
            return (new \App\Controllers\Pages\LoginPage())
                ($request, $response, $args);
        })->setName("login");

        $login->post("/internal", function ($request, $response, $args)
        {
            include_once "controllers/actions/InternalLoginAction.php";
            return (new \App\Controllers\Actions\InternalLoginAction())
                ($request, $response, $args);
        });
    });

    $auth->get("/logout", function ($request, $response, $args)
    {
        include_once "controllers/Actions/LogoutAction.php";
        return (new \App\Controllers\Actions\LogoutAction())
            ($request, $response, $args);
    })->setName("logout");
});

$app->add(new PreMiddleware());
$app->addErrorMiddleware(true, true, true);
$app->run();
