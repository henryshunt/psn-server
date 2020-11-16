<?php
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
    if (isset($_SERVER["PHP_AUTH_USER"]) === true)
    {
        try
        {
            $sql = "SELECT userId FROM users WHERE username = ? AND password = ?";
            $query = database_query($pdo, $sql, [$_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]]);

            if (count($query) > 0)
                return $query[0]["userId"];
            else api_respond(new Response(401));
        }
        catch (PDOException $ex)
        {
            api_respond(new Response(500));
        }
    }
    else if (isset(apache_request_headers()["Authorization"]) &&
        starts_with(apache_request_headers()["Authorization"], "Bearer "))
    {
        $token = substr(apache_request_headers()["Authorization"], 7);
    }
    else if (isset($_COOKIE["session"]))
        $token = $_COOKIE["session"];
    else api_respond(new Response(401));

    try
    {
        $sql = "SELECT userId FROM tokens WHERE token = ?";
        $query = database_query($pdo, $sql, [$token]);

        if (count($query) === 0)
            api_respond(new Response(401));

        return $query[0]["userId"];
    }
    catch (PDOException $ex)
    {
        api_respond(new Response(500));
    }
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


function get_login_session($token, $pdo)
{
    try
    {
        $sql = "SELECT * FROM users WHERE userId = (SELECT userId FROM tokens WHERE token = ?)";
        $query = database_query($pdo, $sql, [$token]);

        return count($query) === 0 ? null : $query[0];
    }
    catch (PDOException $ex)
    {
        return false;
    }
}


/**
 * Removes the keys from an associative array that are not included in a whitelist.
 * @param array $array - The associative array to remove keys from.
 * @param array $whitelist - The whitelist of allowed keys.
 * @return array The original $array but with all non-whitelisted keys removed.
 */
function filter_attributes_allowed($array, $whitelist)
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