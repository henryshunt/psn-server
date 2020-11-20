<?php
abstract class Endpoint
{
    protected $pdo;
    protected $user;
    protected $resParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    abstract public function response(array $resParams) : Response;
}