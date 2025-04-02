<?php

namespace sinri\ark\ldap\exception;

use Exception;

class ArkLDAPDataInvalid extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

}