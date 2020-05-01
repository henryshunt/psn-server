<?php

/**
 * Attempts to open a connection to the database specified in the configuration.
 * 
 * @param Config $config Configuration object from which the database information will be taken.
 * @return PDO Returns a PDO connection object or FALSE if there was an error.
 */
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

/**
 * Queries the database and returns the result.
 * 
 * @param PDO $db_connection PDO connection object to use.
 * @param string $query The query to run on the database, with placeholders for values.
 * @param array $values The values to replace the query placeholders with.
 * @return array Returns an associative array of the results, FALSE if there was an error,
 * TRUE if the query succeeds but does not return a result (e.g. for a DELETE query),
 * or NULL if an empty array of records were returned.
 */
function query_database($db_connection, $query, $values)
{
    try
    {
        if (starts_with($query, "LOCK") || starts_with($query, "lock") ||
            starts_with($query, "UNLOCK") || starts_with($query, "unlock"))
        {
            $db_connection->exec($query);
            return TRUE;
        }
        else
        {
            $db_query = $db_connection->prepare($query);
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

/**
 * Checks if a string starts with another string.
 * 
 * @param string $string The string to check the start of.
 * @param string $start The string to check for at the start of $string.
 * @return boolean Returns a boolean indicating whether the string starts with the
 * $start string.
 */
function starts_with($string, $start)
{
    return substr($string, 0, strlen($start)) === $start;
}


/**
 * Checks if there is a valid existing login session and returns it if so.
 * 
 * @param PDO $db_connection Connection to use to access the database.
 * @return array Returns the login session record from the database, NULL if there
 * is no record or FALSE if there was an error.
 */
function try_loading_session($db_connection)
{
    if (isset($_COOKIE["session"]))
        return get_login_session($_COOKIE["session"], $db_connection);
    else return NULL;
}

/**
 * Queries the database to retrieve the login session with a specific session ID.
 * 
 * @param string $session_id ID of the session to look for.
 * @param PDO $pdo Connection to access the database with.
 * @return array Returns the login session, NULL if no session was found, or FALSE
 * if there was an error.
 */
function get_login_session($session_id, $db_connection)
{
    $QUERY = "SELECT user_id FROM login_sessions WHERE session_id = ?";
    $result = query_database($db_connection, $QUERY, [$session_id]);

    if ($result === FALSE || $result === NULL)
        return $result;
    else return $result[0];
}

/**
 * Creates a new login session for a user.
 * 
 * @param PDO $db_connection Connection to use for accessing the database.
 * @param string $user_id ID of the user to create the login session for.
 * @param int $session_timeout Number of hours to sutomatically end the session after.
 * @return boolean Returns a boolean indicating success or failure.
 */
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

/**
 * Generates a random alphanumeric string of a specific length.
 * Taken from https://stackoverflow.com/questions/5444877/generating-a-unique-random-string-of-a-certain-length-and-restrictions-in-php
 * 
 * @param int $length Length of the random string.
 * @return string Returns a random alphanumeric string of the specified length
 */
function get_random_string($length)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $string = "";

    for ($i = 0; $i < $length; $i++)
        $string .= $characters[mt_rand(0, strlen($characters) - 1)];
    return $string;
}