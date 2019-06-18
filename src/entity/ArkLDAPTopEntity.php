<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\ArkLDAPItem;

class ArkLDAPTopEntity
{
    /**
     * @var ArkLDAP
     */
    protected $arkLdap;
    /**
     * @var ArkLDAPItem
     */
    protected $data;
    /**
     * @var ArkLDAPDistinguishedNameEntity
     */
    protected $dnEntity;

    /**
     * @return ArkLDAPItem
     */
    public function getData(): ArkLDAPItem
    {
        return $this->data;
    }

    /**
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
    public function __construct($arkLdap)
    {
        $this->arkLdap = $arkLdap;
    }

    public function getName()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_NAME);
    }

    public function getDistinguishedName()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_DISTINGUISHED_NAME);
    }

    public function getEntityClassType()
    {
        return "top";
    }

    /**
     * @param ArkLDAPDistinguishedNameEntity $newDNEntity
     * @return bool
     */
    public function move($newDNEntity)
    {
        $newDNEntity->loadMoveDestinationArguments($name, $parent);
        return $this->arkLdap->moveEntry($this->dnEntity->generateDNString(), $name, $parent);
    }

    public function suicide($recursive = false)
    {
        return $this->arkLdap->deleteEntry($this->dnEntity->generateDNString(), $recursive);
    }

    public function __toString()
    {
        return "{" . $this->getEntityClassType() . "|" . $this->getDistinguishedName() . "}";
    }
}