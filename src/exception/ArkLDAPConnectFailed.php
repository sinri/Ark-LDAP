<?php

namespace sinri\ark\ldap\exception;

use sinri\ark\ldap\ArkLDAP;

class ArkLDAPConnectFailed extends ArkLDAPError
{
    public function __construct(ArkLDAP $arkLDAP, string $serverAddress)
    {
        parent::__construct($arkLDAP,"Connect to LDAP server $serverAddress failed.");
    }
}