<?php

class Response
{
    private $status;
    private $body = null;
    private $error = null;

    function __construct($status)
    {
        if (gettype($status) !== "integer")
            throw new InvalidArgumentException("status must be an integer.");

        $this->status = $status;
    }

    function getStatus()
    {
        return $this->status;
    }

    function getBody()
    {
        return $this->body;
    }

    function getError()
    {
        return $this->error;
    }

    function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    function setError($error)
    {
        if (gettype($error) !== "string")
            throw new InvalidArgumentException("status must be a string.");

        $this->error = $error;
        return $this;
    }
}