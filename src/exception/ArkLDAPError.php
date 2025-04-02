<?php

namespace sinri\ark\ldap\exception;

use Exception;
use sinri\ark\ldap\ArkLDAP;
use Throwable;

class ArkLDAPError extends Exception
{
    public function __construct(ArkLDAP $arkLDAP, string $message, Throwable $previous = null)
    {
        parent::__construct(
            $message . " | " . $arkLDAP->getLastErrNo() . ": " . $arkLDAP->getLastError(),
            $arkLDAP->getLastErrNo(),
            $previous
        );
    }
}