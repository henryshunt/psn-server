<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

const SESSION_TOKEN_LENGTH = 64;
const SESSION_EXPIRE_AFTER = 3600;
const SESSION_COOKIE_NAME = "psn-token";
const SESSION_COOKIE_PATH = "/";

const MYSQL_MAX_INT = 2147483647;
const MYSQL_MAX_TINYINT = 127;


function load_configuration($file_path)
{
    $string = file_get_contents($file_path);
    if ($string === false)
        return false;

    $json = json_decode($string);

    if (gettype($json) === "object")
        return (array)$json;
    else return false;
}


/**
 * Finalises the response to an API request. Outputs the body, sets the headers and status code,
 * and terminates the script.
 * @param object $response - A Response object, which contains the information needed to finalise
 * the response.
 * @return void
 */
function api_respond(Response $response) : void
{
    header("Content-Type: application/json");

    if ($response->getStatus() === 200 && $response->getBody() !== null)
        echo json_encode($response->getBody());
    else
    {
        $json = ["status" => $response->getStatus()];

        if ($response->getStatus() !== 200 && $response->getError() !== null)
            $json["error"] = $response->getError();

        echo json_encode($json);
    }

    http_response_code($response->getStatus());
    exit();
}

/**
 * Checks whether the request is authenticated. If it is then the authenticated user's information
 * is returned in the Response object.
 * @param object $pdo - The PDO object to access the database with.
 * @return object A Response object indicating the result of the authentication check. If the
 * request is authenticated then the Response body will contain the authenticated user's
 * information in the form of an associative array.
 */
