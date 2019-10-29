<?php

class Truonglv_YetiShareBridge_CronEntry_Auto
{
    public static function runHourly()
    {
        $db = XenForo_Application::getDb();
        $db->delete(
            'xf_truonglv_yetisharebridge_log',
            'log_date < ' . $db->quote(XenForo_Application::$time - 30 * 86400)
        );
    }
}
