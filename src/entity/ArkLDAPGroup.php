<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\ldap\ArkLDAP;

class ArkLDAPGroup extends ArkLDAPTopEntity
{
    public function getEntityClassType()
    {
        return "group";
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return bool|ArkLDAPGroup
     */
    public static function loadGroupByDNString($arkLdap, $dn)
    {
        $list = $arkLdap->readAll($dn, "name=*");
        if (empty($list)) {
            return false;
        }
        $arkGroup = new ArkLDAPGroup($arkLdap);
        $arkGroup->data = $list[0];
        $arkGroup->dnEntity = ArkLDAPDistinguishedNameEntity::parseFromDNString($arkGroup->getDistinguishedName());
        return $arkGroup;
    }

    /**
     * @param ArkLDAP $arkLdap
     * @param string $dn
     * @return bool
     */
    public static function createGroup($arkLdap, $dn)
    {
        $entry = ['objectclass' => ['top', 'group']];
        return $arkLdap->addEntry($dn, $entry);
    }

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
     * @param $members
     * @return bool
     */
    public function addMembers($members)
    {
        return $this->arkLdap->modifyEntryAddAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * @param $members
     * @return bool
     */
    public function removeMembers($members)
    {
        return $this->arkLdap->modifyEntryDeleteAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }

    /**
     * @param $members
     * @return bool
     */
    public function setMembers($members)
    {
        return $this->arkLdap->modifyEntryReplaceAttributes($this->dnEntity->generateDNString(), ['member' => $members]);
    }
}