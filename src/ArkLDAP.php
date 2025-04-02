<?php
namespace sinri\ark\ldap;


use Exception;
use sinri\ark\core\ArkLogger;
use sinri\ark\ldap\exception\ArkLDAPBindAuthFailed;
use sinri\ark\ldap\exception\ArkLDAPConnectFailed;
use sinri\ark\ldap\exception\ArkLDAPModifyFailed;
use sinri\ark\ldap\exception\ArkLDAPReadFailed;

/**
 * Class ArkLDAP
 * A PHP class for interacting with LDAP (Lightweight Directory Access Protocol) servers
 * 
 * This class provides a comprehensive interface for LDAP operations including:
 * - Connecting and binding to LDAP servers
 * - Searching, reading and listing LDAP entries
 * - Adding, modifying and deleting entries
 * - Managing attributes and moving entries
 * - Password modification support
 * - DN parsing and string escaping utilities
 * 
 * Features:
 * - Secure connection handling with TLS support
 * - Flexible search operations (base, one-level, subtree)
 * - Recursive delete operations
 * - Error handling with specific exceptions
 * - Logging support
 * 
 * @package sinri\ark\ldap
 * @since 0.0.1
 */
class ArkLDAP
{
    protected string $server;
    protected string $username;
    protected string $password;

    protected array $options = [];
    /**
     * @var resource
     */
    protected $connection;

    protected ArkLogger $logger;

    /**
     * ArkLDAP constructor.
     * @param string $server LDAP server address (e.g., ldap://localhost:389)
     * @param string $username The DN of the LDAP account to bind with
     * @param string $password Password for the LDAP account
     * @param array $options Additional LDAP options to set after connection
     */
    public function __construct(string $server, string $username, string $password, array $options = [])
    {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->logger = ArkLogger::makeSilentLogger();
    }

    /**
     * @param ArkLogger $logger
     */
    public function setLogger(ArkLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set additional LDAP options for the connection
     * @param array $options Array of LDAP options where key is the option constant and value is the option value
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Establishes a connection to the LDAP server and performs binding with provided credentials
     * @return void
     * @throws ArkLDAPBindAuthFailed When authentication with provided credentials fails
     * @throws ArkLDAPConnectFailed When connection to LDAP server fails
     */
    public function connect()
    {
        $this->connection = ldap_connect($this->server);
        if (!$this->connection) {
            $this->logger->error("ldap_connect cannot link to " . $this->server);
            throw new ArkLDAPConnectFailed($this, $this->server);
        }

        foreach ($this->options as $key => $value) {
            ldap_set_option($this->connection, $key, $value);
        }

//        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
//        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
//        ldap_start_tls($this->connection);

        $bound = ldap_bind($this->connection, $this->username, $this->password);
        if (!$bound) {
            $this->logger->error(__METHOD__ . '@' . __LINE__ . " ldap_bind failed with " . $this->username, ['error' => ldap_error($this->connection)]);
            throw new ArkLDAPBindAuthFailed($this, $this->username);
        }
    }

    /**
     * Search for LDAP entries in the entire subtree under the specified base DN
     * This method performs a recursive search starting from the base DN and includes all entries in the subtree
     * @param string $baseDN The base DN to start the search from
     * @param string $filter LDAP search filter (e.g., "(objectClass=person)")
     * @param string[] $attrNames Array of attribute names to retrieve, ['*'] for all attributes
     * @param int $attrsOnly Set to 1 to get attribute names only, 0 to get both names and values
     * @param int $sizeLimit Maximum number of entries to return (0 for no limit)
     * @param int $timeLimit Maximum time in seconds to wait for search results (0 for no limit)
     * @return ArkLDAPItem[] Array of LDAP entries matching the search criteria
     * @throws ArkLDAPReadFailed When the search operation fails
     */
    public function searchAll(string $baseDN, string $filter, array $attrNames = ['*'], int $attrsOnly = 0, int $sizeLimit = 0, int $timeLimit = 0): array
    {
        $searchResult = ldap_search($this->connection, $baseDN, $filter, $attrNames, $attrsOnly, $sizeLimit, $timeLimit);
        if (!$searchResult) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . " search result empty", ['dn' => $baseDN, 'filter' => $filter, 'attr' => $attrNames, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPReadFailed($this, "searchAll $filter in $baseDN");
        }

        return $this->fetchResultResource($searchResult);
    }

    /**
     * Read entries directly under the specified base DN using LDAP_SCOPE_BASE scope
     * This method performs a base-level search that only returns the entry at the specified DN
     * and does not search any entries below it in the directory tree
     * @param string $baseDN The base DN to read from
     * @param string $filter LDAP search filter (e.g., "(objectClass=person)")
     * @param array|null $attrNames Array of attribute names to retrieve, null for all attributes
     * @return ArkLDAPItem[] Array of LDAP entries matching the search criteria
     * @throws ArkLDAPReadFailed When the read operation fails
     *
     */
    public function readAll(string $baseDN, string $filter, array $attrNames = null): array
    {
        if ($attrNames === null)
            $readResult = ldap_read($this->connection, $baseDN, $filter);
        else
            $readResult = ldap_read($this->connection, $baseDN, $filter, $attrNames);
        if (!$readResult) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . " search result empty", ['dn' => $baseDN, 'filter' => $filter, 'attr' => $attrNames, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPReadFailed($this, "readAll $filter in $baseDN");
        }

        return $this->fetchResultResource($readResult);
    }

    /**
     * Search for LDAP entries one level under the specified base DN using LDAP_SCOPE_ONELEVEL scope
     * This method performs a single-level search that returns entries directly under the base DN,
     * but does not include the base entry itself or entries deeper in the subtree
     * @param string $baseDN The base DN to search under
     * @param string $filter LDAP search filter (e.g., "(objectClass=person)")
     * @param array|null $attrNames Array of attribute names to retrieve, null for all attributes
     * @return ArkLDAPItem[] Array of LDAP entries matching the search criteria
     * @throws ArkLDAPReadFailed When the list operation fails
     */
    public function listAll(string $baseDN, string $filter, array $attrNames = null): array
    {
        if ($attrNames === null)
            $readResult = ldap_list($this->connection, $baseDN, $filter);
        else
            $readResult = ldap_list($this->connection, $baseDN, $filter, $attrNames);
        if (!$readResult) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . " search result empty", ['dn' => $baseDN, 'filter' => $filter, 'attr' => $attrNames, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPReadFailed($this, "listAll $filter in $baseDN");
        }

        return $this->fetchResultResource($readResult);
    }

    /**
     * Fetches and processes LDAP search results into an array of ArkLDAPItem objects
     * @param resource $result The LDAP search result resource to process; it would not be FALSE as filtered before
     * @return ArkLDAPItem[] Array of LDAP entries as ArkLDAPItem objects
     * @internal This method is used internally to process search/read/list results
     */
    protected function fetchResultResource($result): array
    {
        if (!$result) {
            return [];
        }

        $total = [];
        $entryResource = ldap_first_entry($this->connection, $result);
        while ($entryResource) {
            $info = ldap_get_attributes($this->connection, $entryResource);
            $total[] = new ArkLDAPItem($info);
            $entryResource = ldap_next_entry($this->connection, $entryResource);
        }

        return $total;
    }

    /**
     * Adds a new entry to the LDAP directory
     * @param string $dn The distinguished name (DN) of the entry to add
     * @param array $entry An array that specifies the information about the entry
     * @return void
     * @throws ArkLDAPModifyFailed When the add operation fails
     *
     */
    public function addEntry(string $dn, array $entry)
    {
        $done = ldap_add($this->connection, $dn, $entry);
        if (!$done) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to add entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPModifyFailed($this, "addEntry $dn");
        }
    }

