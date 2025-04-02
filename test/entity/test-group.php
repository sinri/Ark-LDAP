<?php

use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\entity\ArkLDAPGroup;
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
    $baseDN = 'cn=ArkGroup,ou=ark,dc=LQADtest,dc=com';

    ArkLDAPGroup::createGroup($helper, $baseDN);
    echo "created group: " . $baseDN . PHP_EOL;

    $group = ArkLDAPGroup::loadGroupByDNString($helper, $baseDN);
    dumpGroupEntity($group);

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, "ou=ark,dc=LQADtest,dc=com");
    $baseOU->createSubUser("user", ['sn' => 'Mori', 'displayname' => 'Forest']);
    echo "created user" . PHP_EOL;

    $user = ArkLDAPUser::loadUserByDNString($helper, "cn=user,ou=ark,dc=LQADtest,dc=com");
    $group->addMembers([$user->getDistinguishedName()]);
    echo "added user to group" . PHP_EOL;

    $group = ArkLDAPGroup::loadGroupByDNString($helper, $baseDN);
    dumpGroupEntity($group);

    $group->removeMembers([$user->getDistinguishedName()]);
    $group = ArkLDAPGroup::loadGroupByDNString($helper, $baseDN);
    dumpGroupEntity($group);

    $group->suicide(true);
    echo "group suicide" . PHP_EOL;

    $user->suicide();
    echo "user suicide" . PHP_EOL;

    $helper->close();
} catch (ArkLDAPBindAuthFailed|ArkLDAPConnectFailed|ArkLDAPModifyFailed|ArkLDAPDataInvalid|ArkLDAPReadFailed $e) {
    print_r($e);
}
