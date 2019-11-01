<?php

class Truonglv_YetiShareBridge_XenForo_Model_UserUpgrade extends XFCP_Truonglv_YetiShareBridge_XenForo_Model_UserUpgrade
{
    public function upgradeUser($userId, array $upgrade, $allowInsertUnpurchasable = false, $endDate = null)
    {
        $upgradeRecordId = parent::upgradeUser($userId, $upgrade, $allowInsertUnpurchasable, $endDate);
        if ($upgradeRecordId > 0) {
            $userModel = $this->_getUserModel();
            $user = $userModel->getUserById($userId);

            if (Truonglv_YetiShareBridge_Option::getVIPPackageForUser($user) > 0) {
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
            if (Truonglv_YetiShareBridge_Option::getVIPPackageForUser($upgrade) > 0) {
                $userIds[] = $upgrade['user_id'];
            }
        }

        $response = parent::downgradeUserUpgrades($upgrades, $sendAlert);

        if (count($userIds) > 0) {
            $userModel = $this->_getUserModel();
            $users = $userModel->getUsersByIds($userIds);

            foreach ($users as $user) {
                if (Truonglv_YetiShareBridge_Option::getVIPPackageForUser($user) > 0) {
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

    public function updateActiveUpgradeEndDate($userUpgradeRecordId, $endDate)
    {
        parent::updateActiveUpgradeEndDate($userUpgradeRecordId, $endDate);

        $record = $this->_getDb()->fetchRow('
            SELECT active.*, user.*
            FROM xf_user_upgrade_active AS active
                LEFT JOIN xf_user AS user ON (user.user_id = active.user_id)
            WHERE active.user_upgrade_record_id = ?
        ', array($userUpgradeRecordId));
        if (Truonglv_YetiShareBridge_Option::getVIPPackageForUser($record) > 0) {
            Truonglv_YetiShareBridge_Helper_YetiShare::upgradeUser($record, $record['end_date']);
        }
    }
}
