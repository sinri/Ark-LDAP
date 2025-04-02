<?php

namespace sinri\ark\ldap\exception;

use sinri\ark\ldap\ArkLDAP;

class ArkLDAPModifyFailed extends ArkLDAPError
{
    public function __construct(ArkLDAP $arkLDAP, string $modifyDetail)
    {
        parent::__construct($arkLDAP, "Modify ($modifyDetail) in LDAP failed.");
    }
}