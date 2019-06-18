<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\ArkLDAPItem;

/**
 * Class ArkLDAPOrganizationalUnit
 * @package sinri\ark\ldap\entity
 *
 * objectClass,ou,distinguishedName,instanceType,whenCreated,whenChanged,uSNCreated,uSNChanged,name,objectGUID,objectCategory,dSCorePropagationData
 */
class ArkLDAPOrganizationalUnit extends ArkLDAPTopEntity
{

    public function getEntityClassType()
    {
        return "organizationalUnit";
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return bool|ArkLDAPOrganizationalUnit
     */
    public static function loadOrganizationalUnitByDNString($arkLdap, $dn)
    {
        //$dnEntity=ArkLDAPDistinguishedNameEntity::parseFromDNString($dn);
        $list = $arkLdap->readAll($dn, "name=*");
        if (empty($list)) {
            return false;
        }
        $arkOU = new ArkLDAPOrganizationalUnit($arkLdap);
        $arkOU->data = $list[0];
        $arkOU->dnEntity = ArkLDAPDistinguishedNameEntity::parseFromDNString($arkOU->getDistinguishedName());
        return $arkOU;
    }

    public function getOU()
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_ORGANIZATION_NAME);
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param ArkLDAPDistinguishedNameEntity $dnEntity
     * @param $entry
     * @return bool
     */
    public static function createNewOrganizationalUnit($arkLdap, $dnEntity, $entry = [])
    {
        // to be tested
        $name = $dnEntity->getOrganizationalUnitItems()[0];
        $entry['objectclass'] = ["top", "organizationalUnit"];
//        $entry['distinguishedname']=$name;
        $entry['name'] = $name;
        return $arkLdap->addEntry($dnEntity->generateDNString(), $entry);
    }

    public function createSubOrganizationalUnit($name, $attributes = [])
    {
        $dn = "ou=" . ArkLDAP::escapeDNArgument($name) . "," . $this->data->getDN();
        $entry = json_decode(json_encode($attributes), true);
        $entry['objectclass'] = ["top", "organizationalUnit"];
//        $entry['distinguishedname']=$name;
        $entry['name'] = $name;
        return $this->arkLdap->addEntry($dn, $entry);
    }

    /**
     * @param $name
     * @param array $attributes
     * @return bool
     */
    public function createSubUser($name, $attributes = [])
    {
        $userDNEntity = $this->dnEntity->makeSubItemDNWithCN($name);
        return ArkLDAPUser::createNewUser($this->arkLdap, $userDNEntity, $attributes);
    }

    /**
     * @return ArkLDAPUser[]
     */
    public function getSubUsers()
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=*)(objectclass=user))");
        if (empty($subOUs)) return [];
        $list = [];
        foreach ($subOUs as $subOUItem) {
            $list[] = ArkLDAPUser::loadUserByDNString($this->arkLdap, $subOUItem->getDN());
        }
        return $list;
    }

    /**
     * @param $name
     * @return bool|ArkLDAPUser
     */
    public function getSubUserByName($name)
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=" . ArkLDAP::escapeSearchFilterArgument($name) . ")(objectclass=user))");
        if (empty($subOUs)) return false;
        return ArkLDAPUser::loadUserByDNString($this->arkLdap, $subOUs[0]->getDN());
    }

    /**
     * @return ArkLDAPGroup[]
     */
    public function getSubGroups()
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=*)(objectclass=group))");
        if (empty($subOUs)) return [];
        $list = [];
        foreach ($subOUs as $subOUItem) {
            $list[] = ArkLDAPGroup::loadGroupByDNString($this->arkLdap, $subOUItem->getDN());
        }
        return $list;
    }

    /**
     * @param string $name
     * @return bool|ArkLDAPGroup
     */
    public function getSubGroupByName($name)
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=" . ArkLDAP::escapeSearchFilterArgument($name) . ")(objectclass=group))");
        if (empty($subOUs)) return false;
        return ArkLDAPGroup::loadGroupByDNString($this->arkLdap, $subOUs[0]->getDN());
    }

    /**
     * @return ArkLDAPOrganizationalUnit[]
     */
    public function getSubOrganizationalUnits()
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "ou=*");
        if (empty($subOUs)) return [];
        $list = [];
        foreach ($subOUs as $subOUItem) {
            $list[] = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($this->arkLdap, $subOUItem->getDN());
        }
        return $list;
    }

    /**
     * @param string $name
     * @return bool|ArkLDAPOrganizationalUnit
     */
    public function getSubOrganizationalUnitByName($name)
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "ou=" . ArkLDAP::escapeSearchFilterArgument($name));
        if (empty($subOUs)) return false;
        return ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($this->arkLdap, $subOUs[0]->getDN());
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @return ArkLDAPUser[]
     */
    public function getMembers()
    {
        $members = $this->data->getFieldValues('member');
        if (empty($members)) return [];
        $memberUsers = [];
        foreach ($members as $memberDN) {
            $memberUsers[] = ArkLDAPUser::loadUserByDNString($this->arkLdap, $memberDN);
        }
        return $memberUsers;
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @param $members
     * @return bool
     */
    public function addMembers($members)
    {
        return $this->arkLdap->modifyEntryAddAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @param $members
     * @return bool
     */
    public function removeMembers($members)
    {
        return $this->arkLdap->modifyEntryDeleteAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @param $members
     * @return bool
     */
    public function setMembers($members)
    {
        return $this->arkLdap->modifyEntryReplaceAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }
}