    /**
     * Deletes an LDAP entry and optionally its children recursively
     * @param string $dn The distinguished name (DN) of the entry to delete
     * @param bool $recursive If true, recursively deletes all child entries under the specified DN
     * @return void
     * @throws ArkLDAPModifyFailed When the delete operation fails
     *
     */
    public function deleteEntry(string $dn, bool $recursive = false)
    {
        if (!$recursive) {
            $done = (ldap_delete($this->connection, $dn));
            if (!$done) {
                $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to delete entry', ['dn' => $dn, 'error' => ldap_error($this->connection)]);
                throw new ArkLDAPModifyFailed($this, "deleteEntry $dn itself");
            }
        } else {
            //searching for sub entries
            $sr = ldap_list($this->connection, $dn, "ObjectClass=*", array(""));
            $info = ldap_get_entries($this->connection, $sr);
            for ($i = 0; $i < $info['count']; $i++) {
                //deleting recursively sub entries
                $childDN = $info[$i]['dn'];
                $this->deleteEntry($childDN, true);
            }
            $done = ldap_delete($this->connection, $dn);
            if (!$done) {
                $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to delete entry', ['dn' => $dn, 'error' => ldap_error($this->connection)]);
                throw new ArkLDAPModifyFailed($this, "deleteEntry $dn recursively");
            }
        }
    }

