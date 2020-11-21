<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

const SESSION_TOKEN_LENGTH = 64;
const SESSION_EXPIRE_AFTER = 3600;
const SESSION_COOKIE_NAME = "psn-token";
const SESSION_COOKIE_PATH = "/psn-server";

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
 * @param object $response - The Response object to use to finalise the response.
 * @return void
 */
function api_respond($response)
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

function api_authenticate($pdo)
{
    // Authenticate via username and password in request
    if (array_key_exists("PHP_AUTH_USER", $_SERVER))
    {
        try
        {
            // Need to lock the selected user so it can't be deleted
            $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
            
            $query = database_query($pdo, $sql,
                [$_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]]);

            if (count($query) > 0)
            {
                $query[0]["privNodes"] = (bool)$query[0]["privNodes"];
                $query[0]["privUsers"] = (bool)$query[0]["privUsers"];
                return $query[0];
            }
            else api_respond(new Response(403));
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            api_respond(new Response(500));
        }
    }

    // Authenticate via session token in request or cookie
    else if (array_key_exists("Authorization", apache_request_headers()) ||
        array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
    {
        if (array_key_exists("Authorization", apache_request_headers()) &&
            starts_with(apache_request_headers()["Authorization"], "Bearer "))
        {
            $token = substr(apache_request_headers()["Authorization"], 7);
        }
        else if (array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
            $token = $_COOKIE[SESSION_COOKIE_NAME];
        else api_respond(new Response(401));

        try
        {
            // Need to lock the selected user so it can't be deleted
            $sql = "SELECT * FROM users WHERE userId =
                        (SELECT userId FROM tokens WHERE token = ?)";

            $query = database_query($pdo, $sql, [$token]);

            if (count($query) > 0)
            {
                $query[0]["privNodes"] = (bool)$query[0]["privNodes"];
                $query[0]["privUsers"] = (bool)$query[0]["privUsers"];
                return $query[0];
            }
            else api_respond(new Response(403));
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            api_respond(new Response(500));
        }
    }

    else api_respond(new Response(401));
}


/**
 * Opens a connection to a MySQL database using the provided credentials.
 * @param string $host - The hostname of the database server.
 * @param string $database - The name of the database.
 * @param string $username - The username to connect to the database with.
 * @param string $password - The password to connect to the database with.
 * @throws PDOException if there is any error.
 * @return object The PDO connection object.
 */
function database_connect($host, $database, $username, $password)
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
 * @param object $pdo - The PDO connection object.
 * @param string $sql - The SQL query to run. Any values should be replaced with question marks.
 * @param array|null $values (optional) - The values to put into the SQL query. There should be the
 * same number of values as there are question marks in the SQL query.
 * @throws PDOException if there is any error.
 * @return array|boolean The records selected by the query, or true if the query is not a SELECT
 * query.
 */
function database_query($pdo, $sql, $values = null)
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
 * @param object $pdo - The PDO connection object.
 * @param string $sql - The SQL query to run. Any values should be replaced with question marks.
 * @param array|null $values (optional) - The values to put into the SQL query. There should be the
 * same number of values as there are question marks in the SQL query.
 * @throws PDOException if there is any error.
 * @return integer The number of records affected by the query.
 */
function database_query_affected($pdo, $sql, $values = null)
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
function filter_keys($array, $whitelist)
{
    $finalArray = $array;

    foreach (array_keys($array) as $key)
    {
        if (!in_array($key, $whitelist))
            unset($finalArray[$key]);
    }

    return $finalArray;
}


function get_random_string($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $string = "";

    for ($i = 0; $i < $length; $i++)
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    return $string;
}

/**
 * Determines whether a string starts with another string.
 * @param string $string - The string to check inside of.
 * @param string $start - The string to check for at the start.
 * @return boolean true if the string starts with the start string, otherwise false.
 */
function starts_with($string, $start)
{
    return substr($string, 0, strlen($start)) === $start;
}

/**
 * Determines whether a string ends with another string.
 * @param string $string - The string to check inside of.
 * @param string $end - The string to check for at the end.
 * @return boolean true if the string ends with the start string, otherwise false.
 */
function ends_with($string, $end)
{
    if (strlen($end) > 0)
        return substr($string, -strlen($end)) === $end;
    else return true;
}



function api_get_node($pdo, $nodeId)
{
    $sql = "SELECT * FROM nodes WHERE nodeId = ?";
    $query = database_query($pdo, $sql, [$nodeId]);
    return count($query) > 0 ? $query[0] : null;
}

function api_get_project($pdo, $projectId)
{
    $sql = "SELECT * FROM projects WHERE projectId = ?";
    $query = database_query($pdo, $sql, [$projectId]);
    return count($query) > 0 ? $query[0] : null;
}

function api_get_project_node($pdo, $projectId, $nodeId)
{
    $sql = "SELECT * FROM projectNodes WHERE projectId = ? AND nodeId = ?";
    $query = database_query($pdo, $sql, [$projectId, $nodeId]);
    return count($query) > 0 ? $query[0] : null;
}


function sql_update_string($attributes)
{
    $columns = [];

    foreach (array_keys($attributes) as $key)
        array_push($columns, sprintf("`%s` = ?", $key));

    return join(", ", $columns);
}

function sql_insert_string($attributes)
{
    $columns = [];

    foreach (array_keys($attributes) as $key)
        array_push($columns, sprintf("`%s`", $key));

    $sql = sprintf("(%s) VALUES (%s)",
        join(", ", $columns), join(", ", array_fill(0, count($columns), "?")));

    return $sql;
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

    http_response_code($statusCode);
    exit();
}

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

function checkProjectAccess($pdo, $projectId, $userId) : Response
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

function keyExistsMatches($key, $value, $array)
{
    return array_key_exists($key, $array) && $array[$key] === $value;
}