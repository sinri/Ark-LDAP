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

        $total = [];
        $entryResource = ldap_first_entry($this->connection, $searchResult);
        while ($entryResource) {
            $info = ldap_get_attributes($this->connection, $entryResource);
            $total[] = new ArkLDAPItem($info);
            $entryResource = ldap_next_entry($this->connection, $entryResource);
        }

        return $total;
    }

//    protected function debugDumpSearchResultEntries($info)
//    {
//        try {
//            $objectClass = new ArkLDAPObjectClass($info);
//
//            $count = $objectClass->getCount();
//            echo "count: " . $count . PHP_EOL;
//            for ($i = 0; $i < $count; $i++) {
//                echo "ITEM $i:" . PHP_EOL;
//                $rawItem = $objectClass->getRawItemByIndex($i);
//                $item = new ArkLDAPItem($rawItem);
//
//                echo "DN : " . $item->getDN() . PHP_EOL;
//                echo "Surname: " . $item->getFieldValue(ArkLDAPItem::FIELD_SURNAME) . PHP_EOL;
//                echo "Display: " . $item->getFieldValue(ArkLDAPItem::FIELD_DISPLAY_NAME) . PHP_EOL;
//
//            }
//        } catch (Exception $e) {
//            echo "Exception: " . $e->getMessage() . PHP_EOL;
//        }
//    }

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

    public function modifyEntry($dn, $entry)
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
        $argument = str_replace("(", "\(", $argument);
        $argument = str_replace(")", "\)", $argument);
        return $argument;
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
}