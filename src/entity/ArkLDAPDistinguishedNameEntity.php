<?php


namespace sinri\ark\ldap\entity;


use sinri\ark\core\ArkHelper;
use sinri\ark\ldap\ArkLDAP;

/**
 * Represents an LDAP Distinguished Name (DN) entity, which is used to uniquely identify entries in an LDAP directory.
 * The DN is composed of one or more Relative Distinguished Names (RDNs), which are key-value pairs.
 * Common keys include:
 * - cn (Common Name)
 * - ou (Organizational Unit)
 * - dc (Domain Component)
 *
 * Example: cn=John Doe,ou=People,dc=example,dc=com
 *
 * Note: As of 0.0.10, all components are escaped.
 */
class ArkLDAPDistinguishedNameEntity
{
    const DOMAIN_COMPONENT = "dc";
    const ORGANIZATIONAL_UNIT = "ou";
    const COMMON_NAME = "cn";

    protected array $dict;

    /**
     * Constructs a new instance of the class with the provided common name, organizational unit, and domain component.
     *
     * @param string[] $cn An array of common name items.
     * @param string[] $ou An array of organizational unit items.
     * @param string[] $dc An array of domain component items.
     * @param bool $escaped if the provided components are escaped.
     */
    public function __construct(array $cn = [], array $ou = [], array $dc = [], bool $escaped = false)
    {
        if ($escaped) {
            $this->dict = [
                self::COMMON_NAME => $cn,
                self::ORGANIZATIONAL_UNIT => $ou,
                self::DOMAIN_COMPONENT => $dc,
            ];
        } else {
            $this->dict = [
                self::COMMON_NAME => $this->escapeDNComponents($cn),
                self::ORGANIZATIONAL_UNIT => $this->escapeDNComponents($ou),
                self::DOMAIN_COMPONENT => $this->escapeDNComponents($dc),
            ];
        }
    }

    /**
     * @param string $dnComponent
     * @return string
     * @since 0.0.10
     */
    private function escapeOneDNComponent(string $dnComponent): string
    {
        return ArkLDAP::escapeDNArgument($dnComponent);
    }

    /**
     * @param string[] $dnComponents
     * @return string[]
     * @since 0.0.10
     */
    private function escapeDNComponents(array $dnComponents): array
    {
        $a = [];
        foreach ($dnComponents as $dnComponent) {
            $a[] = $this->escapeOneDNComponent($dnComponent);
        }
        return $a;
    }

    /**
     * Retrieves the domain component items from the dictionary.
     *
     * @return string[] An array of domain component items, each of them are escaped.
     */
    public function getDomainComponentItems(): array
    {
        return ArkHelper::readTarget($this->dict, [self::DOMAIN_COMPONENT], []);
    }


    /**
     * Retrieves the organizational unit items from the dictionary.
     *
     * @return string[] An array of organizational unit items, each of them are escaped.
     */
    public function getOrganizationalUnitItems(): array
    {
        return ArkHelper::readTarget($this->dict, [self::ORGANIZATIONAL_UNIT], []);
    }

    /**
     * Retrieves the common name items from the dictionary.
     *
     * @return string[] An array of common name items, each of them are escaped.
     */
    public function getCommonNameItems(): array
    {
        return ArkHelper::readTarget($this->dict, [self::COMMON_NAME], []);
    }

    /**
     * Generates a Distinguished Name (DN) string based on the common name, organizational unit, and domain component items.
     *
     * @return string The generated DN string.
     */
    public function generateDNString(): string
    {
        $dn = "";
        $cns = $this->getCommonNameItems();
        foreach ($cns as $cn) {
            $dn .= ($dn !== '' ? ',' : '') . 'cn=' . $cn;
        }
        $ous = $this->getOrganizationalUnitItems();
        foreach ($ous as $ou) {
            $dn .= ($dn !== '' ? ',' : '') . 'ou=' . $ou;
        }
        $dcs = $this->getDomainComponentItems();
        foreach ($dcs as $dc) {
            $dn .= ($dn !== '' ? ',' : '') . 'dc=' . $dc;
        }
        return $dn;
    }

    /**
     * Parses a Distinguished Name (DN) string and returns an ArkLDAPDistinguishedNameEntity object.
     *
     * @param string $dn The DN string to be parsed, should be fully escaped as DN.
     * @return ArkLDAPDistinguishedNameEntity An object containing the parsed components of the DN string.
     */
    public static function parseFromDNString(string $dn): ArkLDAPDistinguishedNameEntity
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

    /**
     * Creates a new ArkLDAPDistinguishedNameEntity with the provided domain component (DC) added to the beginning of the existing DC list.
     *
     * @param string $dcName The name of the domain component to be added.
     * @return ArkLDAPDistinguishedNameEntity A new instance of ArkLDAPDistinguishedNameEntity with the updated domain component.
     */
    public function makeSubItemDNWithDC(string $dcName, bool $escaped = false): ArkLDAPDistinguishedNameEntity
    {
        $x = $dcName;
        if (!$escaped) {
            $x = $this->escapeOneDNComponent($dcName);
        }
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::DOMAIN_COMPONENT], $x);
        return $newEntity;
    }

    /**
     * Creates a new ArkLDAPDistinguishedNameEntity with the specified organizational unit (OU) added to the beginning of the OU list.
     *
     * @param string $ouName The name of the organizational unit to add.
     * @return ArkLDAPDistinguishedNameEntity A new instance of ArkLDAPDistinguishedNameEntity with the updated OU.
     */
    public function makeSubItemDNWithOU(string $ouName, bool $escaped = false): ArkLDAPDistinguishedNameEntity
    {
        $x = $ouName;
        if (!$escaped) {
            $x = $this->escapeOneDNComponent($ouName);
        }
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::ORGANIZATIONAL_UNIT], $x);
        return $newEntity;
    }

    /**
     * Creates a new ArkLDAPDistinguishedNameEntity with the provided common name (CN) added as a sub-item.
     *
     * @param string $cnName The common name to be added to the Distinguished Name (DN).
     * @return ArkLDAPDistinguishedNameEntity A new entity with the updated CN.
     */
    public function makeSubItemDNWithCN(string $cnName, bool $escaped = false): ArkLDAPDistinguishedNameEntity
    {
        $x = $cnName;
        if (!$escaped) {
            $x = $this->escapeOneDNComponent($cnName);
        }
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::COMMON_NAME], $x);
        return $newEntity;
    }

    /**
     * Loads the move destination arguments for a distinguished name (DN) based on the available common name, organizational unit, or domain component.
     *
     * @param string $name Reference to the variable that will hold the name part of the DN, escaped.
     * @param string $parent Reference to the variable that will hold the parent part of the DN, escaped.
     *
     * @return void
     */
    public function loadMoveDestinationArguments(string &$name, string &$parent)
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