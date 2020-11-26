<?php
namespace App\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class LoginPage
{
    private $request;
    private $response;

    public function __invoke(Request $request, Response $response, array $args) : Response
    {
        $this->request = $request;
        $this->response = $response;
        
        return Twig::fromRequest($request)->render($response, "LoginView.twig");
    }
}