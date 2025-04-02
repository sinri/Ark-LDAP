<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\ArkLDAPItem;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;
use UnexpectedValueException;

class ArkLDAPUser extends ArkLDAPTopEntity
{
    public function getEntityClassType(): string
    {
        return "user";
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return ArkLDAPUser
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public static function loadUserByDNString(ArkLDAP $arkLdap, string $dn): ArkLDAPUser
    {
        $list = $arkLdap->readAll($dn, "name=*");
        if (empty($list)) {
            throw new UnexpectedValueException("No result found for DN.");
        }
        $arkCN = new ArkLDAPUser($arkLdap);
        $arkCN->data = $list[0];
        $arkCN->dnEntity = ArkLDAPDistinguishedNameEntity::parseFromDNString($arkCN->getDistinguishedName());
        return $arkCN;
    }

    public function getSurname(): ?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_SURNAME);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getGivenName(): ?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_GIVEN_NAME);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getDisplayName():?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_DISPLAY_NAME);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getCompany():?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_COMPANY);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getDepartment():?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_DEPARTMENT);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getTitle():?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_TITLE);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getDescription():?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_DESCRIPTION);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    public function getMail():?string
    {
        try {
            return $this->data->getFieldValue(ArkLDAPItem::FIELD_MAIL);
        } catch (ArkLDAPDataInvalid $e) {
            return null;
        }
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param ArkLDAPDistinguishedNameEntity $dnEntity
     * @param array $entry
     * @return void
     * @throws ArkLDAPModifyFailed
     */
    public static function createNewUser(ArkLDAP $arkLdap, ArkLDAPDistinguishedNameEntity $dnEntity, array $entry = [])
    {
        $name = $dnEntity->getCommonNameItems()[0];
        $entry['objectclass'] = ["top", "person", "organizationalPerson", "user"];
        $entry['name'] = $name;
        $arkLdap->addEntry($dnEntity->generateDNString(), $entry);
    }


}