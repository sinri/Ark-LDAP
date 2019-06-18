<?php

use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\entity\ArkLDAPGroup;
use sinri\ark\ldap\entity\ArkLDAPOrganizationalUnit;
use sinri\ark\ldap\entity\ArkLDAPUser;

require_once __DIR__ . '/../test-func.php';

date_default_timezone_set("Asia/Shanghai");

$config = [];
require __DIR__ . '/../../config/config.php';

$helper = new ArkLDAP($config['host'], $config['username'], $config['password']);
if ($helper->connect()) {
    $baseDN = 'cn=ArkGroup,ou=ark,dc=LQADtest,dc=com';

    $done = ArkLDAPGroup::createGroup($helper, $baseDN);
    echo "created group: " . $baseDN . "? " . json_encode($done) . PHP_EOL;

    $group = ArkLDAPGroup::loadGroupByDNString($helper, $baseDN);
    dumpGroupEntity($group);

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, "ou=ark,dc=LQADtest,dc=com");
    $done = $baseOU->createSubUser("user", ['sn' => 'Mori', 'displayname' => 'Forest']);
    echo "created user? " . json_encode($done) . PHP_EOL;

    $user = ArkLDAPUser::loadUserByDNString($helper, "cn=user,ou=ark,dc=LQADtest,dc=com");
    $added = $group->addMembers([$user->getDistinguishedName()]);
    echo "added user to group? " . json_encode($added) . PHP_EOL;

    $group = ArkLDAPGroup::loadGroupByDNString($helper, $baseDN);
    dumpGroupEntity($group);

    $group->removeMembers([$user->getDistinguishedName()]);
    $group = ArkLDAPGroup::loadGroupByDNString($helper, $baseDN);
    dumpGroupEntity($group);

    $done = $group->suicide(true);
    echo "group suicide? " . json_encode($done) . PHP_EOL;

    $done = $user->suicide();
    echo "user suicide? " . json_encode($done) . PHP_EOL;

    $helper->close();
}
