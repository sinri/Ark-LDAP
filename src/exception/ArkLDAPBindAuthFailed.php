<?php

namespace sinri\ark\ldap\exception;

use sinri\ark\ldap\ArkLDAP;

class ArkLDAPBindAuthFailed extends ArkLDAPError
{
    public function __construct(ArkLDAP $arkLDAP, string $username)
    {
        parent::__construct($arkLDAP,"Bind account $username to connected LDAP server failed.");
    }
}