function api_authenticate(PDO $pdo) : Response
{
    // Authenticate via username and password in request
    if (array_key_exists("PHP_AUTH_USER", $_SERVER))
    {
        try
        {
            $sql = "SELECT * FROM users WHERE username = ? AND password = ? LIMIT 1";

            $query = database_query($pdo, $sql,
                [$_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]]);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    // Authenticate via token in request or cookie
    else if (array_key_exists("HTTP_AUTHORIZATION", $_SERVER) ||
        array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
    {
        if (array_key_exists("HTTP_AUTHORIZATION", $_SERVER) &&
            starts_with($_SERVER["HTTP_AUTHORIZATION"], "Bearer "))
        {
            $token = substr($_SERVER["HTTP_AUTHORIZATION"], 7);
        }
        else if (array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
            $token = $_COOKIE[SESSION_COOKIE_NAME];
        else return new Response(401);

        try
        {
            $sql = "SELECT * FROM users WHERE userId =
                        (SELECT userId FROM tokens WHERE token = ?) LIMIT 1";

            $query = database_query($pdo, $sql, [$token]);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
    else return new Response(401);

    
    if (count($query) > 0)
    {
        $query[0]["privNodes"] = (bool)$query[0]["privNodes"];
        $query[0]["privUsers"] = (bool)$query[0]["privUsers"];
        return (new Response(200))->setBody($query[0]);
    }
    else return new Response(401);
}

/**
 * Gets a single node from the database.
 * @param object $pdo - The PDO object to access the database with.
 * @param int|string $nodeId - The ID of the node to get.
 * @return array|null The node if it exists, otherwise null.
 */
function api_get_node(PDO $pdo, $nodeId) : array
{
    $sql = "SELECT * FROM nodes WHERE nodeId = ? LIMIT 1";
    $query = database_query($pdo, $sql, [$nodeId]);
    return count($query) > 0 ? $query[0] : null;
}

/**
 * Gets a single project from the database.
 * @param object $pdo - The PDO object to access the database with.
 * @param int|string $projectId - The ID of the project to get.
 * @return array|null The project if it exists, otherwise null.
 */
function api_get_project(PDO $pdo, $projectId) : array
{
    $sql = "SELECT * FROM projects WHERE projectId = ? LIMIT 1";
    $query = database_query($pdo, $sql, [$projectId]);
    return count($query) > 0 ? $query[0] : null;
}

/**
 * Gets a single projectNode from the database.
 * @param object $pdo - The PDO object to access the database with.
 * @param int|string $projectId - The ID of the project to get the projectNode for.
 * @param int|string $nodeId - The ID of the node to get the projectNode for.
 * @return array|null The projectNode if it exists, otherwise null.
 */
function api_get_project_node(PDO $pdo, $projectId, $nodeId) : array
{
    $sql = "SELECT * FROM projectNodes WHERE projectId = ? AND nodeId = ? LIMIT 1";
    $query = database_query($pdo, $sql, [$projectId, $nodeId]);
    return count($query) > 0 ? $query[0] : null;
}

/**
 * Checks whether a project exists and whether a user can access it.
 * @param object $pdo - The PDO object to access the database with.
 * @param int|string $projectId - The ID of the project to check.
 * @param int $userId - The ID of the user you want to check can access the project.
 * @return object A Response object indicating the result of the check.
 */
function checkProjectAccess(PDO $pdo, $projectId, $userId) : Response
{
    try
    {
        $project = api_get_project($pdo, $projectId);

        if ($project === null)
            return new Response(404);
        else if ($project["userId"] !== $userId)
            return new Response(403);
        else return new Response(200);
    }
    catch (PDOException $ex)
    {
        error_log($ex);
        return new Response(500);
    }
}


/**
 * Opens a connection to a MySQL database using the provided credentials.
 * @param string $host - The hostname of the database server.
 * @param string $database - The name of the database.
 * @param string $username - The username to connect to the database with.
 * @param string $password - The password to connect to the database with.
 * @throws PDOException if there is any error.
 * @return object The resulting PDO object.
 */
function database_connect(string $host, string $database, string $username, string $password) : PDO
{
    $options =
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $data_source = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    return new PDO($data_source, $username, $password, $options);
}

/**
 * Queries a database and returns the results.
 * @param object $pdo - The PDO object to access the database with.
 * @param string $sql - The SQL query to run. Any values should be replaced with question marks.
 * @param array|null $values (optional) - The values to put into the SQL query. There should be the
 * same number of values as there are question marks in the SQL query.
 * @throws PDOException if there is any error.
 * @return array|boolean The records selected by the query, or true if the query is not a SELECT
 * query.
 */
function database_query(PDO $pdo, string $sql, array $values = null)
{
    $query = $pdo->prepare($sql);
    $query->execute($values);

    // Return the data if a select query was run
    if (starts_with(strtolower($sql), "select"))
        return $query->fetchAll();
    else return true;
}

/**
 * Queries a database and returns the number of affected records.
 * @param object $pdo - The PDO object to access the database with.
 * @param string $sql - The SQL query to run. Any values should be replaced with question marks.
 * @param array|null $values (optional) - The values to put into the SQL query. There should be the
 * same number of values as there are question marks in the SQL query.
 * @throws PDOException if there is any error.
 * @return integer The number of records affected by the query.
 */
function database_query_affected(PDO $pdo, string $sql, array $values = null)
{
    $query = $pdo->prepare($sql);
    $query->execute($values);
    return $query->rowCount();
}


/**
 * Removes the keys from an associative array that are not included in a whitelist.
 * @param array $array - The associative array to remove keys from.
 * @param array $whitelist - The whitelist of allowed keys.
 * @return array The original $array but with all non-whitelisted keys removed.
 */
function filter_keys(array $array, array $whitelist) : array
{
    $finalArray = $array;

    foreach (array_keys($array) as $key)
    {
        if (!in_array($key, $whitelist))
            unset($finalArray[$key]);
    }

    return $finalArray;
}

/**
 * Determines whether a string starts with another string.
 * @param string $string - The string to check inside of.
 * @param string $start - The string to check for at the start.
 * @return boolean true if the string starts with the start string, otherwise false.
 */
function starts_with(string $string, string $start) : bool
{
    return substr($string, 0, strlen($start)) === $start;
}

/**
 * Determines whether a string ends with another string.
 * @param string $string - The string to check inside of.
 * @param string $end - The string to check for at the end.
 * @return boolean true if the string ends with the start string, otherwise false.
 */
function ends_with(string $string, string $end) : bool
{
    if (strlen($end) > 0)
        return substr($string, -strlen($end)) === $end;
    else return true;
}

/**
 * Generates a random alphanumeric (including both letter cases) string of a specific length.
 * @param integer $length - The number of characters in the random string.
 * @return string The generated random string.
 */
function random_string(int $length) : string
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $string = "";

    for ($i = 0; $i < $length; $i++)
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    return $string;
}

/**
 * Generates the "(cols,) VALUES (vals,)" part of an SQL insert query.
 * @param array $array - The columns to include in the SQL query.
 * @return string The generated "(cols,) VALUES (vals,)" part of an SQL insert query, using the
 * columns specified in $array. The values will be relaced with question mark placeholders (for
 * secure insertion using PDO). If $array is empty then an empty string is returned.
 */
function sql_insert_string(array $array) : string
{
    if (count($array) === 0)
        return "";

    $columns = [];

    foreach ($array as $key)
        array_push($columns, sprintf("`%s`", $key));

    $sql = sprintf("(%s) VALUES (%s)",
        join(", ", $columns), join(", ", array_fill(0, count($columns), "?")));

    return $sql;
}

/**
 * Generates the "col = val," part of an SQL update query.
 * @param array $array - The columns to include in the SQL query.
 * @return string The generated "col = val," part of an SQL insert query, using the columns
 * specified in $array. The values will be relaced with question mark placeholders (for secure
 * insertion using PDO). If $array is empty then an empty string is returned.
 */
function sql_update_string(array $array) : string
{
    if (count($array) === 0)
        return "";

    $columns = [];

    foreach ($array as $key)
        array_push($columns, sprintf("`%s` = ?", $key));

    return join(", ", $columns);
}

/**
 * Moves all associative array keys that begin with a specific string into a separate array within
 * the main array. The start string is also removed from the moved keys.
 */
function move_prefixed_keys(&$object, $prefix, $target)
{
    foreach ($object as $key => $value)
    {
        if (starts_with($key, $prefix))
        {
            $object[$target][substr($key, strlen($prefix))] = $value;
            unset($object[$key]);
        }
    }
}

function keyExistsMatches($key, $value, $array)
{
    return array_key_exists($key, $array) && $array[$key] === $value;
}


function error_page($statusCode)
{
    if ($statusCode === 500)
    {
        echo "<html>
                  <head>
                      <title>500 Internal Server Error</title>
                  </head>
                  <body>
                      <h1>500 Internal Server Error</h1>
                  </body
              </html>";
    }

    if ($statusCode === 404)
    {
        echo "<html>
                  <head>
                      <title>404 Not Found</title>
                  </head>
                  <body>
                      <h1>404 Not Found</h1>
                  </body
              </html>";
    }

    http_response_code($statusCode);
    exit();
}

function log2($text)
{
    $myfile = fopen("/mnt/c/users/henry/log.txt", "w");
    fwrite($myfile, $text);
    fclose($myfile);
}

use Slim\Psr7\Response as Response;

function withJson($status, $data = null) : Response
{
    $response = new Response();

    if ($data !== null)
    {
        $response->getBody()->write(json_encode($data));
        $response = $response->withHeader("Content-Type", "application/json");
    }

    return $response->withStatus($status);
}