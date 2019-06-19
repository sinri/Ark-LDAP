<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\core\ArkHelper;

class ArkLDAPDistinguishedNameEntity
{
    const DOMAIN_COMPONENT = "dc";
    const ORGANIZATIONAL_UNIT = "ou";
    const COMMON_NAME = "cn";

    protected $dict;

    /**
     * ArkLDAPDistinguishedNameEntity constructor.
     * @param string[] $cn
     * @param string[] $ou
     * @param string[] $dc
     */
    public function __construct($cn = [], $ou = [], $dc = [])
    {
        $this->dict = [
            self::COMMON_NAME => $cn,
            self::ORGANIZATIONAL_UNIT => $ou,
            self::DOMAIN_COMPONENT => $dc,
        ];
    }

    /**
     * @return string[]
     */
    public function getDomainComponentItems()
    {
        return ArkHelper::readTarget($this->dict, [self::DOMAIN_COMPONENT], []);
    }

    /**
     * @return string[]
     */
    public function getOrganizationalUnitItems()
    {
        return ArkHelper::readTarget($this->dict, [self::ORGANIZATIONAL_UNIT], []);
    }

    /**
     * @return string[]
     */
    public function getCommonNameItems()
    {
        return ArkHelper::readTarget($this->dict, [self::COMMON_NAME], []);
    }

    /**
     * @return string
     */
    public function generateDNString()
    {
        $dn = "";
        $cns = $this->getCommonNameItems();
        foreach ($cns as $cn) {
            $dn .= ($dn !== '' ? ',' : '') . 'cn=' . ldap_escape($cn, "", LDAP_ESCAPE_DN);
        }
        $ous = $this->getOrganizationalUnitItems();
        foreach ($ous as $ou) {
            $dn .= ($dn !== '' ? ',' : '') . 'ou=' . ldap_escape($ou, "", LDAP_ESCAPE_DN);
        }
        $dcs = $this->getDomainComponentItems();
        foreach ($dcs as $dc) {
            $dn .= ($dn !== '' ? ',' : '') . 'dc=' . ldap_escape($dc, "", LDAP_ESCAPE_DN);
        }
        return $dn;
    }

    /**
     * @param string $dn
     * @return ArkLDAPDistinguishedNameEntity
     */
    public static function parseFromDNString($dn)
    {
        $components = preg_split('/\s*,\s*/', $dn);
        $result = [
            self::COMMON_NAME => [],
            self::ORGANIZATIONAL_UNIT => [],
            self::DOMAIN_COMPONENT => [],
        ];
        foreach ($components as $component) {
            $parts = explode("=", $component);
            if (count($parts) < 1) continue;
            $key = strtolower($parts[0]);
            $value = $parts[1];
            $result[$key][] = $value;
        }

        $entity = new ArkLDAPDistinguishedNameEntity();
        $entity->dict = $result;

        return $entity;
    }

    public function makeSubItemDNWithDC($dcName)
    {
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::DOMAIN_COMPONENT], $dcName);
        return $newEntity;
    }

    public function makeSubItemDNWithOU($ouName)
    {
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::ORGANIZATIONAL_UNIT], $ouName);
        return $newEntity;
    }

    public function makeSubItemDNWithCN($cnName)
    {
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::COMMON_NAME], $cnName);
        return $newEntity;
    }

    public function loadMoveDestinationArguments(&$name, &$parent)
    {
        if (!empty($this->dict[self::COMMON_NAME])) {
            $name = "cn=" . array_shift($this->dict[self::COMMON_NAME]);
            $parent = $this->generateDNString();
        } elseif (!empty($this->dict[self::ORGANIZATIONAL_UNIT])) {
            $name = "ou=" . array_shift($this->dict[self::ORGANIZATIONAL_UNIT]);
            $parent = $this->generateDNString();
        } elseif (!empty($this->dict[self::DOMAIN_COMPONENT])) {
            $name = "dc=" . array_shift($this->dict[self::DOMAIN_COMPONENT]);
            $parent = $this->generateDNString();
        }
    }
}