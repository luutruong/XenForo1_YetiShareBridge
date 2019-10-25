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
}
