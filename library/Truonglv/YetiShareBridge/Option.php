<?php

class Truonglv_YetiShareBridge_Option
{
    const OPTION_PREFIX = 'YetiShareBridge_';

    const KEY_PACKAGE_ID = 'package_id';
    const KEY_PRIORITY = 'priority';
    const KEY_USER_GROUP_ID = 'user_group_id';

    /**
     * @param string $key
     * @param string|null $subKey
     * @return mixed
     */
    public static function get($key, $subKey = null)
    {
        return XenForo_Application::getOptions()->get(self::OPTION_PREFIX . $key, $subKey);
    }

    /**
     * @param array $user
     * @return int
     * @throws XenForo_Exception
     */
    public static function getVIPPackageForUser(array $user)
    {
        $vipMapping = self::get('vipMapping');
        if (empty($vipMapping)) {
            return 0;
        }

        foreach ($vipMapping as &$value) {
            if (!isset($value[self::KEY_PRIORITY])) {
                $value[self::KEY_PRIORITY] = 0;
            }
        }

        uasort($vipMapping, function ($a, $b) {
            return $b[self::KEY_PRIORITY] - $a[self::KEY_PRIORITY];
        });

        /** @var XenForo_Model_User $userModel */
        $userModel = XenForo_Model::create('XenForo_Model_User');
        foreach ($vipMapping as $value) {
            if ($userModel->isMemberOfUserGroup($user, $value[self::KEY_USER_GROUP_ID])) {
                return $value[self::KEY_PACKAGE_ID];
            }
        }

        return 0;
    }

    public static function renderVIPMapping(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $packages = array();
        try {
            $packages = Truonglv_YetiShareBridge_Helper_YetiShare::getPackageListing();
        } catch (\Exception $e) {
            Truonglv_YetiShareBridge_Helper_YetiShare::log($e);
        }

        $choices = $preparedOption['option_value'];

        $packageOptions = array(
            array(
                'value' => 0,
                'label' => '(' . new XenForo_Phrase('unspecified') . ')'
            )
        );
        if (isset($packages['data'], $packages['data']['packages'])) {
            unset($packageOptions[0]);

            foreach ($packages['data']['packages'] as $package) {
                $packageOptions[] = array(
                    'value' => $package['id'],
                    'label' => $package['label'],
                    'selected' => $package['id'] == $preparedOption['option_value']
                );
            }
        }

        $unspecifiedPhrase = '(' . new XenForo_Phrase('unspecified') . ')';
        $userGroups = XenForo_Option_UserGroupChooser::getUserGroupOptions(0, $unspecifiedPhrase);

        $editLink = $view->createTemplateObject('option_list_option_editlink', array(
            'preparedOption' => $preparedOption,
            'canEditOptionDefinition' => $canEdit
        ));

        return $view->createTemplateObject('yetishare_bridge_option_template_vipMapping', array(
            'fieldPrefix' => $fieldPrefix,
            'listedFieldName' => $fieldPrefix . '_listed[]',
            'preparedOption' => $preparedOption,
            'editLink' => $editLink,

            'packageOptions' => $packageOptions,
            'userGroups' => $userGroups,
            'choices' => $choices,
            'nextCounter' => count($choices),

            'keyUserGroupId' => self::KEY_USER_GROUP_ID,
            'keyPackageId' => self::KEY_PACKAGE_ID,
            'keyPriority' => self::KEY_PRIORITY
        ));
    }

    public static function verifyOptionVIPMapping(&$values)
    {
        $output = array();

        foreach ($values as $value) {
            if (empty($value[self::KEY_USER_GROUP_ID]) || empty($value[self::KEY_PACKAGE_ID])) {
                continue;
            }

            $output[$value[self::KEY_USER_GROUP_ID] . $value[self::KEY_PACKAGE_ID]] = array(
                self::KEY_USER_GROUP_ID => $value[self::KEY_USER_GROUP_ID],
                self::KEY_PACKAGE_ID => $value[self::KEY_PACKAGE_ID],
                self::KEY_PRIORITY => isset($value[self::KEY_PRIORITY]) ? intval($value[self::KEY_PRIORITY]) : 0
            );
        }

        $values = array_values($output);
        uasort($values, function ($a, $b) {
            return $b[self::KEY_PRIORITY] - $a[self::KEY_PRIORITY];
        });

        return true;
    }

    /** @noinspection PhpUnused */
    /**
     * @param XenForo_View $view
     * @param string $fieldPrefix
     * @param array $preparedOption
     * @param bool $canEdit
     * @return XenForo_Template_Abstract
     * @throws Exception
     */
    public static function renderYetiSharePackages(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $packages = array();
        try {
            $packages = Truonglv_YetiShareBridge_Helper_YetiShare::getPackageListing();
        } catch (\Exception $e) {
            Truonglv_YetiShareBridge_Helper_YetiShare::log($e);
        }

        $formatParams = array(
            array(
                'value' => 0,
                'label' => '(' . new XenForo_Phrase('unspecified') . ')'
            )
        );
        if (isset($packages['data'], $packages['data']['packages'])) {
            unset($formatParams[0]);

            foreach ($packages['data']['packages'] as $package) {
                $formatParams[] = array(
                    'value' => $package['id'],
                    'label' => $package['label'],
                    'selected' => $package['id'] == $preparedOption['option_value']
                );
            }
        }

        $preparedOption['formatParams'] = $formatParams;

        return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
            'option_list_option_select',
            $view,
            $fieldPrefix,
            $preparedOption,
            $canEdit
        );
    }

    /** @noinspection PhpUnused */
    /**
     * @return string
     */
    public static function renderAccessToken()
    {
        $value = self::get('accessToken');

        return var_export($value, true);
    }
}
