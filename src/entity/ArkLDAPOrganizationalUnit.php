<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\ArkLDAPItem;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;

/**
 * Class ArkLDAPOrganizationalUnit
 *
 * Represents an Organizational Unit (OU) in LDAP directory structure.
 * Provides methods to manage organizational units including:
 * - Creating new OUs and sub-OUs
 * - Managing sub-users and sub-groups
 * - Managing OU members (if schema allows)
 * - Retrieving OU information
 *
 * @package sinri\ark\ldap\entity
 *
 * Common LDAP attributes for OUs:
 * - objectClass: Defines the object class type
 * - ou: The organizational unit name
 * - distinguishedName: Unique identifier for the OU
 * - name: Common name of the OU
 * - whenCreated: Creation timestamp
 * - whenChanged: Last modified timestamp
 */
class ArkLDAPOrganizationalUnit extends ArkLDAPTopEntity
{

    public function getEntityClassType(): string
    {
        return "organizationalUnit";
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return ArkLDAPOrganizationalUnit
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public static function loadOrganizationalUnitByDNString(ArkLDAP $arkLdap, string $dn): ArkLDAPOrganizationalUnit
    {
        $list = $arkLdap->readAll($dn, "name=*");
        if (!array_key_exists(0, $list)) {
            throw new ArkLDAPDataInvalid("No OU found for DN $dn");
        }
        $arkOU = new ArkLDAPOrganizationalUnit($arkLdap);
        $arkOU->data = $list[0];
        $arkOU->dnEntity = ArkLDAPDistinguishedNameEntity::parseFromDNString($arkOU->getDistinguishedName());
        return $arkOU;
    }

    /**
     * @return string
     * @throws ArkLDAPDataInvalid
     */
    public function getOU(): string
    {
        return $this->data->getFieldValue(ArkLDAPItem::FIELD_ORGANIZATION_NAME);
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param ArkLDAPDistinguishedNameEntity $dnEntity
     * @param array $entry
     * @return void
     * @throws ArkLDAPModifyFailed
     */
    public static function createNewOrganizationalUnit(ArkLDAP $arkLdap, ArkLDAPDistinguishedNameEntity $dnEntity, array $entry = [])
    {
        // to be tested
        $name = $dnEntity->getOrganizationalUnitItems()[0];
        $entry['objectclass'] = ["top", "organizationalUnit"];
//        $entry['distinguishedname']=$name;
        $entry['name'] = $name;
        $arkLdap->addEntry($dnEntity->generateDNString(), $entry);
    }

    /**
     * @param string $name
     * @param array $attributes
     * @throws ArkLDAPModifyFailed
     */
    public function createSubOrganizationalUnit(string $name, array $attributes = [])
    {
        $dn = $this->dnEntity->makeSubItemDNWithOU($name)->generateDNString();
        $entry = $attributes;
        $entry['objectclass'] = ["top", "organizationalUnit"];
//        $entry['distinguishedname']=$name;
        $entry['name'] = $name;
        $this->arkLdap->addEntry($dn, $entry);
    }

    /**
     * @param string $name
     * @param array $attributes
     * @return void
     * @throws ArkLDAPModifyFailed
     */
    public function createSubUser(string $name, array $attributes = [])
    {
        $userDNEntity = $this->dnEntity->makeSubItemDNWithCN($name);
        ArkLDAPUser::createNewUser($this->arkLdap, $userDNEntity, $attributes);
    }

    /**
     * @return ArkLDAPUser[]
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getSubUsers(): array
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=*)(objectclass=user))");
        $list = [];
        foreach ($subOUs as $subOUItem) {
            $list[] = ArkLDAPUser::loadUserByDNString($this->arkLdap, $subOUItem->getDN());
        }
        return $list;
    }

    /**
     * @param string $name
     * @return ArkLDAPUser
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getSubUserByName(string $name): ArkLDAPUser
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=" . ArkLDAP::escapeSearchFilterArgument($name) . ")(objectclass=user))");
        if (!array_key_exists(0, $subOUs)) {
            throw new ArkLDAPDataInvalid("Sub User is not found for name $name");
        }
        return ArkLDAPUser::loadUserByDNString($this->arkLdap, $subOUs[0]->getDN());
    }

    /**
     * @return ArkLDAPGroup[]
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getSubGroups(): array
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=*)(objectclass=group))");
        $list = [];
        foreach ($subOUs as $subOUItem) {
            $list[] = ArkLDAPGroup::loadGroupByDNString($this->arkLdap, $subOUItem->getDN());
        }
        return $list;
    }

    /**
     * @param string $name
     * @return ArkLDAPGroup
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getSubGroupByName(string $name): ArkLDAPGroup
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "(&(cn=" . ArkLDAP::escapeSearchFilterArgument($name) . ")(objectclass=group))");
        if (!array_key_exists(0, $subOUs)) {
            throw new ArkLDAPDataInvalid("Sub Group is not found for name $name");
        }
        return ArkLDAPGroup::loadGroupByDNString($this->arkLdap, $subOUs[0]->getDN());
    }

    /**
     * @return ArkLDAPOrganizationalUnit[]
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getSubOrganizationalUnits(): array
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "ou=*");
        $list = [];
        foreach ($subOUs as $subOUItem) {
            $list[] = ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($this->arkLdap, $subOUItem->getDN());
        }
        return $list;
    }

    /**
     * @param string $name
     * @return ArkLDAPOrganizationalUnit
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getSubOrganizationalUnitByName(string $name): ArkLDAPOrganizationalUnit
    {
        $subOUs = $this->arkLdap->listAll($this->dnEntity->generateDNString(), "ou=" . ArkLDAP::escapeSearchFilterArgument($name));
        if (!array_key_exists(0, $subOUs)) {
            throw new ArkLDAPDataInvalid("Sub Org is not found for name $name");
        }
        return ArkLDAPOrganizationalUnit::loadOrganizationalUnitByDNString($this->arkLdap, $subOUs[0]->getDN());
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @return ArkLDAPUser[]
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getMembers(): array
    {
        $members = $this->data->getFieldValues('member');
        $memberUsers = [];
        foreach ($members as $memberDN) {
            $memberUsers[] = ArkLDAPUser::loadUserByDNString($this->arkLdap, $memberDN);
        }
        return $memberUsers;
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @param array $members
     * @throws ArkLDAPModifyFailed
     */
    public function addMembers(array $members)
    {
        $this->arkLdap->modifyEntryAddAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @param array $members
     * @throws ArkLDAPModifyFailed
     */
    public function removeMembers(array $members)
    {
        $this->arkLdap->modifyEntryDeleteAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * WARNING: MAY NOT AVAILABLE FOR CERTAIN SCHEMA SETTINGS
     * @param $members
     * @throws ArkLDAPModifyFailed
     */
    public function setMembers($members)
    {
        $this->arkLdap->modifyEntryReplaceAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }
}