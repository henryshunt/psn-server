<?php
namespace App\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;

class InternalLoginAction
{
    private $request;
    private $response;

    private $pdo;

    public function __invoke(Request $request, Response $response, array $args) : Response
    {
        $this->request = $request;
        $this->response = $response;
        
        return $this->logIn();
    }

    private function logIn()
    {
        $cookie = FigRequestCookies::get($this->request, SESSION_COOKIE_NAME);

        // if ($cookie !== null)
        //     return $this->redirectToProjects();

        $params = (array)$this->request->getParsedBody();

        if (!array_key_exists("username", $params) || !array_key_exists("password", $params))
            return $this->redirectToLogin("request");

        try
        {
            $sql = "SELECT userId, password FROM users WHERE username = ? LIMIT 1";
            $query = database_query(
                $this->request->getAttribute("pdo"), $sql, [$params["username"]]);

            if (count($query) > 0)
            {
                if ($params["password"] === $query[0]["password"])
                {
                    $token = random_string(SESSION_TOKEN_LENGTH);
                    $expiresAt = time() + SESSION_EXPIRE_AFTER;
                    $expiresAtString = date("Y-m-d H:i:s", $expiresAt);

                    $sql = "INSERT INTO tokens (userId, token, expiresAt) VALUES (?, ?, ?)";
                    $query = database_query($this->request->getAttribute("pdo"), $sql,
                        [$query[0]["userId"], $token, $expiresAtString]);


                    $response = $this->redirectToProjects();
                    $response = FigResponseCookies::set($response,
                        SetCookie::create(SESSION_COOKIE_NAME)->withValue($token)
                            ->withExpires($expiresAt)->withPath(SESSION_COOKIE_PATH));

                    return $response;
                }
                else return $this->redirectToLogin("password");
            }
            else return $this->redirectToLogin("username");
        }
        catch (PDOException $ex)
        {
            return $this->redirectToLogin("internal");
        }
    }

    private function redirectToProjects() : Response
    {
        $url = RouteContext::fromRequest($this->request)->getRouteParser()->urlFor("projects");
        return $this->response->withHeader("Location", $url)->withStatus(302);
    }

    private function redirectToLogin(string $error) : Response
    {
        $url = RouteContext::fromRequest($this->request)->getRouteParser()
            ->urlFor("login", [], ["error" => $error]);
        return $this->response->withHeader("Location", $url)->withStatus(302);
    }
}