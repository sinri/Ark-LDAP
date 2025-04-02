<?php


namespace sinri\ark\ldap;


use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use sinri\ark\core\ArkHelper;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;

/**
 * Class ArkLDAPItem
 *
 * This class represents an LDAP item/entry and provides methods to access and manipulate its attributes.
 * It extends ArkLDAPObjectClass and handles various LDAP-specific data types and formats.
 *
 * Key features:
 * - Access and manage LDAP entry attributes (fields)
 * - Handle special attributes like GUID and SID
 * - Convert LDAP timestamps to DateTime objects
 * - Manage Distinguished Names (DN)
 * - Support for multi-valued attributes
 *
 *
 * @package sinri\ark\ldap
 */
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
    const FIELD_DISPLAY_NAME = "displayName";
    const FIELD_USN_CREATED = "uSNCreated";
    const FIELD_USN_CHANGED = "uSNChanged";
    const FIELD_NAME = "name";
    const FIELD_COMPANY = "company";
    const FIELD_DEPARTMENT = "department";
    const FIELD_TITLE = "title";
    const FIELD_DESCRIPTION = "description";
    const FIELD_MAIL = "mail";
    const FIELD_USER_PASSWORD = "userPassword";
    const FIELD_OBJECT_GUID = "objectGUID";
    const FIELD_USER_ACCOUNT_CONTROL = "userAccountControl";
    const FIELD_BAD_PASSWORD_COUNT = "badPwdCount";
    const FIELD_CODE_PAGE = "codePage";
    const FIELD_COUNTRY_CODE = "countryCode";
    const FIELD_BAD_PASSWORD_TIME = "badPasswordTime";
    const FIELD_LAST_LOG_OFF = "lastLogoff";
    const FIELD_LAST_LOG_ON = "lastLogon";
    const FIELD_PASSWORD_LAST_SET = "pwdLastSet";
    const FIELD_PRIMARY_GROUP_ID = "primaryGroupID";
    const FIELD_OBJECT_SID = "objectSid";
    const FIELD_ACCOUNT_EXPIRES = "accountExpires";
    const FIELD_LOG_ON_COUNT = "logonCount";
    const FIELD_SAM_ACCOUNT_NAME = "sAMAccountName";
    const FIELD_SAM_ACCOUNT_TYPE = "sAMAccountType";
    const FIELD_USER_PRINCIPAL_NAME = "userPrincipalName";
    const FIELD_OBJECT_CATEGORY = "objectCategory";
    const FIELD_DS_CORE_PROPAGATION_DATA = "dSCorePropagationData";
    const FIELD_LAST_LOGON_ON_TIMESTAMP = "lastLogonTimestamp";
    const FIELD_COUNTRY = "c";
    const FIELD_PROVINCE = "St";
    const FIELD_CITY = "l";


    public function __construct(array $array)
    {
        parent::__construct($array);
    }

    /**
     * Get all field names in the LDAP item
     *
     * This method returns an array containing all the field names (attributes)
     * present in the LDAP item. Each field name represents an LDAP attribute
     * that exists for this entry.
     * @return string[]
     * @throws ArkLDAPDataInvalid
     */
    public function getFieldNames(): array
    {
        $names = [];
        for ($i = 0; $i < $this->getCount(); $i++) {
            $names[] = $this->getRawItemByIndex($i);
        }
        return $names;
    }

    /**
     * Get the number of values for a specific field in the LDAP item
     *
     * This method returns the count of values associated with a given field name
     * in the LDAP item. For single-valued attributes, this will return 1.
     * For multi-valued attributes, it returns the number of values present.
     *
     * @param string $fieldName
     * @return int
     * @throws ArkLDAPDataInvalid
     */
    public function getFieldValueCount(string $fieldName): int
    {
        $objectClass = $this->getObjectClassByIndex($fieldName);
        return $objectClass->getCount();
    }

    /**
     * Get the value of a specific field at the given index
     *
     * This method retrieves the value of a specified field (LDAP attribute) at the given index.
     * For single-valued attributes, use index 0. For multi-valued attributes, specify the desired index.
     * Special handling is applied for GUID and SID fields to convert binary data to string format.
     *
     * @param string $fieldName
     * @param int $index
     * @return mixed
     * @throws ArkLDAPDataInvalid
     */
    public function getFieldValue(string $fieldName, int $index = 0)
    {
        $objectClass = $this->getObjectClassByIndex($fieldName);
        $rawItem = $objectClass->getRawItemByIndex($index);

        if (in_array($fieldName, [
            self::FIELD_OBJECT_GUID,
            self::FIELD_OBJECT_SID,
        ])) {
            $rawItem = self::GUIDtoStr($rawItem);
        }

        return $rawItem;
    }

    /**
     * Get all values for a specific field in the LDAP item
     *
     * This method retrieves all values associated with a given field name (LDAP attribute).
     * For single-valued attributes, this will return an array with one element.
     * For multi-valued attributes, it returns an array containing all values.
     * The values are returned in their raw format, except for GUID and SID fields
     * which are automatically converted to string format.
     * @param string $fieldName
     * @return array
     * @throws ArkLDAPDataInvalid
     */
    public function getFieldValues(string $fieldName): array
    {
        $objectClass = $this->getObjectClassByIndex($fieldName);
        $values = [];
        for ($i = 0; $i < $objectClass->getCount(); $i++) {
            $values[] = $objectClass->getRawItemByIndex($i);
        }
        return $values;
    }

    /**
     * Get the Distinguished Name (DN) of the LDAP item
     *
     * This method retrieves the Distinguished Name of the LDAP item. It first attempts
     * to read the DN from the raw array using the 'dn' key. If that fails, it falls
     * back to getting the DN from the distinguishedName field.
     *
     * @return string The Distinguished Name of the LDAP item
     * @throws ArkLDAPDataInvalid
     */
    public function getDN(): string
    {
        $dn = ArkHelper::readTarget($this->rawArray, ['dn']);
        if ($dn !== null) return $dn;
        return $this->getFieldValue(self::FIELD_DISTINGUISHED_NAME);
    }

    /**
     * Convert a binary GUID to its string representation
     *
     * This method takes a binary GUID (Globally Unique Identifier) and converts it
     * to its standard string format (XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX).
     * The method uses unpack() to extract components of the binary GUID and
     * formats them into the standard hexadecimal representation.
     *
     * @param string $binary_guid The binary GUID to convert
     * @return string The GUID in string format
     * @throws ArkLDAPDataInvalid If the binary GUID is not in valid format
     */
    public static function GUIDtoStr(string $binary_guid): string
    {
        $unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
        if (!!$unpacked) {
            return sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
        } else {
            throw new ArkLDAPDataInvalid("Invalid GUID encountered: " . $binary_guid);
        }
    }

    /**
     * Normalize a timestamp string in LDAP format to a DateTime object
     *
     * This method takes a timestamp string in LDAP format (e.g., "20190611024233.0Z" or "20190611024233.0+0800")
     * and converts it to a PHP DateTime object. The method handles both UTC ('Z') and timezone offset formats.
     *
     * The input format should be:
     * - YYYYMMDDhhmmss.0Z for UTC timestamps
     * - YYYYMMDDhhmmss.0±hhmm for timestamps with timezone offsets
     *
     * Example inputs:
     * - "20190611024233.0Z" (UTC)
     * - "20190611024233.0+0800" (UTC+8)
     * - "20190611024233.0-0500" (UTC-5)
     * @param string $dateTimeZ
     * @return DateTime
     * @throws Exception
     */
    public static function normalizeDateTimeZ(string $dateTimeZ): DateTime
    {
        preg_match("/^(\d+).?0?(([+-]\d\d)(\d\d)|Z)$/i", $dateTimeZ, $matches);
        if (!isset($matches[1]) || !isset($matches[2])) {
            throw new InvalidArgumentException(sprintf('Invalid timestamp encountered: %s', $dateTimeZ));
        }
        $tz = (strtoupper($matches[2]) == 'Z') ? 'UTC' : $matches[3] . ':' . $matches[4];
        return new DateTime($matches[1], new DateTimeZone($tz));
    }

    /**
     * Convert an LDAP timestamp string to a Unix timestamp
     *
     * This method takes a timestamp string in LDAP format (e.g., "20190611024233.0Z" or "20190611024233.0+0800")
     * and converts it to a Unix timestamp (seconds since Unix epoch).
     *
     * The method internally uses normalizeDateTimeZ() to parse the LDAP timestamp
     * and then converts it to a Unix timestamp.
     *
     * Example:
     * ```php
     * $timestamp = ArkLDAPItem::DateTimeZtoTimestamp("20190611024233.0Z");
     * // Returns: 1560220953
     * ```
     * You can print the returned timestamp using the following code: `echo $date->format('Y-m-d H:i:s');`
     *
     * @param string $dateTimeZ such as "20190611024233.0Z"
     * @return int
     * @throws Exception
     */
    public static function DateTimeZtoTimestamp(string $dateTimeZ): int
    {
        $date = self::normalizeDateTimeZ($dateTimeZ);
        return $date->getTimestamp();
    }

    /**
     * @throws ArkLDAPDataInvalid
     */
    public function __toString()
    {
        return "{ArkLDAPItem|" . $this->getDN() . "}";
    }

    /**
     * Get the next SID (Security Identifier) for partial search in LDAP
     *
     * This method calculates the next sequential SID based on the current object's SID.
     * It extracts the last 4 bytes of the current SID (which represents the relative ID),
     * increments it by 1, and creates a new SID by combining the original prefix with
     * the incremented ID. The resulting SID is then formatted for use in LDAP filters.
     *
     * Example:
     * If current SID is S-1-5-21-3623811015-3361044348-30300820-1013
     * The next SID will be S-1-5-21-3623811015-3361044348-30300820-1014
     *
     * The returned string is hex-encoded and escaped for use in LDAP filters.
     * @return string
     * @throws ArkLDAPDataInvalid
     * @since 0.0.4
     */
    public function getNextSidForPartialSearch(): string
    {
        list($sid) = $this->getFieldValues('objectSid');
        list($v) = array_values(unpack('V', substr($sid, 24))); // id for user.
        $s = substr($sid, 0, 24) . pack('V', 1 + $v); // next sid.
        // escape for filter
        return preg_replace('/../', '\\\\$0', bin2hex($s));
    }
}