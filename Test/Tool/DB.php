<?php
/**
 * @package Test
 * @subpackage Tool
 */

/**
 * @package Test
 * @subpackage Tool
 * @author Davide Principi <davide.principi@nethesis.it>
 * @see Test_Tool_ModuleTestCase
 */
class Test_Tool_DB extends Test_Tool_MockState
{

    public static function getType($key)
    {
        return array('getType', array($key));
    }

    public static function setType($key, $value)
    {
        return array('setType', array($key, $value));
    }

    public static function getKey($key)
    {
        return array('getKey', array($key));
    }

    public static function setKey($key, $type, $props)
    {
        return array('setKey', array($key, $type, $props));
    }

    public static function getProp($key, $prop)
    {
        return array('getProp', array($key, $prop));
    }

    public static function setProp($key, $prop, $value = NULL)
    {
        if (is_string($prop)) {
            return array('setProp', array($key, array($prop => $value)));
        }
        return array('setProp', array($key, $prop));
    }

    public static function getAll($type = FALSE, $filter = FALSE)
    {
        return array('getAll', array($type, $filter));
    }

    public static function deleteKey($key)
    {
        return array('deleteKey', array($key));
    }

    public static function delProp($key, $prop)
    {
        if (is_string($prop)) {
            $prop = array($prop);
        }
        return array('delProp', array($prop));
    }

}