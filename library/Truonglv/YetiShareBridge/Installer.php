<?php

class Truonglv_YetiShareBridge_Installer
{
    /* Start auto-generated lines of code. Change made will be overwriten... */

    protected static $_tables = array(
        'log' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_truonglv_yetisharebridge_log` (
                `log_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT
                ,`user_id` INT(10) UNSIGNED DEFAULT \'0\'
                ,`method` VARCHAR(12) NOT NULL
                ,`end_point` VARCHAR(100) NOT NULL
                ,`request_data` TEXT NOT NULL
                ,`response_data` TEXT NOT NULL
                ,`response_code` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'
                ,`log_date` INT(10) UNSIGNED DEFAULT \'0\'
                , PRIMARY KEY (`log_id`)
                ,INDEX `log_date` (`log_date`)
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_truonglv_yetisharebridge_log`',
        ),
    );
    protected static $_patches = array();

    public static function install($existingAddOn, $addOnData)
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_tables as $table) {
            $db->query($table['createQuery']);
        }

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['tableCheckQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['checkQuery']);
            if (empty($existed)) {
                $db->query($patch['addQuery']);
            } elseif (!empty($patch['modifyQuery'])) {
                $db->query($patch['modifyQuery']);
            }
        }

        self::installCustomized($existingAddOn, $addOnData);
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        foreach (self::$_patches as $patch) {
            $tableExisted = $db->fetchOne($patch['tableCheckQuery']);
            if (empty($tableExisted)) {
                continue;
            }

            $existed = $db->fetchOne($patch['checkQuery']);
            if (!empty($existed)) {
                $db->query($patch['dropQuery']);
            }
        }

        foreach (self::$_tables as $table) {
            $db->query($table['dropQuery']);
        }

        self::uninstallCustomized();
    }

    /* End auto-generated lines of code. Feel free to make changes below */

    public static function installCustomized($existingAddOn, $addOnData)
    {
        // customized install script goes here
    }

    public static function uninstallCustomized()
    {
        // customized uninstall script goes here
    }

}
