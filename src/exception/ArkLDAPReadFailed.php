<?php

namespace sinri\ark\ldap\exception;

use sinri\ark\ldap\ArkLDAP;

class ArkLDAPReadFailed extends ArkLDAPError
{
    public function __construct(ArkLDAP $arkLDAP, string $modifyDetail)
    {
        parent::__construct($arkLDAP, "Read ($modifyDetail) in LDAP failed.");
    }
}