<?php

use sinri\ark\ldap\entity\ArkLDAPGroup;
use sinri\ark\ldap\entity\ArkLDAPOrganizationalUnit;
use sinri\ark\ldap\entity\ArkLDAPUser;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;

require_once __DIR__ . '/../autoload.php';

/**
 * @param ArkLDAPOrganizationalUnit $baseOU
 * @throws ArkLDAPDataInvalid
 * @throws ArkLDAPReadFailed
 */
function dumpOUEntity(ArkLDAPOrganizationalUnit $baseOU)
{
    $dict = [
        "dn" => $baseOU->getDistinguishedName(),
        "ou" => $baseOU->getOU(),
        "name" => $baseOU->getName(),
        "members" => $baseOU->getMembers(),
        "sub_ou" => $baseOU->getSubOrganizationalUnits(),
    ];
    echo "=== dumpOUEntity: " . $baseOU->getDistinguishedName() . PHP_EOL;
    foreach ($dict as $key => $value) {
        echo "KEY {$key}: ";
        if (is_array($value)) {
            if (empty($value)) echo "Empty Array" . PHP_EOL;
            else echo "Array of " . implode(" , ", $value) . PHP_EOL;
        } else {
            echo json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    }
    echo "======" . PHP_EOL;
}

/**
 * @param ArkLDAPUser $user
 * @throws ArkLDAPDataInvalid
 */
function dumpUserEntity(ArkLDAPUser $user)
{
    $dict = [
        "dn" => $user->getDistinguishedName(),
        "cn" => $user->getName(),
        "surname" => $user->getSurname(),
        "given name" => $user->getGivenName(),
        "company" => $user->getCompany(),
        "department" => $user->getDepartment(),
        "title" => $user->getTitle(),
        "mail" => $user->getMail(),
        "desc" => $user->getDescription(),
    ];
    echo "=== dumpUserEntity: " . $user->getDistinguishedName() . PHP_EOL;
    foreach ($dict as $key => $value) {
        echo "KEY {$key}: ";
        if (is_array($value)) {
            if (empty($value)) echo "Empty Array" . PHP_EOL;
            else echo "Array of " . implode(" , ", $value) . PHP_EOL;
        } else {
            echo json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    }
    echo "======" . PHP_EOL;
}

/**
 * @param ArkLDAPGroup $group
 * @throws ArkLDAPDataInvalid
 * @throws ArkLDAPReadFailed
 */
function dumpGroupEntity(ArkLDAPGroup $group)
{
    $dict = [
        "dn" => $group->getDistinguishedName(),
        "cn" => $group->getName(),
        "members" => $group->getMembers(),
    ];
    echo "=== dumpGroupEntity: " . $group->getDistinguishedName() . PHP_EOL;
    foreach ($dict as $key => $value) {
        echo "KEY {$key}: ";
        if (is_array($value)) {
            if (empty($value)) echo "Empty Array" . PHP_EOL;
            else echo "Array of " . implode(" , ", $value) . PHP_EOL;
        } else {
            echo json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    }
    echo "======" . PHP_EOL;
}