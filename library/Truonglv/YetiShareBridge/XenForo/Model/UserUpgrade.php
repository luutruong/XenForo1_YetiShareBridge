<?php

class Truonglv_YetiShareBridge_XenForo_Model_UserUpgrade extends XFCP_Truonglv_YetiShareBridge_XenForo_Model_UserUpgrade
{
    public function upgradeUser($userId, array $upgrade, $allowInsertUnpurchasable = false, $endDate = null)
    {
        $upgradeRecordId = parent::upgradeUser($userId, $upgrade, $allowInsertUnpurchasable, $endDate);
        if ($upgradeRecordId > 0) {
            $userModel = $this->_getUserModel();
            $user = $userModel->getUserById($userId);

            if (Truonglv_YetiShareBridge_Option::isUserVIP($user)) {
                $upgradeRecord = $this->getActiveUserUpgradeRecordById($upgradeRecordId);
                Truonglv_YetiShareBridge_Helper_YetiShare::upgradeUser($user, $upgradeRecord['end_date']);
            }
        }

        return $upgradeRecordId;
    }

    public function downgradeUserUpgrades(array $upgrades, $sendAlert = true)
    {
        $userIds = array();
        foreach ($upgrades as $upgrade) {
            if (Truonglv_YetiShareBridge_Option::isUserVIP($upgrade)) {
                $userIds[] = $upgrade['user_id'];
            }
        }

        $response = parent::downgradeUserUpgrades($upgrades, $sendAlert);

        if (count($userIds) > 0) {
            $userModel = $this->_getUserModel();
            $users = $userModel->getUsersByIds($userIds);

            foreach ($users as $user) {
                if (Truonglv_YetiShareBridge_Option::isUserVIP($user)) {
                    continue;
                }

                XenForo_Application::defer(
                    'Truonglv_YetiShareBridge_Deferred_Downgrade',
                    array('userId' => $user['user_id']),
                    'YetiShare_downgrade' . $user['user_id']
                );
            }
        }

        return $response;
    }
}
