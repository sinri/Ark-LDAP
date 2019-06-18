<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\ArkLDAPItem;

class ArkLDAPUser extends ArkLDAPTopEntity
{
    public function getEntityClassType()
    {
        return "user";
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return bool|ArkLDAPUser
     */
    public static function loadUserByDNString($arkLdap, $dn)
    {
        $list = $arkLdap->readAll($dn, "name=*");
        if (empty($list)) {
            return false;
        }
        $arkCN = new ArkLDAPUser($arkLdap);
        $arkCN->data = $list[0];
        $arkCN->dnEntity = ArkLDAPDistinguishedNameEntity::parseFromDNString($arkCN->getDistinguishedName());
        return $arkCN;
    }

    public function getSurname()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_SURNAME);
    }

    public function getGivenName()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_GIVEN_NAME);
    }

    public function getDisplayName()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_DISPLAY_NAME);
    }

    public function getCompany()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_COMPANY);
    }

    public function getDepartment()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_DEPARTMENT);
    }

    public function getTitle()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_TITLE);
    }

    public function getDescription()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_DESCRIPTION);
    }

    public function getMail()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_MAIL);
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param ArkLDAPDistinguishedNameEntity $dnEntity
     * @param $entry
     * @return bool
     */
    public static function createNewUser($arkLdap, $dnEntity, $entry = [])
    {
        $name = $dnEntity->getCommonNameItems()[0];
        $entry['objectclass'] = ["top", "person", "organizationalPerson", "user"];
        $entry['name'] = $name;
        return $arkLdap->addEntry($dnEntity->generateDNString(), $entry);
    }


}