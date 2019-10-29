<?php

class Truonglv_YetiShareBridge_Helper_YetiShare
{
    const ENDPOINT_AUTHORIZE = 'authorize';
    const ENDPOINT_ACCOUNT_CREATE = 'account/create';
    const ENDPOINT_ACCOUNT_EDIT = 'account/edit';
    const ENDPOINT_ACCOUNT_INFO = 'account/info';
    const ENDPOINT_PACKAGE_LISTING = 'package/listing';

    const PROVIDER_EXTERNAL_USER = 'YetiShare';

    /**
     * @var array
     */
    protected static $_packages = null;

    public static function updateUser(array $user, array $changes)
    {
        $externalAuth = self::_getUserExternalModel()->getExternalAuthAssociationForUser(
            self::PROVIDER_EXTERNAL_USER,
            $user['user_id']
        );
        if (empty($externalAuth)) {
            return false;
        }

        $updates = array(
            'account_id' => $externalAuth['provider_key']
        );
        if (!empty($changes['is_downgrade'])) {
            $freePackage = Truonglv_YetiShareBridge_Option::get('defaultPackage');
            $updates['package_id'] = $freePackage;
        } elseif (!empty($changes['is_upgrade'])) {
            $vipPackageId = Truonglv_YetiShareBridge_Option::getVIPPackageForUser($user);
            if ($vipPackageId > 0) {
                $updates['package_id'] = $vipPackageId;

                if (!empty($changes['upgrade_expiration_date'])) {
                    $updates['paid_expiry_date'] = date('Y-m-d H:i:s', $changes['upgrade_expiration_date']);
                }
            }
        }
        if (isset($changes['user_state'])) {
            $updates['status'] = ($changes['user_state'] === 'valid') ? 'active' : 'pending';
        }

        $legacyKeys = array('email', 'password');
        foreach ($legacyKeys as $legacyKey) {
            if (isset($changes[$legacyKey])) {
                $updates[$legacyKey] = $changes[$legacyKey];
            }
        }

        self::_ensureAccessTokenLoaded();
        $response = self::_request('POST', self::ENDPOINT_ACCOUNT_EDIT, $updates);

        if (self::_expectResponseData($response, array('id'))) {
            return $response['data'];
        }

        self::log('Failed to update YetiShare user.'
            . ' $userId=' . $user['user_id']
            . ' $YetiShareAccountId=' . $updates['account_id']
            . ' $error=' . $response['response']);

        return false;
    }

    public static function upgradeUser(array $user, $expirationDate)
    {
        return self::updateUser($user, array(
            'is_upgrade' => true,
            'upgrade_expiration_date' => $expirationDate
        ));
    }

    public static function downgradeUser(array $user)
    {
        return self::updateUser($user, array('is_downgrade' => true));
    }

    public static function createUser(array $user, $password)
    {
        $defaultPackage = Truonglv_YetiShareBridge_Option::get('defaultPackage');
        if ($defaultPackage < 0) {
            return false;
        }

        $vipPackage = Truonglv_YetiShareBridge_Option::getVIPPackageForUser($user);
        if ($vipPackage > 0) {
            $defaultPackage = $vipPackage;
        }

        self::_ensureAccessTokenLoaded();

        $payload = array(
            'username' => $user['username'],
            'password' => $password,
            'email' => $user['email'],
            'package_id' => $defaultPackage,
            'status' => $user['user_state'] === 'valid' ? 'active' : 'pending',
            'title' => $user['gender'] === 'male' ? 'Mr' : 'Ms',
            'firstname' => $user['username'],
            'lastname' => $user['username']
        );

        $response = self::_request('POST', self::ENDPOINT_ACCOUNT_CREATE, $payload);
        if (isset($response['data'], $response['data']['id'])) {
            $userExternalModel = self::_getUserExternalModel();
            $userExternalModel->updateExternalAuthAssociation(
                self::PROVIDER_EXTERNAL_USER,
                $response['data']['id'],
                $user['user_id'],
                $response['data']
            );

            return true;
        } else {
            self::log('Failed to create YetiShare user for user. $id='
                . $user['user_id']
                . ' $error=' . $response['response']);
        }

        return false;
    }

    public static function getPackageListing()
    {
        if (self::$_packages === null) {
            self::_ensureAccessTokenLoaded();

            $packages = (array) self::_request('GET', self::ENDPOINT_PACKAGE_LISTING);
            self::$_packages = $packages;
        }

        return self::$_packages;
    }

    public static function fetchAccountInfo($accountId, $accessToken)
    {
        $response = self::_request('GET', self::ENDPOINT_ACCOUNT_INFO, array(
            'access_token' => $accessToken,
            'account_id' => $accountId
        ));

        return self::_expectResponseData($response, 'id');
    }

    public static function fetchAccessToken($username, $password)
    {
        $response = self::_request('POST', self::ENDPOINT_AUTHORIZE, [
            'username' => $username,
            'password' => $password
        ]);

        return self::_expectResponseData($response, array('access_token'));
    }

