<?php

// Uses code from https://github.com/henryshunt/c-aws-server/blob/master/routines/database.php
function database_connection($config)
{
    try
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => FALSE,
        ];

        $host = $config->get_database_host();
        $database = $config->get_database_name();
        $username = $config->get_database_username();
        $password = $config->get_database_password();
        $charset = "utf8mb4";

        $data_source = "mysql:host=$host;dbname=$database;charset=$charset";
        return new PDO($data_source, $username, $password, $options);
    }
    catch (Exception $e) { return FALSE; }
}

// Uses code from https://github.com/henryshunt/c-aws-server/blob/master/routines/database.php
function query_database($pdo, $query, $values)
{
    try
    {
        if (starts_with($query, "LOCK") || starts_with($query, "lock") ||
            starts_with($query, "UNLOCK") || starts_with($query, "unlock"))
        {
            $pdo->exec($query);
            return TRUE;
        }
        else
        {
            $db_query = $pdo->prepare($query);
            if (!$db_query) return FALSE;
            $db_query->execute($values);
            if (!$db_query) return FALSE;

            // Fetch the data if we just ran a select query
            if (starts_with($query, "SELECT") || starts_with($query, "select"))
            {
                $result = $db_query->fetchAll();
                return empty($result) ? NULL : $result;
            } else return TRUE;
        }
    }
    catch (Exception $e) { return FALSE; }
}

function starts_with($string, $start)
{
    return substr($string, 0, strlen($start)) === $start;
}


function try_loading_session($db_connection)
{
    if (isset($_COOKIE["session"]))
        return get_login_session($_COOKIE["session"], $db_connection);
    else return NULL;
}

function get_login_session($session_id, $db_connection)
{
    $QUERY = "SELECT user_id FROM login_sessions WHERE session_id = ?";
    $result = query_database($db_connection, $QUERY, [$session_id]);

    if ($result === FALSE || $result === NULL)
        return $result;
    else return $result[0];
}

function new_login_session($db_connection, $user_id, $session_timeout)
{
    $session_id = get_random_string(16);
    $QUERY = "INSERT INTO login_sessions (session_id, user_id, login_time) VALUES (?, ?, NOW())";
    $result = query_database($db_connection, $QUERY, [$session_id, $user_id]);

    if ($result === TRUE)
    {
        setcookie("session", $session_id, time() + (3600 * $session_timeout), "/");
        return TRUE;
    } else return FALSE;
}

// Taken from https://stackoverflow.com/questions/5444877/generating-a-unique-random-string-of-a-certain-length-and-restrictions-in-php
function get_random_string($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $string = "";

    for ($i = 0; $i < $length; $i++)
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    return $string;
}