<?php

class Truonglv_YetiShareBridge_Deferred_User extends XenForo_Deferred_Abstract
{
    /**
     * @var Zend_Db_Adapter_Abstract|null
     */
    protected $_dbSource;

    public function __destruct()
    {
        if ($this->_dbSource !== null) {
            $this->_dbSource->closeConnection();
            $this->_dbSource = null;
        }
    }

    public function canCancel()
    {
        return true;
    }

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_replace(array(
            'batch' => 100,
            'position' => 0,

            'method' => 'api',
            'db' => array(
                'host' => null,
                'port' => 3306,
                'username' => null,
                'password' => null,
                'dbname' => null
            )
        ), $data);

        if ($data['method'] === 'db') {
            $this->_dbSource = Zend_Db::factory('mysqli', array_replace($data['db'], array(
                'charset' => 'utf8',
                'adapterNamespace' => 'Zend_Db_Adapter'
            )));

            switch (get_class($this->_dbSource)) {
                case 'Zend_Db_Adapter_Mysqli':
                    $this->_dbSource->getConnection()->query("SET @@session.sql_mode='STRICT_ALL_TABLES'");
                    break;
                case 'Zend_Db_Adapter_Pdo_Mysql':
                    $this->_dbSource->getConnection()->exec("SET @@session.sql_mode='STRICT_ALL_TABLES'");
                    break;
            }
        }

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
            $info = $this->_fetchAccountInfo($associated['provider_key']);
            if (empty($info['id'])) {
                $userExternalModel->deleteExternalAuthAssociationForUser(
                    Truonglv_YetiShareBridge_Helper_YetiShare::PROVIDER_EXTERNAL_USER,
                    $user['user_id']
                );
            } else {
                // that is existing. Keep it.
                return;
            }
        }

        $YetiShareUser = $this->_findUser($user['email']);
        if (empty($YetiShareUser)) {
            $suffix = 0;
            $foundByUser = null;

            while ($suffix < 10) {
                $username = $user['username'];
                if ($suffix > 0) {
                    $username = sprintf('%s%02d', $username, $suffix);
                }

                $userByName = $this->_findUser($username);
                if (empty($userByName)) {
                    $foundByUser = $username;

                    break;
                }

                $suffix++;
            }

            if ($foundByUser === null) {
                $e = new XenForo_Exception('Failed to create YetiShare account'
                    . ' $userId=' . $user['user_id']);
                XenForo_Error::logException($e, false, '[tl] YetiShare Bridge: ');

                return;
            }

            $userData = array_replace($user, array(
                'username' => $foundByUser
            ));

            $randomPassword = XenForo_Application::generateRandomString(10);
            Truonglv_YetiShareBridge_Helper_YetiShare::createUser($userData, $randomPassword);
        }
    }

    protected function _findUser($nameOrEmail)
    {
        if ($this->_dbSource !== null) {
            if (strpos($nameOrEmail, '@') === false) {
                $whereColumn = 'username = ?';
            } else {
                $whereColumn = 'email = ?';
            }

            $info = $this->_dbSource->fetchRow('
                SELECT *
                FROM users
                WHERE ' . $whereColumn . '
            ', array($nameOrEmail));

            return $this->_makeDataAsSafe($info);
        }

        return Truonglv_YetiShareBridge_Helper_YetiShare::findUser($nameOrEmail);
    }

    protected function _fetchAccountInfo($accountId)
    {
        if ($this->_dbSource !== null) {
            $info = $this->_dbSource->fetchRow('
                SELECT *
                FROM users
                WHERE id = ?
            ', array($accountId));

            return $this->_makeDataAsSafe($info);
        }

        return Truonglv_YetiShareBridge_Helper_YetiShare::fetchAccountInfo($accountId);
    }

    private function _makeDataAsSafe($data)
    {
        if (is_array($data)) {
            $unsetKeys = array(
                'password',
                'passwordResetHash',
                'apikey'
            );
            foreach ($unsetKeys as $unsetKey) {
                if (array_key_exists($unsetKey, $data)) {
                    unset($data[$unsetKey]);
                }
            }
        }

        return $data;
    }
}
