<?php

// Based on https://github.com/henryshunt/c-aws-server/blob/master/routines/config.php
class Config
{
    private $database_host = NULL;
    private $database_name = NULL;
    private $database_username = NULL;
    private $database_password = NULL;


    function load_config($config_file)
    {
        $config = parse_ini_file($config_file);
        if (!$config) return false;

        if ($config["host"] != NULL) $this->database_host = $config["host"];
        if ($config["name"] != NULL) $this->database_name = $config["name"];
        if ($config["username"] != NULL) $this->database_username = $config["username"];
        if ($config["password"] != NULL) $this->database_password = $config["password"];

        if (!$this->validate()) return false;
        return true;
    }

    private function validate()
    {
        // Convert empty strings to NULL
        if ($this->get_database_host() == "")
            $this->database_host = NULL;
        if ($this->get_database_name() == "")
            $this->database_name = NULL;
        if ($this->get_database_username() == "")
            $this->database_username = NULL;
        if ($this->get_database_password() == "")
            $this->database_password = NULL;

        // Validate the configuration values
        if ($this->get_database_host() == NULL ||
            $this->get_database_name() == NULL ||
            $this->get_database_username() == NULL ||
            $this->get_database_password() == NULL)
            { return false; }

        return true;
    }


    function get_database_host()
    {
        return $this->database_host;
    }

    function get_database_name()
    {
        return $this->database_name;
    }

    function get_database_username()
    {
        return $this->database_username;
    }

    function get_database_password()
    {
        return $this->database_password;
    }
}