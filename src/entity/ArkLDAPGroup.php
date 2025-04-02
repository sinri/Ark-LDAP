<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;
use sinri\ark\ldap\exception\ArkLDAPDataInvalid;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;
use UnexpectedValueException;

class ArkLDAPGroup extends ArkLDAPTopEntity
{
    public function getEntityClassType(): string
    {
        return "group";
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return ArkLDAPGroup
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public static function loadGroupByDNString(ArkLDAP $arkLdap, string $dn): ArkLDAPGroup
    {
        $list = $arkLdap->readAll($dn, "name=*");
        if (empty($list)) {
            throw new UnexpectedValueException("No result found for DN.");
        }
        $arkGroup = new ArkLDAPGroup($arkLdap);
        $arkGroup->data = $list[0];
        $arkGroup->dnEntity = ArkLDAPDistinguishedNameEntity::parseFromDNString($arkGroup->getDistinguishedName());
        return $arkGroup;
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @throws ArkLDAPModifyFailed
     */
    public static function createGroup(ArkLDAP $arkLdap, string $dn)
    {
        $entry = ['objectclass' => ['top', 'group']];
        $arkLdap->addEntry($dn, $entry);
    }

    /**
     * @return ArkLDAPUser[]
     * @throws ArkLDAPDataInvalid
     * @throws ArkLDAPReadFailed
     */
    public function getMembers(): array
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
     * @param array $members
     * @throws ArkLDAPModifyFailed
     */
    public function addMembers(array $members)
    {
        $this->arkLdap->modifyEntryAddAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * @param array $members
     * @throws ArkLDAPModifyFailed
     */
    public function removeMembers(array $members)
    {
        $this->arkLdap->modifyEntryDeleteAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * @param array $members
     * @return void
     * @throws ArkLDAPModifyFailed
     */
    public function setMembers(array $members)
    {
        $this->arkLdap->modifyEntryReplaceAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }
}