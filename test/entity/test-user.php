<?php

use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\entity\ArkLDAPOrganizationalUnit;
use sinri\ark\ldap\entity\ArkLDAPUser;

require_once __DIR__ . '/../test-func.php';

date_default_timezone_set("Asia/Shanghai");

$config = [];
require __DIR__ . '/../../config/config.php';

$helper = new ArkLDAP($config['host'], $config['username'], $config['password']);
if ($helper->connect()) {
    $baseDN = "ou=ark,dc=LQADtest,dc=com";

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    $done = $baseOU->createSubUser("user", ['sn' => 'Mori', 'displayname' => 'Forest']);

    echo "created user? " . json_encode($done) . PHP_EOL;

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    dumpOUEntity($baseOU);

    $user = ArkLDAPUser::loadUserByDNString($helper, "cn=user," . $baseDN);
    dumpUserEntity($user);

    $added = $baseOU->addMembers([$user->getDistinguishedName()]);
    echo "Added Member[" . $user->getDistinguishedName() . "] to ARK? " . json_encode($added) . PHP_EOL;

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    dumpOUEntity($baseOU);

    $died = $user->suicide();
    echo "user suicide? " . json_encode($died) . PHP_EOL;

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);
    dumpOUEntity($baseOU);

    $helper->close();
}