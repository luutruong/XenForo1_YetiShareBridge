<?php

class Truonglv_YetiShareBridge_Deferred_Downgrade extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        if (empty($data['userId'])) {
            return true;
        }

        /** @var XenForo_Model_User $userModel */
        $userModel = XenForo_Model::create('XenForo_Model_User');
        $user = $userModel->getUserById($data['userId']);

        if (empty($user)) {
            return true;
        }

        if (Truonglv_YetiShareBridge_Option::getVIPPackageForUser($user) > 0) {
            return true;
        }

        Truonglv_YetiShareBridge_Helper_YetiShare::downgradeUser($user);

        return true;
    }
}
