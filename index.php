<?php
require_once "vendor/autoload.php";
require_once "controllers/autoload.php";
require_once "php/helpers.php";
require_once "php/PreMiddleware.php";
require_once "php/AuthMiddleware.php";
require_once "php/ActionErrorMiddleware.php";


$app = \Slim\Factory\AppFactory::create();

$twig = Slim\Views\Twig::create(__DIR__ . "/views", ["cache" => false]);
$twig->getEnvironment()->addGlobal("assets", "/assets");
$app->add(Slim\Views\TwigMiddleware::create($app, $twig));


// These routes resolve to views (i.e. they are "pages")
$app->group("/projects", function ($projects)
{
    $projects->group("/{projectId}", function ($project)
    {
        $project->group("/nodes/{nodeId}", function ($node)
        {
            $node->get("", App\Controllers\Pages\NodePage::class)->setName("node");
        });

        $project->get("", App\Controllers\Pages\ProjectPage::class)->setName("project");
    });
    
    $projects->get("", App\Controllers\Pages\ProjectsPage::class)->setName("projects");

})->add(new AuthMiddleware(true));


// These routes resolve to actions (API-like, JSON response)
$app->group("/projects", function ($projects)
{
    $projects->post("", App\Controllers\Actions\ProjectsPostAction::class);

})->add(new AuthMiddleware(false))->add(new ActionErrorMiddleware());


// These routes provide the authentication system
$app->group("/auth", function ($auth)
{
    $auth->group("/login", function ($login)
    {
        $login->get("", App\Controllers\Pages\LoginPage::class)->setName("login");
        $login->post("/internal", App\Controllers\Actions\InternalLoginAction::class);
    });

    $auth->get("/logout", App\Controllers\Actions\LogoutAction::class)->setName("logout");
});


$app->add(new PreMiddleware());
$app->addErrorMiddleware(true, true, true);
$app->run();