    /**
     * Adds new attributes to an existing LDAP entry
     * @param string $dn The distinguished name (DN) of the entry to modify
     * @param array $entry An array containing the attributes to add, where keys are attribute names and values are attribute values
     * @return void
     * @throws ArkLDAPModifyFailed When the attribute addition fails
     *
     */
    public function modifyEntryAddAttributes(string $dn, array $entry)
    {
        $done = ldap_mod_add($this->connection, $dn, $entry);
        if (!$done) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to modify entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPModifyFailed($this, "modifyEntryAddAttributes $dn");
        }
    }

    /**
     * Deletes specified attributes from an existing LDAP entry
     * @param string $dn The distinguished name (DN) of the entry to modify
     * @param array $entry An array containing the attributes to delete, where keys are attribute names and values are attribute values
     * @return void
     * @throws ArkLDAPModifyFailed When the attribute deletion fails
     *
     */
    public function modifyEntryDeleteAttributes(string $dn, array $entry)
    {
        $done = ldap_mod_del($this->connection, $dn, $entry);
        if (!$done) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to modify entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPModifyFailed($this, "modifyEntryDeleteAttributes $dn");
        }
    }

    /**
     * Replaces existing attributes in an LDAP entry with new values
     * @param string $dn The distinguished name (DN) of the entry to modify
     * @param array $entry An array containing the attributes to replace, where keys are attribute names and values are new attribute values
     *
     * @return void
     * @throws ArkLDAPModifyFailed
     */
    public function modifyEntryReplaceAttributes(string $dn, array $entry)
    {
        $done = ldap_mod_replace($this->connection, $dn, $entry);
        if (!$done) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to modify entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPModifyFailed($this, "modifyEntryReplaceAttributes $dn");
        }
    }

    /**
     * Moves or renames an LDAP entry to a new location in the directory tree
     * @param string $dn The distinguished name (DN) of the entry to move, such as 'cn=X,ou=Y,dc=Z'
     * @param string $newDn The new RDN (Relative Distinguished Name) for the entry, such as 'cn=X1'
     * @param string $newParentDn The DN of the new parent entry where the entry will be moved to, such as 'ou=Y1,dc=Z'
     * @return void
     *
     * @throws ArkLDAPModifyFailed
     */
    public function moveEntry(string $dn, string $newDn, string $newParentDn)
    {
        $done = ldap_rename($this->connection, $dn, $newDn, $newParentDn, true);
        if (!$done) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to move entry', ['dn' => $dn, 'new_dn' => $newDn, 'new_parent_dn' => $newParentDn, 'error' => ldap_error($this->connection)]);
            throw new ArkLDAPModifyFailed($this, "moveEntry $dn to $newDn under $newParentDn");
        }
    }

    /**
     * Closes the LDAP connection
     * @return bool Returns true on success or false on failure
     * @since 0.0.1
     */
    public function close(): bool
    {
        return ldap_unbind($this->connection);
    }

    /**
     * Escapes special characters in a string to be used in an LDAP search filter
     * This method ensures that special characters in search filter arguments are properly escaped
     * to prevent LDAP injection attacks and ensure correct filter syntax
     * @param string $argument The string to be escaped
     * @return string The escaped string safe for use in LDAP search filters
     * @since 0.0.1
     */
    public static function escapeSearchFilterArgument(string $argument): string
    {
        //$argument = str_replace("(", "\(", $argument);
        //$argument = str_replace(")", "\)", $argument);
        //return $argument;

        return ldap_escape($argument, '', LDAP_ESCAPE_FILTER);
    }

    /**
     * Escapes special characters in a string to be used in a DN (Distinguished Name)
     * This method ensures that special characters in DN components are properly escaped
     * to prevent LDAP injection attacks and ensure correct DN syntax
     * @param string $argument The string to be escaped
     * @return string The escaped string safe for use in LDAP DNs
     */
    public static function escapeDNArgument(string $argument): string
    {
        return ldap_escape($argument, '', LDAP_ESCAPE_DN);
    }

    /**
     * Parses a Distinguished Name (DN) string into its component parts
     * This method breaks down a DN string into its constituent components and organizes them by type (cn, ou, dc)
     * @param string $dn The Distinguished Name string to parse (e.g., "cn=user,ou=people,dc=example,dc=com")
     * @return array An associative array containing the parsed components grouped by type:
     *               ['cn' => [...], 'ou' => [...], 'dc' => [...]]
     * @since 0.0.1
     */
    public static function parseDN(string $dn): array
    {
        $components = preg_split('/\s*,\s*/', $dn);
        $result = [
            'cn' => [],
            'ou' => [],
            'dc' => [],
        ];
        foreach ($components as $component) {
            $parts = explode("=", $component);
            if (count($parts) < 1) continue;
            $key = strtolower($parts[0]);
            $value = $parts[1];
            $result[$key][] = $value;
        }

        return $result;
    }

    /**
     * Modifies a user's password using the LDAP Password Modify Extended Operation (RFC 3062)
     * This method allows changing LDAP user passwords using the extended operation
     * @param string $dnOfUser The distinguished name (DN) of the user whose password is to be modified
     * @param string|null $oldPassword The user's current password (can be null if not required)
     * @param string|null $newPassword The new password to set (can be null to generate a random password)
     * @return bool|string Returns true on success, or the generated password if newPassword was null
     * @throws Exception When the password modification operation fails
     * @since 0.0.3
     * @uses PHP 7.2
     */
    public function modifyUserPasswordExOp(string $dnOfUser, string $oldPassword = null, string $newPassword = null)
    {
        return ldap_exop_passwd($this->connection, $dnOfUser, $oldPassword, $newPassword);
    }

    /**
     * Returns the current LDAP connection resource
     * This method provides access to the underlying LDAP connection resource
     * that can be used for direct LDAP operations
     * @return resource The LDAP connection resource handle
     * @since 0.0.4
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return int
     * @since 0.0.5
     */
    public function getLastErrNo(): int
    {
        if (!!$this->connection) {
            return ldap_errno($this->connection);
        }
        return -1;
    }

    /**
     * @return string
     * @since 0.0.5
     */
    public function getLastError(): string
    {
        if (!!$this->connection) {
            return ldap_error($this->connection);
        }
        return "Not Connected Yet";
    }
}