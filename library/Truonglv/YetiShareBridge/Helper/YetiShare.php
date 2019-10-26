<?php

class Truonglv_YetiShareBridge_Helper_YetiShare
{
    const ENDPOINT_AUTHORIZE = 'authorize';
    const ENDPOINT_ACCOUNT_CREATE = 'account/create';
    const ENDPOINT_ACCOUNT_EDIT = 'account/edit';
    const ENDPOINT_PACKAGE_LISTING = 'package/listing';

    /**
     * @var array
     */
    protected static $_packages = null;

    public static function upgradeUser(array $user)
    {
        $vipPackageId = (int) Truonglv_YetiShareBridge_Option::get('vipPackageId');
        if ($vipPackageId <= 0) {
            return false;
        }

        self::_ensureAccessTokenLoaded();

        $response = self::_request('POST', self::ENDPOINT_ACCOUNT_EDIT, [
            'account_id' => 0,
            'package_id' => $vipPackageId,
            'paid_expiry_date' => 0
        ]);

        return isset($response['data']) && isset($response['data']['id']);
    }

    public static function downgradeUser(array $user)
    {
        self::_ensureAccessTokenLoaded();
    }

    public static function createUser(array $user, $password)
    {
        self::_ensureAccessTokenLoaded();

        $payload = array(
            'username' => $user['username'],
            'password' => $password,
            'email' => $user['email'],
            'package_id' => 0,
            'status' => $user['user_state'] === 'valid' ? 'active' : 'pending',
            'title' => $user['gender'] === 'male' ? 'Mr' : 'Ms',
            'firstname' => $user['username'],
            'lastname' => $user['username']
        );

        $response = self::_request('POST', self::ENDPOINT_ACCOUNT_CREATE, $payload);
        if (isset($response['data'], $response['data']['id'])) {
            // good. it's created
        } else {
            self::log('Failed to create YetiShare user for user. $id=' . $user['user_id']);
        }
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

    /**
     * @param string $apiKey1
     * @param string $apiKey2
     * @return array|null
     * @throws XenForo_Exception
     */
    public static function fetchAccessToken($apiKey1, $apiKey2)
    {
        return self::_request('POST', self::ENDPOINT_AUTHORIZE, [
            'key1' => $apiKey1,
            'key2' => $apiKey2
        ]);
    }

    protected static function _ensureAccessTokenLoaded()
    {
        $accessToken = Truonglv_YetiShareBridge_Option::get('accessToken');
        if (empty($accessToken)) {
            $token = self::_request('POST', self::ENDPOINT_AUTHORIZE, [
                'key1' => Truonglv_YetiShareBridge_Option::get('apiKey1'),
                'key2' => Truonglv_YetiShareBridge_Option::get('apiKey2')
            ]);

            if (!$token || (isset($token['status']) && $token['status'] === 'error')) {
                throw new XenForo_Exception('Fetch access token with error: '
                    . $token['response']);
            }

            self::_updateOption($token);
        }
    }

    protected static function _revokeToken()
    {
        self::_updateOption(array());
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
            $payload['access_token'] = $accessToken['data']['access_token'];
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
