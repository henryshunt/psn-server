<?php
namespace Psn\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;


class LoginPage
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        return Twig::fromRequest($request)->render($response, "pages/login.twig");
    }
}