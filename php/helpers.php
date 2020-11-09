<?php

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


function api_respond($status_code, $content = null)
{
    header("Content-Type: application/json");
    
    if ($content === null)
        echo "{\"status_code\":$status_code}";
    else echo $content;

    http_response_code($status_code);
    exit();
}

function api_authenticate($pdo)
{
    if (isset(apache_request_headers()["Authorization"]) === true &&
        starts_with(apache_request_headers()["Authorization"], "Bearer ") === true)
    {
        $token = substr(apache_request_headers()["Authorization"], 7);
        
        try
        {
            $sql = "SELECT user_id FROM tokens WHERE token = ?";
            $query = database_query($pdo, $sql, [$token]);

            if (count($query) === 0)
                api_respond(401, null);

            return $query[0]["user_id"];
        }
        catch (PDOException $ex)
        {
            api_respond(500, null);
        }
    }
    else api_respond(401, null);
}


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

function database_query($pdo, $sql, $values)
{
    $query = $pdo->prepare($sql);
    $query->execute($values);

    if (starts_with(strtolower($sql), "select"))
        return $query->fetchAll();
    else return true;
}


function get_login_session($token, $pdo)
{
    try
    {
        $sql = "SELECT * FROM users WHERE user_id = (SELECT user_id FROM tokens WHERE token = ?)";
        $query = database_query($pdo, $sql, [$token]);

        return count($query) === 0 ? null : $query[0];
    }
    catch (PDOException $ex)
    {
        return false;
    }
}


function get_random_string($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $string = "";

    for ($i = 0; $i < $length; $i++)
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    return $string;
}

function starts_with($string, $start)
{
    return substr($string, 0, strlen($start)) === $start;
}