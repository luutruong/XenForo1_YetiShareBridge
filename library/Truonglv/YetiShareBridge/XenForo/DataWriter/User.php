<?php

class Truonglv_YetiShareBridge_XenForo_DataWriter_User extends XFCP_Truonglv_YetiShareBridge_XenForo_DataWriter_User
{
    protected function _postSave()
    {
        parent::_postSave();

        $shouldUpgrade = false;
        $shouldDowngrade = false;

        if ($this->isChanged('user_group_id')) {
            $existingUserGroupId = $this->getExisting('user_group_id');
            $newUserGroupId = $this->get('user_group_id');

            $this->_tYetiShareBridge_onDataChanges($existingUserGroupId, $newUserGroupId, $shouldUpgrade, $shouldDowngrade);
        }

        if ($this->isChanged('secondary_group_ids')) {
            $existingUserGroupIds = $this->_tYetiShareBridge_convertArray($this->getExisting('secondary_group_ids'));
            $newUserGroupIds = $this->_tYetiShareBridge_convertArray($this->get('secondary_group_ids'));

            $this->_tYetiShareBridge_onDataChanges($existingUserGroupIds, $newUserGroupIds, $shouldUpgrade, $shouldDowngrade);
        }

        if ($shouldUpgrade) {
            Truonglv_YetiShareBridge_Helper_YetiShare::upgradeUser($this->getMergedData());
        } elseif ($shouldDowngrade) {
            Truonglv_YetiShareBridge_Helper_YetiShare::downgradeUser($this->getMergedData());
        }
    }

    /**
     * @param int|array $existing
     * @param int|array $new
     * @param bool $shouldUpgrade
     * @param bool $shouldDowngrade
     * @return void
     */
    protected function _tYetiShareBridge_onDataChanges($existing, $new, &$shouldUpgrade, &$shouldDowngrade)
    {
        if ($this->_tYetiShareBridge_isVipUser($existing)
            && $this->_tYetiShareBridge_isVipUser($new)
        ) {
            // good
        } elseif ($this->_tYetiShareBridge_isVipUser($existing)
            && !$this->_tYetiShareBridge_isVipUser($new)
        ) {
            $shouldUpgrade = false;
            $shouldDowngrade = true;
        } elseif (!$this->_tYetiShareBridge_isVipUser($existing)
            && $this->_tYetiShareBridge_isVipUser($new)
        ) {
            $shouldUpgrade = true;
            $shouldDowngrade = false;
        }
    }

    /**
     * @param string $string
     * @return array
     */
    protected function _tYetiShareBridge_convertArray($string)
    {
        $array = explode(',', $string);
        $array = array_map('intval', $array);
        $array = array_diff($array, array(0));

        return $array;
    }

    /**
     * @param int|array $userGroupId
     * @return bool
     */
    protected function _tYetiShareBridge_isVipUser($userGroupId)
    {
        if (!is_array($userGroupId)) {
            $userGroupId = array($userGroupId);
        }

        $userGroupId = array_map('intval', $userGroupId);
        $vipUserGroupId = (int) Truonglv_YetiShareBridge_Option::get('vipGroupId');
        if ($vipUserGroupId <= 0) {
            return false;
        }

        return in_array($vipUserGroupId, $userGroupId, true);
    }
}
