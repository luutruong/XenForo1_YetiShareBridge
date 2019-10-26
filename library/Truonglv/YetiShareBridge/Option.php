<?php

class Truonglv_YetiShareBridge_Option
{
    const OPTION_PREFIX = 'YetiShareBridge_';

    /**
     * @param string $key
     * @param string|null $subKey
     * @return mixed
     */
    public static function get($key, $subKey = null)
    {
        return XenForo_Application::getOptions()->get(self::OPTION_PREFIX . $key, $subKey);
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
