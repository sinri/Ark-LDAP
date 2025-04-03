<?php


namespace sinri\ark\ldap\entity;


use InvalidArgumentException;
use sinri\ark\core\ArkHelper;

/**
 * Represents an LDAP Distinguished Name (DN) entity, which is used to uniquely identify entries in an LDAP directory.
 * The DN is composed of one or more Relative Distinguished Names (RDNs), which are key-value pairs.
 * Common keys include:
 * - cn (Common Name)
 * - ou (Organizational Unit)
 * - dc (Domain Component)
 *
 * Example: cn=John Doe,ou=People,dc=example,dc=com
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
     */
    public function __construct(array $cn = [], array $ou = [], array $dc = [])
    {
        $this->dict = [
            self::COMMON_NAME => $cn,
            self::ORGANIZATIONAL_UNIT => $ou,
            self::DOMAIN_COMPONENT => $dc,
        ];
    }

    /**
     * Retrieves the domain component items from the dictionary.
     *
     * @return string[] An array of domain component items.
     */
    public function getDomainComponentItems(): array
    {
        return ArkHelper::readTarget($this->dict, [self::DOMAIN_COMPONENT], []);
    }


    /**
     * Retrieves the organizational unit items from the dictionary.
     *
     * @return string[] An array of organizational unit items.
     */
    public function getOrganizationalUnitItems(): array
    {
        return ArkHelper::readTarget($this->dict, [self::ORGANIZATIONAL_UNIT], []);
    }

    /**
     * Retrieves the common name items from the dictionary.
     *
     * @return string[] An array of common name items.
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
     * Parses a Distinguished Name (DN) string and returns an ArkLDAPDistinguishedNameEntity object.
     *
     * @param string $dn The DN string to be parsed.
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
            $result[$key][] = static::unescapeDNComponent($value);
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
    public function makeSubItemDNWithDC(string $dcName): ArkLDAPDistinguishedNameEntity
    {
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::DOMAIN_COMPONENT], $dcName);
        return $newEntity;
    }

    /**
     * Creates a new ArkLDAPDistinguishedNameEntity with the specified organizational unit (OU) added to the beginning of the OU list.
     *
     * @param string $ouName The name of the organizational unit to add.
     * @return ArkLDAPDistinguishedNameEntity A new instance of ArkLDAPDistinguishedNameEntity with the updated OU.
     */
    public function makeSubItemDNWithOU(string $ouName): ArkLDAPDistinguishedNameEntity
    {
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::ORGANIZATIONAL_UNIT], $ouName);
        return $newEntity;
    }

    /**
     * Creates a new ArkLDAPDistinguishedNameEntity with the provided common name (CN) added as a sub-item.
     *
     * @param string $cnName The common name to be added to the Distinguished Name (DN).
     * @return ArkLDAPDistinguishedNameEntity A new entity with the updated CN.
     */
    public function makeSubItemDNWithCN(string $cnName): ArkLDAPDistinguishedNameEntity
    {
        $newEntity = new ArkLDAPDistinguishedNameEntity();
        $newEntity->dict = $this->dict;
        array_unshift($newEntity->dict[self::COMMON_NAME], $cnName);
        return $newEntity;
    }

    /**
     * Loads the move destination arguments for a distinguished name (DN) based on the available common name, organizational unit, or domain component.
     *
     * @param string $name Reference to the variable that will hold the name part of the DN.
     * @param string $parent Reference to the variable that will hold the parent part of the DN.
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

    /**
     * Unescapes a string that contains escaped characters in the form of \xx, where xx is a two-digit hexadecimal number.
     *
     * @param string $escaped The string to unescape. It should contain valid escape sequences in the form of \xx.
     * @return string The unescaped string.
     * @throws InvalidArgumentException If an invalid escape sequence is encountered.
     * @since 0.0.8
     */
    public static function unescapeDNComponent(string $escaped): string
    {
        if (strlen($escaped) === 0) {
            return '';
        }

        // 正则表达式匹配所有LDAP转义序列：\XX（两位十六进制）
        $unescaped = preg_replace_callback(
            '/\\\\([0-9A-Fa-f]{2})/', // 匹配 \ 后跟两位十六进制
            function ($matches) {
                // 将十六进制转换为对应的字符
                $hexValue = hexdec($matches[1]);
                if ($hexValue === 0 && !ctype_xdigit($matches[1])) {
                    throw new InvalidArgumentException("Invalid hex sequence: " . $matches[0]);
                }
                return chr($hexValue);
            },
            $escaped
        );

        // 处理连续反斜杠的情况（如 \\\\ → \\）
        // 注意：ldap_escape转义反斜杠为 \5c，所以反转义后应恢复为单个反斜杠
        // 但原始字符串中的连续反斜杠可能被转义为多个\5c，因此需要确保正确还原
        // 例如，原始字符串 "\\" 被转义为 "\5c5c"，反转义后应为 "\\"（两个反斜杠）
        // 此处无需额外处理，因为正则表达式已经覆盖所有转义序列

        return $unescaped;
    }

}