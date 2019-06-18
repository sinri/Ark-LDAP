<?php


namespace sinri\ark\ldap;


use DateTime;
use DateTimeZone;
use Exception;
use sinri\ark\core\ArkHelper;

class ArkLDAPItem extends ArkLDAPObjectClass
{
    const FIELD_OBJECT_CLASS = "objectClass";
    const FIELD_COMMON_NAME = "cn";
    const FIELD_SURNAME = "sn";
    const FIELD_GIVEN_NAME = "givenName";
    const FIELD_ORGANIZATION_NAME = "ou";
    const FIELD_DISTINGUISHED_NAME = "distinguishedName";
    const FIELD_INSTANCE_TYPE = "instanceType";
    const FIELD_WHEN_CREATED = "whenCreated";
    const FIELD_WHEN_CHANGED = "whenChanged";
    CONST FIELD_DISPLAY_NAME = "displayName";
    CONST FIELD_USN_CREATED = "uSNCreated";
    CONST FIELD_USN_CHANGED = "uSNChanged";
    CONST FIELD_NAME = "name";
    CONST FIELD_COMPANY = "company";
    CONST FIELD_DEPARTMENT = "department";
    CONST FIELD_TITLE = "title";
    CONST FIELD_DESCRIPTION = "description";
    CONST FIELD_MAIL = "mail";
    CONST FIELD_USER_PASSWORD = "userPassword";
    CONST FIELD_OBJECT_GUID = "objectGUID";
    CONST FIELD_USER_ACCOUNT_CONTROL = "userAccountControl";
    CONST FIELD_BAD_PASSWORD_COUNT = "badPwdCount";
    CONST FIELD_CODE_PAGE = "codePage";
    CONST FIELD_COUNTRY_CODE = "countryCode";
    CONST FIELD_BAD_PASSWORD_TIME = "badPasswordTime";
    CONST FIELD_LAST_LOG_OFF = "lastLogoff";
    CONST FIELD_LAST_LOG_ON = "lastLogon";
    CONST FIELD_PASSWORD_LAST_SET = "pwdLastSet";
    CONST FIELD_PRIMARY_GROUP_ID = "primaryGroupID";
    CONST FIELD_OBJECT_SID = "objectSid";
    CONST FIELD_ACCOUNT_EXPIRES = "accountExpires";
    CONST FIELD_LOG_ON_COUNT = "logonCount";
    CONST FIELD_SAM_ACCOUNT_NAME = "sAMAccountName";
    CONST FIELD_SAM_ACCOUNT_TYPE = "sAMAccountType";
    CONST FIELD_USER_PRINCIPAL_NAME = "userPrincipalName";
    CONST FIELD_OBJECT_CATEGORY = "objectCategory";
    CONST FIELD_DS_CORE_PROPAGATION_DATA = "dSCorePropagationData";
    CONST FIELD_LAST_LOGON_ON_TIMESTAMP = "lastLogonTimestamp";
    CONST FIELD_COUNTRY = "c";
    CONST FIELD_PROVINCE = "St";
    CONST FIELD_CITY = "l";


    public function __construct($array)
    {
        parent::__construct($array);
    }

    public function getFieldNames()
    {
        $names = [];
        for ($i = 0; $i < $this->getCount(); $i++) {
            $names[] = ArkHelper::readTarget($this->rawArray, [$i]);
        }
        return $names;
    }

    public function getFieldValueCount($fieldName)
    {
        $objectClass = $this->getObjectClassByIndex($fieldName);
        if (empty($objectClass)) return false;
        return $objectClass->getCount();
    }

    public function getFieldValue($fieldName, $index = 0)
    {
        $objectClass = $this->getObjectClassByIndex($fieldName);
        if (empty($objectClass)) return false;
        $rawItem = $objectClass->getRawItemByIndex($index);

        if (in_array($fieldName, [
            self::FIELD_OBJECT_GUID,
            self::FIELD_OBJECT_SID,
        ])) {
            $rawItem = self::GUIDtoStr($rawItem);
        }

        return $rawItem;
    }

    public function getFieldValues($fieldName)
    {
        $objectClass = $this->getObjectClassByIndex($fieldName);
        if (empty($objectClass)) return false;
        $values = [];
        for ($i = 0; $i < $objectClass->getCount(); $i++) {
            $values[] = $objectClass->getRawItemByIndex($i);
        }
        return $values;
    }

    public function getDN()
    {
        $dn = ArkHelper::readTarget($this->rawArray, ['dn']);
        if ($dn !== null) return $dn;
        return $this->getFieldValue(self::FIELD_DISTINGUISHED_NAME);
    }

    public static function GUIDtoStr($binary_guid)
    {
        $unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
        return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
    }

    /**
     * @param $dateTimeZ
     * @return DateTime
     * @throws Exception
     */
    public static function normalizeDateTimeZ($dateTimeZ)
    {
        preg_match("/^(\d+).?0?(([+-]\d\d)(\d\d)|Z)$/i", $dateTimeZ, $matches);
        if (!isset($matches[1]) || !isset($matches[2])) {
            throw new Exception(sprintf('Invalid timestamp encountered: %s', $dateTimeZ));
//            return false;
        }
        $tz = (strtoupper($matches[2]) == 'Z') ? 'UTC' : $matches[3] . ':' . $matches[4];
        $date = new DateTime($matches[1], new DateTimeZone($tz));

        return $date;
    }

    /**
     * @param string $dateTimeZ such as "20190611024233.0Z"
     * @return int
     * @throws Exception
     */
    public static function DateTimeZtoTimestamp($dateTimeZ)
    {
        $date = self::normalizeDateTimeZ($dateTimeZ);
        return $date->getTimestamp();

        // Now print it in any format you like
//        echo $date->format('Y-m-d H:i:s');
    }

    public function __toString()
    {
        return "{ArkLDAPItem|" . $this->getDN() . "}";
    }
}