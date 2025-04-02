<?php

use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\entity\ArkLDAPOrganizationalUnit;
use sinri\ark\ldap\entity\ArkLDAPUser;
use sinri\ark\ldap\exception\ArkLDAPBindAuthFailed;
use sinri\ark\ldap\exception\ArkLDAPConnectFailed;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;

require_once __DIR__ . '/../test-func.php';

date_default_timezone_set("Asia/Shanghai");

$config = [];
require __DIR__ . '/../../config/config.php';

$helper = new ArkLDAP($config['host'], $config['username'], $config['password']);

try {
    $helper->connect();
    $baseDN = "ou=ark,dc=LQADtest,dc=com";

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    $baseOU->createSubUser("user", ['sn' => 'Mori', 'displayname' => 'Forest']);

    echo "created user" . PHP_EOL;

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    dumpOUEntity($baseOU);

    $user = ArkLDAPUser::loadUserByDNString($helper, "cn=user," . $baseDN);
    dumpUserEntity($user);

    $baseOU->addMembers([$user->getDistinguishedName()]);
    echo "Added Member[" . $user->getDistinguishedName() . "] to ARK" . PHP_EOL;

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    dumpOUEntity($baseOU);

    $user->suicide();
    echo "user suicide" . PHP_EOL;

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    dumpOUEntity($baseOU);

    $helper->close();
} catch (ArkLDAPBindAuthFailed|ArkLDAPConnectFailed|ArkLDAPModifyFailed|ArkLDAPDataInvalid|ArkLDAPReadFailed $e) {
    print_r($e);
}