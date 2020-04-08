<?php

// Based on https://github.com/henryshunt/c-aws-server/blob/master/routines/config.php
class Config
{
    private $database_host = NULL;
    private $database_name = NULL;
    private $database_username = NULL;
    private $database_password = NULL;

    private $admin_password = NULL;
    private $guest_password = NULL;
    private $oauth_client_id = NULL;
    private $oauth_client_secret = NULL;
    private $oauth_redirect_url = NULL;
    private $oauth_authorise_url = NULL;
    private $oauth_access_token_url = NULL;
    private $oauth_resource_owner_url = NULL;
    private $oauth_scopes = NULL;
    private $session_timeout = NULL;


    function load_config($config_file)
    {
        $config = parse_ini_file($config_file);
        if (!$config) return FALSE;

        $this->database_host = $config["host"] === "" ? NULL : $config["host"];
        $this->database_name = $config["name"] === "" ? NULL : $config["name"];
        $this->database_username = $config["username"] === "" ? NULL : $config["username"];
        $this->database_password = $config["password"] === "" ? NULL : $config["password"];

        $this->admin_password =
            $config["admin_password"] === "" ? NULL : $config["admin_password"];
        $this->guest_password =
            $config["guest_password"] === "" ? NULL : $config["guest_password"];
        $this->oauth_client_id =
            $config["oauth_client_id"] === "" ? NULL : $config["oauth_client_id"];
        $this->oauth_client_secret =
            $config["oauth_client_secret"] === "" ? NULL : $config["oauth_client_secret"];
        $this->oauth_redirect_url =
            $config["oauth_redirect_url"] === "" ? NULL : $config["oauth_redirect_url"];
        $this->oauth_authorise_url =
            $config["oauth_authorise_url"] === "" ? NULL : $config["oauth_authorise_url"];
        $this->oauth_access_token_url = $config["oauth_access_token_url"] === "" ?
            NULL : $config["oauth_access_token_url"];
        $this->oauth_resource_owner_url = $config["oauth_resource_owner_url"] === "" ?
            NULL : $config["oauth_resource_owner_url"];
        $this->oauth_scopes =
            $config["oauth_scopes"] === "" ? NULL : $config["oauth_scopes"];
        $this->session_timeout = $config["session_timeout"];

        return $this->validate();
    }

    private function validate()
    {
        if ($this->database_host === NULL ||
            $this->database_name === NULL ||
            $this->database_username === NULL ||
            $this->database_password === NULL ||
            $this->admin_password === NULL ||
            $this->guest_password === NULL ||
            $this->oauth_client_id === NULL ||
            $this->oauth_client_secret === NULL ||
            $this->oauth_redirect_url === NULL ||
            $this->session_timeout === NULL)
            { return FALSE; }

        return TRUE;
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

    function get_admin_password()
    {
        return $this->admin_password;
    }

    function get_guest_password()
    {
        return $this->guest_password;
    }

    function get_oauth_client_id()
    {
        return $this->oauth_client_id;
    }

    function get_oauth_client_secret()
    {
        return $this->oauth_client_secret;
    }

    function get_oauth_redirect_url()
    {
        return $this->oauth_redirect_url;
    }

    function get_oauth_authorise_url()
    {
        return $this->oauth_authorise_url;
    }

    function get_oauth_access_token_url()
    {
        return $this->oauth_access_token_url;
    }

    function get_oauth_resource_owner_url()
    {
        return $this->oauth_resource_owner_url;
    }

    function get_oauth_scopes()
    {
        return $this->oauth_scopes;
    }

    function get_session_timeout()
    {
        return $this->session_timeout;
    }
}