<?php

use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\entity\ArkLDAPOrganizationalUnit;
use sinri\ark\ldap\exception\ArkLDAPBindAuthFailed;
use sinri\ark\ldap\exception\ArkLDAPConnectFailed;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;

require_once __DIR__ . '/../test-func.php';

date_default_timezone_set("Asia/Shanghai");

$config = [];
require __DIR__ . '/../../config/config.php';

$helper = new ArkLDAP($config['host'], $config['username'], $config['password'], [
    LDAP_OPT_PROTOCOL_VERSION => 3,
    LDAP_OPT_REFERRALS => 0,
]);
try {
    $helper->connect();
    $baseDN = "ou=ark,dc=LQADtest,dc=com";

    $baseOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $baseDN);

    echo "BASE OU [ARK]: " . $baseOU . PHP_EOL;
    dumpOUEntity($baseOU);

// create a sub org
    $subOUName = "ark-测试-" . rand(100, 999);
    $baseOU->createSubOrganizationalUnit($subOUName);
    echo "created a sub ou " . $subOUName . PHP_EOL;

    $subOUDNEntity = $baseOU->getDnEntity()->makeSubItemDNWithOU($subOUName);

    $subOU = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($helper, $subOUDNEntity->generateDNString());
    dumpOUEntity($subOU);

// sub org suicide
    $subOU->suicide(true);


// finally
    dumpOUEntity($baseOU);

    $helper->close();

} catch (ArkLDAPBindAuthFailed|ArkLDAPConnectFailed|ArkLDAPDataInvalid|ArkLDAPReadFailed|ArkLDAPModifyFailed $e) {
    print_r($e);
}