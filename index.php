<?php
require_once "vendor/autoload.php";
require_once "php/autoload.php";
require_once "php/helpers.php";

use Psn\Middleware\PreMiddleware;
use Psn\Middleware\AuthMiddleware;
use Psn\Middleware\ActionMiddleware;


$app = \Slim\Factory\AppFactory::create();

$twig = Slim\Views\Twig::create(__DIR__ . "/assets/views", ["cache" => false]);
$twig->getEnvironment()->addGlobal("assets", "/assets");
$app->add(Slim\Views\TwigMiddleware::create($app, $twig));


// These routes resolve to views (i.e. they are "pages")
$app->group("/projects", function ($projects)
{
    $projects->group("/{projectId}", function ($project)
    {
        $project->group("/nodes/{nodeId}", function ($node)
        {
            $node->get("", Psn\Controllers\Pages\NodePage::class)->setName("node");
        });

        $project->get("", Psn\Controllers\Pages\ProjectPage::class)->setName("project");
    });
    
    $projects->get("", Psn\Controllers\Pages\ProjectsPage::class)->setName("projects");

})->add(new AuthMiddleware("AUTH_CONT_NAUTH_LOGIN"));


// These routes resolve to actions (API-like, JSON response)
$app->group("", function ($actions)
{
    $actions->group("/projects", function ($projects)
    {
        $projects->group("/{projectId}", function ($project)
        {
            $project->patch("", Psn\Controllers\Actions\ProjectPatchAction::class);
            $project->delete("", Psn\Controllers\Actions\ProjectDeleteAction::class);
        });

        $projects->post("", Psn\Controllers\Actions\ProjectsPostAction::class);

    });

    $actions->group("/nodes/inactive", function ($nodes)
    {
        $nodes->get("", Psn\Controllers\Actions\NodesInactiveGetAction::class);
    });

})->add(new AuthMiddleware("AUTH_CONT_NAUTH_RETURN"))->add(new ActionMiddleware());


// These routes provide the authentication system
$app->group("/auth", function ($auth)
{
    $auth->group("/login", function ($login)
    {
        $login->get("", Psn\Controllers\Pages\LoginPage::class)->setName("login");
        $login->post("/internal", Psn\Controllers\Pages\LoginInternalPage::class);
    })->add(new AuthMiddleware("AUTH_INDEX_NAUTH_CONT"));

    $auth->get("/logout", Psn\Controllers\Pages\LogoutPage::class)->setName("logout")
        ->add(new AuthMiddleware("AUTH_CONT_NAUTH_LOGIN"));
});


$app->add(new PreMiddleware());
$app->addErrorMiddleware(true, true, true);
$app->run();