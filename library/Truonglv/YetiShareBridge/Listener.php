<?php

class Truonglv_YetiShareBridge_Listener
{
    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += Truonglv_YetiShareBridge_FileSums::getHashes();
    }

    public static function load_class_XenForo_DataWriter_User($class, array &$extend)
    {
        if ($class === 'XenForo_DataWriter_User') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_DataWriter_User';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Login($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Login') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_ControllerPublic_Login';
        }
    }

    public static function load_class_XenForo_Model_UserUpgrade($class, array &$extend)
    {
        if ($class === 'XenForo_Model_UserUpgrade') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_Model_UserUpgrade';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Logout($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Logout') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_ControllerPublic_Logout';
        }
    }

    public static function load_class_XenForo_ControllerAdmin_Log($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerAdmin_Log') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_ControllerAdmin_Log';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Register($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Register') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_ControllerPublic_Register';
        }
    }

    public static function load_class_XenForo_ControllerPublic_Account($class, array &$extend)
    {
        if ($class === 'XenForo_ControllerPublic_Account') {
            $extend[] = 'Truonglv_YetiShareBridge_XenForo_ControllerPublic_Account';
        }
    }
}
