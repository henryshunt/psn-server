<?php
spl_autoload_register(function ($className)
{
    if (!starts_with($className, "App\\Controllers"))
        return false;

    $className = str_replace("\\", "/", $className);
    $className = str_replace("App/Controllers/", "", $className);

    $segments = explode("/", $className);
    for ($i = 0; $i < count($segments) - 1; $i++)
        $segments[$i] = strtolower($segments[$i]);

    $className = __DIR__ . "/" . join("/", $segments) . ".php";

    if (file_exists($className))
    {
        require_once $className;
        return true;
    }
    else return false;
});