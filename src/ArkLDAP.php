<?php


namespace sinri\ark\ldap;


use sinri\ark\core\ArkLogger;

class ArkLDAP
{
    protected $server;
    protected $username;
    protected $password;

    protected $options = [];

    protected $connection;

    protected $logger;

    public function __construct($server, $username, $password, $options = [])
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
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function connect()
    {
        $this->connection = ldap_connect($this->server);
        if (!$this->connection) {
            $this->logger->error("ldap_connect cannot link to " . $this->server);
            return false;
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
        }
        return $bound;
    }

    /**
     * LDAP_SCOPE_SUBTREE
     * @param $baseDN
     * @param $filter
     * @param null $attrNames
     * @return ArkLDAPItem[]|bool
     */
    public function searchAll($baseDN, $filter, $attrNames = null)
    {
        if (is_array($attrNames))
            $searchResult = ldap_search($this->connection, $baseDN, $filter, $attrNames);
        else
            $searchResult = ldap_search($this->connection, $baseDN, $filter);
        if (!$searchResult) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . " search result empty", ['dn' => $baseDN, 'filter' => $filter, 'attr' => $attrNames, 'error' => ldap_error($this->connection)]);
            return false;
        }

        return $this->fetchResultResource($searchResult);
    }

    /**
     * LDAP_SCOPE_BASE
     * @param $baseDN
     * @param $filter
     * @param null $attrNames
     * @return bool|ArkLDAPItem[]
     */
    public function readAll($baseDN, $filter, $attrNames = null)
    {
        if ($attrNames === null)
            $readResult = ldap_read($this->connection, $baseDN, $filter);
        else
            $readResult = ldap_read($this->connection, $baseDN, $filter, $attrNames);
        if (!$readResult) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . " search result empty", ['dn' => $baseDN, 'filter' => $filter, 'attr' => $attrNames, 'error' => ldap_error($this->connection)]);
            return false;
        }

        return $this->fetchResultResource($readResult);
    }

    /**
     * LDAP_SCOPE_ONELEVEL
     * @param $baseDN
     * @param $filter
     * @param null $attrNames
     * @return bool|ArkLDAPItem[]
     */
    public function listAll($baseDN, $filter, $attrNames = null)
    {
        if ($attrNames === null)
            $readResult = ldap_list($this->connection, $baseDN, $filter);
        else
            $readResult = ldap_list($this->connection, $baseDN, $filter, $attrNames);
        if (!$readResult) {
            $this->logger->warning(__METHOD__ . '@' . __LINE__ . " search result empty", ['dn' => $baseDN, 'filter' => $filter, 'attr' => $attrNames, 'error' => ldap_error($this->connection)]);
            return false;
        }

        return $this->fetchResultResource($readResult);
    }

    /**
     * @param $result
     * @return ArkLDAPItem[]|bool
     */
    protected function fetchResultResource($result)
    {
        if (!$result) {
            return false;
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

    public function addEntry($dn, $entry)
    {
        $done = ldap_add($this->connection, $dn, $entry);
        if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to add entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
        return $done;
    }

    public function deleteEntry($dn, $recursive = false)
    {
        if ($recursive == false) {
            $done = (ldap_delete($this->connection, $dn));
            if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to delete entry', ['dn' => $dn, 'error' => ldap_error($this->connection)]);
            return $done;
        } else {
            //searching for sub entries
            $sr = ldap_list($this->connection, $dn, "ObjectClass=*", array(""));
            $info = ldap_get_entries($this->connection, $sr);
            for ($i = 0; $i < $info['count']; $i++) {
                //deleting recursively sub entries
                $result = $this->deleteEntry($info[$i]['dn'], $recursive);
                if (!$result) {
                    //return result code, if delete fails
                    $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to delete entry', ['dn' => $dn, 'error' => ldap_error($this->connection)]);
                    return ($result);
                }
            }
            $done = ldap_delete($this->connection, $dn);
            if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to delete entry', ['dn' => $dn, 'error' => ldap_error($this->connection)]);
            return $done;
        }
    }

    public function modifyEntryAddAttributes($dn, $entry)
    {
        $done = ldap_mod_add($this->connection, $dn, $entry);
        if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to modify entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
        return $done;
    }

    public function modifyEntryDeleteAttributes($dn, $entry)
    {
        $done = ldap_mod_del($this->connection, $dn, $entry);
        if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to modify entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
        return $done;
    }

    public function modifyEntryReplaceAttributes($dn, $entry)
    {
        $done = ldap_mod_replace($this->connection, $dn, $entry);
        if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to modify entry', ['dn' => $dn, 'entry' => $entry, 'error' => ldap_error($this->connection)]);
        return $done;
    }

    /**
     * @param $dn 'cn=X,ou=Y,dc=Z'
     * @param $newDn 'cn=X1'
     * @param $newParentDn 'ou=Y1,dc=Z'
     * @return bool
     */
    public function moveEntry($dn, $newDn, $newParentDn)
    {
        $done = ldap_rename($this->connection, $dn, $newDn, $newParentDn, true);
        if (!$done) $this->logger->warning(__METHOD__ . '@' . __LINE__ . ' failed to move entry', ['dn' => $dn, 'new_dn' => $newDn, 'new_parent_dn' => $newParentDn, 'error' => ldap_error($this->connection)]);
        return $done;
    }

    public function close()
    {
        ldap_close($this->connection);
    }

    public static function escapeSearchFilterArgument($argument)
    {
        //$argument = str_replace("(", "\(", $argument);
        //$argument = str_replace(")", "\)", $argument);
        //return $argument;

        return ldap_escape($argument, '', LDAP_ESCAPE_FILTER);
    }

    public static function escapeDNArgument($argument)
    {
        return ldap_escape($argument, '', LDAP_ESCAPE_DN);
    }

    public static function parseDN($dn)
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
     * @param string $dnOfUser
     * @param null $oldPassword
     * @param null $newPassword
     * @return bool|string
     * @since 0.0.3
     * @uses PHP 7.2
     */
    public function modifyUserPasswordExOp($dnOfUser, $oldPassword = null, $newPassword = null)
    {
        return ldap_exop_passwd($this->connection, $dnOfUser, $oldPassword, $newPassword);
    }
}