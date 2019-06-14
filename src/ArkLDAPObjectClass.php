<?php


namespace sinri\ark\ldap;


use sinri\ark\core\ArkHelper;

class ArkLDAPObjectClass
{
    protected $rawArray;

    public function __construct($array)
    {
        $this->rawArray = $array;
    }

    public function getCount()
    {
        return ArkHelper::readTarget($this->rawArray, ['count'], 0);
    }

    /**
     * @param $index
     * @return mixed|null
     */
    public function getRawItemByIndex($index)
    {
        if ($index < 0 || $index >= $this->getCount()) {
            //throw new Exception("Index Out Of Bound");
            return false;
        }
        return ArkHelper::readTarget($this->rawArray, [$index]);
    }

    /**
     * @param $index
     * @return bool|ArkLDAPObjectClass
     */
    public function getObjectClassByIndex($index)
    {
        if ($index < 0 || $index >= $this->getCount()) {
            //throw new Exception("Index Out Of Bound");
            return false;
        }
        $x = ArkHelper::readTarget($this->rawArray, [$index]);
        if (empty($x)) return false;
        return new ArkLDAPObjectClass($x);
    }
}