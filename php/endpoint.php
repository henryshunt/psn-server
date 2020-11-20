<?php
abstract class Endpoint
{
    protected $pdo;
    protected $user;
    protected $resParams;
    protected $urlParams;

    public function __construct(PDO $pdo, array $user, array $resParams)
    {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->resParams = $resParams;
        $this->urlParams = $_GET;
    }

    abstract public function response() : Response;
}