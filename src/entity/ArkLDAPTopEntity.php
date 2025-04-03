<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\ArkLDAPItem;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;

/**
 * ArkLDAPTopEntity Class
 *
 * This class represents the top-level entity in LDAP hierarchy and provides basic LDAP entry operations.
 * It serves as the base class for all LDAP entity types in the Ark-LDAP framework.
 *
 * The class maintains core LDAP entry attributes and provides functionality for:
 * - Reading entry data and distinguished names
 * - Moving entries within the LDAP directory
 * - Deleting entries (with optional recursive deletion)
 * - String representation of the entity
 *
 * @package sinri\ark\ldap\entity
 */
class ArkLDAPTopEntity
{
    /**
     * @var ArkLDAP
     */
    protected ArkLDAP $arkLdap;
    /**
     * @var ArkLDAPItem
     */
    protected ArkLDAPItem $data;
    /**
     * @var ArkLDAPDistinguishedNameEntity
     */
    protected ArkLDAPDistinguishedNameEntity $dnEntity;

    /**
     * @return ArkLDAPItem
     */
    public function getData(): ArkLDAPItem
    {
        return $this->data;
    }

    /**
     * Get the DN (Distinguished Name) entity associated with this LDAP entry.
     * DN is a unique identifier that describes the entry's position in the LDAP directory tree hierarchy.
     * For example: "cn=John Doe,ou=People,dc=example,dc=com"
     * - cn: Common Name
     * - ou: Organizational Unit
     * - dc: Domain Component
     * @return ArkLDAPDistinguishedNameEntity
     */
    public function getDnEntity(): ArkLDAPDistinguishedNameEntity
    {
        return $this->dnEntity;
    }

    /**
     * ArkLDAPOrganizationalUnit constructor.
     * @param ArkLDAP $arkLdap
     */
    public function __construct(ArkLDAP $arkLdap)
    {
        $this->arkLdap = $arkLdap;
    }

    /**
     * @return string
     * @throws ArkLDAPDataInvalid
     */
    public function getName(): string
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_NAME);
    }

    /**
     * @return string
     * @throws ArkLDAPDataInvalid
     */
    public function getDistinguishedName(): string
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_DISTINGUISHED_NAME);
    }

    public function getEntityClassType(): string
    {
        return "top";
    }

    /**
     * Move this LDAP entry to a new location in the directory tree
     *
     * This method relocates the current LDAP entry to a new position specified by the provided
     * Distinguished Name entity. The move operation preserves the entry's attributes while
     * changing its location in the directory hierarchy.
     *
     * @param ArkLDAPDistinguishedNameEntity $newDNEntity
     * @throws ArkLDAPModifyFailed
     */
    public function move(ArkLDAPDistinguishedNameEntity $newDNEntity)
    {
        $name="";
        $parent="";
        $newDNEntity->loadMoveDestinationArguments($name, $parent);
        $this->arkLdap->moveEntry($this->dnEntity->generateDNString(), $name, $parent);
    }

    /**
     * Delete this LDAP entry from the directory
     *
     * This method removes the current LDAP entry from the directory. When recursive deletion
     * is enabled, it will also delete all child entries under this entry.
     *
     * @param bool $recursive Whether to recursively delete child entries (default: false)
     * @throws ArkLDAPModifyFailed if the deletion operation fails
     */
    public function suicide(bool $recursive = false)
    {
         $this->arkLdap->deleteEntry($this->dnEntity->generateDNString(), $recursive);
    }

    /**
     * @return string
     * @throws ArkLDAPDataInvalid
     */
    public function __toString()
    {
        return "{" . $this->getEntityClassType() . "|" . $this->getDistinguishedName() . "}";
    }
}