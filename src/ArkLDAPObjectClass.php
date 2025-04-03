<?php


namespace sinri\ark\ldap;


use sinri\ark\ldap\exception\ArkLDAPDataInvalid;

/**
 * Class ArkLDAPObjectClass
 * This class provides a wrapper for LDAP object class data structures.
 * It handles the raw array data returned from LDAP operations and provides methods to access and manipulate this data.
 *
 * Key features:
 * - Wraps raw LDAP array data
 * - Provides safe access to count and indexed items
 * - Handles type checking and validation
 * - Throws ArkLDAPDataInvalid exceptions for invalid operations
 *
 * @package sinri\ark\ldap
 */
class ArkLDAPObjectClass
{
    /**
     * @var array
     */
    protected array $rawArray;

    public function __construct(array $array)
    {
        $this->rawArray = $array;
    }

    /**
     * Get the count of items in the LDAP object class.
     * This method returns the number of items in the LDAP object class data structure.
     * It validates that the 'count' key exists and contains an integer value.
     * @return int The number of items in the LDAP object class
     * @throws ArkLDAPDataInvalid
     */
    public function getCount(): int
    {
        $x = $this->getAttribute('count');
        if (!is_int($x)) {
            throw new ArkLDAPDataInvalid("Value mapped to key count is not an integer.");
        }
        return $x;
    }

    /**
     * @param string $attributeName
     * @return mixed
     * @throws ArkLDAPDataInvalid
     * @since 0.0.6
     */
    public function getAttribute(string $attributeName)
    {
        if (!array_key_exists($attributeName, $this->rawArray)) {
            throw new ArkLDAPDataInvalid("Key $attributeName is not found.");
        }
        return $this->rawArray[$attributeName];
    }

    /**
     * Get the raw array data structure.
     * This method returns the raw array data structure that the class is wrapping.
     * The raw array contains the raw data returned from LDAP operations.
     * @param int $index
     * @return mixed any raw data mapped by the index
     * @throws ArkLDAPDataInvalid the index is out of bound
     */
    public function getRawItemByIndex(int $index)
    {
        if (array_key_exists($index, $this->rawArray)) {
            return $this->rawArray[$index];
        } else {
            throw new ArkLDAPDataInvalid("Index is not valid");
        }
    }

    /**
     * Get the object class by index by wrapping the raw data into an ArkLDAPObjectClass instance.
     * This method returns the object class at the specified index.
     * If the index is out of bound, it throws an exception.
     * If the raw data mapped to the index is null, it returns null.
     * @param int $index
     * @return ArkLDAPObjectClass|null The object class at the specified index, or null if the index is out of bound.
     * @throws ArkLDAPDataInvalid
     */
    public function getObjectClassByIndex(int $index): ArkLDAPObjectClass
    {
        $rawItem = $this->getRawItemByIndex($index);
        if (!is_array($rawItem)) {
            throw new ArkLDAPDataInvalid("LDAP Object Class is null.");
        }
        return new ArkLDAPObjectClass($rawItem);
    }

    /**
     * @param string $attributeName
     * @return ArkLDAPObjectClass
     * @throws ArkLDAPDataInvalid
     * @since 0.0.6
     */
    public function getAttributeAsObjectClass(string $attributeName): ArkLDAPObjectClass
    {
        $rawItem = $this->getAttribute($attributeName);
        if (!is_array($rawItem)) {
            throw new ArkLDAPDataInvalid("LDAP Object Class is null.");
        }
        return new ArkLDAPObjectClass($rawItem);
    }
}