    public static function getSSOUrl($userId, $action, $redirect, $ipAddress)
    {
        if (empty($redirect)) {
            $redirect = XenForo_Link::buildPublicLink('index');
        }
        $redirect = XenForo_Link::convertUriToAbsoluteUri($redirect, true);

        /** @var XenForo_Model_UserExternal $userExternalModel */
        $userExternalModel = XenForo_Model::create('XenForo_Model_UserExternal');
        $authAssoc = $userExternalModel->getExternalAuthAssociationForUser(self::PROVIDER_EXTERNAL_USER, $userId);

        if (empty($authAssoc)) {
            return null;
        }

        $baseUrl = Truonglv_YetiShareBridge_Option::get('baseUrl');
        if (empty($baseUrl)) {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/') . '/xfbridge.php';
        $payload = array(
            'redirect' => $redirect,
            'timestamp' => time(),
            'action' => $action,
            'userId' => $authAssoc['provider_key'],
            'ip' => $ipAddress
        );

        $encryptKey = Truonglv_YetiShareBridge_Option::get('encryptKey');
        $encrypted = Truonglv_YetiShareBridge_Helper_Encrypt::encrypt($payload, $encryptKey);

        return $baseUrl . '?' . XenForo_Link::buildQueryString(array(
            'd' => $encrypted
        ));
    }

    protected static function _ensureAccessTokenLoaded()
    {
        $accessToken = Truonglv_YetiShareBridge_Option::get('accessToken');
        $username = Truonglv_YetiShareBridge_Option::get('username');
        $password = Truonglv_YetiShareBridge_Option::get('password');

        if (empty($accessToken) || empty($accessToken['hash'])) {
            $shouldReload = true;
        } else {
            $shouldReload = md5($username . $password) !== $accessToken['hash'];
        }

        if ($shouldReload) {
            $token = self::fetchAccessToken($username, $password);

            if (!$token) {
                throw new XenForo_Exception('Fetch access token error');
            }

            $tokenArray = $token;
            $tokenArray['_datetime'] = date('Y-m-d H:i:s', time());
            $tokenArray['hash'] = md5($username . $password);

            self::_updateOption($tokenArray);
        }
    }

    protected static function _revokeToken()
    {
        self::_updateOption(array());
    }

    protected static function _expectResponseData($response, $expectedKeys)
    {
        if (!is_array($response) || !isset($response['data'])) {
            return null;
        }
        if (!is_array($expectedKeys)) {
            $expectedKeys = array($expectedKeys);
        }

        $data = $response['data'];
        foreach ($expectedKeys as $expectedKey) {
            if (is_array($data) && array_key_exists($expectedKey, $data)) {
                $data = $data[$expectedKey];
            } else {
                return null;
            }
        }

        return $response['data'];
    }

    /**
     * @return XenForo_Model_UserExternal
     */
    protected static function _getUserExternalModel()
    {
        /** @var XenForo_Model_UserExternal $model */
        $model = XenForo_Model::create('XenForo_Model_UserExternal');

        return $model;
    }

    /**
     * @param string $method
     * @param string $endPoint
     * @param array $payload
     * @return mixed|null
     * @throws XenForo_Exception
     */
    protected static function _request($method, $endPoint, array $payload = [])
    {
        $url = rtrim(Truonglv_YetiShareBridge_Option::get('apiUrl')) . '/' . $endPoint;
        $method = strtoupper($method);

        if ($endPoint !== self::ENDPOINT_AUTHORIZE && !isset($payload['access_token'])) {
            $accessToken = Truonglv_YetiShareBridge_Option::get('accessToken');
            $payload['access_token'] = $accessToken['access_token'];
        }

        $client = XenForo_Helper_Http::getClient($url);
        if ($method === 'GET') {
            $client->setParameterGet($payload);
        } elseif ($method === 'POST') {
            $client->setParameterPost($payload);
        } else {
            throw new \XenForo_Exception('Unknown request http method: ' . $method);
        }

        /** @var Truonglv_YetiShareBridge_Model_Log $logModel */
        $logModel = XenForo_Model::create('Truonglv_YetiShareBridge_Model_Log');

        $start = microtime(true);

        try {
            $response = $client->request($method);

            $body = $response->getBody();

            $json = json_decode($body, true);
            if (!is_array($json)) {
                $json = array();
            }
            $json['_request_timing'] = number_format((microtime(true) - $start), 4);
            if (self::_isAccessTokenInvalid($json)) {
                self::_revokeToken();
                self::_ensureAccessTokenLoaded();
                unset($payload['access_token']);

                return self::_request($method, $endPoint, $payload);
            }

            $logModel->log($method, $endPoint, $payload, $json, $response->getStatus());

            return $json;
        } catch (Zend_Http_Exception $e) {
            self::log($e);
        }

        return null;
    }

    public static function log($message)
    {
        if ($message instanceof \Exception) {
            XenForo_Error::logException($message, false, '[tl] YetiShare Bridge: ');
        } else {
            self::log(new \Exception($message));
        }
    }

    private static function _updateOption($value)
    {
        $optionDw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
        $optionDw->setExistingData(Truonglv_YetiShareBridge_Option::OPTION_PREFIX . 'accessToken');
        $optionDw->set('option_value', $value);
        $optionDw->save();

        $options = XenForo_Application::getOptions();
        $options->set(Truonglv_YetiShareBridge_Option::OPTION_PREFIX . 'accessToken', $value);
    }

    private static function _isAccessTokenInvalid(array $response)
    {
        if (isset($response['status']) && isset($response['response'])) {
            return !!preg_match('/^Could not validate access_token/i', $response['response']);
        }

        return false;
    }
}
