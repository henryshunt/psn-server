<?php
include_once("config.php");

// Uses code from https://github.com/henryshunt/c-aws-server/blob/master/routines/database.php
function database_connection($config)
{
    try
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $host = $config->get_database_host();
        $database = $config->get_database_name();
        $username = $config->get_database_username();
        $password = $config->get_database_password();
        $charset = "utf8mb4";

        $data_source = "mysql:host=$host;dbname=$database;charset=$charset";
        return new PDO($data_source, $username, $password, $options);
    }
    catch (Exception $e) { return false; }
}

function query_database($pdo, $query, $values)
{
    try
    {
        if (starts_with($query, "LOCK") || starts_with($query, "lock") ||
            starts_with($query, "UNLOCK") || starts_with($query, "unlock"))
        {
            $pdo->exec($query);
            return true;
        }
        else
        {
            $db_query = $pdo->prepare($query);
            if (!$db_query) return false;
            $db_query->execute($values);
            if (!$db_query) return false;

            // Fetch the data if we just ran a select query
            if (starts_with($query, "SELECT") || starts_with($query, "select"))
            {
                $result = $db_query->fetchAll();
                return empty($result) ? NULL : $result;
            } else return true;
        }
    }
    catch (Exception $e) { return false; }
}

function starts_with($string, $start)
{
    return substr($string, 0, strlen($start)) === $start;
}