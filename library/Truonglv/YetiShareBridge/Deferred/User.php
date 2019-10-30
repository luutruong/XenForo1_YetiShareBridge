<?php

class Truonglv_YetiShareBridge_Deferred_User extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_replace(array(
            'batch' => 100,
            'position' => 0
        ), $data);

        /** @var XenForo_Model_User $userModel */
        $userModel = XenForo_Model::create('XenForo_Model_User');
        $userIds = $userModel->getUserIdsInRange($data['position'], $data['batch']);

        if (count($userIds) === 0) {
            return true;
        }

        $users = $userModel->getUsersByIds($userIds);
        $start = microtime(true);

        foreach ($userIds as $userId) {
            $data['position'] = $userId;

            $userRef = isset($users[$userId]) ? $users[$userId] : null;
            if (!$userRef) {
                continue;
            }

            $this->_doCreateYetiShareUserIfNeeded($userRef, $userModel);

            if ($targetRunTime > 0 && (microtime(true) - $start) >= $targetRunTime) {
                break;
            }
        }

        $actionPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('users');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

        return $data;
    }

    protected function _doCreateYetiShareUserIfNeeded(array $user, XenForo_Model_User $userModel)
    {
        /** @var XenForo_Model_UserExternal $userExternalModel */
        $userExternalModel = $userModel->getModelFromCache('XenForo_Model_UserExternal');
        $associated = $userExternalModel->getExternalAuthAssociationForUser(
            Truonglv_YetiShareBridge_Helper_YetiShare::PROVIDER_EXTERNAL_USER,
            $user['user_id']
        );
        if (!empty($associated)) {
            return;
        }

        $YetiShareUser = Truonglv_YetiShareBridge_Helper_YetiShare::findUser($user['email']);
        if (empty($YetiShareUser)) {
            $suffix = 0;
            $foundByUser = null;

            while ($suffix < 50) {
                $username = $user['username'];
                if ($suffix > 0) {
                    $username = sprintf('%s%02d', $username, $suffix);
                }

                $userByName = Truonglv_YetiShareBridge_Helper_YetiShare::findUser($user['username']);
                if (empty($userByName)) {
                    $foundByUser = $username;

                    break;
                }

                $suffix++;
            }

            if ($foundByUser === null) {
                throw new XenForo_Exception('Too many retry to detect username!'
                    . ' $username=' . $user['username']
                    . ' $email=' . $user['email']);
            }

            $userData = array_replace($user, array(
                'username' => $foundByUser
            ));

            $randomPassword = XenForo_Application::generateRandomString(10);
            Truonglv_YetiShareBridge_Helper_YetiShare::createUser($userData, $randomPassword);
        }
    }
}
